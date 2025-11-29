<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to import Quran data from JSON file
 */
final readonly class ImportQuranFromJsonCommand
{
    public function __construct(
        public string $filePath,
        public string $type, // 'surahs', 'verses', 'translations', 'tafsir', 'reciters'
        public bool $truncateFirst = false,
    ) {}
}
