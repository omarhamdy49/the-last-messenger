<?php

declare(strict_types=1);

namespace App\Services\Dtos;

/**
 * A disposable plaintext copy of a media-library original, ready to be
 * re-attached elsewhere (e.g. a model's Spatie media collection).
 */
final readonly class TemporaryMediaCopy
{
    public function __construct(
        public string $path,
        public string $filename,
    ) {}
}
