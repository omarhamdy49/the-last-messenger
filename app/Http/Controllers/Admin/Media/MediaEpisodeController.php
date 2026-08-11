<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Media;

use App\Domain\Media\DTOs\CreateMediaEpisodeData;
use App\Domain\Media\DTOs\UpdateMediaEpisodeData;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Services\MediaAttachmentService;
use App\Domain\Media\Services\MediaEpisodeService;
use App\Domain\Media\Services\MediaProgramService;
use App\Domain\Media\Services\YouTubeSyncService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaProgram\StoreMediaEpisodeRequest;
use App\Http\Requests\Admin\MediaProgram\UpdateMediaEpisodeRequest;
use App\Http\Requests\Admin\MediaProgram\UpdateMediaEpisodeThumbnailRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin Media Episode Controller
 */
class MediaEpisodeController extends Controller
{
    public function __construct(
        protected MediaEpisodeService $episodeService,
        protected MediaProgramService $programService,
        protected MediaAttachmentService $attachments,
        protected AdminLoggerService $logger,
    ) {}

    /**
     * GET /su/portal/v1/admin/media-episodes?program_id=X
     */
    public function index(Request $request): View|JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'program_id' => ['nullable', 'integer'],
            'exclude_program_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', Rule::in(['created_at', 'episode_number', 'views_count', 'duration_seconds'])],
            'dir' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'publish_status' => ['nullable', 'string', Rule::in(['published', 'scheduled', 'draft'])],
        ]);

        $programId = isset($validated['program_id']) ? (int) $validated['program_id'] : null;
        $search = isset($validated['q']) ? trim($validated['q']) : null;

        $episodes = $this->episodeService->paginateEpisodes(
            perPage: (int) ($validated['per_page'] ?? 20),
            programId: $programId ?: null,
            search: $search,
            excludeProgramId: isset($validated['exclude_program_id']) ? (int) $validated['exclude_program_id'] : null,
            sortBy: $validated['sort'] ?? 'created_at',
            sortDir: $validated['dir'] ?? 'desc',
            status: $validated['publish_status'] ?? null,
        );
        $episodes->appends($request->query());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $episodes->items(),
                'meta' => [
                    'current_page' => $episodes->currentPage(),
                    'per_page' => $episodes->perPage(),
                    'total' => $episodes->total(),
                    'last_page' => $episodes->lastPage(),
                ],
            ]);
        }

        $program = $programId ? $this->programService->find($programId) : null;
        $stats = $this->episodeService->getIndexStats($programId ?: null);
        $programs = $this->programService->paginate(100);

        return view('admin.media-episodes.index', compact('episodes', 'program', 'programs', 'stats'));
    }

    /**
     * GET /su/portal/v1/admin/media-episodes/create?program_id=X
     */
    public function create(Request $request): View
    {
        $programId = $request->integer('program_id') ?: null;
        // The program is chosen through a paginated search modal, so only the
        // preselected program (if any) is loaded — never the whole list.
        $selectedProgram = $programId ? $this->programService->find($programId) : null;

        return view('admin.media-episodes.create', compact('programId', 'selectedProgram'));
    }

    /**
     * POST /su/portal/v1/admin/media-episodes
     */
    public function store(StoreMediaEpisodeRequest $request): RedirectResponse
    {
        $dto = CreateMediaEpisodeData::fromRequest($request);

        // One unit of work — an episode row without translations is unusable.
        $episode = DB::transaction(function () use ($dto): MediaEpisode {
            $episode = $this->episodeService->createEpisode($dto);

            $episode->syncTranslations([
                'title' => $dto->title,
                'description' => $dto->description,
                'transcript' => $dto->transcript,
                'seo_title' => $dto->seoTitle,
                'seo_description' => $dto->seoDescription,
                'seo_keywords' => $dto->seoKeywords,
            ]);

            $this->syncEpisodeThumbnail($episode, $dto->thumbnail, $dto->thumbnailId);

            return $episode;
        });

        $this->logger->logCreate($episode, $request->user());

        return redirect()->route('admin.media-episodes.index', ['program_id' => $dto->programId])
            ->with('success', __('admin.episode_created'));
    }

    /**
     * GET /su/portal/v1/admin/media-episodes/{id}
     */
    public function show(int $id): View
    {
        $episode = $this->episodeService->find($id);

        if (! $episode) {
            abort(404, 'Episode not found');
        }

        $episode->loadMissing(['translations', 'program.translations']);

        return view('admin.media-episodes.show', compact('episode'));
    }

    /**
     * GET /su/portal/v1/admin/media-episodes/{id}/edit
     */
    public function edit(int $id): View
    {
        $episode = $this->episodeService->find($id);

        if (! $episode) {
            abort(404, 'Episode not found');
        }

        $episode->loadMissing(['translations', 'program.translations']);

        return view('admin.media-episodes.edit', compact('episode'));
    }

    /**
     * PUT /su/portal/v1/admin/media-episodes/{id}
     */
    public function update(UpdateMediaEpisodeRequest $request, int $id): RedirectResponse
    {
        $old = $this->episodeService->find($id);

        if (! $old) {
            abort(404, 'Episode not found');
        }

        $oldValues = $old->toArray();
        $dto = UpdateMediaEpisodeData::fromRequest($request);

        $episode = DB::transaction(function () use ($id, $dto): MediaEpisode {
            $episode = $this->episodeService->updateEpisode($id, $dto);

            $episode->syncTranslations([
                'title' => $dto->title,
                'description' => $dto->description,
                'transcript' => $dto->transcript,
                'seo_title' => $dto->seoTitle,
                'seo_description' => $dto->seoDescription,
                'seo_keywords' => $dto->seoKeywords,
            ]);

            $this->syncEpisodeThumbnail($episode, $dto->thumbnail, $dto->thumbnailId);

            return $episode;
        });

        $this->logger->logUpdate($episode, $oldValues, $episode->toArray(), $request->user());

        return redirect()->route('admin.media-episodes.show', $episode->id)
            ->with('success', __('admin.episode_updated'));
    }

    /**
     * DELETE /su/portal/v1/admin/media-episodes/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        $episode = $this->episodeService->findOrFail($id);
        $programId = $episode->program_id;

        $this->episodeService->deleteEpisode($id);
        $this->logger->logDelete($episode, request()->user());

        // Deletes triggered from the program page return there.
        if (request()->boolean('from_program')) {
            return redirect()->route('admin.media-programs.show', $programId)
                ->with('success', __('admin.episode_deleted'));
        }

        return redirect()->route('admin.media-episodes.index', ['program_id' => $programId])
            ->with('success', __('admin.episode_deleted'));
    }

    /**
     * POST /su/portal/v1/admin/media-episodes/{id}/thumbnail
     * Inline thumbnail change/remove from the episode hero.
     */
    public function updateThumbnail(UpdateMediaEpisodeThumbnailRequest $request, int $id): RedirectResponse
    {
        $episode = $this->episodeService->find($id);

        if (! $episode) {
            abort(404, 'Episode not found');
        }

        if ($request->boolean('remove')) {
            $episode->clearMediaCollection('thumbnail');
        } else {
            $this->syncEpisodeThumbnail(
                $episode,
                $request->hasFile('thumbnail') ? $request->file('thumbnail') : null,
                null,
            );
        }

        $this->logger->logUpdate($episode, [], ['thumbnail' => $request->boolean('remove') ? 'removed' : 'updated'], $request->user());

        return redirect()->route('admin.media-episodes.show', $id)
            ->with('success', __('admin.episode_updated'));
    }

    /**
     * POST /su/portal/v1/admin/media-episodes/{id}/youtube/sync
     * Refresh this episode's title/description/transcript/duration/thumbnail
     * from its YouTube video. Synchronous — a single video is quick.
     */
    public function syncYoutube(YouTubeSyncService $service, int $id): RedirectResponse
    {
        $episode = $this->episodeService->find($id);

        if (! $episode) {
            abort(404, 'Episode not found');
        }

        @set_time_limit(0);

        try {
            $service->syncEpisode($episode);
            $this->logger->logUpdate($episode, [], ['youtube_sync' => 'applied'], request()->user());

            return redirect()->route('admin.media-episodes.show', $id)
                ->with('success', __('admin.yt_episode_synced'));
        } catch (\Throwable $e) {
            return redirect()->route('admin.media-episodes.show', $id)
                ->with('error', __('admin.yt_sync_failed').': '.$e->getMessage());
        }
    }

    /**
     * POST /su/portal/v1/admin/media-episodes/youtube/preview
     * Fetch one video's metadata (title/description/transcript/duration/
     * thumbnail) to prefill the create/edit form before any episode exists.
     */
    public function youtubePreview(Request $request, YouTubeSyncService $service): JsonResponse
    {
        $request->validate([
            'video_url' => ['required', 'string', 'max:500'],
        ]);

        @set_time_limit(120);

        try {
            return response()->json([
                'success' => true,
                'data' => $service->previewVideo((string) $request->input('video_url')),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: __('admin.yt_sync_failed'),
            ], 422);
        }
    }

    /**
     * Attach the thumbnail from a direct upload or a media-library pick.
     * An upload wins over a library id when the form somehow sends both.
     */
    private function syncEpisodeThumbnail(
        MediaEpisode $episode,
        ?UploadedFile $file,
        ?int $libraryMediaId
    ): void {
        $this->attachments->attach($episode, 'thumbnail', $file ?? $libraryMediaId);
    }

    /**
     * POST /su/portal/v1/admin/media-episodes/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:media_episodes,id'],
        ]);

        $deleted = 0;
        foreach ($request->input('ids') as $id) {
            try {
                $this->episodeService->deleteEpisode((int) $id);
                $deleted++;
            } catch (\Throwable) {
                // Continue
            }
        }

        return redirect()->route('admin.media-episodes.index')
            ->with('success', "Deleted {$deleted} episode(s) successfully.");
    }
}
