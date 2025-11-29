<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Commands;

use App\Domain\Shared\Application\Commands\Command;

/**
 * Command to update an existing Hadith Collection
 */
final readonly class UpdateCollectionCommand implements Command
{
    /**
     * @param int $collectionId The collection ID to update
     * @param array<string, string>|null $name Collection name translations
     * @param string|null $slug URL-friendly slug
     * @param array<string, string>|null $description Description translations
     * @param int|null $scholarId Author/compiler scholar ID
     * @param int|null $hadithCount Total hadith count
     * @param bool|null $isActive Whether collection is active
     */
    public function __construct(
        public int $collectionId,
        public ?array $name = null,
        public ?string $slug = null,
        public ?array $description = null,
        public ?int $scholarId = null,
        public ?int $hadithCount = null,
        public ?bool $isActive = null,
    ) {}
}
