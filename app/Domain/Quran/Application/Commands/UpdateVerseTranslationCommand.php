<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to update a verse translation
 */
final readonly class UpdateVerseTranslationCommand
{
    public function __construct(
        public int $id,
        public ?string $lang = null,
        public ?string $text = null,
        public ?string $translatorName = null,
        public ?bool $isActive = null,
    ) {}
}
