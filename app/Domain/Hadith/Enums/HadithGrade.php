<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Enums;

/**
 * Hadith Grade Enum
 * Represents the authenticity level of a hadith
 */
enum HadithGrade: string
{
    case SAHIH = 'sahih';      // Authentic
    case HASAN = 'hasan';      // Good
    case DAIF = 'daif';        // Weak
    case MAWDU = 'mawdu';      // Fabricated
    case UNKNOWN = 'unknown';   // Not graded

    /**
     * Get all values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get label for display
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::SAHIH => 'Sahih (Authentic)',
            self::HASAN => 'Hasan (Good)',
            self::DAIF => 'Daif (Weak)',
            self::MAWDU => 'Mawdu (Fabricated)',
            self::UNKNOWN => 'Unknown',
        };
    }

    /**
     * Get Arabic label
     *
     * @return string
     */
    public function labelArabic(): string
    {
        return match ($this) {
            self::SAHIH => 'صحيح',
            self::HASAN => 'حسن',
            self::DAIF => 'ضعيف',
            self::MAWDU => 'موضوع',
            self::UNKNOWN => 'غير مصنف',
        };
    }

    /**
     * Check if hadith is reliable (sahih or hasan)
     *
     * @return bool
     */
    public function isReliable(): bool
    {
        return in_array($this, [self::SAHIH, self::HASAN]);
    }
}

