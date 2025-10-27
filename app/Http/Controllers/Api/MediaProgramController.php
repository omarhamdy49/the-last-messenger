<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Media\Services\MediaProgramService;
use App\Http\Resources\MediaEpisodeResource;
use App\Http\Resources\MediaProgramResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MediaProgramController extends Controller
{
    use ApiResponse;

    /**
     * MediaProgramController constructor
     *
     * @param MediaProgramService $mediaProgramService
     */
    public function __construct(
        private readonly MediaProgramService $mediaProgramService
    ) {
    }

    /**
     * Get all programs with pagination
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $programs = $this->mediaProgramService->getAllPrograms($perPage);

        return $this->success(
            MediaProgramResource::collection($programs)->response()->getData(true)
        );
    }

    /**
     * Get a single program by slug
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $program = $this->mediaProgramService->getProgramBySlug($slug);

            return $this->success(
                new MediaProgramResource($program)
            );
        } catch (\Exception $e) {
            return $this->notFound($e->getMessage());
        }
    }

    /**
     * Get programs by category
     *
     * @param Request $request
     * @param int $categoryId
     * @return JsonResponse
     */
    public function byCategory(Request $request, int $categoryId): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $programs = $this->mediaProgramService->getProgramsByCategory($categoryId, $perPage);

        return $this->success(
            MediaProgramResource::collection($programs)->response()->getData(true)
        );
    }

    /**
     * Get popular programs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        $programs = $this->mediaProgramService->getPopularPrograms($limit);

        return $this->success(
            MediaProgramResource::collection($programs)
        );
    }

    /**
     * Get recent programs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        $programs = $this->mediaProgramService->getRecentPrograms($limit);

        return $this->success(
            MediaProgramResource::collection($programs)
        );
    }

    /**
     * Search programs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $perPage = $request->integer('per_page', 15);

        if (empty($query)) {
            return $this->error('Search query is required', 400);
        }

        $programs = $this->mediaProgramService->searchPrograms($query, $perPage);

        return $this->success(
            MediaProgramResource::collection($programs)->response()->getData(true)
        );
    }

    /**
     * Get program with specific episode
     *
     * @param string $slug
     * @param int $episodeNumber
     * @return JsonResponse
     */
    public function episode(string $slug, int $episodeNumber): JsonResponse
    {
        try {
            $result = $this->mediaProgramService->getProgramWithEpisode($slug, $episodeNumber);

            return $this->success([
                'program' => new MediaProgramResource($result['program']),
                'episode' => new MediaEpisodeResource($result['episode']),
            ]);
        } catch (\Exception $e) {
            return $this->notFound($e->getMessage());
        }
    }

    /**
     * Get all episodes for a program
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function episodes(string $slug): JsonResponse
    {
        try {
            $program = $this->mediaProgramService->getProgramBySlug($slug);
            $episodes = $program->publishedEpisodes;

            return $this->success(
                MediaEpisodeResource::collection($episodes)
            );
        } catch (\Exception $e) {
            return $this->notFound($e->getMessage());
        }
    }
}

