<?php

declare(strict_types=1);

namespace App\Domain\Shared\Search;

use App\Domain\Shared\Builders\FilterBuilder;
use App\Domain\Shared\Enums\FilterOperator;
use App\Domain\Shared\Enums\FilterType;
use App\Domain\Shared\Enums\SearchModule;
use App\Domain\Shared\Enums\SortDirection;
use App\Domain\Shared\Helpers\FullTextSearchHelper;
use App\Support\Utilities\TextSanitizer;
use Illuminate\Database\Eloquent\Builder;

/**
 * Search Query Builder
 *
 * Responsible for constructing Eloquent queries for the search pipeline:
 * full-text search application, filter mapping, sort resolution, eager-load
 * defaults, and per-page clamping. SearchService delegates all query
 * construction here and works only with the finished Builder / scalar values
 * that this class returns.
 */
class SearchQueryBuilder
{
    /**
     * The only sort values the search pipeline accepts, mapped to real columns.
     *
     * Everything outside this map — a raw column name from a request, or a
     * legacy `sort_options` blob replayed by SearchService::applySavedFilter —
     * falls back to self::SORT_FALLBACK, so user input never reaches orderBy().
     *
     * @var array<string, string>
     */
    private const SORT_ALIASES = [
        'relevance' => 'id',
        'date' => 'created_at',
        'newest' => 'created_at',
        'oldest' => 'created_at',
        'popular' => 'views_count',
        'updated' => 'updated_at',
    ];

    /**
     * Column used when the requested sort is not in the allow-list. `id` is the
     * one column guaranteed to exist on every searchable model.
     */
    private const SORT_FALLBACK = 'id';

    /**
     * Apply the appropriate full-text search strategy to an existing Builder.
     *
     * This used to gate on `method_exists($modelClass, 'getFullTextColumns')` — which NO
     * model implemented, so 100% of unified search took the OR-LIKE path: per request, four
     * modules × (COUNT + SELECT) with correlated `orWhereHas` LIKEs over `article_locale`
     * and `quran_verse_translations` (112k rows in production). One crawler on `/search?q=`
     * could saturate both cores while every FULLTEXT index in the schema sat unused.
     *
     * The gate could never have fired: MATCH() needs an index covering exactly the listed
     * columns and cannot span tables, and for every content module the searchable text is
     * in a per-locale translation table. So the MATCH has to go INSIDE the relationship
     * subquery. SearchModule::fullTextGroups() carries that mapping; anything no index
     * covers stays on LIKE via likeOnlyFields(), so narrowing coverage never silently drops
     * a field from search.
     *
     * @param  Builder  $query  Eloquent query to mutate
     * @param  SearchModule  $module  Module being searched
     * @param  string  $searchTerm  Raw search term
     */
    public function applySearch(
        Builder $query,
        SearchModule $module,
        string $searchTerm
    ): void {
        $searchTerm = trim($searchTerm);
        $fields = $module->searchableFields();

        if ($searchTerm === '' || $fields === []) {
            return;
        }

        $groups = $module->fullTextGroups();

        if ($groups === []) {
            $this->applyBasicSearch($query, $fields, $searchTerm);

            return;
        }

        $likeOnly = $module->likeOnlyFields();

        $query->where(function (Builder $outer) use ($groups, $likeOnly, $searchTerm): void {
            foreach ($groups as $group) {
                $columns = $group['columns'];

                if ($group['relation'] === null) {
                    // Columns on the module's own table.
                    $outer->orWhere(
                        fn (Builder $q) => FullTextSearchHelper::apply($q, $columns, $searchTerm)
                    );

                    continue;
                }

                // Columns on a related (translation) table — MATCH inside the subquery.
                $outer->orWhereHas(
                    $group['relation'],
                    fn (Builder $q) => FullTextSearchHelper::apply($q, $columns, $searchTerm)
                );
            }

            foreach ($likeOnly as $field) {
                $this->applyLikeToField($outer, $field, $searchTerm, boolean: 'or');
            }
        });
    }

    /**
     * Translate the $filters array into FilterBuilder conditions.
     *
     * Only keys that appear in $module->filterableFields() are processed;
     * unknown keys and null values are silently ignored.
     *
     * @param  array<string, mixed>  $filters
     */
    public function applyFilters(FilterBuilder $builder, array $filters, SearchModule $module): void
    {
        $filterableFields = $module->filterableFields();

        foreach ($filters as $field => $value) {
            if (! isset($filterableFields[$field]) || $value === null) {
                continue;
            }

            $filterType = $filterableFields[$field];

            match ($filterType) {
                FilterType::DATE_RANGE => $builder->addDateRange($field, $value['from'] ?? null, $value['to'] ?? null),
                FilterType::RANGE => $builder->addNumberRange($field, $value['min'] ?? null, $value['max'] ?? null),
                FilterType::MULTISELECT => $builder->addMultiSelect($field, (array) $value),
                FilterType::TEXT => $builder->addTextSearch($field, (string) $value),
                default => $builder->addFilter($field, FilterOperator::EQUALS, $value),
            };
        }
    }

