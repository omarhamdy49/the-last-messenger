<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to update an existing Quran reciter
 */
final readonly class UpdateReciterCommand
{
    /**
     * @param array<string, string>|null $name
     * @param array<string, string>|null $description
     */
    public function __construct(
        public int $id,
        public ?array $name = null,
        public ?string $slug = null,
        public ?array $description = null,
        public ?string $style = null,
        public ?string $country = null,
        public ?string $profileImage = null,
        public ?bool $isFeatured = null,
        public ?bool $isActive = null,
        public ?int $displayOrder = null,
    ) {}
}
