<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

use App\Domain\Shared\Support\ArabicNormalizer;
use RuntimeException;

final class SunnahFortyMapper
{
    /** @var array<string, array{slug: string, reference: string, sort: int, expected: int}> */
    private const BOOKS = [
        '1' => [
            'slug' => 'nawawi40',
            'reference' => 'Hadith {hadithNumber}, 40 Hadith an-Nawawi',
            'sort' => 15,
            'expected' => 42,
        ],
        '2' => [
            'slug' => 'qudsi40',
            'reference' => 'Hadith {hadithNumber}, 40 Hadith Qudsi',
            'sort' => 16,
            'expected' => 40,
        ],
        '3' => [
            'slug' => 'shahwaliullah40',
            'reference' => 'Hadith {hadithNumber}, 40 Hadith Shah Waliullah',
            'sort' => 17,
            'expected' => 40,
        ],
    ];

    public function __construct(
        private readonly ArabicNormalizer $normalizer,
        private readonly SunnahGradeMapper $gradeMapper,
    ) {}

    /**
     * @param  list<SunnahBookData>  $books
     * @return list<MappedFortyCollection>
     */
    public function map(array $books): array
    {
        $byNumber = [];
        foreach ($books as $book) {
            if (isset($byNumber[$book->number])) {
                throw new RuntimeException("Duplicate forty book {$book->number} from Sunnah API.");
            }
            $byNumber[$book->number] = $book;
        }

        $unknown = array_diff(array_keys($byNumber), array_keys(self::BOOKS));
        if ($unknown !== []) {
            throw new RuntimeException('Unexpected forty book(s): '.implode(', ', $unknown).'.');
        }

        $mapped = [];
        foreach (self::BOOKS as $bookNumber => $definition) {
            $book = $byNumber[$bookNumber] ?? null;
            if (! $book instanceof SunnahBookData) {
                throw new RuntimeException("Sunnah API forty book {$bookNumber} is missing.");
            }

            $mapped[] = $this->mapBook($book, $definition);
        }

        return $mapped;
    }

    /** @param array{slug: string, reference: string, sort: int, expected: int} $definition */
    private function mapBook(SunnahBookData $book, array $definition): MappedFortyCollection
    {
        $actual = count($book->hadiths);
        if ($book->declaredHadiths !== $definition['expected'] || $actual !== $definition['expected']) {
            throw new RuntimeException(
                "{$definition['slug']} count mismatch: expected {$definition['expected']}, API declared "
                ."{$book->declaredHadiths}, fetched {$actual}."
            );
        }

        $hadiths = [];
        $seen = [];
        $missingArabic = 0;
        $ungradedHadiths = 0;
        $unmappedGrades = 0;

        foreach ($book->hadiths as $position => $source) {
            if ($source->collection !== 'forty' || $source->bookNumber !== $book->number) {
                throw new RuntimeException("Unexpected source identity for forty book {$book->number}, hadith {$source->number}.");
            }

            $referenceKey = '1-'.$this->keyPart($source->number);
            if (isset($seen[$referenceKey])) {
                throw new RuntimeException("Duplicate reference key {$definition['slug']}:{$referenceKey}.");
            }
            $seen[$referenceKey] = true;

            [$grade, $rawGrade, $unmapped] = $this->gradeMapper->map($source);
            $ungradedHadiths += $grade === null ? 1 : 0;
            $unmappedGrades += $unmapped;
            $missingArabic += $source->bodyAr === null ? 1 : 0;

            $hadiths[] = new MappedHadithData(
                referenceKey: $referenceKey,
                number: $source->number,
                sortOrder: $position + 1,
                grade: $grade,
                rawGrade: $rawGrade,
                textAr: $source->bodyAr,
                textEn: $source->bodyEn,
                searchAr: $this->normalizer->normalize($source->bodyAr),
                searchEn: $this->normalizer->normalize($source->bodyEn),
                canonicalReference: str_replace('{hadithNumber}', $source->number, $definition['reference']),
            );
        }

        return new MappedFortyCollection(
            sourceBookNumber: $book->number,
            slug: $definition['slug'],
            titleAr: $book->titleAr,
            titleEn: $book->titleEn,
            referenceTemplate: $definition['reference'],
            sortOrder: $definition['sort'],
            expectedHadiths: $definition['expected'],
            hadiths: $hadiths,
            missingArabic: $missingArabic,
            ungradedHadiths: $ungradedHadiths,
            unmappedGrades: $unmappedGrades,
        );
    }

    private function keyPart(string $number): string
    {
        $part = preg_replace('/[^A-Za-z0-9._-]+/u', '-', trim($number)) ?? '';
        $part = trim($part, '-');

        if ($part === '') {
            throw new RuntimeException("Cannot derive reference key from hadith number '{$number}'.");
        }

        return $part;
    }
}
