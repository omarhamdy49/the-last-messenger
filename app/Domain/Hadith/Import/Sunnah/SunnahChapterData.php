<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

use RuntimeException;

final readonly class SunnahChapterData
{
    public function __construct(
        public string $sourceId,
        public string $number,
        public string $titleAr,
        public ?string $titleEn,
        public ?string $introAr,
        public ?string $introEn,
        public ?string $endingAr,
        public ?string $endingEn,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromApi(array $payload): self
    {
        $sourceId = trim((string) ($payload['chapterId'] ?? ''));
        $localized = [];
        foreach (($payload['chapter'] ?? []) as $translation) {
            if (! is_array($translation)) {
                continue;
            }
            $language = (string) ($translation['lang'] ?? '');
            if (in_array($language, ['ar', 'en'], true)) {
                $localized[$language] = $translation;
            }
        }

        $number = trim((string) ($localized['ar']['chapterNumber'] ?? $localized['en']['chapterNumber'] ?? ''));
        $titleAr = trim((string) ($localized['ar']['chapterTitle'] ?? ''));
        $titleEn = self::optional($localized['en']['chapterTitle'] ?? null);
        if ($sourceId === '' || ($number === '' && $titleAr === '' && $titleEn === null)) {
            throw new RuntimeException('Sunnah API chapter is missing its identity or title.');
        }

        return new self(
            sourceId: $sourceId,
            // Sunnah has legitimate decimal subchapters with a structural ID
            // and title but no displayed chapter number (for example 22.10).
            number: $number !== '' ? $number : $sourceId,
            titleAr: $titleAr !== '' ? $titleAr : (string) $titleEn,
            titleEn: $titleEn,
            introAr: self::optional($localized['ar']['intro'] ?? null),
            introEn: self::optional($localized['en']['intro'] ?? null),
            endingAr: self::optional($localized['ar']['ending'] ?? null),
            endingEn: self::optional($localized['en']['ending'] ?? null),
        );
    }

    private static function optional(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
