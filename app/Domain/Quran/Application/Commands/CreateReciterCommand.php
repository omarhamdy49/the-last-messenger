<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to create a new Quran reciter
 */
final readonly class CreateReciterCommand
{
    /**
     * @param array<string, string> $name
     * @param array<string, string>|null $description
     */
    public function __construct(
        public array $name,
        public string $slug,
        public ?array $description = null,
        public ?string $style = null,
        public ?string $country = null,
        public ?string $profileImage = null,
        public bool $isFeatured = false,
        public bool $isActive = true,
        public int $displayOrder = 0,
    ) {}
}
