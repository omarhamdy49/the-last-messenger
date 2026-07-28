<?php

declare(strict_types=1);

namespace App\Domain\Content\DTOs;

final readonly class ReorderHomeSectionsData
{
    /**
     * @param  array<int, int>  $displayOrderById
     */
    public function __construct(
        public array $displayOrderById,
    ) {}
}
