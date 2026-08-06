<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Enums;

/**
 * The single authenticity verdict displayed for a hadith (Decision A).
 *
 * One nullable verdict replaces the old claims table. NULL is a real and
 * common outcome: conflicting claims, isnad-only scope, part-specific scope,
 * and unrecognised wording all project to null rather than guessing, and the
 * verbatim source string is kept in `raw_grade` so the nuance is still
 * readable even though it is no longer queryable.
 */
enum Grade: string
{
    case Sahih = 'sahih';
    case Hasan = 'hasan';
    case Daif = 'daif';
    case Mawdu = 'mawdu';

    public function label(string $locale = 'ar'): string
    {
        return match ($this) {
            self::Sahih => $locale === 'ar' ? 'صحيح' : 'Sahih',
            self::Hasan => $locale === 'ar' ? 'حسن' : 'Hasan',
            self::Daif => $locale === 'ar' ? 'ضعيف' : "Da'if",
            self::Mawdu => $locale === 'ar' ? 'موضوع' : "Mawdu'",
        };
    }

    /** @return array<string, string> */
    public static function options(string $locale = 'ar'): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label($locale);
        }

        return $options;
    }
}
