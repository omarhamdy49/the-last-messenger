<?php

declare(strict_types=1);

namespace App\Domain\Content\DTOs;

final readonly class UpdateHomeSectionData
{
    /**
     * @param  array<string, string|null>  $headings
     * @param  array<string, string|null>  $subheadings
     */
    public function __construct(
        public int $id,
        public array $headings,
        public array $subheadings,
    ) {}
}
