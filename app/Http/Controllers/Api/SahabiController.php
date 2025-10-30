<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Sahaba\Services\SahabiService;
use App\Http\Resources\SahabiResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SahabiController extends Controller
{
    use ApiResponse;

    /**
     * SahabiController constructor
     */
    public function __construct(
        private readonly SahabiService $sahabiService
    ) {}

    /**
     * Get all companions with pagination
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $companions = $this->sahabiService->getAllCompanions($perPage);

        return $this->success(
            SahabiResource::collection($companions)->response()->getData(true)
        );
    }

    /**
     * Get a single companion by slug
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $companion = $this->sahabiService->getCompanionBySlug($slug);

            return $this->success(
                new SahabiResource($companion)
            );
        } catch (\Exception $e) {
            return $this->notFound($e->getMessage());
        }
    }

    /**
     * Get companions by status
     */
    public function byStatus(Request $request, string $status): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $companions = $this->sahabiService->getCompanionsByStatus($status, $perPage);

        return $this->success(
            SahabiResource::collection($companions)->response()->getData(true)
        );
    }

    /**
     * Get companions by tribe
     */
    public function byTribe(Request $request, string $tribe): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $companions = $this->sahabiService->getCompanionsByTribe($tribe, $perPage);

        return $this->success(
            SahabiResource::collection($companions)->response()->getData(true)
        );
    }

    /**
     * Get companions who participated in a battle
     */
    public function byBattle(Request $request, string $battle): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $companions = $this->sahabiService->getCompanionsByBattle($battle, $perPage);

        return $this->success(
            SahabiResource::collection($companions)->response()->getData(true)
        );
    }

    /**
     * Get featured companions
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        $companions = $this->sahabiService->getFeaturedCompanions($limit);

        return $this->success(
            SahabiResource::collection($companions)
        );
    }

    /**
     * Get early Muslims by conversion order
     */
    public function earlyMuslims(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        $companions = $this->sahabiService->getEarlyMuslims($limit);

        return $this->success(
            SahabiResource::collection($companions)
        );
    }

    /**
     * Get top hadith narrators
     */
    public function topNarrators(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        $companions = $this->sahabiService->getTopNarrators($limit);

        return $this->success(
            SahabiResource::collection($companions)
        );
    }

    /**
     * Search companions
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $perPage = $request->integer('per_page', 15);

        if (empty($query)) {
            return $this->error('Search query is required', 400);
        }

        $companions = $this->sahabiService->searchCompanions($query, $perPage);

        return $this->success(
            SahabiResource::collection($companions)->response()->getData(true)
        );
    }
}
