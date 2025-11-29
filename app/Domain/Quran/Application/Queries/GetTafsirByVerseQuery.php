<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to get tafsir for a verse
 */
final readonly class GetTafsirByVerseQuery
{
    public function __construct(
        public int $verseId,
        public string $locale = 'ar',
    ) {}
}
