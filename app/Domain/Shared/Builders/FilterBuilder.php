<?php

declare(strict_types=1);

namespace App\Domain\Shared\Builders;

use App\Domain\Shared\Enums\FilterOperator;
use App\Domain\Shared\Enums\SortDirection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Filter Builder
 * Builds dynamic query filters from array configuration
 */
class FilterBuilder
{
    protected Builder $query;

    /** @var array<array{field: string, operator: FilterOperator, value: mixed}> */
    protected array $filters = [];

    /** @var array<array{field: string, direction: SortDirection}> */
    protected array $sorts = [];

    protected ?int $limit = null;

    protected ?int $offset = null;

    /**
     * Create new filter builder instance
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * Add a filter condition
     *
     * @param  string  $field  Field name
     * @param  FilterOperator|string  $operator  Filter operator
     * @param  mixed  $value  Filter value
     */
    public function addFilter(string $field, FilterOperator|string $operator, mixed $value = null): self
    {
        if (is_string($operator)) {
            $operator = FilterOperator::from($operator);
        }

        // Skip null/empty values for optional filters
        if ($value === null && $operator->requiresValue()) {
            return $this;
        }

        $this->filters[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Add date range filter
     *
     * @param  string  $field  Date field name
     * @param  string|Carbon|null  $from  Start date
     * @param  string|Carbon|null  $to  End date
     */
    public function addDateRange(string $field, string|Carbon|null $from = null, string|Carbon|null $to = null): self
    {
        if ($from !== null) {
            $fromDate = $from instanceof Carbon ? $from : Carbon::parse($from);
            $this->addFilter($field, FilterOperator::GREATER_THAN_OR_EQUAL, $fromDate->startOfDay());
        }

        if ($to !== null) {
            $toDate = $to instanceof Carbon ? $to : Carbon::parse($to);
            $this->addFilter($field, FilterOperator::LESS_THAN_OR_EQUAL, $toDate->endOfDay());
        }

        return $this;
    }

    /**
     * Add number range filter
     *
     * @param  string  $field  Number field name
     * @param  int|float|null  $min  Minimum value
     * @param  int|float|null  $max  Maximum value
     */
    public function addNumberRange(string $field, int|float|null $min = null, int|float|null $max = null): self
    {
        if ($min !== null) {
            $this->addFilter($field, FilterOperator::GREATER_THAN_OR_EQUAL, $min);
        }

        if ($max !== null) {
            $this->addFilter($field, FilterOperator::LESS_THAN_OR_EQUAL, $max);
        }

        return $this;
    }

    /**
     * Add multi-select filter (IN clause)
     *
     * @param  string  $field  Field name
     * @param  array<mixed>  $values  Array of values
     */
    public function addMultiSelect(string $field, array $values): self
    {
        if (empty($values)) {
            return $this;
        }

        $this->addFilter($field, FilterOperator::IN, $values);

        return $this;
    }

    /**
     * Add text search filter
     *
     * @param  string  $field  Field name
     * @param  string  $query  Search query
     * @param  bool  $exact  Exact match or partial match
     */
    public function addTextSearch(string $field, string $query, bool $exact = false): self
    {
        if (empty($query)) {
            return $this;
        }

        $operator = $exact ? FilterOperator::EQUALS : FilterOperator::LIKE;
        $value = $exact ? $query : "%{$query}%";

        $this->addFilter($field, $operator, $value);

        return $this;
    }

    /**
     * Add full-text search across multiple fields
     *
     * @param  array<string>  $fields  Fields to search
     * @param  string  $query  Search query
     */
    public function addFullTextSearch(array $fields, string $query): self
    {
        if (empty($query) || empty($fields)) {
            return $this;
        }

        $this->query->where(function ($q) use ($fields, $query) {
            foreach ($fields as $field) {
                $q->orWhere($field, 'LIKE', "%{$query}%");
            }
        });

        return $this;
    }

    /**
     * Add MySQL full-text search (for indexed columns)
     *
     * @param  array<string>  $columns  Columns with FULLTEXT index
     * @param  string  $query  Search query
     */
    public function addMySQLFullTextSearch(array $columns, string $query): self
    {
        if (empty($query) || empty($columns)) {
            return $this;
        }

        $columnsString = implode(',', $columns);

        $this->query->whereRaw(
            "MATCH({$columnsString}) AGAINST(? IN NATURAL LANGUAGE MODE)",
            [$query]
        );

        return $this;
    }

    /**
     * Add JSON field filter
     *
     * @param  string  $field  JSON field name
     * @param  string  $key  JSON key
     * @param  FilterOperator|string  $operator  Filter operator
     * @param  mixed  $value  Filter value
     */
    public function addJsonFilter(string $field, string $key, FilterOperator|string $operator, mixed $value): self
    {
        if (is_string($operator)) {
            $operator = FilterOperator::from($operator);
        }

        $jsonPath = "{$field}->{$key}";

        $this->filters[] = [
            'field' => $jsonPath,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Add sorting
     *
     * @param  string  $field  Field name
     * @param  SortDirection|string  $direction  Sort direction
     */
    public function addSort(string $field, SortDirection|string $direction = SortDirection::ASC): self
    {
        if (is_string($direction)) {
            $direction = SortDirection::from($direction);
        }

        $this->sorts[] = [
            'field' => $field,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * Set limit
     */
    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Set offset
     */
    public function setOffset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Build and apply all filters to query
     */
    public function build(): Builder
    {
        // Apply filters
        foreach ($this->filters as $filter) {
            $this->applyFilter($filter);
        }

        // Apply sorting
        foreach ($this->sorts as $sort) {
            $this->query->orderBy($sort['field'], $sort['direction']->value);
        }

        // Apply limit and offset
        if ($this->limit !== null) {
            $this->query->limit($this->limit);
        }

        if ($this->offset !== null) {
            $this->query->offset($this->offset);
        }

        return $this->query;
    }

    /**
     * Apply a single filter to the query
     *
     * @param  array{field: string, operator: FilterOperator, value: mixed}  $filter
     */
    protected function applyFilter(array $filter): void
    {
        $field = $filter['field'];
        $operator = $filter['operator'];
        $value = $filter['value'];

        match ($operator) {
            FilterOperator::EQUALS => $this->query->where($field, '=', $value),
            FilterOperator::NOT_EQUALS => $this->query->where($field, '!=', $value),
            FilterOperator::GREATER_THAN => $this->query->where($field, '>', $value),
            FilterOperator::GREATER_THAN_OR_EQUAL => $this->query->where($field, '>=', $value),
            FilterOperator::LESS_THAN => $this->query->where($field, '<', $value),
            FilterOperator::LESS_THAN_OR_EQUAL => $this->query->where($field, '<=', $value),
            FilterOperator::LIKE => $this->query->where($field, 'LIKE', $value),
            FilterOperator::NOT_LIKE => $this->query->where($field, 'NOT LIKE', $value),
            FilterOperator::IN => $this->query->whereIn($field, (array) $value),
            FilterOperator::NOT_IN => $this->query->whereNotIn($field, (array) $value),
            FilterOperator::BETWEEN => $this->query->whereBetween($field, (array) $value),
            FilterOperator::NULL => $this->query->whereNull($field),
            FilterOperator::NOT_NULL => $this->query->whereNotNull($field),
            FilterOperator::CONTAINS => $this->query->where($field, 'LIKE', "%{$value}%"),
            FilterOperator::STARTS_WITH => $this->query->where($field, 'LIKE', "{$value}%"),
            FilterOperator::ENDS_WITH => $this->query->where($field, 'LIKE', "%{$value}"),
            default => null,
        };
    }

    /**
     * Convert filters to array representation
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'filters' => array_map(function ($filter) {
                return [
                    'field' => $filter['field'],
                    'operator' => $filter['operator']->value,
                    'value' => $filter['value'],
                ];
            }, $this->filters),
            'sorts' => array_map(function ($sort) {
                return [
                    'field' => $sort['field'],
                    'direction' => $sort['direction']->value,
                ];
            }, $this->sorts),
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];
    }

    /**
     * Create filter builder from array configuration
     *
     * @param  array<string, mixed>  $config  Filter configuration
     */
    public static function fromArray(Builder $query, array $config): self
    {
        $builder = new self($query);

        // Apply filters
        if (isset($config['filters']) && is_array($config['filters'])) {
            foreach ($config['filters'] as $filter) {
                if (isset($filter['field'], $filter['operator'])) {
                    $builder->addFilter(
                        $filter['field'],
                        FilterOperator::from($filter['operator']),
                        $filter['value'] ?? null
                    );
                }
            }
        }

        // Apply sorts
        if (isset($config['sorts']) && is_array($config['sorts'])) {
            foreach ($config['sorts'] as $sort) {
                if (isset($sort['field'])) {
                    $builder->addSort(
                        $sort['field'],
                        isset($sort['direction']) ? SortDirection::from($sort['direction']) : SortDirection::ASC
                    );
                }
            }
        }

        // Apply pagination
        if (isset($config['limit'])) {
            $builder->setLimit((int) $config['limit']);
        }

        if (isset($config['offset'])) {
            $builder->setOffset((int) $config['offset']);
        }

        return $builder;
    }
}
