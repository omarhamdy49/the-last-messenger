<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

use RuntimeException;

final readonly class SunnahHadithData
{
    /**
     * @param  list<string>  $gradesAr
     * @param  list<string>  $gradesEn
     */
    public function __construct(
        public string $collection,
        public string $bookNumber,
        public string $chapterId,
        public string $number,
        public ?string $bodyAr,
        public ?string $bodyEn,
        public array $gradesAr,
        public array $gradesEn,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromApi(array $payload): self
    {
        $collection = trim((string) ($payload['collection'] ?? ''));
        $bookNumber = trim((string) ($payload['bookNumber'] ?? ''));
        $number = trim((string) ($payload['hadithNumber'] ?? ''));

        if ($collection === '' || $bookNumber === '' || $number === '') {
            throw new RuntimeException('Sunnah API hadith is missing collection, bookNumber, or hadithNumber.');
        }

        $localized = [];
        foreach (($payload['hadith'] ?? []) as $translation) {
            if (! is_array($translation)) {
                continue;
            }

            $language = (string) ($translation['lang'] ?? '');
            if (! in_array($language, ['ar', 'en'], true)) {
                continue;
            }
            if (isset($localized[$language])) {
                throw new RuntimeException("Duplicate {$language} payload for {$collection}:{$number}.");
            }

            $localized[$language] = $translation;
        }

        return new self(
            collection: $collection,
            bookNumber: $bookNumber,
            chapterId: trim((string) ($payload['chapterId'] ?? '')),
            number: $number,
            bodyAr: self::body($localized['ar']['body'] ?? null),
            bodyEn: self::body($localized['en']['body'] ?? null),
            gradesAr: self::grades($localized['ar']['grades'] ?? []),
            gradesEn: self::grades($localized['en']['grades'] ?? []),
        );
    }

    private static function body(mixed $body): ?string
    {
        if (! is_string($body) || trim($body) === '') {
            return null;
        }

        $text = preg_replace('/<br\s*\/?>|<\/p>/iu', "\n", $body) ?? $body;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+\n/u', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /** @return list<string> */
    private static function grades(mixed $claims): array
    {
        if (! is_array($claims)) {
            return [];
        }

        $grades = [];
        foreach ($claims as $claim) {
            if (! is_array($claim)) {
                continue;
            }

            $grade = trim((string) ($claim['grade'] ?? ''));
            if ($grade === '') {
                continue;
            }

            $grader = trim((string) ($claim['graded_by'] ?? ''));
            $grades[] = $grader === '' ? $grade : "{$grade} ({$grader})";
        }

        return array_values(array_unique($grades));
    }
}
