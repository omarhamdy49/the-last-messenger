<?php

declare(strict_types=1);

namespace App\Domain\Hadith\DTOs;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Models\Hadith;

/**
 * One hadith in full: every populated locale, every grading claim, and the three
 * reference lines. The shape matches contracts/hadith-api.md §8.
 *
 * `texts` contains only locales that HAVE text, so an Arabic-only hadith has one
 * entry rather than an entry with an empty body.
 *
 * `collectionName` and `bookTitle` are the display strings the home-page card and
 * the detail page both need; carrying them here keeps that mapping out of the
 * controllers.
 */
final readonly class HadithDetail
{
    /**
     * @param  list<array<string, mixed>>  $texts
     * @param  list<array<string, mixed>>  $gradings
     * @param  array<string, mixed>  $gradingContext
     * @param  array{slug: string, name: string}|null  $primaryNarrator
     */
    public function __construct(
        public int $id,
        public string $collectionCode,
        public ?string $collectionName,
        public ?string $bookNumber,
        public ?string $bookTitle,
        public ?string $chapterNumber,
        public string $hadithNumber,
        public ?int $inBookNumber,
        public ?HadithGrade $displayGrade,
        public ReferenceSet $reference,
        public ?array $primaryNarrator,
        public array $texts,
        public array $gradings,
        public array $gradingContext,
    ) {}

    /**
     * Build from a loaded model. Callers must have eager-loaded `collection`,
     * `book`, `chapter`, `texts` and `gradings.scholar` — this method must never
     * trigger a query, or every detail render becomes an N+1.
     */
    public static function fromModel(Hadith $hadith, ReferenceSet $reference, string $locale): self
    {
        return new self(
            id: $hadith->id,
            collectionCode: (string) $hadith->collection->code,
            collectionName: $hadith->collection->getTranslation('name', $locale, true) ?: null,
            bookNumber: $hadith->book?->book_number,
            bookTitle: $hadith->book?->getTranslation('title', $locale, true) ?: null,
            chapterNumber: $hadith->chapter?->chapter_number,
            hadithNumber: (string) $hadith->hadith_number,
            inBookNumber: $hadith->in_book_number,
            displayGrade: $hadith->display_grade,
            reference: $reference,
            primaryNarrator: $hadith->primaryNarrator === null ? null : [
                'slug' => $hadith->primaryNarrator->slug,
                'name' => $hadith->primaryNarrator->getTranslation('name', $locale, true),
            ],
            texts: $hadith->texts->map(static fn ($text): array => [
                'locale' => $text->locale,
                'body' => $text->body,
                'narrated_by' => $text->narrated_by,
                'sanad' => $text->sanad,
            ])->all(),
            gradings: $hadith->gradings->map(static fn ($grading): array => [
                'grade' => $grading->grade->value,
                'scope' => $grading->scope->value,
                'part_label' => $grading->part_label,
                'raw_grade' => $grading->raw_grade,
                'scholar' => $grading->scholar?->getTranslation('name', $locale, true) ?: null,
            ])->all(),
            gradingContext: self::gradingContext($hadith),
        );
    }

    /** @return array<string, mixed> */
    private static function gradingContext(Hadith $hadith): array
    {
        $source = is_array($hadith->source_metadata) ? ($hadith->source_metadata['grading_context'] ?? []) : [];
        $source = is_array($source) ? $source : [];

        return [
            'per_hadith_status' => $hadith->gradings->isNotEmpty()
                ? 'explicit'
                : ($source['per_hadith_status'] ?? 'not_recorded'),
            'collection_classification' => $source['collection_classification'] ?? null,
            'source_note' => $source['source_note'] ?? null,
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'collection' => $this->collectionCode,
            'collection_name' => $this->collectionName,
            'book_number' => $this->bookNumber,
            'book_title' => $this->bookTitle,
            'chapter_number' => $this->chapterNumber,
            'hadith_number' => $this->hadithNumber,
            'in_book_number' => $this->inBookNumber,
            'display_grade' => $this->displayGrade?->value,
            'reference' => $this->reference->toArray(),
            'primary_narrator' => $this->primaryNarrator,
            'texts' => $this->texts,
            'gradings' => $this->gradings,
            'grading_context' => $this->gradingContext,
        ];
    }
}
