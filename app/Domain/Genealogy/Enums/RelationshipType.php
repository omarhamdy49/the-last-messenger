<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Enums;

/**
 * Relationship Type Enum
 * Types of family relationships
 */
enum RelationshipType: string
{
    case FATHER = 'father';
    case MOTHER = 'mother';
    case SON = 'son';
    case DAUGHTER = 'daughter';
    case BROTHER = 'brother';
    case SISTER = 'sister';
    case HUSBAND = 'husband';
    case WIFE = 'wife';
    case GRANDFATHER = 'grandfather';
    case GRANDMOTHER = 'grandmother';
    case GRANDSON = 'grandson';
    case GRANDDAUGHTER = 'granddaughter';
    case UNCLE = 'uncle';
    case AUNT = 'aunt';
    case NEPHEW = 'nephew';
    case NIECE = 'niece';
    case COUSIN = 'cousin';

    /**
     * Get human-readable label
     *
     * @return array<string, string>
     */
    public function label(): array
    {
        return match ($this) {
            self::FATHER => [
                'ar' => 'أب',
                'en' => 'Father',
                'fr' => 'Père',
                'tr' => 'Baba',
                'ur' => 'والد',
            ],
            self::MOTHER => [
                'ar' => 'أم',
                'en' => 'Mother',
                'fr' => 'Mère',
                'tr' => 'Anne',
                'ur' => 'والدہ',
            ],
            self::SON => [
                'ar' => 'ابن',
                'en' => 'Son',
                'fr' => 'Fils',
                'tr' => 'Oğul',
                'ur' => 'بیٹا',
            ],
            self::DAUGHTER => [
                'ar' => 'ابنة',
                'en' => 'Daughter',
                'fr' => 'Fille',
                'tr' => 'Kız',
                'ur' => 'بیٹی',
            ],
            self::BROTHER => [
                'ar' => 'أخ',
                'en' => 'Brother',
                'fr' => 'Frère',
                'tr' => 'Erkek Kardeş',
                'ur' => 'بھائی',
            ],
            self::SISTER => [
                'ar' => 'أخت',
                'en' => 'Sister',
                'fr' => 'Sœur',
                'tr' => 'Kız Kardeş',
                'ur' => 'بہن',
            ],
            self::HUSBAND => [
                'ar' => 'زوج',
                'en' => 'Husband',
                'fr' => 'Mari',
                'tr' => 'Koca',
                'ur' => 'شوہر',
            ],
            self::WIFE => [
                'ar' => 'زوجة',
                'en' => 'Wife',
                'fr' => 'Épouse',
                'tr' => 'Eş',
                'ur' => 'بیوی',
            ],
            self::GRANDFATHER => [
                'ar' => 'جد',
                'en' => 'Grandfather',
                'fr' => 'Grand-père',
                'tr' => 'Büyükbaba',
                'ur' => 'دادا',
            ],
            self::GRANDMOTHER => [
                'ar' => 'جدة',
                'en' => 'Grandmother',
                'fr' => 'Grand-mère',
                'tr' => 'Büyükanne',
                'ur' => 'دادی',
            ],
            self::GRANDSON => [
                'ar' => 'حفيد',
                'en' => 'Grandson',
                'fr' => 'Petit-fils',
                'tr' => 'Torun (Erkek)',
                'ur' => 'پوتا',
            ],
            self::GRANDDAUGHTER => [
                'ar' => 'حفيدة',
                'en' => 'Granddaughter',
                'fr' => 'Petite-fille',
                'tr' => 'Torun (Kız)',
                'ur' => 'پوتی',
            ],
            self::UNCLE => [
                'ar' => 'عم',
                'en' => 'Uncle',
                'fr' => 'Oncle',
                'tr' => 'Amca',
                'ur' => 'چچا',
            ],
            self::AUNT => [
                'ar' => 'عمة',
                'en' => 'Aunt',
                'fr' => 'Tante',
                'tr' => 'Teyze',
                'ur' => 'پھوپھی',
            ],
            self::NEPHEW => [
                'ar' => 'ابن الأخ',
                'en' => 'Nephew',
                'fr' => 'Neveu',
                'tr' => 'Yeğen (Erkek)',
                'ur' => 'بھتیجا',
            ],
            self::NIECE => [
                'ar' => 'ابنة الأخ',
                'en' => 'Niece',
                'fr' => 'Nièce',
                'tr' => 'Yeğen (Kız)',
                'ur' => 'بھتیجی',
            ],
            self::COUSIN => [
                'ar' => 'ابن العم',
                'en' => 'Cousin',
                'fr' => 'Cousin',
                'tr' => 'Kuzen',
                'ur' => 'چچا زاد',
            ],
        };
    }

    /**
     * Get all types as array
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
