<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

use App\Domain\Shared\Support\ArabicNormalizer;
use RuntimeException;

final class SunnahCollectionMapper
{
    public function __construct(
        private readonly ArabicNormalizer $normalizer,
        private readonly SunnahGradeMapper $gradeMapper,
    ) {}

    public function map(
        SunnahCollectionDefinition $definition,
        SunnahCollectionSnapshot $snapshot,
    ): MappedSunnahCollection {
        $this->assertCollection($definition, $snapshot->collection);

        $books = [];
        $seenBooks = [];
        $seenReferences = [];
        $sortOrder = 0;
        $missingArabic = 0;
        $ungradedHadiths = 0;
        $unmappedGrades = 0;

        foreach ($snapshot->books as $bookIndex => $sourceBook) {
            if (isset($seenBooks[$sourceBook->number])) {
                throw new RuntimeException("Duplicate {$definition->source} book {$sourceBook->number}.");
            }
            $seenBooks[$sourceBook->number] = true;
            $result = $this->mapBook($definition, $sourceBook, $bookIndex + 1, $sortOrder, $seenReferences);
            $books[] = $result->book;
            $missingArabic += $result->missingArabic;
            $ungradedHadiths += $result->ungradedHadiths;
            $unmappedGrades += $result->unmappedGrades;
        }

        if ($sortOrder !== $definition->expectedHadiths) {
            throw new RuntimeException(
                "{$definition->source} fetched-count mismatch: expected {$definition->expectedHadiths}, fetched {$sortOrder}."
            );
        }

        return new MappedSunnahCollection(
            source: $definition->source,
            slug: $definition->slug,
            titleAr: $snapshot->collection->titleAr,
            titleEn: $snapshot->collection->titleEn,
            introAr: $snapshot->collection->introAr,
            introEn: $snapshot->collection->introEn,
            referenceTemplate: $definition->referenceTemplate,
            sortOrder: $definition->sortOrder,
            expectedHadiths: $definition->expectedHadiths,
            hasChapters: $definition->hasChapters,
            books: $books,
            missingArabic: $missingArabic,
            ungradedHadiths: $ungradedHadiths,
            unmappedGrades: $unmappedGrades,
        );
    }

    public function assertCollection(
        SunnahCollectionDefinition $definition,
        SunnahCollectionData $collection,
    ): void {
        if ($collection->name !== $definition->source) {
            throw new RuntimeException("Expected {$definition->source}, received {$collection->name}.");
        }
        if ($collection->availableHadiths !== $definition->expectedHadiths) {
            throw new RuntimeException(
                "{$definition->source} available-count mismatch: expected {$definition->expectedHadiths}, API reports "
                ."{$collection->availableHadiths}."
            );
        }
    }

