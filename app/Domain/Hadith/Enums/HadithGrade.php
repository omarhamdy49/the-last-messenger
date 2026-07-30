<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Enums;

/**
 * The five normalised verdicts.
 *
 * Deliberately closed. The previous implementation lowercased each distinct raw
 * source string into a taxonomy row and produced 566 junk entries; the verbatim
 * string is preserved in `hadith_gradings.raw_grade` instead, and anything the
 * normaliser cannot interpret becomes `unknown` and is reported (FR-007).
 */
enum HadithGrade: string
{
    case SAHIH = 'sahih';
    case HASAN = 'hasan';
    case DAIF = 'daif';
    case MAWDU = 'mawdu';
    case UNKNOWN = 'unknown';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return __('hadith.grade_'.$this->value);
    }
}
