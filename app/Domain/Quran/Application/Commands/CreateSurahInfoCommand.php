<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to create Surah info (asbab al-nuzul)
 */
final readonly class CreateSurahInfoCommand
{
    public function __construct(
        public int $surahId,
        public string $lang,
        public ?string $description = null,
        public ?string $content = null,
        public ?string $source = null,
        public bool $isActive = true,
    ) {}
}
