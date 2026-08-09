<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Media\Services\MediaEpisodeService;
use App\Domain\Media\Services\MediaProgramService;
use App\Exceptions\Domain\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreWatchProgressRequest;
use App\Http\Resources\MediaEpisodeResource;
use App\Http\Resources\MediaProgramResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Authenticated media API controller.
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
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'category' => ['sometimes', 'string', 'in:seerah,quran,hadith,kids,documentary'],
            'sort' => ['sometimes', 'string', 'in:display_order,views_count,created_at,title'],
            'dir' => ['sometimes', 'string', 'in:asc,desc'],
        ]);

        try {
            $perPage = (int) ($validated['per_page'] ?? 15);
            $category = $validated['category'] ?? null;
            $sortBy = $validated['sort'] ?? 'display_order';
            $sortDir = $validated['dir'] ?? 'asc';

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
        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        try {
            $limit = (int) ($validated['limit'] ?? 12);
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
        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        try {
            $limit = (int) ($validated['limit'] ?? 12);
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
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:200'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $perPage = (int) ($validated['per_page'] ?? 15);
            $programs = $this->programService->search($validated['q'], $perPage, true);

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
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $program = $this->programService->getBySlug($slug);
            $perPage = (int) ($validated['per_page'] ?? 20);
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
        $validated = $request->validate([
            'season' => ['sometimes', 'integer', 'min:1', 'max:1000'],
        ]);

        try {
            $program = $this->programService->getBySlug($slug);
            $season = (int) ($validated['season'] ?? 1);
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
        $validated = $request->validated();

        try {
            $this->episodeService->saveProgress(
                $request->user()->id,
                (int) $validated['episode_id'],
                (int) $validated['seconds'],
                (bool) ($validated['completed'] ?? false),
            );

            return $this->success(null, 'Progress saved');
        } catch (ResourceNotFoundException $e) {
            return $this->notFound('Episode not found');
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
        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        try {
            $userId = $request->user()->id;
            $limit = (int) ($validated['limit'] ?? 10);

            $progress = $this->episodeService->getContinueWatching($userId, $limit);

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
