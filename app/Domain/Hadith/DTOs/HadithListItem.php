<?php

declare(strict_types=1);

namespace App\Domain\Hadith\DTOs;

use App\Domain\Hadith\Enums\HadithGrade;

/**
 * A hadith as it appears in a list: one locale's body only, no gradings detail.
 *
 * Carries `snippet` and `score` for search results; both are null elsewhere so
 * list and search rows share one shape.
 */
final readonly class HadithListItem
{
    public function __construct(
        public int $id,
        public string $collectionCode,
        public string $hadithNumber,
        public ?int $inBookNumber,
        public ?HadithGrade $displayGrade,
        public string $locale,
        public string $body,
        public ?string $narratedBy = null,
        public ?string $snippet = null,
        public ?float $score = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'collection' => $this->collectionCode,
            'hadith_number' => $this->hadithNumber,
            'in_book_number' => $this->inBookNumber,
            'display_grade' => $this->displayGrade?->value,
            'locale' => $this->locale,
            'body' => $this->body,
            'narrated_by' => $this->narratedBy,
            'snippet' => $this->snippet,
            'score' => $this->score,
        ], static fn ($value): bool => $value !== null);
    }
}
