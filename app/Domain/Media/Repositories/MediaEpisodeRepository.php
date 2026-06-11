<?php

declare(strict_types=1);

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Models\EpisodeWatchProgress;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Support\MediaCacheKeys;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MediaEpisodeRepository extends BaseRepository implements MediaEpisodeRepositoryInterface
{
    private const CACHE_TTL = 1800;

    protected function getModel(): Model
    {
        return new MediaEpisode;
    }

    public function paginateByProgram(int $programId, int $perPage = 20): LengthAwarePaginator
    {
        return MediaEpisode::query()
            ->select(['id', 'program_id', 'season_number', 'episode_number', 'slug', 'video_url', 'duration_seconds', 'views_count', 'is_featured', 'created_at'])
            ->with(['translations:id,media_episode_id,locale,title,description,is_ready'])
            ->where('program_id', $programId)
            ->orderBy('season_number')
            ->orderBy('episode_number')
            ->paginate($perPage);
    }

    public function getByProgram(int $programId): Collection
    {
        return Cache::remember(
            MediaCacheKeys::episodesByProgram($programId),
            self::CACHE_TTL,
            fn () => MediaEpisode::query()
                ->select(['id', 'program_id', 'season_number', 'episode_number', 'slug', 'video_url', 'duration_seconds', 'views_count', 'is_featured', 'created_at'])
                ->with(['translations:id,media_episode_id,locale,title,is_ready', 'media'])
                ->where('program_id', $programId)
                ->orderBy('season_number')
                ->orderBy('episode_number')
                ->get()
        );
    }

    public function findByNumber(int $programId, int $season, int $episodeNumber): ?MediaEpisode
    {
        return Cache::remember(
            MediaCacheKeys::episodeByProgramAndNumber($programId, $season, $episodeNumber),
            self::CACHE_TTL,
            fn () => MediaEpisode::query()
                ->select(['id', 'program_id', 'season_number', 'episode_number', 'slug', 'video_url', 'duration_seconds', 'views_count', 'is_featured', 'created_at', 'updated_at'])
                ->with(['translations', 'program:id,slug,category', 'media'])
                ->where('program_id', $programId)
                ->where('season_number', $season)
                ->where('episode_number', $episodeNumber)
                ->first()
        );
    }

    public function findNext(MediaEpisode $episode): ?MediaEpisode
    {
        // Try same season first, then move to the next season
        return MediaEpisode::query()
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

        DB::transaction(function () use ($programId, $orderedIds) {
            // Phase 1: park current numbers out of range so the per-row unique
            // (program_id, season_number, episode_number) check can't collide
            // while rows are renumbered.
            MediaEpisode::query()
                ->where('program_id', $programId)
                ->whereIn('id', $orderedIds)
                ->increment('episode_number', 10000);

            foreach ($orderedIds as $index => $episodeId) {
                MediaEpisode::query()
                    ->where('program_id', $programId)
                    ->where('id', $episodeId)
                    ->update(['episode_number' => $index + 1]);
            }
        });

        $this->clearCache();
        $this->clearCacheForProgram($programId);
    }

    public function assignToProgram(int $programId, array $episodeIds): int
    {
        $episodeIds = array_values(array_map('intval', $episodeIds));

        $moved = DB::transaction(function () use ($programId, $episodeIds) {
            $episodes = MediaEpisode::query()
                ->select(['id', 'program_id'])
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
                        'episode_number' => $nextNumber++,
                    ]);
            }

            return $episodes;
        });

        if ($moved->isNotEmpty()) {
            $this->clearCache();
            $this->clearCacheForProgram($programId);
            foreach ($moved->pluck('program_id')->unique() as $sourceProgramId) {
                $this->clearCacheForProgram((int) $sourceProgramId);
            }
        }

        return $moved->count();
    }

    public function clearCacheForProgram(int $programId): void
    {
        Cache::forget(MediaCacheKeys::episodesByProgram($programId));
    }

    public function getContinueWatching(int $userId, int $limit = 10): Collection
    {
        // Per-user, freshness-sensitive — intentionally uncached.
        return EpisodeWatchProgress::query()
            ->select(['id', 'user_id', 'episode_id', 'seconds', 'completed', 'updated_at'])
            ->with([
                'episode:id,program_id,season_number,episode_number,slug,video_url,duration_seconds',
                'episode.translations:id,media_episode_id,locale,title,is_ready',
                'episode.media',
                'episode.program:id,slug,category',
                'episode.program.translations:id,media_program_id,locale,title,is_ready',
            ])
            ->where('user_id', $userId)
            ->where('completed', false)
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }
}
