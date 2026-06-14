<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Media;

use App\Domain\Media\DTOs\CreateLiveChannelData;
use App\Domain\Media\DTOs\UpdateLiveChannelData;
use App\Domain\Media\Enums\LiveStatus;
use App\Domain\Media\Models\LiveChannel;
use App\Domain\Media\Services\LiveChannelService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LiveChannel\StoreLiveChannelRequest;
use App\Http\Requests\Admin\LiveChannel\UpdateLiveChannelRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin Live Channel Controller — CRUD for media live channels.
 */
class LiveChannelController extends Controller
{
    public function __construct(
        protected LiveChannelService $channelService,
        protected AdminLoggerService $logger,
    ) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(array_column(LiveStatus::cases(), 'value'))],
            'sort' => ['nullable', 'string', Rule::in(['display_order', 'created_at', 'views_count', 'status'])],
            'dir' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ]);

        $channels = $this->channelService->paginateChannels(
            (int) ($validated['per_page'] ?? 15),
            $validated['status'] ?? null,
            isset($validated['q']) ? trim($validated['q']) : null,
            $validated['sort'] ?? 'display_order',
            $validated['dir'] ?? 'asc',
        );
        $channels->appends($request->query());

        $statuses = LiveStatus::cases();

        return view('admin.live-channels.index', compact('channels', 'statuses'));
    }

    public function create(): View
    {
        $statuses = LiveStatus::cases();

        return view('admin.live-channels.create', compact('statuses'));
    }

    public function store(StoreLiveChannelRequest $request): RedirectResponse
    {
        $dto = CreateLiveChannelData::fromRequest($request);
        $channel = $this->channelService->createChannel($dto);

        $this->syncThumbnail($channel, $dto->thumbnail, $dto->thumbnailId);

        $channel->syncTranslations([
            'title' => $dto->title,
            'description' => $dto->description,
        ]);

        $this->logger->logCreate($channel, $request->user());

        return redirect()->route('admin.live-channels.index')
            ->with('success', __('admin.live_channel_created'));
    }

    public function show(int $id): View
    {
        $channel = $this->channelService->find($id);

        if (! $channel) {
            abort(404, 'Live channel not found');
        }

        $channel->loadMissing(['translations']);

        return view('admin.live-channels.show', compact('channel'));
    }

    public function edit(int $id): View
    {
        $channel = $this->channelService->find($id);

        if (! $channel) {
            abort(404, 'Live channel not found');
        }

        $channel->loadMissing(['translations']);
        $statuses = LiveStatus::cases();

        return view('admin.live-channels.edit', compact('channel', 'statuses'));
    }

    public function update(UpdateLiveChannelRequest $request, int $id): RedirectResponse
    {
        $old = $this->channelService->find($id);

        if (! $old) {
            abort(404, 'Live channel not found');
        }

        $oldValues = $old->toArray();
        $dto = UpdateLiveChannelData::fromRequest($request);
        $channel = $this->channelService->updateChannel($id, $dto);

        $this->syncThumbnail($channel, $dto->thumbnail, $dto->thumbnailId);

        $channel->syncTranslations([
            'title' => $dto->title,
            'description' => $dto->description,
        ]);

        $this->logger->logUpdate($channel, $oldValues, $channel->toArray(), $request->user());

        return redirect()->route('admin.live-channels.show', $channel->id)
            ->with('success', __('admin.live_channel_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $channel = $this->channelService->findOrFail($id);
        $this->channelService->deleteChannel($id);
        $this->logger->logDelete($channel, request()->user());

        return redirect()->route('admin.live-channels.index')
            ->with('success', __('admin.live_channel_deleted'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:live_channels,id'],
        ]);

        $deleted = 0;
        foreach ($request->input('ids') as $id) {
            try {
                $this->channelService->deleteChannel((int) $id);
                $deleted++;
            } catch (\Throwable) {
                // Continue — partial deletes are acceptable for bulk operations.
            }
        }

        $this->logger->logBulkAction('bulk_delete', LiveChannel::class, $request->input('ids'), $request->user());

        return redirect()->route('admin.live-channels.index')
            ->with('success', __('admin.bulk_deleted', ['count' => $deleted]));
    }

    /**
     * Attach the thumbnail from a direct upload or a media-library pick.
     */
    private function syncThumbnail(LiveChannel $channel, ?\Illuminate\Http\UploadedFile $file, ?int $libraryMediaId): void
    {
        if ($file === null && $libraryMediaId === null) {
            return;
        }

        $channel->clearMediaCollection('thumbnail');

        if ($file !== null) {
            $channel->addMedia($file)->toMediaCollection('thumbnail');

            return;
        }

        // Library originals are encrypted at rest — materialize a plaintext copy.
        $copy = app(\App\Services\MediaService::class)->temporaryOriginalCopyById($libraryMediaId);
        if ($copy !== null) {
            $channel->addMedia($copy->path)
                ->usingFileName($copy->filename)
                ->toMediaCollection('thumbnail');
        }
    }
}
