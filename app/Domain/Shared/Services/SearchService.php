<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Builders\FilterBuilder;
use App\Domain\Shared\Enums\SearchModule;
use App\Domain\Shared\Enums\SortDirection;
use App\Domain\Shared\Models\SearchHistory;
use App\Domain\Shared\Models\SearchPreference;
use App\Domain\Shared\Models\SavedFilter;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Search Service
 * Unified search service across all content modules
 */
class SearchService
{
    protected int $defaultPerPage = 15;

    protected int $suggestionLimit = 10;

    protected int $historyLimit = 20;

    protected int $cacheTTL = 300; // 5 minutes

    /**
     * Perform unified search across modules
     *
     * @param  string  $query  Search query
     * @param  array<string, mixed>  $filters  Additional filters
     * @param  array<string, mixed>  $options  Search options (sort, per_page, etc.)
     * @return array<string, mixed>  Search results grouped by module
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

        $totalResults = array_sum(array_column($results, 'total'));
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
            'total_results' => $totalResults,
            'execution_time_ms' => $executionTime,
            'filters_applied' => !empty($filters),
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
        $modelClass = $module->modelClass();

        if ($modelClass === null) {
            throw new \InvalidArgumentException("Invalid module type: {$moduleType}");
        }

        // Build base query
        $queryBuilder = $modelClass::query();

        // Apply full-text search on searchable fields
        $searchableFields = $module->searchableFields();
        if (!empty($query) && !empty($searchableFields)) {
            // Check if model has generated columns for full-text search
            $hasFullText = method_exists($modelClass, 'getFullTextColumns');

            if ($hasFullText) {
                $fullTextColumns = $modelClass::getFullTextColumns();
                $this->applyMySQLFullText($queryBuilder, $fullTextColumns, $query);
            } else {
                $this->applyBasicSearch($queryBuilder, $searchableFields, $query);
            }
        }

        // Build and apply filters
        $filterBuilder = new FilterBuilder($queryBuilder);
        $this->applyFilters($filterBuilder, $filters, $module);

        // Apply sorting
        $sort = $options['sort'] ?? 'relevance';
        $order = $options['order'] ?? 'desc';
        $this->applySorting($filterBuilder, $sort, SortDirection::from($order), $module);

        // Build query
        $queryBuilder = $filterBuilder->build();

        // Apply eager loading if model has relationships
        $relations = $options['with'] ?? $this->getDefaultRelations($module);
        if (!empty($relations)) {
            $queryBuilder->with($relations);
        }

        // Get per page from options or user preference
        $perPage = $this->getPerPage($options);

        // Execute and paginate
        $results = $queryBuilder->paginate($perPage);

        // Save search history
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
     * Get search suggestions based on partial query
     *
     * @param  string  $query  Partial search query
     * @param  string|null  $module  Module to get suggestions for
     * @return Collection<int, string>  Suggested queries
     */
    public function getSuggestions(string $query, ?string $module = null): Collection
    {
        $cacheKey = "search:suggestions:{$module}:" . md5($query);

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($query, $module) {
            $queryBuilder = SearchHistory::query()
                ->select('query')
                ->where('query', 'LIKE', "{$query}%")
                ->groupBy('query')
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
            SavedFilter::where('user_id', $userId)
                ->where('module', $module)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        return SavedFilter::create([
            'user_id' => $userId,
            'name' => $name,
            'description' => $options['description'] ?? null,
            'module' => $module,
            'filters' => $filters,
            'sort_options' => $options['sort_options'] ?? null,
            'is_public' => $options['is_public'] ?? false,
            'is_default' => $options['is_default'] ?? false,
        ]);
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

        $query = SavedFilter::where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('usage_count', 'desc');

        if ($module !== null) {
            $query->where('module', $module);
        }

        return $query->get();
    }

    /**
     * Apply saved filter
     *
     * @param  int  $filterId  Saved filter ID
     * @param  array<string, mixed>  $options  Additional options
     */
    public function applySavedFilter(int $filterId, array $options = []): LengthAwarePaginator
    {
        $savedFilter = SavedFilter::findOrFail($filterId);
        $savedFilter->incrementUsage();

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

    /**
     * Save search to history
     *
     * @param  string  $query  Search query
     * @param  string|null  $module  Module type
     * @param  array<string, mixed>  $filters  Applied filters
     * @param  int  $resultsCount  Number of results
     * @param  float|null  $executionTime  Execution time in milliseconds
     * @param  User|null  $user  User who performed search
     */
    protected function saveSearchHistory(
        string $query,
        ?string $module,
        array $filters,
        int $resultsCount,
        ?float $executionTime = null,
        ?User $user = null
    ): void {
        $data = [
            'user_id' => $user?->id,
            'query' => $query,
            'module' => $module,
            'filters' => !empty($filters) ? $filters : null,
            'results_count' => $resultsCount,
            'results_meta' => $executionTime ? ['execution_time_ms' => $executionTime] : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'searched_at' => now(),
        ];

        SearchHistory::create($data);
    }

    /**
     * Apply MySQL full-text search
     *
     * @param  Builder  $query  Query builder
     * @param  array<string>  $columns  Columns with FULLTEXT index
     * @param  string  $searchQuery  Search query
     */
    protected function applyMySQLFullText(Builder $query, array $columns, string $searchQuery): void
    {
        $columnsString = implode(',', $columns);
        $query->whereRaw(
            "MATCH({$columnsString}) AGAINST(? IN NATURAL LANGUAGE MODE)",
            [$searchQuery]
        );
    }

    /**
     * Apply basic LIKE search across fields
     *
     * @param  Builder  $query  Query builder
     * @param  array<string>  $fields  Fields to search
     * @param  string  $searchQuery  Search query
     */
    protected function applyBasicSearch(Builder $query, array $fields, string $searchQuery): void
    {
        $query->where(function ($q) use ($fields, $searchQuery) {
            foreach ($fields as $field) {
                // Handle JSON fields (e.g., title->en)
                if (str_contains($field, '->')) {
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT({$field})) LIKE ?", ["%{$searchQuery}%"]);
                } else {
                    $q->orWhere($field, 'LIKE', "%{$searchQuery}%");
                }
            }
        });
    }

    /**
     * Apply filters using FilterBuilder
     *
     * @param  FilterBuilder  $builder  Filter builder
     * @param  array<string, mixed>  $filters  Filters to apply
     * @param  SearchModule  $module  Module being searched
     */
    protected function applyFilters(FilterBuilder $builder, array $filters, SearchModule $module): void
    {
        $filterableFields = $module->filterableFields();

        foreach ($filters as $field => $value) {
            if (!isset($filterableFields[$field]) || $value === null) {
                continue;
            }

            $filterType = $filterableFields[$field];

            match ($filterType) {
                \App\Domain\Shared\Enums\FilterType::DATE_RANGE => $builder->addDateRange(
                    $field,
                    $value['from'] ?? null,
                    $value['to'] ?? null
                ),
                \App\Domain\Shared\Enums\FilterType::RANGE => $builder->addNumberRange(
                    $field,
                    $value['min'] ?? null,
                    $value['max'] ?? null
                ),
                \App\Domain\Shared\Enums\FilterType::MULTISELECT => $builder->addMultiSelect($field, (array) $value),
                \App\Domain\Shared\Enums\FilterType::TEXT => $builder->addTextSearch($field, (string) $value),
                default => $builder->addFilter($field, \App\Domain\Shared\Enums\FilterOperator::EQUALS, $value),
            };
        }
    }

    /**
     * Apply sorting to query
     *
     * @param  FilterBuilder  $builder  Filter builder
     * @param  string  $sort  Sort field
     * @param  SortDirection  $direction  Sort direction
     * @param  SearchModule  $module  Module being searched
     */
    protected function applySorting(
        FilterBuilder $builder,
        string $sort,
        SortDirection $direction,
        SearchModule $module
    ): void {
        // Map common sort aliases
        $sortField = match ($sort) {
            'relevance' => 'id', // Default to ID for relevance
            'date', 'newest' => 'created_at',
            'oldest' => 'created_at',
            'popular' => 'views_count',
            'updated' => 'updated_at',
            default => $sort,
        };

        // Adjust direction for certain sort types
        if ($sort === 'oldest') {
            $direction = SortDirection::ASC;
        }

        $builder->addSort($sortField, $direction);
    }

    /**
     * Get default relations to eager load for a module
     *
     * @param  SearchModule  $module  Module type
     * @return array<string>
     */
    protected function getDefaultRelations(SearchModule $module): array
    {
        return match ($module) {
            SearchModule::ARTICLE => ['author', 'category'],
            SearchModule::HADITH => ['book', 'chapter', 'narrators'],
            SearchModule::MEDIA => ['category', 'episodes'],
            SearchModule::SEERAH => ['participants'],
            SearchModule::SAHABA => [],
            SearchModule::QURAN => ['surah'],
            SearchModule::GENEALOGY => ['relationships'],
            default => [],
        };
    }

    /**
     * Get per page value from options or default
     *
     * @param  array<string, mixed>  $options  Search options
     */
    protected function getPerPage(array $options): int
    {
        $perPage = $options['per_page'] ?? $this->defaultPerPage;

        // Ensure per_page is within reasonable bounds
        return min(max($perPage, 1), 100);
    }
}
