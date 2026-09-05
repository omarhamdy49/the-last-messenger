<?php

declare(strict_types=1);

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Models\EpisodeWatchProgress;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Support\MediaCacheKeys;
use App\Domain\Shared\Repositories\BaseRepository;
use App\Domain\Shared\Services\PublicVisibilityRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MediaEpisodeRepository extends BaseRepository implements MediaEpisodeRepositoryInterface
{
    private const CACHE_TTL = 1800;

    public function __construct(
        private readonly PublicVisibilityRegistry $publicVisibility,
    ) {
        parent::__construct();
    }

    protected function getModel(): Model
    {
        return new MediaEpisode;
    }

    public function paginateByProgram(int $programId, int $perPage = 20): LengthAwarePaginator
    {
        $query = MediaEpisode::query();
        $this->publicEpisodes($query);

        return $query
            ->select(['id', 'program_id', 'season_number', 'episode_number', 'slug', 'video_url', 'duration_seconds', 'views_count', 'is_featured', 'created_at'])
            ->with(['translations:id,media_episode_id,locale,title,description,is_ready', 'media'])
            ->where('program_id', $programId)
            ->orderBy('season_number')
            ->orderBy('episode_number')
            ->paginate($perPage);
    }

    public function slugExists(string $slug): bool
    {
        return MediaEpisode::query()->where('slug', $slug)->exists();
    }

    public function paginateEpisodes(
        int $perPage = 20,
        ?int $programId = null,
        ?string $search = null,
        ?int $excludeProgramId = null,
        string $sortBy = 'created_at',
        string $sortDir = 'desc',
        ?string $status = null
    ): LengthAwarePaginator {
        $allowedSorts = ['created_at', 'episode_number', 'views_count', 'duration_seconds'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'created_at';
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        $query = MediaEpisode::query()
            ->select(['id', 'program_id', 'season_number', 'episode_number', 'slug', 'video_url', 'duration_seconds', 'views_count', 'is_featured', 'is_published', 'publish_at', 'created_at'])
            ->with([
                'translations:id,media_episode_id,locale,title,is_ready',
                'program:id,slug',
                'program.translations:id,media_program_id,locale,title,is_ready',
                'media',
            ])
            ->when($programId, fn ($q) => $q->where('program_id', $programId))
            ->when($excludeProgramId, fn ($q) => $q->where('program_id', '!=', $excludeProgramId))
            ->when($search !== null && $search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('slug', 'LIKE', "%{$search}%")
                        ->orWhereHas('translations', fn ($t) => $t->where('title', 'LIKE', "%{$search}%"));
                });
            })
            ->when($status !== null, function ($q) use ($status) {
                match ($status) {
                    'published' => $q->where('is_published', true),
                    'scheduled' => $q->where('is_published', false)->whereNotNull('publish_at'),
                    'draft' => $q->where('is_published', false)->whereNull('publish_at'),
                    default => null,
                };
            });

        // Within one program the natural season/episode order beats any
        // generic sort; the sort selector applies on the global list only.
        if ($programId) {
            $query->orderBy('season_number')->orderBy('episode_number');
        } else {
            $query->orderBy($sortBy, $sortDir)->orderBy('id', 'desc');
        }

        return $query->paginate($perPage);
    }

    public function getIndexStats(?int $programId = null): array
    {
        $row = DB::table('media_episodes')
            ->when($programId, fn ($q) => $q->where('program_id', $programId))
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured,
                SUM(views_count) as total_views,
                SUM(duration_seconds) as total_duration
            ')
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'featured' => (int) ($row->featured ?? 0),
            'total_views' => (int) ($row->total_views ?? 0),
            'total_duration_seconds' => (int) ($row->total_duration ?? 0),
        ];
    }

    public function getByProgram(int $programId): Collection
    {
        return Cache::remember(
            MediaCacheKeys::episodesByProgram($programId),
            self::CACHE_TTL,
            function () use ($programId) {
                $query = MediaEpisode::query();
                $this->publicEpisodes($query);

                return $query
                    ->select(['id', 'program_id', 'season_number', 'episode_number', 'slug', 'video_url', 'duration_seconds', 'views_count', 'is_featured', 'created_at'])
                    ->with(['translations:id,media_episode_id,locale,title,is_ready', 'media'])
                    ->where('program_id', $programId)
                    ->orderBy('season_number')
                    ->orderBy('episode_number')
                    ->get();
            }
        );
    }

    public function getByProgramForSync(int $programId): Collection
    {
        return MediaEpisode::query()
            ->select([
                'id',
                'program_id',
                'season_number',
                'episode_number',
                'video_url',
            ])
            ->where('program_id', $programId)
            ->orderBy('season_number')
            ->orderBy('episode_number')
            ->get();
    }

    public function findByNumber(int $programId, int $season, int $episodeNumber): ?MediaEpisode
    {
        return Cache::remember(
            MediaCacheKeys::episodeByProgramAndNumber($programId, $season, $episodeNumber),
            self::CACHE_TTL,
            function () use ($programId, $season, $episodeNumber) {
                $query = MediaEpisode::query();
                $this->publicEpisodes($query);

                return $query
                    ->select(['id', 'program_id', 'season_number', 'episode_number', 'slug', 'video_url', 'duration_seconds', 'views_count', 'is_featured', 'created_at', 'updated_at'])
                    ->with(['translations', 'program:id,slug,category', 'media'])
                    ->where('program_id', $programId)
                    ->where('season_number', $season)
                    ->where('episode_number', $episodeNumber)
                    ->first();
            }
        );
    }

    public function findNext(MediaEpisode $episode): ?MediaEpisode
    {
        // Try same season first, then move to the next season
        $query = MediaEpisode::query();
        $this->publicEpisodes($query);

        return $query
            ->select(['id', 'program_id', 'season_number', 'episode_number', 'slug', 'video_url', 'duration_seconds', 'is_featured'])
            ->with(['translations:id,media_episode_id,locale,title,is_ready', 'media'])
            ->where('program_id', $episode->program_id)
            ->where(function ($q) use ($episode) {
                $q->where(function ($inner) use ($episode) {
                    $inner->where('season_number', $episode->season_number)
                        ->where('episode_number', '>', $episode->episode_number);
                })->orWhere('season_number', '>', $episode->season_number);
            })
            ->orderBy('season_number')
            ->orderBy('episode_number')
            ->first();
    }

    public function reorderForProgram(int $programId, array $orderedIds): void
    {
        $orderedIds = array_values(array_map('intval', $orderedIds));

        // Renumbering remaps which episode answers a given (season, number) —
        // the per-number public cache must drop both old and new keys.
        $previousNumbers = MediaEpisode::query()
            ->select(['id', 'season_number', 'episode_number'])
            ->where('program_id', $programId)
            ->whereIn('id', $orderedIds)
            ->get();

        DB::transaction(function () use ($programId, $orderedIds) {
            if (count($orderedIds) === 0) {
                return;
            }

            // Phase 1: park current numbers out of range so the per-row unique
            // (program_id, season_number, episode_number) check can't collide
            // while rows are renumbered.
            MediaEpisode::query()
                ->where('program_id', $programId)
                ->whereIn('id', $orderedIds)
                ->increment('episode_number', 10000);

            // Phase 2: CASE-based UPDATE — single query instead of N queries.
            $cases = [];
            $params = [];
            foreach ($orderedIds as $index => $episodeId) {
                $cases[] = 'WHEN id = ? THEN ?';
                $params[] = $episodeId;
                $params[] = $index + 1;
            }
            $params[] = $programId;
            $params = array_merge($params, $orderedIds);

            $caseStatement = implode(' ', $cases);
            $placeholders = implode(',', array_fill(0, count($orderedIds), '?'));

            DB::update(
                "UPDATE media_episodes
                SET episode_number = CASE {$caseStatement} END
                WHERE program_id = ? AND id IN ({$placeholders})",
                $params
            );
        });

        $this->clearCache();
        $this->clearCacheForProgram($programId);

        // Single loop to clear both old and new cache keys
        foreach ($orderedIds as $index => $episodeId) {
            $episode = $previousNumbers->firstWhere('id', $episodeId);
            if ($episode !== null) {
                $season = (int) $episode->season_number;
                $oldNumber = (int) $episode->episode_number;
                $newNumber = $index + 1;

                // Clear old cache key (before reordering)
                $this->clearCacheForEpisodeNumber($programId, $season, $oldNumber);

                // Clear new cache key (after reordering) if different
                if ($oldNumber !== $newNumber) {
                    $this->clearCacheForEpisodeNumber($programId, $season, $newNumber);
                }
            }
        }
    }

    public function assignToProgram(int $programId, array $episodeIds): int
    {
        $episodeIds = array_values(array_map('intval', $episodeIds));

        $assignedNumbers = [];

        $moved = DB::transaction(function () use ($programId, $episodeIds, &$assignedNumbers) {
            $episodes = MediaEpisode::query()
                ->select(['id', 'program_id', 'season_number', 'episode_number'])
                ->whereIn('id', $episodeIds)
                ->where('program_id', '!=', $programId)
                ->lockForUpdate()
                ->get();

            if ($episodes->isEmpty()) {
                return collect();
            }

            $nextNumber = (int) MediaEpisode::query()
                ->where('program_id', $programId)
                ->max('episode_number') + 1;

            foreach ($episodes as $episode) {
                MediaEpisode::query()
                    ->where('id', $episode->id)
                    ->update([
                        'program_id' => $programId,
                        'season_number' => 1,
                        'episode_number' => $nextNumber,
                    ]);
                $assignedNumbers[] = $nextNumber++;
            }

            return $episodes;
        });

        if ($moved->isNotEmpty()) {
            $this->clearCache();
            $this->clearCacheForProgram($programId);
            foreach ($moved->pluck('program_id')->unique() as $sourceProgramId) {
                $this->clearCacheForProgram((int) $sourceProgramId);
            }
            // Drop per-number public cache for the vacated and newly taken slots.
            foreach ($moved as $episode) {
                $this->clearCacheForEpisodeNumber((int) $episode->program_id, (int) $episode->season_number, (int) $episode->episode_number);
            }
            foreach ($assignedNumbers as $number) {
                $this->clearCacheForEpisodeNumber($programId, 1, $number);
            }
        }

        return $moved->count();
    }

    public function clearCacheForProgram(int $programId): void
    {
        Cache::forget(MediaCacheKeys::episodesByProgram($programId));
    }

    public function clearCacheForEpisodeNumber(int $programId, int $season, int $episodeNumber): void
    {
        Cache::forget(MediaCacheKeys::episodeByProgramAndNumber($programId, $season, $episodeNumber));
    }

    public function publishDue(): int
    {
        $due = MediaEpisode::query()
            ->select(['id', 'program_id'])
            ->where('is_published', false)
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())
            ->get();

        if ($due->isEmpty()) {
            return 0;
        }

        MediaEpisode::query()
            ->whereIn('id', $due->pluck('id'))
            ->update(['is_published' => true]);

        // Bulk update bypasses BaseRepository::update(), so invalidate both
        // the versioned base cache (find/findBy) and the per-program lists.
        $this->clearCache();
        foreach ($due->pluck('program_id')->unique() as $programId) {
            $this->clearCacheForProgram((int) $programId);
        }

        return $due->count();
    }

    public function getContinueWatching(int $userId, int $limit = 10): Collection
    {
        // Per-user, freshness-sensitive — intentionally uncached.
        return EpisodeWatchProgress::query()
            ->select(['id', 'user_id', 'episode_id', 'seconds', 'completed', 'updated_at'])
            ->with([
                'episode:id,program_id,season_number,episode_number,slug,video_url,duration_seconds,is_featured',
                'episode.translations:id,media_episode_id,locale,title,is_ready',
                'episode.media',
                'episode.program:id,slug,category',
                'episode.program.translations:id,media_program_id,locale,title,is_ready',
                // The nested program resource renders poster/banner URLs.
                'episode.program.media',
            ])
            ->where('user_id', $userId)
            ->where('completed', false)
            ->whereHas('episode', fn (Builder $query) => $this->publicEpisodes($query))
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    public function publicEpisodeExists(int $episodeId): bool
    {
        $query = MediaEpisode::query()->whereKey($episodeId);

        return $this->publicEpisodes($query)->exists();
    }

    public function getProgressForEpisodes(int $userId, array $episodeIds): array
    {
        if ($episodeIds === []) {
            return [];
        }

        // Per-user, freshness-sensitive — intentionally uncached.
        return EpisodeWatchProgress::query()
            ->select(['episode_id', 'seconds', 'completed'])
            ->where('user_id', $userId)
            ->whereIn('episode_id', $episodeIds)
            ->get()
            ->mapWithKeys(fn (EpisodeWatchProgress $progress): array => [
                (int) $progress->episode_id => [
                    'seconds' => (int) $progress->seconds,
                    'completed' => (bool) $progress->completed,
                ],
            ])
            ->all();
    }

    public function saveWatchProgress(int $userId, int $episodeId, int $seconds, bool $completed): void
    {
        // (user_id, episode_id) is unique — upsert is safe.
        EpisodeWatchProgress::query()->updateOrCreate(
            ['user_id' => $userId, 'episode_id' => $episodeId],
            ['seconds' => $seconds, 'completed' => $completed],
        );
    }

    private function publicEpisodes(Builder $query): Builder
    {
        return $this->publicVisibility->apply($query, app()->getLocale());
    }
}