    /**
     * Resolve the sort alias and add the ORDER BY clause to FilterBuilder.
     *
     * Translates human-friendly aliases (relevance, date, popular, oldest …)
     * to actual column names, correcting direction for the "oldest" alias.
     *
     * @param  string  $sort  Sort alias; anything unrecognised is discarded
     */
    public function applySorting(FilterBuilder $builder, string $sort, SortDirection $direction): void
    {
        $sortField = self::SORT_ALIASES[$sort] ?? self::SORT_FALLBACK;

        if ($sort === 'oldest') {
            $direction = SortDirection::ASC;
        }

        $builder->addSort($sortField, $direction);
    }

    /**
     * Return the default eager-loaded relationships for a module.
     *
     * @return array<string>
     */
    public function getDefaultRelations(SearchModule $module): array
    {
        return match ($module) {
            SearchModule::ARTICLE => ['locales', 'writer', 'category'],
            // The rebuilt module keeps per-locale bodies in `texts` and reaches
            // its collection directly, not through chapter -> book.
            // `category` is an enum column on media_programs (not a relation);
            // searchable text lives in the translations table.
            SearchModule::MEDIA => ['translations'],
            SearchModule::SEERAH => ['translations'],
            SearchModule::SAHABA => ['roles'],
            SearchModule::QURAN => ['surah', 'translations'],
            default => [],
        };
    }

    /**
     * Clamp a raw per-page value to the range [1, 100].
     *
     * @param  array<string, mixed>  $options
     * @param  int  $default  Fallback when per_page is absent
     */
    public function resolvePerPage(array $options, int $default = 15): int
    {
        $perPage = $options['per_page'] ?? $default;

        return min(max((int) $perPage, 1), 100);
    }

    // -------------------------------------------------------------------------
    // Private search-strategy helpers
    // -------------------------------------------------------------------------

    /**
     * Add OR-LIKE conditions across plain, JSON-path, and related-table fields.
     *
     * Field formats supported:
     *  - "column"            → LIKE on the model's own column
     *  - "relation.column"   → orWhereHas() existence check against a related
     *                          table column (used for per-locale translation
     *                          tables, e.g. "translations.text")
     *  - "json->path"        → JSON_EXTRACT comparison for embedded JSON
     *
     * @param  array<string>  $fields
     */
    private function applyBasicSearch(Builder $query, array $fields, string $searchTerm): void
    {
        $query->where(function (Builder $q) use ($fields, $searchTerm) {
            foreach ($fields as $field) {
                $this->applyLikeToField($q, $field, $searchTerm, boolean: 'or');
            }
        });
    }

    /**
     * Add a single LIKE condition for one searchable field, in any of the three supported
     * shapes. Shared by applyBasicSearch() and by applySearch()'s likeOnlyFields() pass, so
     * a field that no FULLTEXT index covers behaves identically on both paths.
     *
     * @param  'and'|'or'  $boolean
     */
    private function applyLikeToField(
        Builder $query,
        string $field,
        string $searchTerm,
        string $boolean = 'and'
    ): void {
        $or = $boolean === 'or';

        if (str_contains($field, '->')) {
            $sql = "JSON_UNQUOTE(JSON_EXTRACT({$field})) LIKE ?";
            $or
                ? $query->orWhereRaw($sql, ["%{$searchTerm}%"])
                : $query->whereRaw($sql, ["%{$searchTerm}%"]);

            return;
        }

        if (str_contains($field, '.')) {
            [$relation, $column] = explode('.', $field, 2);
            $columnTerm = $this->termForColumn($column, $searchTerm);
            $callback = fn (Builder $relationQuery) => $relationQuery->where($column, 'LIKE', "%{$columnTerm}%");

            $or
                ? $query->orWhereHas($relation, $callback)
                : $query->whereHas($relation, $callback);

            return;
        }

        $columnTerm = $this->termForColumn($field, $searchTerm);

        $or
            ? $query->orWhere($field, 'LIKE', "%{$columnTerm}%")
            : $query->where($field, 'LIKE', "%{$columnTerm}%");
    }

    /**
     * Adapt the raw search term to the column being matched.
     *
     * Columns whose name ends in "notashkeel" store Arabic text that has been
     * stripped of diacritics and normalized (alef/ya/hamza forms collapsed).
     * A user query rarely carries tashkeel or the Quranic wasla alef, so the
     * term must be normalized the same way for a LIKE to match. All other
     * columns receive the term unchanged.
     */
    private function termForColumn(string $column, string $searchTerm): string
    {
        if (str_ends_with($column, 'notashkeel')) {
            return TextSanitizer::normalizeArabicForSearch($searchTerm);
        }

        return $searchTerm;
    }
}
