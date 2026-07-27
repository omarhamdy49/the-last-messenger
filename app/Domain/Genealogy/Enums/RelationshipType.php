<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Enums;

/**
 * Stored family edges only.
 *
 * Parent, child and grandparent relations are derived from father_id/mother_id
 * and are deliberately not duplicated in the relationships table.
 */
enum RelationshipType: string
{
    case SPOUSE = 'spouse';
    case SIBLING = 'sibling';
    case UNCLE = 'uncle';
    case AUNT = 'aunt';
    case COUSIN = 'cousin';

    public function label(): array
    {
        return match ($this) {
            self::SPOUSE => ['ar' => 'زوج/زوجة', 'en' => 'Spouse'],
            self::SIBLING => ['ar' => 'أخ/أخت', 'en' => 'Sibling'],
            self::UNCLE => ['ar' => 'عم/خال', 'en' => 'Uncle'],
            self::AUNT => ['ar' => 'عمة/خالة', 'en' => 'Aunt'],
            self::COUSIN => ['ar' => 'ابن/ابنة العم', 'en' => 'Cousin'],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
