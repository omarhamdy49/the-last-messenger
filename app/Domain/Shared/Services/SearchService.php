<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Builders\FilterBuilder;
use App\Domain\Shared\Enums\SearchModule;
use App\Domain\Shared\Enums\SortDirection;
use App\Domain\Shared\Models\SavedFilter;
use App\Domain\Shared\Models\SearchHistory;
use App\Domain\Shared\Models\SearchPreference;
use App\Domain\Shared\Repositories\SavedFilterRepositoryInterface;
use App\Domain\Shared\Search\SearchQueryBuilder;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Search Service
 *
 * Thin orchestrator for unified search across all content modules.
 * Query construction is delegated to SearchQueryBuilder; this class is
 * responsible for module iteration, history persistence, cache management,
 * and user preference / saved-filter operations.
 */
class SearchService
{
    protected int $defaultPerPage = 15;

    protected int $suggestionLimit = 10;

    protected int $historyLimit = 20;

    protected int $cacheTTL = 300; // 5 minutes

    public function __construct(
        protected SavedFilterRepositoryInterface $savedFilterRepository,
        protected SearchQueryBuilder $queryBuilder,
        protected PublicVisibilityRegistry $publicVisibility,
    ) {}

    /**
     * Perform unified search across modules
     *
     * @param  string  $query  Search query
     * @param  array<string, mixed>  $filters  Additional filters
     * @param  array<string, mixed>  $options  Search options (sort, per_page, etc.)
     * @return array<string, mixed> Search results grouped by module
     */
    public function search(string $query, array $filters = [], array $options = []): array
    {
        $startTime = microtime(true);
        $results = [];

        // Get modules to search (default: all)
        $modules = $options['modules'] ?? array_filter(
            SearchModule::cases(),
            fn ($module) => $module !== SearchModule::ALL
        );

        foreach ($modules as $module) {
            if (is_string($module)) {
                $module = SearchModule::from($module);
            }

            $moduleResults = $this->searchWithin($module->value, $query, $filters, $options);
            $results[$module->value] = [
                'total' => $moduleResults->total(),
                'data' => $moduleResults->items(),
                'per_page' => $moduleResults->perPage(),
                'current_page' => $moduleResults->currentPage(),
            ];
        }

        // Per-module facet counts, derived from the already-fetched paginators
        // (each paginator ran its own COUNT) — zero additional queries.
        $counts = array_map(
            static fn (array $moduleGroup): int => (int) $moduleGroup['total'],
            $results
        );

        $totalResults = array_sum($counts);
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        // Save search history
        $this->saveSearchHistory(
            query: $query,
            module: null,
            filters: $filters,
            resultsCount: $totalResults,
            executionTime: $executionTime,
            user: $options['user'] ?? null
        );

        return [
            'query' => $query,
            'results' => $results,
            'counts' => $counts,
            'total_results' => $totalResults,
            'execution_time_ms' => $executionTime,
            'filters_applied' => ! empty($filters),
        ];
    }

    /**
     * Search within a specific module
     *
     * @param  string  $moduleType  Module type (article, hadith, etc.)
     * @param  string  $query  Search query
     * @param  array<string, mixed>  $filters  Additional filters
     * @param  array<string, mixed>  $options  Search options
     */
    public function searchWithin(
        string $moduleType,
        string $query,
        array $filters = [],
        array $options = []
    ): LengthAwarePaginator {
        $module = SearchModule::from($moduleType);

        $filterBuilder = $this->buildModuleSearchQuery($module, $query, $filters);

        $sort = $options['sort'] ?? 'relevance';
        $direction = SortDirection::from($options['order'] ?? 'desc');
        $this->queryBuilder->applySorting($filterBuilder, $sort, $direction);

        $eloquentQuery = $filterBuilder->build();

        // Eager-load relationships
        $relations = $options['with'] ?? $this->queryBuilder->getDefaultRelations($module);
        if (! empty($relations)) {
            $eloquentQuery->with($relations);
        }

        $perPage = $this->queryBuilder->resolvePerPage($options, $this->defaultPerPage);
        $results = $eloquentQuery->paginate($perPage);

        // Save search history (per-module searches made by authenticated users)
        if (isset($options['user'])) {
            $this->saveSearchHistory(
                query: $query,
                module: $moduleType,
                filters: $filters,
                resultsCount: $results->total(),
                user: $options['user']
            );
        }

        return $results;
    }

