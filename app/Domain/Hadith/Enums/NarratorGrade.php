<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Enums;

/**
 * Narrator Reliability Grade Enum
 * Represents the trustworthiness of a hadith narrator
 */
enum NarratorGrade: string
{
    case THIQAH = 'thiqah';     // Trustworthy
    case SADUQ = 'saduq';       // Truthful
    case DAIF = 'daif';         // Weak
    case MATRUK = 'matruk';     // Abandoned/Rejected
    case UNKNOWN = 'unknown';    // Unknown

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
     */
    public function label(): string
    {
        return match ($this) {
            self::THIQAH => 'Thiqah (Trustworthy)',
            self::SADUQ => 'Saduq (Truthful)',
            self::DAIF => 'Daif (Weak)',
            self::MATRUK => 'Matruk (Abandoned)',
            self::UNKNOWN => 'Unknown',
        };
    }

    /**
     * Get Arabic label
     */
    public function labelArabic(): string
    {
        return match ($this) {
            self::THIQAH => 'ثقة',
            self::SADUQ => 'صدوق',
            self::DAIF => 'ضعيف',
            self::MATRUK => 'متروك',
            self::UNKNOWN => 'مجهول',
        };
    }
}
