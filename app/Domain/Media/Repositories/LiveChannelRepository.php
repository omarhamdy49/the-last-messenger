<?php

declare(strict_types=1);

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Models\LiveChannel;
use App\Domain\Media\Support\MediaCacheKeys;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class LiveChannelRepository extends BaseRepository implements LiveChannelRepositoryInterface
{
    private const CACHE_TTL = 600; // 10 minutes — live status changes more often

    private const ALLOWED_SORT_COLUMNS = ['display_order', 'created_at', 'views_count', 'status'];

    /** Explicit column list reused across reads. */
    private const LIST_COLUMNS = ['id', 'slug', 'streaming_url', 'status', 'is_published', 'display_order', 'views_count', 'created_at'];

    protected function getModel(): Model
    {
        return new LiveChannel;
    }

    public function paginateChannels(
        int $perPage = 15,
        ?string $status = null,
        ?string $search = null,
        string $sortBy = 'display_order',
        string $sortDir = 'asc'
    ): LengthAwarePaginator {
        $sortBy = in_array($sortBy, self::ALLOWED_SORT_COLUMNS, true) ? $sortBy : 'display_order';
        $sortDir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

        return LiveChannel::query()
            ->select(self::LIST_COLUMNS)
            ->with(['translations:id,live_channel_id,locale,title,is_ready', 'media'])
            ->when($status !== null, fn ($q) => $q->where('status', $status))
            ->when(
                $search !== null && $search !== '',
                fn ($q) => $q->whereHas('translations', fn ($t) => $t->where('title', 'LIKE', "%{$search}%"))
            )
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    public function getPublishedForRow(int $limit = 12): Collection
    {
        return Cache::remember(
            MediaCacheKeys::liveChannelsRow($limit),
            self::CACHE_TTL,
            fn () => LiveChannel::query()
                ->select(self::LIST_COLUMNS)
                ->with(['translations:id,live_channel_id,locale,title,is_ready', 'media'])
                ->published()
                ->ordered()
                ->limit($limit)
                ->get()
        );
    }

    public function paginatePublished(int $perPage = 24): LengthAwarePaginator
    {
        return LiveChannel::query()
            ->select(self::LIST_COLUMNS)
            ->with(['translations:id,live_channel_id,locale,title,is_ready', 'media'])
            ->published()
            ->ordered()
            ->paginate($perPage);
    }

    public function findPublishedBySlug(string $slug): ?LiveChannel
    {
        return Cache::remember(
            MediaCacheKeys::liveChannelBySlug($slug),
            self::CACHE_TTL,
            fn () => LiveChannel::query()
                ->with(['translations', 'media'])
                ->published()
                ->where('slug', $slug)
                ->first()
        );
    }

    public function firstPublished(): ?LiveChannel
    {
        return Cache::remember(
            MediaCacheKeys::liveChannelFirst(),
            self::CACHE_TTL,
            fn () => LiveChannel::query()
                ->select(self::LIST_COLUMNS)
                ->with(['translations:id,live_channel_id,locale,title,is_ready', 'media'])
                ->published()
                ->ordered()
                ->first()
        );
    }

    public function getRelated(int $excludeId, int $limit = 6): Collection
    {
        return LiveChannel::query()
            ->select(self::LIST_COLUMNS)
            ->with(['translations:id,live_channel_id,locale,title,is_ready', 'media'])
            ->published()
            ->where('id', '!=', $excludeId)
            ->ordered()
            ->limit($limit)
            ->get();
    }

    public function slugExists(string $slug): bool
    {
        return LiveChannel::query()->where('slug', $slug)->exists();
    }

    public function clearCacheForModel(int $id): void
    {
        $channel = LiveChannel::find($id, ['id', 'slug']);

        // Row caches at the common caps used by the public list.
        Cache::forget(MediaCacheKeys::liveChannelsRow(12));
        Cache::forget(MediaCacheKeys::liveChannelsRow(10));
        Cache::forget(MediaCacheKeys::liveChannelFirst());

        if ($channel !== null) {
            Cache::forget(MediaCacheKeys::liveChannelBySlug($channel->slug));
        }
    }
}
