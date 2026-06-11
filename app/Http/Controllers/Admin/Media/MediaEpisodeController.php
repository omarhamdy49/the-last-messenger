<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Media;

use App\Domain\Media\DTOs\CreateMediaEpisodeData;
use App\Domain\Media\DTOs\UpdateMediaEpisodeData;
use App\Domain\Media\Services\MediaEpisodeService;
use App\Domain\Media\Services\MediaProgramService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaProgram\StoreMediaEpisodeRequest;
use App\Http\Requests\Admin\MediaProgram\UpdateMediaEpisodeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Media Episode Controller
 */
class MediaEpisodeController extends Controller
{
    public function __construct(
        protected MediaEpisodeService $episodeService,
        protected MediaProgramService $programService,
        protected AdminLoggerService $logger,
    ) {}

    /**
     * GET /su/portal/v1/admin/media-episodes?program_id=X
     */
    public function index(Request $request): View|JsonResponse
    {
        $programId = $request->integer('program_id');
        $perPage = min($request->integer('per_page', 20), 100);
        $search = trim((string) $request->input('q', ''));
        $excludeProgramId = $request->integer('exclude_program_id');

        if ($programId) {
            $episodes = $this->episodeService->paginateByProgram($programId, $perPage);
            $program = $this->programService->find($programId);
        } else {
            // All episodes with program context — also serves the
            // "episodes library" picker (q search + exclude_program_id).
            $episodes = \App\Domain\Media\Models\MediaEpisode::query()
                ->select(['id', 'program_id', 'season_number', 'episode_number', 'slug', 'video_url', 'duration_seconds', 'views_count', 'is_featured', 'created_at'])
                ->with(['translations:id,media_episode_id,locale,title,is_ready', 'program:id,slug'])
                ->with(['program.translations:id,media_program_id,locale,title,is_ready'])
                ->when($excludeProgramId, fn ($q) => $q->where('program_id', '!=', $excludeProgramId))
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($q) use ($search) {
                        $q->where('slug', 'LIKE', "%{$search}%")
                            ->orWhereHas('translations', fn ($t) => $t->where('title', 'LIKE', "%{$search}%"));
                    });
                })
                ->orderBy('program_id')
                ->orderBy('season_number')
                ->orderBy('episode_number')
                ->paginate($perPage);
            $program = null;
        }

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

        $programs = $this->programService->paginate(100);

        return view('admin.media-episodes.index', compact('episodes', 'program', 'programs'));
    }

    /**
     * GET /su/portal/v1/admin/media-episodes/create?program_id=X
     */
    public function create(Request $request): View
    {
        $programs = $this->programService->paginate(100);
        $programId = $request->integer('program_id');

        return view('admin.media-episodes.create', compact('programs', 'programId'));
    }

    /**
     * POST /su/portal/v1/admin/media-episodes
     */
    public function store(StoreMediaEpisodeRequest $request): RedirectResponse
    {
        $dto = CreateMediaEpisodeData::fromRequest($request);
        $episode = $this->episodeService->createEpisode($dto);

        $episode->syncTranslations([
            'title' => $dto->title,
            'description' => $dto->description,
            'transcript' => $dto->transcript,
        ]);

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

        $episode->loadMissing(['translations']);
        $programs = $this->programService->paginate(100);

        return view('admin.media-episodes.edit', compact('episode', 'programs'));
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
        $episode = $this->episodeService->updateEpisode($id, $dto);

        $episode->syncTranslations([
            'title' => $dto->title,
            'description' => $dto->description,
            'transcript' => $dto->transcript,
        ]);

        $this->logger->logUpdate($episode, $oldValues, $episode->toArray(), $request->user());

        return redirect()->route('admin.media-episodes.index', ['program_id' => $episode->program_id])
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
