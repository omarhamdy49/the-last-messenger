<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Shared\Services\SearchService;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Http\Resources\SearchHistoryResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Search Controller
 * Handles all search operations across modules
 */
class SearchController extends Controller
{
    use ApiResponse;

    public function __construct(protected SearchService $searchService)
    {
        // Most search endpoints are public, only history requires auth
        $this->middleware('auth:sanctum')->only(['history', 'clearHistory']);
    }

    /**
     * Perform global search across all modules
     *
     * POST /api/search
     * Body: query, filters, modules, sort, order, per_page
     *
     * @return JsonResponse
     */
    public function index(SearchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $results = $this->searchService->search(
            query: $validated['query'],
            filters: $validated['filters'] ?? [],
            options: [
                'modules' => $validated['modules'] ?? null,
                'sort' => $validated['sort'] ?? 'relevance',
                'order' => $validated['order'] ?? 'desc',
                'per_page' => $validated['per_page'] ?? 15,
                'user' => $request->user(),
            ]
        );

        return $this->successResponse($results, 'Search completed successfully');
    }

    /**
     * Search within a specific module
     *
     * POST /api/search/{module}
     * Body: query, filters, sort, order, per_page
     *
     * @param  string  $module  Module type (article, hadith, etc.)
     * @return JsonResponse
     */
    public function searchModule(string $module, SearchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $results = $this->searchService->searchWithin(
                moduleType: $module,
                query: $validated['query'],
                filters: $validated['filters'] ?? [],
                options: [
                    'sort' => $validated['sort'] ?? 'relevance',
                    'order' => $validated['order'] ?? 'desc',
                    'per_page' => $validated['per_page'] ?? 15,
                    'user' => $request->user(),
                ]
            );

            return $this->paginatedResponse($results, "Search in {$module} completed successfully");
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get search suggestions based on query
     *
     * GET /api/search/suggestions?q={query}&module={module}
     *
     * @return JsonResponse
     */
    public function suggestions(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
            'module' => 'nullable|string',
        ]);

        $suggestions = $this->searchService->getSuggestions(
            query: $request->input('q'),
            module: $request->input('module')
        );

        return $this->successResponse([
            'suggestions' => $suggestions,
            'count' => $suggestions->count(),
        ], 'Suggestions retrieved successfully');
    }

    /**
     * Get user's search history
     *
     * GET /api/search/history?module={module}
     *
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'module' => 'nullable|string',
        ]);

        $history = $this->searchService->getSearchHistory(
            user: $request->user(),
            module: $request->input('module')
        );

        return $this->successResponse(
            SearchHistoryResource::collection($history),
            'Search history retrieved successfully'
        );
    }

    /**
     * Clear user's search history
     *
     * DELETE /api/search/history?module={module}
     *
     * @return JsonResponse
     */
    public function clearHistory(Request $request): JsonResponse
    {
        $request->validate([
            'module' => 'nullable|string',
        ]);

        $deleted = $this->searchService->clearSearchHistory(
            user: $request->user(),
            module: $request->input('module')
        );

        return $this->successResponse(
            ['deleted_count' => $deleted],
            'Search history cleared successfully'
        );
    }

    /**
     * Get popular/trending searches
     *
     * GET /api/search/trending?module={module}&days={days}
     *
     * @return JsonResponse
     */
    public function trending(Request $request): JsonResponse
    {
        $request->validate([
            'module' => 'nullable|string',
            'days' => 'nullable|integer|min:1|max:30',
        ]);

        $trending = $this->searchService->getPopularSearches(
            module: $request->input('module'),
            days: (int) $request->input('days', 7)
        );

        return $this->successResponse([
            'trending' => $trending,
            'count' => $trending->count(),
            'period_days' => (int) $request->input('days', 7),
        ], 'Trending searches retrieved successfully');
    }

    /**
     * Get available search modules and their filterable fields
     *
     * GET /api/search/modules
     *
     * @return JsonResponse
     */
    public function modules(): JsonResponse
    {
        $modules = \App\Domain\Shared\Enums\SearchModule::cases();

        $modulesData = array_map(function ($module) {
            if ($module === \App\Domain\Shared\Enums\SearchModule::ALL) {
                return null;
            }

            return [
                'value' => $module->value,
                'label' => $module->label(),
                'searchable_fields' => $module->searchableFields(),
                'filterable_fields' => array_map(
                    fn ($type) => $type->value,
                    $module->filterableFields()
                ),
            ];
        }, $modules);

        $modulesData = array_filter($modulesData);

        return $this->successResponse([
            'modules' => array_values($modulesData),
            'count' => count($modulesData),
        ], 'Search modules retrieved successfully');
    }
}
