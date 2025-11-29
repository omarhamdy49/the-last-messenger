<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Commands;

use App\Domain\Shared\Application\Commands\Command;

/**
 * Command to create a new Hadith Collection
 */
final readonly class CreateCollectionCommand implements Command
{
    /**
     * @param array<string, string> $name Collection name translations
     * @param string $slug URL-friendly slug
     * @param array<string, string> $description Description translations
     * @param int|null $scholarId Author/compiler scholar ID
     * @param int $hadithCount Total hadith count
     * @param bool $isActive Whether collection is active
     */
    public function __construct(
        public array $name,
        public string $slug,
        public array $description = [],
        public ?int $scholarId = null,
        public int $hadithCount = 0,
        public bool $isActive = true,
    ) {}
}
