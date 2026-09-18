<?php

declare(strict_types=1);

namespace App\Domain\Quran\DTOs;

/**
 * An audio file row: ayah-by-ayah (target = verse key) or whole-chapter
 * (target = chapter number).
 */
final readonly class AudioFileData
{
    /**
     * @param  list<array{0: int, 1: int, 2: int}>|null  $segments
     */
    public function __construct(
        public string|int $target,
        public string $url,
        public ?string $format = 'mp3',
        public ?int $durationMs = null,
        public ?int $fileSize = null,
        public ?array $segments = null,
        public bool $isLocal = false,
    ) {}
}
