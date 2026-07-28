<?php

declare(strict_types=1);

namespace App\Domain\Content\DTOs;

final readonly class HomeSectionData
{
    /**
     * @param  array<string, string|null>  $headings
     * @param  array<string, string|null>  $subheadings
     */
    public function __construct(
        public int $id,
        public string $key,
        public bool $isActive,
        public int $displayOrder,
        public ?string $heading,
        public ?string $subheading,
        public array $headings,
        public array $subheadings,
    ) {}
}
