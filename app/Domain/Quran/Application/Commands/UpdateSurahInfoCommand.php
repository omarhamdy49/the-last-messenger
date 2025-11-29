<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to update Surah info (asbab al-nuzul)
 */
final readonly class UpdateSurahInfoCommand
{
    public function __construct(
        public int $id,
        public ?string $lang = null,
        public ?string $description = null,
        public ?string $content = null,
        public ?string $source = null,
        public ?bool $isActive = null,
    ) {}
}
