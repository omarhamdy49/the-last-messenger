<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\DTOs;

use App\Domain\Hadith\Models\Hadith;
use JsonSerializable;

/**
 * Hadith Data Transfer Object
 *
 * Represents Hadith data for API responses.
 */
final readonly class HadithDTO implements JsonSerializable
{
    /**
     * @param  array<string, array{text: string}>  $translations
     * @param  array<string>  $themes
     */
    public function __construct(
        public int $id,
        public string $hadithNumber,
        public string $gradingStatus,
        public bool $gradingAgreement,
        public int $bookId,
        public ?string $bookTitle,
        public ?string $collectionName,
        public array $translations = [],
        public array $themes = [],
        public ?string $significance = null,
        public ?string $historicalContext = null
    ) {}

    /**
     * Create from Hadith model
     */
    public static function fromModel(Hadith $hadith, string $locale = 'ar'): self
    {
        $translations = [];
        foreach ($hadith->translations as $translation) {
            $translations[$translation->language_code] = [
                'text' => $translation->text,
            ];
        }

        $themes = $hadith->themes
            ->map(fn ($theme) => $locale === 'ar' ? $theme->theme_ar : ($theme->theme_en ?? $theme->theme_ar))
            ->filter()
            ->values()
            ->toArray();

        return new self(
            id: $hadith->id,
            hadithNumber: $hadith->hadith_number,
            gradingStatus: $hadith->grading_status ?? 'unknown',
            gradingAgreement: $hadith->grading_agreement ?? false,
            bookId: $hadith->book_id,
            bookTitle: $hadith->book?->getTitleInLocale($locale),
            collectionName: $hadith->book?->collection?->getNameInLocale($locale),
            translations: $translations,
            themes: $themes,
            significance: $locale === 'ar' ? $hadith->significance_ar : $hadith->significance_en,
            historicalContext: $locale === 'ar' ? $hadith->historical_context_ar : $hadith->historical_context_en
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'hadith_number' => $this->hadithNumber,
            'grading_status' => $this->gradingStatus,
            'grading_agreement' => $this->gradingAgreement,
            'book_id' => $this->bookId,
            'book_title' => $this->bookTitle,
            'collection_name' => $this->collectionName,
            'translations' => $this->translations,
            'themes' => $this->themes,
            'significance' => $this->significance,
            'historical_context' => $this->historicalContext,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
