<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\DTOs\AssignmentResult;
use App\Domain\Quran\DTOs\PlaylistData;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Models\Playlist;
use App\Domain\Quran\Models\PlaylistItem;
use App\Domain\Quran\Repositories\AudioRepositoryInterface;
use App\Domain\Quran\Repositories\ResourceRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Random\Randomizer;

/**
 * Visitor playlists: one chapter-reciter recording per chapter. A "reciter" in the
 * wizard is a chapter-reciter resource; its "recording" is the same resource.
 */
final class PlaylistService
{
    private readonly Randomizer $rng;

    public function __construct(
        private readonly AudioRepositoryInterface $audio,
        private readonly ResourceRepositoryInterface $resources,
        ?Randomizer $rng = null,
    ) {
        // Injectable so tests can seed it; production uses the secure default.
        $this->rng = $rng ?? new Randomizer;
    }

    /**
     * Playable (chapter, reciter) pairs: an active chapter reciter with a file for the chapter.
     *
     * @param  list<int>  $chapterIds
     * @param  list<int>|null  $reciterIds
     * @return Collection<int, object{resource_id: int, chapter_id: int, url: string, duration_ms: ?int, display_order: int}>
     */
    private function playableFor(array $chapterIds, ?array $reciterIds = null): Collection
    {
        return $this->audio->playableChapterFiles($chapterIds, $reciterIds);
    }

    /**
     * Give every chapter in scope a recording from one of the chosen reciters.
     *
     * Balanced random: each chapter goes to the reciter with the fewest chapters so
     * far (ties broken at random), so three reciters over 114 chapters land near
     * 38/38/38 while still feeling shuffled. A chapter nobody covers is reported in
     * `uncovered`, never silently dropped.
     *
     * @param  list<int>  $reciterIds
     * @param  list<int>  $chapterIds
     * @param  array<int, int>  $keep  chapterId => reciterId the user pinned; wins if it still covers
     */
    public function assign(array $reciterIds, array $chapterIds, array $keep = []): AssignmentResult
    {
        $reciterIds = array_values(array_unique(array_map('intval', $reciterIds)));
        $chapterIds = array_values(array_unique(array_map('intval', $chapterIds)));
        sort($chapterIds);

        if ($reciterIds === [] || $chapterIds === []) {
            return new AssignmentResult([], $chapterIds, []);
        }

        // chapterId => reciterId => [reciterId] (the alternatives shape the wizard expects).
        $alternatives = [];
        foreach ($this->playableFor($chapterIds, $reciterIds) as $row) {
            $alternatives[(int) $row->chapter_id][(int) $row->resource_id] = [(int) $row->resource_id];
        }

        $items = [];
        $uncovered = [];
        $load = array_fill_keys($reciterIds, 0);

        foreach ($chapterIds as $chapter) {
            $options = $alternatives[$chapter] ?? [];
            if ($options === []) {
                $uncovered[] = $chapter;

                continue;
            }

            $pinned = isset($keep[$chapter]) ? (int) $keep[$chapter] : null;
            if ($pinned !== null && isset($options[$pinned])) {
                $items[$chapter] = $pinned;
                $load[$pinned]++;

                continue;
            }

            $candidates = array_keys($options);
            $min = min(array_map(static fn (int $r): int => $load[$r], $candidates));
            $tied = array_values(array_filter($candidates, static fn (int $r): bool => $load[$r] === $min));
            $reciter = $tied[$this->rng->getInt(0, count($tied) - 1)];

            $items[$chapter] = $reciter;
            $load[$reciter]++;
        }

        return new AssignmentResult($items, $uncovered, $alternatives);
    }

    /**
     * (chapter, reciter) pairs with no playable audio. Empty means the payload is safe to store.
     *
     * @param  array<int, int|null>  $items
     * @return list<array{surah: int, recording: int}>
     */
    public function invalidPairs(array $items): array
    {
        $pairs = array_filter($items, static fn ($reciter) => $reciter !== null && $reciter !== '');
        if ($pairs === []) {
            return [];
        }

        $valid = $this->playableFor(array_map('intval', array_keys($pairs)), array_map('intval', array_values($pairs)))
            ->map(static fn (object $r): string => $r->chapter_id.':'.$r->resource_id)
            ->flip();

        $bad = [];
        foreach ($pairs as $chapter => $reciter) {
            if (! isset($valid[$chapter.':'.$reciter])) {
                $bad[] = ['surah' => (int) $chapter, 'recording' => (int) $reciter];
            }
        }

        return $bad;
    }

