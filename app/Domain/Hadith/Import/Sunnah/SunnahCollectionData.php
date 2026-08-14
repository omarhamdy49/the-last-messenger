<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

use RuntimeException;

final readonly class SunnahCollectionData
{
    public function __construct(
        public string $name,
        public string $titleAr,
        public string $titleEn,
        public ?string $introAr,
        public ?string $introEn,
        public int $availableHadiths,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromApi(array $payload): self
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $localized = [];
        foreach (($payload['collection'] ?? []) as $translation) {
            if (! is_array($translation)) {
                continue;
            }
            $language = (string) ($translation['lang'] ?? '');
            if (in_array($language, ['ar', 'en'], true)) {
                $localized[$language] = $translation;
            }
        }

        $titleAr = trim((string) ($localized['ar']['title'] ?? ''));
        $titleEn = trim((string) ($localized['en']['title'] ?? ''));
        if ($name === '' || $titleAr === '' || $titleEn === '') {
            throw new RuntimeException('Sunnah API collection is missing its name or bilingual title.');
        }

        return new self(
            name: $name,
            titleAr: $titleAr,
            titleEn: $titleEn,
            introAr: self::arabicOnly(self::optional($localized['ar']['shortIntro'] ?? null)),
            introEn: self::optional($localized['en']['shortIntro'] ?? null),
            availableHadiths: (int) ($payload['totalAvailableHadith'] ?? 0),
        );
    }

    /**
     * Sunnah.com serves the ENGLISH shortIntro under lang=ar for every
     * collection. Reject mostly-Latin text rather than store it as Arabic.
     * Counted, not just matched — the English text still contains the ﷺ
     * ligature, which lives in the Arabic Unicode block.
     */
    private static function arabicOnly(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return preg_match_all('/\p{Arabic}/u', $value) > preg_match_all('/\p{Latin}/u', $value)
            ? $value
            : null;
    }

    private static function optional(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
