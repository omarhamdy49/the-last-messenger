<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to sync Quran data from external API
 */
final readonly class SyncQuranDataCommand
{
    public function __construct(
        public bool $syncSurahs = true,
        public bool $syncVerses = true,
        public bool $syncTranslations = true,
        public bool $syncReciters = true,
        public ?string $language = null,
        public ?int $surahId = null,
    ) {}
}
