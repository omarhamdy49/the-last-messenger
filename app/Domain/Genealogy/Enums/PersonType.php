<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Enums;

/**
 * Person Type Enum
 * Types of people in the genealogical tree
 */
enum PersonType: string
{
    case PROPHET = 'prophet';
    case COMPANION_MALE = 'companion_male';
    case COMPANION_FEMALE = 'companion_female';
    case FAMILY = 'family';
    case TRIBE_LEADER = 'tribe_leader';
    case HISTORICAL_FIGURE = 'historical_figure';

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
            self::COMPANION_MALE => [
                'ar' => 'صحابي',
                'en' => 'Companion (Male)',
                'fr' => 'Compagnon',
                'tr' => 'Sahabe (Erkek)',
                'ur' => 'صحابی',
            ],
            self::COMPANION_FEMALE => [
                'ar' => 'صحابية',
                'en' => 'Companion (Female)',
                'fr' => 'Compagnonne',
                'tr' => 'Sahabe (Kadın)',
                'ur' => 'صحابیہ',
            ],
            self::FAMILY => [
                'ar' => 'عائلة النبي',
                'en' => 'Prophet\'s Family',
                'fr' => 'Famille du Prophète',
                'tr' => 'Peygamber\'in Ailesi',
                'ur' => 'نبی کا خاندان',
            ],
            self::TRIBE_LEADER => [
                'ar' => 'زعيم قبيلة',
                'en' => 'Tribe Leader',
                'fr' => 'Chef de Tribu',
                'tr' => 'Kabile Lideri',
                'ur' => 'قبیلے کا سردار',
            ],
            self::HISTORICAL_FIGURE => [
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