    public function create(int $userId, PlaylistData $data): Playlist
    {
        return DB::transaction(function () use ($userId, $data): Playlist {
            $playlist = Playlist::query()->create([
                'user_id' => $userId,
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
     * @param  array<int, int|null>  $items  chapter => reciter resource id
     */
    private function replaceItems(Playlist $playlist, array $items): void
    {
        ksort($items);
        $now = now();
        $rows = collect($items)->map(static fn ($reciter, int $chapter): array => [
            'playlist_id' => $playlist->id,
            'chapter_id' => $chapter,
            'resource_id' => $reciter !== null && $reciter !== '' ? (int) $reciter : null,
            'created_at' => $now,
            'updated_at' => $now,
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

    /**
     * Every playable reciter per chapter, labelled for the admin editor.
     *
     * @param  list<int>  $chapterIds
     * @return array<int, list<array{id: int, label: string}>>
     */
    public function coverage(array $chapterIds): array
    {
        if ($chapterIds === []) {
            return [];
        }
        $locale = app()->getLocale();
        $names = $this->resources->active(ResourceKind::CHAPTER_RECITER)
            ->mapWithKeys(static fn ($r): array => [$r->id => trim($r->displayName($locale).' · '.($r->style?->label($locale) ?? ''), ' ·')]);

        return $this->playableFor(array_map('intval', $chapterIds))
            ->groupBy('chapter_id')
            ->map(static fn (Collection $group) => $group->map(static fn (object $r): array => [
                'id' => (int) $r->resource_id,
                'label' => (string) ($names[$r->resource_id] ?? $r->resource_id),
            ])->values()->all())
            ->all();
    }

    /**
     * The playable file URL for one (chapter, reciter), or null.
     */
    public function audioUrl(int $chapter, int $reciter): ?string
    {
        $row = $this->playableFor([$chapter], [$reciter])->first();

        return $row === null ? null : AudioService::absolutize($row->url);
    }

    /**
     * Player-ready rows for a loaded playlist (items with chapter + reciter loaded).
     *
     * @return list<array<string, mixed>>
     */
    public function tracks(Playlist $playlist, string $locale): array
    {
        $items = $playlist->items;
        $assigned = $items->whereNotNull('resource_id');

        $audio = $assigned->isEmpty() ? collect() : $this->playableFor(
            $assigned->pluck('chapter_id')->map('intval')->unique()->values()->all(),
            $assigned->pluck('resource_id')->map('intval')->unique()->values()->all(),
        )->keyBy(static fn (object $r): string => $r->resource_id.':'.$r->chapter_id);

        return $items->map(function (PlaylistItem $item) use ($audio, $locale): array {
            $file = $item->resource_id ? $audio->get($item->resource_id.':'.$item->chapter_id) : null;
            $reciter = $item->reciter;

            return [
                'surah' => $item->chapter_id,
                'surahName' => $item->chapter?->displayName($locale) ?? '',
                'verses' => $item->chapter?->verses_count,
                'recording' => $item->resource_id,
                'reciterName' => $reciter?->displayName($locale),
                'reciterImage' => PlaylistCard::imageUrl($reciter?->image),
                'reciterUrl' => $reciter ? route('public.quran.reciter', $reciter->slug) : null,
                'recordingLabel' => $reciter?->style?->label($locale),
                'url' => $file === null ? null : AudioService::absolutize($file->url),
                'duration' => $file?->duration_ms === null ? null : intdiv((int) $file->duration_ms, 1000),
                'readerUrl' => route('public.quran.surah', $item->chapter_id).($item->resource_id ? '?reciter='.$item->resource_id : ''),
            ];
        })->values()->all();
    }
}
