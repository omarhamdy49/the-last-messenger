<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Shared\Services\SavedFilterService;
use App\Domain\Shared\Services\SearchService;
use App\Exceptions\Domain\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveFilterRequest;
use App\Http\Resources\SavedFilterResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Filter Controller
 * Manages saved filters and search preferences
 */
class FilterController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SearchService $searchService,
        protected SavedFilterService $savedFilterService,
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user's saved filters
     *
     * GET /api/filters?module={module}
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'module' => 'nullable|string|in:article,media,seerah,sahaba,quran',
        ]);

        $filters = $this->searchService->getSavedFilters(
            user: $request->user(),
            module: $request->input('module')
        );

        return $this->successResponse(
            SavedFilterResource::collection($filters),
            'Saved filters retrieved successfully'
        );
    }

    /**
     * Save a new filter
     *
     * POST /api/filters
     * Body: name, description, module, filters, sort_options, is_public, is_default
     */
    public function store(SaveFilterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filter = $this->searchService->saveFilter(
            user: $request->user(),
            name: $validated['name'],
            module: $validated['module'],
            filters: $validated['filters'],
            options: [
                'description' => $validated['description'] ?? null,
                'sort_options' => $validated['sort_options'] ?? null,
                'is_public' => $validated['is_public'] ?? false,
                'is_default' => $validated['is_default'] ?? false,
            ]
        );

        return $this->createdResponse(
            new SavedFilterResource($filter),
            'Filter saved successfully'
        );
    }

    /**
     * Update a saved filter
     *
     * PUT /api/filters/{id}
     * Body: name, description, filters, sort_options, is_public, is_default
     */
    public function update(int $id, SaveFilterRequest $request): JsonResponse
    {
        try {
            $filter = $this->savedFilterService->update(
                id: $id,
                user: $request->user(),
                data: $request->validated(),
            );
        } catch (ResourceNotFoundException) {
            return $this->notFoundResponse('Filter not found');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse($e->getMessage());
        }

        return $this->successResponse(
            new SavedFilterResource($filter),
            'Filter updated successfully'
        );
    }

    /**
     * Delete a saved filter
     *
     * DELETE /api/filters/{id}
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        try {
            $this->savedFilterService->delete(
                id: $id,
                user: $request->user(),
            );
        } catch (ResourceNotFoundException) {
            return $this->notFoundResponse('Filter not found');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse($e->getMessage());
        }

        return $this->successResponse(null, 'Filter deleted successfully');
    }

    /**
     * Apply a saved filter
     *
     * POST /api/filters/{id}/apply
     * Body: query (optional), per_page (optional)
     */
    public function apply(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'nullable|string|min:2|max:500',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            // Verify access and increment usage via service (no Eloquent in controller).
            $this->savedFilterService->findAccessibleForApply($id, $request->user());

            $results = $this->searchService->applySavedFilter(
                filterId: $id,
                options: [
                    'query' => $request->input('query', ''),
                    'per_page' => $request->input('per_page', 15),
                    'user' => $request->user(),
                ]
            );

            return $this->paginatedResponse($results, 'Filter applied successfully');
        } catch (ResourceNotFoundException) {
            return $this->notFoundResponse('Filter not found');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse($e->getMessage());
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to apply filter', 400);
        }
    }

    /**
     * Set a filter as default for its module
     *
     * POST /api/filters/{id}/set-default
     */
    public function setDefault(int $id, Request $request): JsonResponse
    {
        try {
            $filter = $this->savedFilterService->setDefault(
                id: $id,
                user: $request->user(),
            );
        } catch (ResourceNotFoundException) {
            return $this->notFoundResponse('Filter not found');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse($e->getMessage());
        }

        return $this->successResponse(
            new SavedFilterResource($filter),
            'Filter set as default successfully'
        );
    }

    /**
     * Get public filters
     *
     * GET /api/filters/public?module={module}
     */
    public function publicFilters(Request $request): JsonResponse
    {
        $request->validate([
            'module' => 'nullable|string|in:article,media,seerah,sahaba,quran',
        ]);

        $filters = $this->savedFilterService->listPublic($request->input('module'));

        return $this->successResponse(
            SavedFilterResource::collection($filters),
            'Public filters retrieved successfully'
        );
    }

    /**
     * Get user's search preferences for a module
     *
     * GET /api/filters/preferences/{module}
     */
    public function getPreferences(string $module, Request $request): JsonResponse
    {
        $preference = $this->searchService->getSearchPreference(
            user: $request->user(),
            module: $module
        );

        if (! $preference) {
            return $this->successResponse(null, 'No preferences found for this module');
        }

        return $this->successResponse($preference, 'Preferences retrieved successfully');
    }

    /**
     * Save user's search preferences for a module
     *
     * POST /api/filters/preferences/{module}
     * Body: default_filters, default_sort, default_order, default_per_page, show_facets, highlight_results
     */
    public function savePreferences(string $module, Request $request): JsonResponse
    {
        $request->validate([
            'default_filters' => 'nullable|array|max:25',
            'default_sort' => 'nullable|string|in:relevance,date,newest,oldest,popular,updated',
            'default_order' => 'nullable|string|in:asc,desc',
            'default_per_page' => 'nullable|integer|min:1|max:100',
            'show_facets' => 'nullable|boolean',
            'highlight_results' => 'nullable|boolean',
            'language' => 'nullable|string|in:ar,en,fr,tr,ur',
        ]);

        $preference = $this->searchService->saveSearchPreference(
            user: $request->user(),
            module: $module,
            preferences: $request->only([
                'default_filters',
                'default_sort',
                'default_order',
                'default_per_page',
                'show_facets',
                'highlight_results',
                'language',
            ])
        );

        return $this->successResponse($preference, 'Preferences saved successfully');
    }
}
