<?php

declare(strict_types=1);

namespace App\Domain\Quran\DTOs;

/**
 * Where one verse starts/ends in a chapter audio file (milliseconds).
 */
final readonly class TimestampRowData
{
    /**
     * @param  list<array{0: int, 1: int, 2: int}>|null  $segments  [word, start_ms, end_ms]
     */
    public function __construct(
        public string $verseKey,
        public int $from,
        public int $to,
        public ?array $segments = null,
    ) {}
}
