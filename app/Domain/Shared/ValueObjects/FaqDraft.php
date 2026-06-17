<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

/**
 * A locale-scoped FAQ draft handed across module boundaries (e.g. when the
 * Quran "Ask AI" admin publishes an inquiry into the Content FAQ module).
 *
 * Only the source locale is populated; the publisher leaves the other languages
 * blank for an admin to fill in the FAQ editor.
 */
final readonly class FaqDraft
{
    public function __construct(
        public string $question,
        public string $answer,
        public string $locale,
        public ?string $category = null,
        public bool $isActive = false,
    ) {}
}
