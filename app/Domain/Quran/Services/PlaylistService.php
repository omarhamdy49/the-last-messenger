<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\DTOs\AssignmentResult;
use App\Domain\Quran\DTOs\PlaylistData;
use App\Domain\Quran\Models\Playlist;
use App\Domain\Quran\Repositories\PlaylistRepository;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Random\Randomizer;

final class PlaylistService
{
    private readonly Randomizer $rng;

    public function __construct(private readonly PlaylistRepository $repo, ?Randomizer $rng = null)
    {
        // Injectable so tests can seed it; production uses the secure default.
        $this->rng = $rng ?? new Randomizer;
    }

    /**
     * Recitations that actually play: active recitation-kind recordings whose
     * audio source is active — the same rule the public reciter page applies.
     */
    private function playable(): Builder
    {
        return DB::table('quran_recitations as rc')
            ->join('quran_reciters as r', 'r.id', '=', 'rc.reciter_id')
            ->join('quran_recording_sources as s', 's.id', '=', 'rc.source_id')
            ->where('r.kind', 'recitation')
            ->where('r.is_active', true)
            ->where('s.is_active', true);
    }

    /**
     * Give every surah in scope a recording from one of the chosen people.
     *
     * Balanced random: each surah goes to the person with the fewest surahs so
     * far (ties broken at random), so three reciters over 114 surahs land near
     * 38/38/38 instead of an arbitrary 60/40/14 while still feeling shuffled.
     * A surah nobody covers is reported in `uncovered`, never silently dropped.
     *
     * @param  list<int>  $profileIds
     * @param  list<int>  $surahIds
     * @param  array<int, int>  $keep  surahId => recordingId the user pinned; wins if it still covers
     */
    public function assign(array $profileIds, array $surahIds, array $keep = []): AssignmentResult
    {
        $profileIds = array_values(array_unique(array_map('intval', $profileIds)));
        $surahIds = array_values(array_unique(array_map('intval', $surahIds)));
        sort($surahIds);

        if ($profileIds === [] || $surahIds === []) {
            return new AssignmentResult([], $surahIds, []);
        }

        $rows = $this->playable()
            ->whereIn('r.profile_id', $profileIds)
            ->whereIn('rc.surah_id', $surahIds)
            ->orderBy('r.display_order')->orderBy('r.id')
            ->get(['r.profile_id', 'rc.reciter_id', 'rc.surah_id']);

        // surahId => profileId => list<recordingId>, recordings in display order.
        $alternatives = [];
        foreach ($rows as $row) {
            $list = &$alternatives[(int) $row->surah_id][(int) $row->profile_id];
            $list ??= [];
            if (! in_array((int) $row->reciter_id, $list, true)) {
                $list[] = (int) $row->reciter_id;
            }
            unset($list);
        }

        $defaults = DB::table('quran_reciter_profiles')->whereIn('id', $profileIds)->pluck('default_recording_id', 'id');

        $items = [];
        $uncovered = [];
        $load = array_fill_keys($profileIds, 0);

        foreach ($surahIds as $surah) {
            $options = $alternatives[$surah] ?? [];
            if ($options === []) {
                $uncovered[] = $surah;

                continue;
            }

            $pinned = isset($keep[$surah]) ? (int) $keep[$surah] : null;
            if ($pinned !== null) {
                foreach ($options as $profileId => $recordings) {
                    if (in_array($pinned, $recordings, true)) {
                        $items[$surah] = $pinned;
                        $load[$profileId]++;

                        continue 2;
                    }
                }
            }

            $candidates = array_keys($options);
            $min = min(array_map(fn (int $p): int => $load[$p], $candidates));
            $tied = array_values(array_filter($candidates, fn (int $p): bool => $load[$p] === $min));
            $profileId = $tied[$this->rng->getInt(0, count($tied) - 1)];

            $recordings = $options[$profileId];
            $default = (int) ($defaults[$profileId] ?? 0);
            $items[$surah] = in_array($default, $recordings, true) ? $default : $recordings[0];
            $load[$profileId]++;
        }

        return new AssignmentResult($items, $uncovered, $alternatives);
    }

