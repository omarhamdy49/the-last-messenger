<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\DTOs\CreateLiveChannelData;
use App\Domain\Media\DTOs\UpdateLiveChannelData;
use App\Domain\Media\Enums\MediaViewableType;
use App\Domain\Media\Models\LiveChannel;
use App\Domain\Media\Repositories\LiveChannelRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * @extends BaseService<LiveChannelRepositoryInterface>
 */
class LiveChannelService extends BaseService
{
    public function __construct(
        LiveChannelRepositoryInterface $repository,
        private readonly MediaViewRecorder $viewRecorder,
    ) {
        $this->repository = $repository;
    }

    public function paginateChannels(
        int $perPage = 15,
        ?string $status = null,
        ?string $search = null,
        string $sortBy = 'display_order',
        string $sortDir = 'asc'
    ): LengthAwarePaginator {
        return $this->repository->paginateChannels($perPage, $status, $search, $sortBy, $sortDir);
    }

    public function getPublishedForRow(int $limit = 12): Collection
    {
        return $this->repository->getPublishedForRow($limit);
    }

    public function paginatePublished(int $perPage = 24): LengthAwarePaginator
    {
        return $this->repository->paginatePublished($perPage);
    }

    public function getRelated(int $excludeId, int $limit = 6): Collection
    {
        return $this->repository->getRelated($excludeId, $limit);
    }

    /** First published channel in display order — used for the nav live quick-link. */
    public function getFirstPublished(): ?LiveChannel
    {
        return $this->repository->firstPublished();
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function getPublishedBySlug(string $slug): LiveChannel
    {
        $channel = $this->repository->findPublishedBySlug($slug);

        if (! $channel) {
            throw new ResourceNotFoundException("Live channel not found: {$slug}");
        }

        // Queued + deduplicated; replaces the old synchronous incrementViews().
        $this->viewRecorder->record(MediaViewableType::LiveChannel, (int) $channel->id);

        return $channel;
    }

    public function createChannel(CreateLiveChannelData $data): LiveChannel
    {
        /** @var LiveChannel $channel */
        $channel = $this->repository->create([
            'slug' => $data->slug ?? $this->generateSlug($data->title),
            'streaming_url' => $data->streamingUrl,
            'stream_type' => $data->streamType,
            'status' => $data->status,
            'is_published' => $data->isPublished,
            'display_order' => $data->displayOrder,
            'views_count' => 0,
        ]);

        $this->repository->clearCacheForModel($channel->id);

        return $channel;
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function updateChannel(int $id, UpdateLiveChannelData $data): LiveChannel
    {
        $previous = $this->repository->find($id);

        $updated = $this->repository->update($id, [
            'slug' => $data->slug,
            'streaming_url' => $data->streamingUrl,
            'stream_type' => $data->streamType,
            'status' => $data->status,
            'is_published' => $data->isPublished,
            'display_order' => $data->displayOrder,
        ]);

        if (! $updated) {
            throw new ResourceNotFoundException("Live channel not found: {$id}");
        }

        // Drop the previous slug cache too (slug may have changed).
        if ($previous !== null) {
            $this->repository->clearCacheForModel($previous->id);
        }
        $this->repository->clearCacheForModel($id);

        return $this->repository->find($id);
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function deleteChannel(int $id): bool
    {
        $channel = $this->repository->find($id);

        if (! $channel) {
            throw new ResourceNotFoundException("Live channel not found: {$id}");
        }

        $this->repository->clearCacheForModel($id);
        $this->repository->delete($id);

        return true;
    }

    /**
     * Default slug from the Arabic (or first available) title, with a numeric
     * suffix when the candidate is already taken.
     *
     * @param  array<string, string>  $title
     */
    private function generateSlug(array $title): string
    {
        $source = $title['ar'] ?? ($title['en'] ?? (reset($title) ?: 'live-channel'));
        $base = Str::slug($source) ?: 'live-channel';

        $candidate = $base;
        for ($suffix = 2; $this->repository->slugExists($candidate); $suffix++) {
            $candidate = "{$base}-{$suffix}";
        }

        return $candidate;
    }
}
