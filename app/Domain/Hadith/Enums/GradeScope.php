<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Enums;

/**
 * What a verdict is about.
 *
 * A verdict on the chain is a different claim from one on the hadith, so
 * "Sanad Da'if wal-Hadith Hasan" is two rows, not one. Only `HADITH`-scope
 * claims feed `display_grade` (data-model.md §4).
 */
enum GradeScope: string
{
    case HADITH = 'hadith';
    case ISNAD = 'isnad';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
