<?php

declare(strict_types=1);

namespace App\Domain\Shared\Application\DTOs;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Paginated Result DTO
 *
 * Represents paginated data with metadata.
 *
 * @template T
 */
final readonly class PaginatedResult
{
    /**
     * @param  array<T>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $perPage,
        public int $currentPage,
        public int $lastPage,
        public bool $hasMorePages
    ) {}

    /**
     * Create from Laravel paginator
     *
     * @template TItem
     *
     * @param  LengthAwarePaginator<TItem>  $paginator
     * @return PaginatedResult<TItem>
     */
    public static function fromPaginator(LengthAwarePaginator $paginator): self
    {
        return new self(
            items: $paginator->items(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            hasMorePages: $paginator->hasMorePages()
        );
    }

    /**
     * Create from a Laravel paginator while replacing the page items with an
     * already-transformed set (e.g. DTOs mapped from the paginator's models).
     * Pagination metadata still comes from the paginator.
     *
     * @template TItem
     *
     * @param  LengthAwarePaginator<mixed>  $paginator
     * @param  array<TItem>  $items
     * @return PaginatedResult<TItem>
     */
    public static function fromPaginatorWithItems(LengthAwarePaginator $paginator, array $items): self
    {
        return new self(
            items: $items,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            hasMorePages: $paginator->hasMorePages()
        );
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'meta' => [
                'total' => $this->total,
                'per_page' => $this->perPage,
                'current_page' => $this->currentPage,
                'last_page' => $this->lastPage,
                'has_more_pages' => $this->hasMorePages,
            ],
        ];
    }
}