    /** @param array<string, int> $seenReferences */
    public function mapBook(
        SunnahCollectionDefinition $definition,
        SunnahBookData $sourceBook,
        int $bookSortOrder,
        int &$hadithSortOrder,
        array &$seenReferences,
    ): MappedSunnahBookResult {
        if (count($sourceBook->hadiths) !== $sourceBook->declaredHadiths) {
            throw new RuntimeException(
                "{$definition->source} book {$sourceBook->number} count mismatch: API declares "
                ."{$sourceBook->declaredHadiths}, fetched ".count($sourceBook->hadiths).'.'
            );
        }

        $chapters = $this->chapters($definition, $sourceBook);
        $chapterIds = array_fill_keys(array_map(
            static fn (MappedSunnahChapter $chapter): string => $chapter->sourceId,
            $chapters,
        ), true);
        $untitledChapterIds = array_fill_keys(array_map(
            static fn (SunnahChapterData $chapter): string => $chapter->sourceId,
            array_filter(
                $sourceBook->chapters,
                static fn (SunnahChapterData $chapter): bool => $chapter->titleAr === '' && $chapter->titleEn === null,
            ),
        ), true);
        $hadiths = [];
        $missingArabic = 0;
        $ungradedHadiths = 0;
        $unmappedGrades = 0;

        foreach ($sourceBook->hadiths as $position => $source) {
            if ($source->collection !== $definition->source || $source->bookNumber !== $sourceBook->number) {
                throw new RuntimeException(
                    "Unexpected source identity for {$definition->source} book {$sourceBook->number}, Hadith {$source->number}."
                );
            }

            $chapterSourceId = $this->chapterSourceId($definition, $source, $chapterIds, $untitledChapterIds);
            $baseReference = $this->keyPart($sourceBook->number).'-'.$this->keyPart($source->number);
            $occurrence = ($seenReferences[$baseReference] ?? 0) + 1;
            $seenReferences[$baseReference] = $occurrence;
            $referenceKey = $occurrence === 1 ? $baseReference : "{$baseReference}-{$occurrence}";
            [$grade, $rawGrade, $unmapped] = $this->gradeMapper->map($source);

            $hadithSortOrder++;
            $missingArabic += $source->bodyAr === null ? 1 : 0;
            $ungradedHadiths += $grade === null ? 1 : 0;
            $unmappedGrades += $unmapped;
            $inBookNumber = (string) ($position + 1);
            $hadiths[] = new MappedHadithData(
                referenceKey: $referenceKey,
                number: $source->number,
                sortOrder: $hadithSortOrder,
                grade: $grade,
                rawGrade: $rawGrade,
                textAr: $source->bodyAr,
                textEn: $source->bodyEn,
                searchAr: $this->normalizer->normalize($source->bodyAr),
                searchEn: $this->normalizer->normalize($source->bodyEn),
                canonicalReference: $this->reference($definition->referenceTemplate, $sourceBook->number, $source->number),
                bookNumber: $sourceBook->number,
                chapterSourceId: $chapterSourceId,
                inBookNumber: $inBookNumber,
                inBookReference: "Book {$sourceBook->number}, Hadith {$inBookNumber}",
            );
        }

        return new MappedSunnahBookResult(
            book: new MappedSunnahBook(
                number: $sourceBook->number,
                titleAr: $sourceBook->titleAr,
                titleEn: $sourceBook->titleEn,
                firstHadithNumber: $sourceBook->firstHadithNumber,
                lastHadithNumber: $sourceBook->lastHadithNumber,
                sortOrder: $bookSortOrder,
                chapters: $chapters,
                hadiths: $hadiths,
            ),
            missingArabic: $missingArabic,
            ungradedHadiths: $ungradedHadiths,
            unmappedGrades: $unmappedGrades,
        );
    }

    /** @return list<MappedSunnahChapter> */
    private function chapters(SunnahCollectionDefinition $definition, SunnahBookData $book): array
    {
        if (! $definition->hasChapters) {
            if ($book->chapters !== []) {
                throw new RuntimeException("{$definition->source} unexpectedly returned chapters.");
            }

            return [];
        }

        $mapped = [];
        $seen = [];
        foreach ($book->chapters as $index => $chapter) {
            if (isset($seen[$chapter->sourceId])) {
                throw new RuntimeException(
                    "Duplicate {$definition->source} book {$book->number} chapter identity {$chapter->sourceId}."
                );
            }
            $seen[$chapter->sourceId] = true;
            if ($chapter->titleAr === '' && $chapter->titleEn === null) {
                continue;
            }
            $mapped[] = new MappedSunnahChapter(
                sourceId: $chapter->sourceId,
                number: $chapter->number,
                titleAr: $chapter->titleAr,
                titleEn: $chapter->titleEn,
                introAr: $chapter->introAr,
                introEn: $chapter->introEn,
                endingAr: $chapter->endingAr,
                endingEn: $chapter->endingEn,
                sortOrder: count($mapped) + 1,
            );
        }

        return $mapped;
    }

    /**
     * @param  array<string, bool>  $chapterIds
     * @param  array<string, bool>  $untitledChapterIds
     */
    private function chapterSourceId(
        SunnahCollectionDefinition $definition,
        SunnahHadithData $hadith,
        array $chapterIds,
        array $untitledChapterIds,
    ): ?string {
        if (! $definition->hasChapters || $hadith->chapterId === '' || $hadith->chapterId === '0.00') {
            return null;
        }
        if (isset($untitledChapterIds[$hadith->chapterId])) {
            return null;
        }
        if (! isset($chapterIds[$hadith->chapterId])) {
            throw new RuntimeException(
                "{$definition->source} Hadith {$hadith->number} references missing chapter {$hadith->chapterId}."
            );
        }

        return $hadith->chapterId;
    }

    private function keyPart(string $value): string
    {
        $part = preg_replace('/[^A-Za-z0-9._-]+/u', '-', trim($value)) ?? '';
        $part = trim($part, '-');
        if ($part === '') {
            throw new RuntimeException("Cannot derive a reference key from '{$value}'.");
        }

        return mb_strtolower($part, 'UTF-8');
    }

    private function reference(string $template, string $bookNumber, string $hadithNumber): string
    {
        return str_replace(
            ['{bookNumber}', '{hadithNumber}'],
            [$bookNumber, $hadithNumber],
            $template,
        );
    }
}
