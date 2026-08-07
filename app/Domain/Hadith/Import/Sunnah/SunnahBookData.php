<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

use RuntimeException;

final readonly class SunnahBookData
{
    /**
     * @param  list<SunnahChapterData>  $chapters
     * @param  list<SunnahHadithData>  $hadiths
     */
    public function __construct(
        public string $number,
        public string $titleAr,
        public string $titleEn,
        public int $declaredHadiths,
        public ?string $firstHadithNumber,
        public ?string $lastHadithNumber,
        public array $chapters,
        public array $hadiths,
    ) {}

    /**
     * @param  array<string, mixed>  $book
     * @param  list<SunnahHadithData>  $hadiths
     * @param  list<SunnahChapterData>  $chapters
     */
    public static function fromApi(array $book, array $hadiths, array $chapters = []): self
    {
        $number = trim((string) ($book['bookNumber'] ?? ''));
        $titles = [];

        foreach (($book['book'] ?? []) as $translation) {
            if (! is_array($translation)) {
                continue;
            }
            $language = (string) ($translation['lang'] ?? '');
            if (in_array($language, ['ar', 'en'], true)) {
                $titles[$language] = trim((string) ($translation['name'] ?? ''));
            }
        }

        if ($number === '' || ($titles['ar'] ?? '') === '' || ($titles['en'] ?? '') === '') {
            throw new RuntimeException('Sunnah API forty book is missing its number or bilingual title.');
        }

        return new self(
            number: $number,
            titleAr: $titles['ar'],
            titleEn: $titles['en'],
            declaredHadiths: (int) ($book['numberOfHadith'] ?? 0),
            firstHadithNumber: self::optionalNumber($book['hadithStartNumber'] ?? null),
            lastHadithNumber: self::optionalNumber($book['hadithEndNumber'] ?? null),
            chapters: $chapters,
            hadiths: $hadiths,
        );
    }

    private static function optionalNumber(mixed $value): ?string
    {
        $number = trim((string) ($value ?? ''));

        return $number !== '' ? $number : null;
    }
}
