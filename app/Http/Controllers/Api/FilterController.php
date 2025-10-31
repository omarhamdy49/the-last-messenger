<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Shared\Services\SearchService;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveFilterRequest;
use App\Http\Resources\SavedFilterResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Filter Controller
 * Manages saved filters and search preferences
 */
class FilterController extends Controller
{
    use ApiResponse;

    public function __construct(protected SearchService $searchService)
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user's saved filters
     *
     * GET /api/filters?module={module}
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'module' => 'nullable|string',
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
     *
     * @return JsonResponse
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
     *
     * @return JsonResponse
     */
    public function update(int $id, SaveFilterRequest $request): JsonResponse
    {
        $filter = \App\Domain\Shared\Models\SavedFilter::findOrFail($id);

        // Verify ownership
        if ($filter->user_id !== $request->user()->id) {
            return $this->forbiddenResponse('You do not have permission to update this filter');
        }

        $validated = $request->validated();

        // If setting as default, unset other defaults
        if (($validated['is_default'] ?? false) && ! $filter->is_default) {
            \App\Domain\Shared\Models\SavedFilter::where('user_id', $request->user()->id)
                ->where('module', $filter->module)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $filter->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $filter->description,
            'filters' => $validated['filters'],
            'sort_options' => $validated['sort_options'] ?? $filter->sort_options,
            'is_public' => $validated['is_public'] ?? $filter->is_public,
            'is_default' => $validated['is_default'] ?? $filter->is_default,
        ]);

        return $this->successResponse(
            new SavedFilterResource($filter->fresh()),
            'Filter updated successfully'
        );
    }

    /**
     * Delete a saved filter
     *
     * DELETE /api/filters/{id}
     *
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $filter = \App\Domain\Shared\Models\SavedFilter::findOrFail($id);

        // Verify ownership
        if ($filter->user_id !== $request->user()->id) {
            return $this->forbiddenResponse('You do not have permission to delete this filter');
        }

        $filter->delete();

        return $this->successResponse(null, 'Filter deleted successfully');
    }

    /**
     * Apply a saved filter
     *
     * POST /api/filters/{id}/apply
     * Body: query (optional), per_page (optional)
     *
     * @return JsonResponse
     */
    public function apply(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'nullable|string|min:2|max:500',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $filter = \App\Domain\Shared\Models\SavedFilter::findOrFail($id);

        // Check if filter is public or owned by user
        if (! $filter->is_public && $filter->user_id !== $request->user()?->id) {
            return $this->forbiddenResponse('You do not have permission to use this filter');
        }

        try {
            $results = $this->searchService->applySavedFilter(
                filterId: $id,
                options: [
                    'query' => $request->input('query', ''),
                    'per_page' => $request->input('per_page', 15),
                    'user' => $request->user(),
                ]
            );

            return $this->paginatedResponse($results, 'Filter applied successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to apply filter: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Set a filter as default for its module
     *
     * POST /api/filters/{id}/set-default
     *
     * @return JsonResponse
     */
    public function setDefault(int $id, Request $request): JsonResponse
    {
        $filter = \App\Domain\Shared\Models\SavedFilter::findOrFail($id);

        // Verify ownership
        if ($filter->user_id !== $request->user()->id) {
            return $this->forbiddenResponse('You do not have permission to modify this filter');
        }

        // Unset other defaults for this module
        \App\Domain\Shared\Models\SavedFilter::where('user_id', $request->user()->id)
            ->where('module', $filter->module)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        // Set this as default
        $filter->update(['is_default' => true]);

        return $this->successResponse(
            new SavedFilterResource($filter->fresh()),
            'Filter set as default successfully'
        );
    }

    /**
     * Get public filters
     *
     * GET /api/filters/public?module={module}
     *
     * @return JsonResponse
     */
    public function publicFilters(Request $request): JsonResponse
    {
        $request->validate([
            'module' => 'nullable|string',
        ]);

        $filters = \App\Domain\Shared\Models\SavedFilter::query()
            ->where('is_public', true)
            ->when($request->input('module'), function ($query, $module) {
                $query->where('module', $module);
            })
            ->orderByDesc('usage_count')
            ->get();

        return $this->successResponse(
            SavedFilterResource::collection($filters),
            'Public filters retrieved successfully'
        );
    }

    /**
     * Get user's search preferences for a module
     *
     * GET /api/filters/preferences/{module}
     *
     * @return JsonResponse
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
     *
     * @return JsonResponse
     */
    public function savePreferences(string $module, Request $request): JsonResponse
    {
        $request->validate([
            'default_filters' => 'nullable|array',
            'default_sort' => 'nullable|string|max:50',
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
