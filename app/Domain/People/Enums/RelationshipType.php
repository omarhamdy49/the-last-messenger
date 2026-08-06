<?php

declare(strict_types=1);

namespace App\Domain\People\Enums;

/**
 * Non-descent relationships only.
 *
 * Parent, child, and grandparent relations are derived from
 * `muslims.father_id` / `muslims.mother_id` and are deliberately NEVER stored
 * as relationship rows. One father and one mother is enforced by the column
 * itself; expressing that in a table of edges would need a partial unique index
 * MySQL does not have.
 *
 * Every case here is symmetric, so a relationship is stored ONCE, in one
 * direction, and read with `muslim_id = ? OR related_muslim_id = ?`. Storing
 * both directions doubles the rows and lets the pair drift out of sync.
 * `Uncle`/`Aunt` are symmetric in the sense that the reverse edge — nephew or
 * niece — is the same row read from the other side, which is what
 * `reverseLabel()` supplies.
 */
enum RelationshipType: string
{
    case Spouse = 'spouse';
    case Sibling = 'sibling';
    case Uncle = 'uncle';
    case Aunt = 'aunt';
    case Cousin = 'cousin';

    public function label(string $locale = 'ar'): string
    {
        return match ($this) {
            self::Spouse => $locale === 'ar' ? 'زوج/زوجة' : 'Spouse',
            self::Sibling => $locale === 'ar' ? 'أخ/أخت' : 'Sibling',
            self::Uncle => $locale === 'ar' ? 'عم/خال' : 'Uncle',
            self::Aunt => $locale === 'ar' ? 'عمة/خالة' : 'Aunt',
            self::Cousin => $locale === 'ar' ? 'ابن/ابنة العم' : 'Cousin',
        };
    }

    /**
     * What the row means when read from `related_muslim_id`'s side.
     * Spouse, sibling, and cousin are their own reverse; uncle and aunt are not.
     */
    public function reverseLabel(string $locale = 'ar'): string
    {
        return match ($this) {
            self::Spouse, self::Sibling, self::Cousin => $this->label($locale),
            self::Uncle, self::Aunt => $locale === 'ar' ? 'ابن/ابنة الأخ أو الأخت' : 'Nephew/Niece',
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
