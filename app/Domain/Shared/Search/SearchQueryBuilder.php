<?php

declare(strict_types=1);

namespace App\Domain\Shared\Search;

use App\Domain\Shared\Builders\FilterBuilder;
use App\Domain\Shared\Enums\FilterOperator;
use App\Domain\Shared\Enums\FilterType;
use App\Domain\Shared\Enums\SearchModule;
use App\Domain\Shared\Enums\SortDirection;
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
     * Apply the appropriate full-text search strategy to an existing Builder.
     *
     * If the model exposes getFullTextColumns(), MySQL MATCH/AGAINST is used;
     * otherwise falls back to OR-LIKE across searchable fields.
     *
     * @param  Builder  $query  Eloquent query to mutate
     * @param  array<string>  $fields  Searchable field list from SearchModule
     * @param  string  $modelClass  Fully-qualified model class name
     * @param  string  $searchTerm  Raw search term
     */
    public function applySearch(
        Builder $query,
        array $fields,
        string $modelClass,
        string $searchTerm
    ): void {
        if ($searchTerm === '' || empty($fields)) {
            return;
        }

        if (method_exists($modelClass, 'getFullTextColumns')) {
            $this->applyMySQLFullText($query, $modelClass::getFullTextColumns(), $searchTerm);
        } else {
            $this->applyBasicSearch($query, $fields, $searchTerm);
        }
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
     * @param  string  $sort  Sort alias or raw column name
     */
    public function applySorting(FilterBuilder $builder, string $sort, SortDirection $direction): void
    {
        $sortField = match ($sort) {
            'relevance' => 'id',
            'date', 'newest' => 'created_at',
            'oldest' => 'created_at',
            'popular' => 'views_count',
            'updated' => 'updated_at',
            default => $sort,
        };

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
     * Add a MySQL MATCH … AGAINST full-text clause.
     *
     * @param  array<string>  $columns  Columns that carry a FULLTEXT index
     */
    private function applyMySQLFullText(Builder $query, array $columns, string $searchTerm): void
    {
        $columnsString = implode(',', $columns);
        $query->whereRaw(
            "MATCH({$columnsString}) AGAINST(? IN NATURAL LANGUAGE MODE)",
            [$searchTerm]
        );
    }

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
                if (str_contains($field, '->')) {
                    $q->orWhereRaw(
                        "JSON_UNQUOTE(JSON_EXTRACT({$field})) LIKE ?",
                        ["%{$searchTerm}%"]
                    );
                } elseif (str_contains($field, '.')) {
                    [$relation, $column] = explode('.', $field, 2);
                    $columnTerm = $this->termForColumn($column, $searchTerm);
                    $q->orWhereHas(
                        $relation,
                        fn (Builder $relationQuery) => $relationQuery->where($column, 'LIKE', "%{$columnTerm}%")
                    );
                } else {
                    $columnTerm = $this->termForColumn($field, $searchTerm);
                    $q->orWhere($field, 'LIKE', "%{$columnTerm}%");
                }
            }
        });
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
