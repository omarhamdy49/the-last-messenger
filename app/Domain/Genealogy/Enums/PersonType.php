<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Enums;

/**
 * Person Type Enum
 * Types of people in the genealogical tree. Gender is deliberately not
 * encoded here — `sahaba.gender` already exists as its own column, so
 * "companion_male"/"companion_female" would be pure duplication.
 */
enum PersonType: string
{
    case PROPHET = 'prophet';
    case SAHABI = 'sahabi';
    case FAMILY = 'family';
    case TRIBE = 'tribe';
    case HISTORICAL = 'historical';

    /**
     * Get human-readable label
     *
     * @return array<string, string>
     */
    public function label(): array
    {
        return match ($this) {
            self::PROPHET => [
                'ar' => 'نبي',
                'en' => 'Prophet',
                'fr' => 'Prophète',
                'tr' => 'Peygamber',
                'ur' => 'نبی',
            ],
            self::SAHABI => [
                'ar' => 'صحابي',
                'en' => 'Companion',
                'fr' => 'Compagnon',
                'tr' => 'Sahabe',
                'ur' => 'صحابی',
            ],
            self::FAMILY => [
                'ar' => 'عائلة النبي',
                'en' => 'Prophet\'s Family',
                'fr' => 'Famille du Prophète',
                'tr' => 'Peygamber\'in Ailesi',
                'ur' => 'نبی کا خاندان',
            ],
            self::TRIBE => [
                'ar' => 'زعيم قبيلة',
                'en' => 'Tribe Leader',
                'fr' => 'Chef de Tribu',
                'tr' => 'Kabile Lideri',
                'ur' => 'قبیلے کا سردار',
            ],
            self::HISTORICAL => [
                'ar' => 'شخصية تاريخية',
                'en' => 'Historical Figure',
                'fr' => 'Figure Historique',
                'tr' => 'Tarihi Şahsiyet',
                'ur' => 'تاریخی شخصیت',
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
