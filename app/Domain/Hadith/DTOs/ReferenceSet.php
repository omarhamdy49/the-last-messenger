<?php

declare(strict_types=1);

namespace App\Domain\Hadith\DTOs;

/**
 * The three scholarly reference lines exactly as the source presents them.
 *
 * Built from the collection's `reference_template` plus the numbers, never
 * stored: storing them would let a corrected book number and a stale reference
 * line disagree.
 *
 * `translationDeprecated` reflects sunnah.com's own note, which appears only for
 * bukhari, muslim and malik.
 */
final readonly class ReferenceSet
{
    public function __construct(
        public string $canonical,
        public ?string $inBook = null,
        public ?string $translation = null,
        public bool $translationDeprecated = false,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'canonical' => $this->canonical,
            'in_book' => $this->inBook,
            'translation' => $this->translation,
            'translation_deprecated' => $this->translationDeprecated,
        ];
    }
}
