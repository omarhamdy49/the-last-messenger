<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Media;

use App\Domain\Media\DTOs\CreateMediaProgramData;
use App\Domain\Media\DTOs\UpdateMediaProgramData;
use App\Domain\Media\Enums\ProgramCategory;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Services\MediaAttachmentService;
use App\Domain\Media\Services\MediaEpisodeService;
use App\Domain\Media\Services\MediaProgramService;
use App\Domain\Media\Services\YouTubeSyncService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Media\YouTubeSourceRequest;
use App\Http\Requests\Admin\MediaProgram\StoreMediaProgramRequest;
use App\Http\Requests\Admin\MediaProgram\UpdateMediaProgramImagesRequest;
use App\Http\Requests\Admin\MediaProgram\UpdateMediaProgramRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin Media Program Controller
 * Handles program management for the admin portal.
 */
class MediaProgramController extends Controller
{
    public function __construct(
        protected MediaProgramService $programService,
        protected MediaAttachmentService $attachments,
        protected AdminLoggerService $logger,
    ) {}

    /**
     * GET /su/portal/v1/admin/media-programs
     */
    public function index(Request $request): View|JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'category' => ['nullable', 'string', Rule::in(array_column(ProgramCategory::cases(), 'value'))],
            'q' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', Rule::in(['created_at', 'display_order', 'views_count', 'rating'])],
            'dir' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'publish_status' => ['nullable', 'string', Rule::in(['published', 'scheduled', 'draft'])],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);
        $category = $validated['category'] ?? null;
        $search = isset($validated['q']) ? trim($validated['q']) : null;
        $sortBy = $validated['sort'] ?? 'created_at';
        $sortDir = $validated['dir'] ?? 'desc';
        $status = $validated['publish_status'] ?? null;

        $programs = $this->programService->paginatePrograms($perPage, $category, $sortBy, $sortDir, $search, false, $status);
        $programs->appends($request->query());
        $stats = $this->programService->getIndexStats();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $programs->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $programs->currentPage(),
                    'per_page' => $programs->perPage(),
                    'total' => $programs->total(),
                    'last_page' => $programs->lastPage(),
                ],
            ]);
        }

        $categories = ProgramCategory::cases();

        return view('admin.media-programs.index', compact('programs', 'stats', 'categories'));
    }

    /**
     * GET /su/portal/v1/admin/media-programs/create
     */
    public function create(): View
    {
        $categories = ProgramCategory::cases();

        return view('admin.media-programs.create', compact('categories'));
    }

    /**
     * POST /su/portal/v1/admin/media-programs
     */
    public function store(StoreMediaProgramRequest $request, YouTubeSyncService $youtube): RedirectResponse
    {
        $dto = CreateMediaProgramData::fromRequest($request);

        // One unit of work: a program without its translations is invisible
        // in every listing, so the row must never outlive a failed sync.
        $program = DB::transaction(function () use ($dto): MediaProgram {
            $program = $this->programService->createProgram($dto);

            $program->syncTranslations([
                'title' => $dto->title,
                'description' => $dto->description,
                'host' => $dto->host,
                'seo_title' => $dto->seoTitle,
                'seo_description' => $dto->seoDescription,
                'seo_keywords' => $dto->seoKeywords,
            ]);

            $this->syncProgramImages($program, $dto->poster, $dto->banner, $dto->posterId, $dto->bannerId);

            return $program;
        });

        $this->logger->logCreate($program, $request->user());

        // A source pasted on the create form starts its import immediately;
        // the show page picks the run up from ?yt_run and shows live progress.
        // Started outside the transaction so the queued job can never see a
        // run whose program has not been committed yet.
        $validated = $request->validated();
        $source = $validated['youtube_url'] ?? null;
        if (filled($source)) {
            $run = $youtube->startRun(
                $program->id,
                $source,
                $request->user()?->id,
                $validated['youtube_items'] ?? null,
            );

            return redirect()->route('admin.media-programs.show', ['media_program' => $program->id, 'yt_run' => $run->id])
                ->with('success', __('admin.program_created'));
        }

        return redirect()->route('admin.media-programs.index')
            ->with('success', __('admin.program_created'));
    }

    /**
     * GET /su/portal/v1/admin/media-programs/{id}
     */
    public function show(int $id): View
    {
        $program = $this->programService->find($id);

        if (! $program) {
            abort(404, 'Program not found');
        }

        $program->loadMissing(['translations', 'episodes.translations']);

        return view('admin.media-programs.show', compact('program'));
    }

    /**
     * GET /su/portal/v1/admin/media-programs/{id}/edit
     */
    public function edit(int $id): View
    {
        $program = $this->programService->find($id);

        if (! $program) {
            abort(404, 'Program not found');
        }

        $program->loadMissing(['translations']);
        $categories = ProgramCategory::cases();

        return view('admin.media-programs.edit', compact('program', 'categories'));
    }

    /**
     * PUT /su/portal/v1/admin/media-programs/{id}
     */
    public function update(UpdateMediaProgramRequest $request, int $id): RedirectResponse
    {
        $old = $this->programService->find($id);

        if (! $old) {
            abort(404, 'Program not found');
        }

        $oldValues = $old->toArray();
        $dto = UpdateMediaProgramData::fromRequest($request);

        $program = DB::transaction(function () use ($id, $dto): MediaProgram {
            $program = $this->programService->updateProgram($id, $dto);

            $program->syncTranslations([
                'title' => $dto->title,
                'description' => $dto->description,
                'host' => $dto->host,
                'seo_title' => $dto->seoTitle,
                'seo_description' => $dto->seoDescription,
                'seo_keywords' => $dto->seoKeywords,
            ]);

            $this->syncProgramImages($program, $dto->poster, $dto->banner, $dto->posterId, $dto->bannerId);

            return $program;
        });

        $this->logger->logUpdate($program, $oldValues, $program->toArray(), $request->user());

        return redirect()->route('admin.media-programs.show', $program->id)
            ->with('success', __('admin.program_updated'));
    }

    /**
     * DELETE /su/portal/v1/admin/media-programs/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        $program = $this->programService->findOrFail($id);
        $this->programService->deleteProgram($id);
        $this->logger->logDelete($program, request()->user());

        return redirect()->route('admin.media-programs.index')
            ->with('success', __('admin.program_deleted'));
    }

    /**
     * POST /su/portal/v1/admin/media-programs/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:media_programs,id'],
        ]);

        $deleted = 0;
        foreach ($request->input('ids') as $id) {
            try {
                $this->programService->deleteProgram((int) $id);
                $deleted++;
            } catch (\Throwable) {
                // Continue — partial deletes are acceptable for bulk operations
            }
        }

        $this->logger->logBulkAction(
            'bulk_delete',
            MediaProgram::class,
            $request->input('ids'),
            $request->user()
        );

        return redirect()->route('admin.media-programs.index')
            ->with('success', "Deleted {$deleted} program(s) successfully.");
    }

    /**
     * POST /su/portal/v1/admin/media-programs/{id}/images
     * Inline poster/banner update from the program hero.
     */
    public function updateImages(UpdateMediaProgramImagesRequest $request, int $id): RedirectResponse
    {
        $program = $this->programService->find($id);

        if (! $program) {
            abort(404, 'Program not found');
        }

        $validated = $request->validated();

        $this->syncProgramImages(
            $program,
            $request->hasFile('poster') ? $request->file('poster') : null,
            $request->hasFile('banner') ? $request->file('banner') : null,
            isset($validated['poster_id']) ? (int) $validated['poster_id'] : null,
            isset($validated['banner_id']) ? (int) $validated['banner_id'] : null,
        );

        $this->logger->logUpdate($program, [], ['images' => 'updated'], $request->user());

        return redirect()->route('admin.media-programs.show', $id)
            ->with('success', __('admin.program_updated'));
    }

    /**
     * POST /su/portal/v1/admin/media-programs/{id}/episodes/reorder
     */
    public function reorderEpisodes(Request $request, MediaEpisodeService $episodeService, int $id): JsonResponse
    {
        $program = $this->programService->find($id);

        if (! $program) {
            abort(404, 'Program not found');
        }

        $validated = $request->validate([
            'episode_ids' => ['required', 'array', 'min:1'],
            'episode_ids.*' => ['integer', 'exists:media_episodes,id'],
        ]);

        $episodeService->reorderEpisodes($id, $validated['episode_ids']);

        return response()->json(['success' => true]);
    }

    /**
     * POST /su/portal/v1/admin/media-programs/{id}/episodes/assign
     */
    public function assignEpisodes(Request $request, MediaEpisodeService $episodeService, int $id): JsonResponse
    {
        $program = $this->programService->find($id);

        if (! $program) {
            abort(404, 'Program not found');
        }

        $validated = $request->validate([
            'episode_ids' => ['required', 'array', 'min:1'],
            'episode_ids.*' => ['integer', 'exists:media_episodes,id'],
        ]);

        $assigned = $episodeService->assignEpisodes($id, $validated['episode_ids']);

        return response()->json(['success' => true, 'assigned' => $assigned]);
    }

    /**
     * POST /su/portal/v1/admin/media/youtube/analyze
     * Read-only preview of a playlist or single video (runs yt-dlp inline).
     * Deliberately program-independent: the create form analyzes a source
     * before the program row exists.
     */
    public function analyzeYoutube(YouTubeSourceRequest $request, YouTubeSyncService $service): JsonResponse
    {
        @set_time_limit(0);

        try {
            return response()->json([
                'success' => true,
                'data' => $service->analyzeSource($request->validated()['playlist_url']),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /su/portal/v1/admin/media-programs/{id}/youtube/sync
     * Create a sync run + dispatch the background import job.
     */
    public function syncYoutube(YouTubeSourceRequest $request, YouTubeSyncService $service, int $id): JsonResponse
    {
        $program = $this->programService->find($id);

        if (! $program) {
            return response()->json(['success' => false, 'message' => __('admin.not_found')], 404);
        }

        $validated = $request->validated();

        $run = $service->startRun(
            $program->id,
            $validated['playlist_url'],
            $request->user()?->id,
            $validated['items'] ?? null,
        );

        return response()->json([
            'success' => true,
            'run_id' => $run->id,
            'status_url' => route('admin.media.youtube.sync-status', $run->id),
        ]);
    }

    /**
     * GET /su/portal/v1/admin/media/youtube/sync-status/{run}
     * Progress poll target for the import modal.
     */
    public function youtubeSyncStatus(YouTubeSyncService $service, int $runId): JsonResponse
    {
        $status = $service->getRunStatus($runId);

        if ($status === null) {
            return response()->json(['success' => false, 'message' => __('admin.not_found')], 404);
        }

        return response()->json(['success' => true, 'data' => $status]);
    }

    /**
     * Attach poster/banner from a direct upload or a media-library pick.
     * An upload wins over a library id when the form somehow sends both.
     */
    private function syncProgramImages(
        MediaProgram $program,
        ?UploadedFile $posterFile,
        ?UploadedFile $bannerFile,
        ?int $posterId,
        ?int $bannerId
    ): void {
        $this->attachments->attach($program, 'poster', $posterFile ?? $posterId);
        $this->attachments->attach($program, 'banner', $bannerFile ?? $bannerId);
    }

    /**
     * GET /su/portal/v1/admin/media-programs/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $programs = $this->programService->search(
            $request->input('q'),
            $request->integer('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'data' => $programs->items(),
            'meta' => [
                'current_page' => $programs->currentPage(),
                'last_page' => $programs->lastPage(),
                'per_page' => $programs->perPage(),
                'total' => $programs->total(),
            ],
        ]);
    }
}