    /**
     * Count matching results in a module without fetching rows.
     *
     * Used for facet counts on modules excluded from the current result set
     * (e.g. when a content-type filter is active): one COUNT query, no
     * sorting, eager loads, pagination, or history side effects.
     *
     * @param  string  $moduleType  Module type (article, hadith, etc.)
     * @param  string  $query  Search query
     * @param  array<string, mixed>  $filters  Additional filters
     */
    public function countWithin(string $moduleType, string $query, array $filters = []): int
    {
        $module = SearchModule::from($moduleType);

        return $this->buildModuleSearchQuery($module, $query, $filters)
            ->build()
            ->count();
    }

    /**
     * Get search suggestions based on partial query
     *
     * @param  string  $query  Partial search query
     * @param  string|null  $module  Module to get suggestions for
     * @return Collection<int, string> Suggested queries
     */
    public function getSuggestions(string $query, ?string $module = null): Collection
    {
        $cacheKey = "search:suggestions:{$module}:".md5($query);

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($query, $module) {
            $queryBuilder = SearchHistory::query()
                ->select('query')
                ->where('query', 'LIKE', "{$query}%")
                ->groupBy('query')
                // Do not turn a single user's private search into a suggestion.
                ->havingRaw('COUNT(*) >= 3')
                ->orderByRaw('COUNT(*) DESC')
                ->limit($this->suggestionLimit);

            if ($module !== null) {
                $queryBuilder->where('module', $module);
            }

            return $queryBuilder->pluck('query');
        });
    }

    /**
     * Get user's search history
     *
     * @param  User|int  $user  User instance or ID
     * @param  string|null  $module  Filter by module
     * @return Collection<int, SearchHistory>
     */
    public function getSearchHistory(User|int $user, ?string $module = null): Collection
    {
        $userId = $user instanceof User ? $user->id : $user;

        $query = SearchHistory::query()
            ->where('user_id', $userId)
            ->orderBy('searched_at', 'desc')
            ->limit($this->historyLimit);

        if ($module !== null) {
            $query->where('module', $module);
        }

        return $query->get();
    }

    /**
     * Save user's search preference
     *
     * @param  User|int  $user  User instance or ID
     * @param  string  $module  Module type
     * @param  array<string, mixed>  $preferences  Preference data
     */
    public function saveSearchPreference(User|int $user, string $module, array $preferences): SearchPreference
    {
        $userId = $user instanceof User ? $user->id : $user;

        return SearchPreference::updateOrCreate(
            ['user_id' => $userId, 'module' => $module],
            $preferences
        );
    }

    /**
     * Get user's search preference for a module
     *
     * @param  User|int  $user  User instance or ID
     * @param  string  $module  Module type
     */
    public function getSearchPreference(User|int $user, string $module): ?SearchPreference
    {
        $userId = $user instanceof User ? $user->id : $user;

        return SearchPreference::byUserAndModule($userId, $module)->first();
    }

    /**
     * Get popular/trending searches
     *
     * @param  string|null  $module  Filter by module
     * @param  int  $days  Number of days to look back
     * @return Collection<int, array{query: string, module: string|null, search_count: int}>
     */
    public function getPopularSearches(?string $module = null, int $days = 7): Collection
    {
        $cacheKey = "search:popular:{$module}:{$days}";

        return Cache::remember($cacheKey, 3600, function () use ($module, $days) {
            $query = SearchHistory::query()
                ->select('query', 'module', DB::raw('COUNT(*) as search_count'))
                ->where('searched_at', '>=', now()->subDays($days))
                ->groupBy('query', 'module')
                // Small-cohort suppression protects uncommon/private queries.
                ->havingRaw('COUNT(*) >= 3')
                ->orderByDesc('search_count')
                ->limit(20);

            if ($module !== null) {
                $query->where('module', $module);
            }

            return $query->get();
        });
    }

    /**
     * Save a filter combination for reuse
     *
     * @param  User|int  $user  User instance or ID
     * @param  string  $name  Filter name
     * @param  string  $module  Module type
     * @param  array<string, mixed>  $filters  Filter configuration
     * @param  array<string, mixed>  $options  Additional options
     */
    public function saveFilter(
        User|int $user,
        string $name,
        string $module,
        array $filters,
        array $options = []
    ): SavedFilter {
        $userId = $user instanceof User ? $user->id : $user;

        // If this is set as default, unset other defaults
        if ($options['is_default'] ?? false) {
            $this->savedFilterRepository->clearDefaults($userId, $module);
        }

        /** @var SavedFilter $savedFilter */
        $savedFilter = $this->savedFilterRepository->create([
            'user_id' => $userId,
            'name' => $name,
            'description' => $options['description'] ?? null,
            'module' => $module,
            'filters' => $filters,
            'sort_options' => $options['sort_options'] ?? null,
            'is_public' => $options['is_public'] ?? false,
            'is_default' => $options['is_default'] ?? false,
        ]);

        return $savedFilter;
    }

    /**
     * Get user's saved filters
     *
     * @param  User|int  $user  User instance or ID
     * @param  string|null  $module  Filter by module
     * @return Collection<int, SavedFilter>
     */
    public function getSavedFilters(User|int $user, ?string $module = null): Collection
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->savedFilterRepository->getByUser($userId, $module);
    }

    /**
     * Apply saved filter
     *
     * @param  int  $filterId  Saved filter ID
     * @param  array<string, mixed>  $options  Additional options
     */
    public function applySavedFilter(int $filterId, array $options = []): LengthAwarePaginator
    {
        /** @var SavedFilter $savedFilter */
        $savedFilter = $this->savedFilterRepository->findOrFail($filterId);
        $this->savedFilterRepository->incrementUsage($filterId);

        $module = $savedFilter->module;
        $filters = $savedFilter->filters;
        $sortOptions = $savedFilter->sort_options ?? [];

        // Merge saved sort options with provided options
        $searchOptions = array_merge($sortOptions, $options);

        return $this->searchWithin(
            moduleType: $module,
            query: $options['query'] ?? '',
            filters: $filters,
            options: $searchOptions
        );
    }

    /**
     * Clear user's search history
     *
     * @param  User|int  $user  User instance or ID
     * @param  string|null  $module  Clear specific module only
     */
    public function clearSearchHistory(User|int $user, ?string $module = null): int
    {
        $userId = $user instanceof User ? $user->id : $user;

        $query = SearchHistory::where('user_id', $userId);

        if ($module !== null) {
            $query->where('module', $module);
        }

        return $query->delete();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build the filtered (search term + module filters) query for a module.
     *
     * Shared by searchWithin() (which adds sorting/eager loads/pagination on
     * top) and countWithin() (which only counts).
     *
     * @param  array<string, mixed>  $filters
     *
     * @throws \InvalidArgumentException when the module has no model class.
     */
    protected function buildModuleSearchQuery(SearchModule $module, string $query, array $filters): FilterBuilder
    {
        $modelClass = $module->modelClass();

        if ($modelClass === null) {
            throw new \InvalidArgumentException("Invalid module type: {$module->value}");
        }

        $eloquentQuery = $modelClass::query();

        // Public visibility gating applies to every searchable module. The
        // previous Genealogy exemption is gone with that module: people are now
        // `muslims` rows and are gated by `is_published` like everything else.
        $this->publicVisibility->apply($eloquentQuery, app()->getLocale());

        // Delegate full-text / LIKE search to the query builder
        $this->queryBuilder->applySearch(
            $eloquentQuery,
            $module->searchableFields(),
            $modelClass,
            $query
        );

        $filterBuilder = new FilterBuilder($eloquentQuery);
        $this->queryBuilder->applyFilters($filterBuilder, $filters, $module);

        return $filterBuilder;
    }

    /**
     * Persist a search event to history.
     *
     * @param  array<string, mixed>  $filters
     * @param  float|null  $executionTime  Milliseconds
     */
    protected function saveSearchHistory(
        string $query,
        ?string $module,
        array $filters,
        int $resultsCount,
        ?float $executionTime = null,
        ?User $user = null
    ): void {
        SearchHistory::create([
            'user_id' => $user?->id,
            'query' => $query,
            'module' => $module,
            'filters' => ! empty($filters) ? $filters : null,
            'results_count' => $resultsCount,
            'results_meta' => $executionTime ? ['execution_time_ms' => $executionTime] : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'searched_at' => now(),
        ]);
    }
}
