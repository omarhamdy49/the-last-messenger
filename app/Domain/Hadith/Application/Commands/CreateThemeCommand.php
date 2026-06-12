<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Commands;

use App\Domain\Shared\Application\Commands\Command;

/**
 * Command to create a new Hadith Theme
 */
final readonly class CreateThemeCommand implements Command
{
    /**
     * @param  array<string, string>  $name  Theme name translations
     * @param  array<string, string>  $slug  Slug translations
     * @param  array<string, string>  $description  Description translations
     * @param  int|null  $parentId  Parent theme ID for hierarchical themes
     * @param  int  $sortOrder  Display order
     */
    public function __construct(
        public array $name,
        public array $slug,
        public array $description = [],
        public ?int $parentId = null,
        public int $sortOrder = 0,
    ) {}
}