    /**
     * (surah, recording) pairs with no playable audio. Empty means the payload is safe to store.
     *
     * @param  array<int, int|null>  $items
     * @return list<array{surah: int, recording: int}>
     */
    public function invalidPairs(array $items): array
    {
        $pairs = array_filter($items, fn ($recording) => $recording !== null && $recording !== '');
        if ($pairs === []) {
            return [];
        }

        $valid = $this->playable()
            ->whereIn('rc.reciter_id', array_values($pairs))
            ->whereIn('rc.surah_id', array_keys($pairs))
            ->get(['rc.surah_id', 'rc.reciter_id'])
            ->map(fn ($row) => $row->surah_id.':'.$row->reciter_id)
            ->flip();

        $bad = [];
        foreach ($pairs as $surah => $recording) {
            if (! isset($valid[$surah.':'.$recording])) {
                $bad[] = ['surah' => (int) $surah, 'recording' => (int) $recording];
            }
        }

        return $bad;
    }

    public function create(User $user, PlaylistData $data): Playlist
    {
        return DB::transaction(function () use ($user, $data): Playlist {
            $playlist = Playlist::query()->create([
                'user_id' => $user->id,
                'name' => $data->name,
                'description' => $data->description,
                'scope' => $data->scope,
                'is_public' => $data->isPublic,
            ]);
            $this->replaceItems($playlist, $data->items);

            return $playlist->refresh();
        });
    }

    public function update(Playlist $playlist, PlaylistData $data): Playlist
    {
        return DB::transaction(function () use ($playlist, $data): Playlist {
            $playlist->fill([
                'name' => $data->name,
                'description' => $data->description,
                'scope' => $data->scope,
                'is_public' => $data->isPublic,
            ])->save();
            $this->replaceItems($playlist, $data->items);

            return $playlist->refresh();
        });
    }

    /**
     * @param  array<int, int|null>  $items
     */
    private function replaceItems(Playlist $playlist, array $items): void
    {
        $playlist->items()->delete();
        ksort($items);
        $rows = [];
        foreach ($items as $surah => $recording) {
            $rows[] = [
                'playlist_id' => $playlist->id,
                'surah_id' => (int) $surah,
                'reciter_id' => $recording !== null && $recording !== '' ? (int) $recording : null,
            ];
        }
        if ($rows !== []) {
            DB::table('quran_playlist_items')->insert($rows);
        }
        $playlist->forceFill(['items_count' => count($rows)])->save();
    }

    public function delete(Playlist $playlist): void
    {
        $playlist->delete();
    }

    public function setPublic(Playlist $playlist, bool $on): void
    {
        $playlist->forceFill(['is_public' => $on])->save();
    }

    public function setFeatured(Playlist $playlist, bool $on): void
    {
        $playlist->forceFill(['is_featured' => $on])->save();
    }

    public function recordPlay(Playlist $playlist): void
    {
        // Query-builder increment: no model event, so listings are not re-cached per play.
        Playlist::query()->whereKey($playlist->id)->increment('plays_count');
    }

    /**
     * Every playable recording per surah, labelled for the admin editor.
     *
     * @param  list<int>  $surahIds
     * @return array<int, list<array{id: int, label: string}>>
     */
    public function coverage(array $surahIds): array
    {
        if ($surahIds === []) {
            return [];
        }
        $locale = app()->getLocale();

        $rows = $this->playable()
            ->leftJoin('quran_reciter_profile_translations as t', fn (JoinClause $j) => $j->on('t.profile_id', '=', 'r.profile_id')->where('t.locale', $locale))
            ->leftJoin('quran_reciter_profile_translations as ta', fn (JoinClause $j) => $j->on('ta.profile_id', '=', 'r.profile_id')->where('ta.locale', 'ar'))
            ->whereIn('rc.surah_id', $surahIds)
            ->orderBy('r.display_order')->orderBy('r.id')
            ->get(['rc.surah_id', 'r.id', 'r.style', 'r.riwayah', DB::raw('COALESCE(t.name, ta.name, r.slug) AS name')]);

        $out = [];
        $seen = [];
        foreach ($rows as $row) {
            $key = $row->surah_id.':'.$row->id;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $suffix = $row->riwayah ? ' · '.$row->riwayah : ($row->style ? ' · '.__('quran.recordings.'.$row->style) : '');
            $out[(int) $row->surah_id][] = ['id' => (int) $row->id, 'label' => $row->name.$suffix];
        }

        return $out;
    }
}
