<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Media\Models\EpisodeWatchProgress;
use App\Domain\Media\Services\MediaEpisodeService;
use App\Domain\Media\Services\MediaProgramService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreWatchProgressRequest;
use App\Http\Resources\MediaEpisodeResource;
use App\Http\Resources\MediaProgramResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public Media API Controller
 *
 * All public endpoints are unauthenticated except:
 *   POST  /media/progress           — requires auth:sanctum
 *   GET   /media/continue-watching  — requires auth:sanctum
 */
class MediaProgramController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MediaProgramService $programService,
        private readonly MediaEpisodeService $episodeService,
    ) {}

    /**
     * Paginated list of programs, optionally filtered by category.
     * GET /api/v1/media/programs?category=seerah&sort=views_count&dir=desc&per_page=15
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = min((int) $request->input('per_page', 15), 100);
            $category = $request->input('category');
            $sortBy = $request->input('sort', 'display_order');
            $sortDir = $request->input('dir', 'asc');

            $programs = $this->programService->paginatePrograms($perPage, $category, $sortBy, $sortDir, null, true);

            return $this->success(
                MediaProgramResource::collection($programs)->response()->getData(true)
            );
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve programs', 500);
        }
    }

    /**
     * Top programs by view count.
     * GET /api/v1/media/programs/popular
     */
    public function popular(Request $request): JsonResponse
    {
        try {
            $limit = min((int) $request->input('limit', 12), 50);
            $programs = $this->programService->getPopular($limit);

            return $this->success(MediaProgramResource::collection($programs));
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve popular programs', 500);
        }
    }

    /**
     * Most recently added programs.
     * GET /api/v1/media/programs/recent
     */
    public function recent(Request $request): JsonResponse
    {
        try {
            $limit = min((int) $request->input('limit', 12), 50);
            $programs = $this->programService->getRecent($limit);

            return $this->success(MediaProgramResource::collection($programs));
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve recent programs', 500);
        }
    }

    /**
     * Full-text / LIKE search across program translations.
     * GET /api/v1/media/programs/search?q=عمر&per_page=15
     * Rate limited: throttle:search (30/min).
     */
    public function search(Request $request): JsonResponse
    {
        $term = (string) $request->input('q', '');

        if (mb_strlen($term) < 2) {
            return $this->validationErrorResponse(['q' => 'Search term must be at least 2 characters.']);
        }

        try {
            $perPage = min((int) $request->input('per_page', 15), 100);
            $programs = $this->programService->search($term, $perPage, true);

            return $this->success(
                MediaProgramResource::collection($programs)->response()->getData(true)
            );
        } catch (\Throwable $e) {
            return $this->error('Search failed', 500);
        }
    }

    /**
     * Programs filtered by category slug.
     * GET /api/v1/media/programs/category/{categoryId}
     */
    public function byCategory(string $categoryId): JsonResponse
    {
        try {
            $programs = $this->programService->paginatePrograms(15, $categoryId, 'display_order', 'asc', null, true);

            return $this->success(
                MediaProgramResource::collection($programs)->response()->getData(true)
            );
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve programs by category', 500);
        }
    }

    /**
     * Single program by slug.
     * GET /api/v1/media/programs/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $program = $this->programService->getBySlug($slug);

            return $this->success(new MediaProgramResource($program));
        } catch (\Throwable $e) {
            return $this->notFound("Program not found: {$slug}");
        }
    }

    /**
     * Paginated episodes for a program.
     * GET /api/v1/media/programs/{slug}/episodes?per_page=20
     */
    public function episodes(Request $request, string $slug): JsonResponse
    {
        try {
            $program = $this->programService->getBySlug($slug);
            $perPage = min((int) $request->input('per_page', 20), 100);
            $episodes = $this->episodeService->paginateByProgram($program->id, $perPage);

            return $this->success(
                MediaEpisodeResource::collection($episodes)->response()->getData(true)
            );
        } catch (\Throwable $e) {
            return $this->notFound("Program not found: {$slug}");
        }
    }

    /**
     * Single episode within a program.
     * GET /api/v1/media/programs/{slug}/episodes/{episodeNumber}?season=1
     */
    public function episode(Request $request, string $slug, int $episodeNumber): JsonResponse
    {
        try {
            $program = $this->programService->getBySlug($slug);
            $season = (int) $request->input('season', 1);
            $episode = $this->episodeService->getEpisode($program->id, $season, $episodeNumber);
            $next = $this->episodeService->findNextEpisode($episode);

            return $this->success([
                'episode' => new MediaEpisodeResource($episode),
                'next_episode' => $next ? new MediaEpisodeResource($next) : null,
            ]);
        } catch (\Throwable $e) {
            return $this->notFound($e->getMessage());
        }
    }

    /**
     * Upsert watch progress for the authenticated user.
     * POST /api/v1/media/progress
     * Requires auth:sanctum — IDOR-safe: user_id is always from auth.
     */
    public function storeProgress(StoreWatchProgressRequest $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $validated = $request->validated();

            EpisodeWatchProgress::updateOrCreate(
                [
                    'user_id' => $userId,
                    'episode_id' => (int) $validated['episode_id'],
                ],
                [
                    'seconds' => (int) $validated['seconds'],
                    'completed' => (bool) ($validated['completed'] ?? false),
                ]
            );

            return $this->success(null, 'Progress saved');
        } catch (\Throwable $e) {
            return $this->error('Failed to save progress', 500);
        }
    }

    /**
     * Return in-progress episodes for the authenticated user.
     * GET /api/v1/media/continue-watching
     * Requires auth:sanctum.
     */
    public function continueWatching(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $limit = min((int) $request->input('limit', 10), 50);

            $progress = EpisodeWatchProgress::query()
                ->select(['user_id', 'episode_id', 'seconds', 'completed', 'updated_at'])
                ->with([
                    'episode:id,program_id,season_number,episode_number,slug,duration_seconds,video_url,is_featured',
                    'episode.translations:id,media_episode_id,locale,title,is_ready',
                    'episode.program:id,slug,category',
                    'episode.program.translations:id,media_program_id,locale,title,is_ready',
                ])
                ->where('user_id', $userId)
                ->where('completed', false)
                ->orderByDesc('updated_at')
                ->limit($limit)
                ->get();

            $result = $progress->map(fn ($p) => [
                'episode' => new MediaEpisodeResource($p->episode),
                'seconds' => $p->seconds,
                'completed' => $p->completed,
                'updated_at' => $p->updated_at?->toIso8601String(),
            ]);

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve continue-watching list', 500);
        }
    }
}
