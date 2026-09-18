<?php

declare(strict_types=1);

namespace App\Domain\Quran\DTOs;

use App\Domain\Quran\Enums\RecitationStyle;

/**
 * Descriptive reciter fields a source provides ("Fetch data").
 */
final readonly class ReciterData
{
    /**
     * @param  array<string, string>  $names  locale => name, only locales the source really has
     */
    public function __construct(
        public string $name,
        public array $names,
        public ?RecitationStyle $style,
        public ?string $qirat,
        public ?string $info = null,
        /** Public-disk path or absolute URL; null leaves the current image alone. */
        public ?string $image = null,
    ) {}
}
