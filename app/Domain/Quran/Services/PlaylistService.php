<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\DTOs\AssignmentResult;
use App\Domain\Quran\DTOs\PlaylistData;
use App\Domain\Quran\Models\Playlist;
use App\Domain\Quran\Models\PlaylistItem;
use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\ReciterProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Random\Randomizer;

final class PlaylistService
{
    private readonly Randomizer $rng;

    public function __construct(?Randomizer $rng = null)
    {
        // Injectable so tests can seed it; production uses the secure default.
        $this->rng = $rng ?? new Randomizer;
    }

    /**
     * Playable recitations for the given surahs, with the recording's person.
     *
     * @param  list<int>  $surahIds
     * @return Collection<int, Recitation>
     */
    private function playableFor(array $surahIds, ?callable $constrain = null): Collection
    {
        return Recitation::query()
            ->playable()
            ->whereIn('surah_id', $surahIds)
            ->when($constrain, fn (Builder $q) => $constrain($q))
            ->with('reciter:id,profile_id,display_order,style,riwayah,slug')
            ->get(['id', 'reciter_id', 'surah_id']);
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

        $recitations = $this->playableFor(
            $surahIds,
            fn (Builder $q) => $q->whereHas('reciter', fn (Builder $r) => $r->whereIn('profile_id', $profileIds)),
        );

        // surahId => profileId => list<recordingId>, recordings in display order.
        $alternatives = [];
        foreach ($recitations->sortBy([['reciter.display_order', 'asc'], ['reciter_id', 'asc']]) as $recitation) {
            $bucket = &$alternatives[$recitation->surah_id][$recitation->reciter->profile_id];
            $bucket ??= [];
            if (! in_array($recitation->reciter_id, $bucket, true)) {
                $bucket[] = $recitation->reciter_id;
            }
            unset($bucket);
        }

        $defaults = ReciterProfile::query()->whereKey($profileIds)->pluck('default_recording_id', 'id');

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

        $valid = $this->playableFor(array_keys($pairs), fn (Builder $q) => $q->whereIn('reciter_id', array_values($pairs)))
            ->map(fn (Recitation $r) => $r->surah_id.':'.$r->reciter_id)
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
            $playlist = $user->quranPlaylists()->create([
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
     * Replace all items atomically (caller holds the transaction): one delete, one bulk insert.
     *
     * @param  array<int, int|null>  $items
     */
    private function replaceItems(Playlist $playlist, array $items): void
    {
        ksort($items);
        $rows = collect($items)->map(fn ($recording, int $surah): array => [
            'playlist_id' => $playlist->id,
            'surah_id' => $surah,
            'reciter_id' => $recording !== null && $recording !== '' ? (int) $recording : null,
        ])->values();

        $playlist->items()->delete();
        PlaylistItem::query()->insert($rows->all());
        $playlist->forceFill(['items_count' => $rows->count()])->save();
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
        // Quiet: no model event, so listings are not re-cached on every play.
        $playlist->incrementQuietly('plays_count');
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

        return $this->playableFor($surahIds)
            ->load('reciter.profile:id', 'reciter.profile.translations:id,profile_id,locale,name')
            ->sortBy([['reciter.display_order', 'asc'], ['reciter_id', 'asc']])
            ->unique(fn (Recitation $r) => $r->surah_id.':'.$r->reciter_id)
            ->groupBy('surah_id')
            ->map(fn (Collection $group) => $group->map(fn (Recitation $r) => [
                'id' => $r->reciter_id,
                'label' => ($r->reciter->profile?->displayName($locale) ?? $r->reciter->slug).' · '.$r->reciter->recordingLabel($locale),
            ])->values()->all())
            ->all();
    }
}
