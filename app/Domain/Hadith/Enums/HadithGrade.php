<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Enums;

/**
 * Hadith Grade Enum
 * Defines authenticity grades for hadiths based on Islamic scholarship
 */
enum HadithGrade: string
{
    case SAHIH = 'sahih';           // Authentic
    case HASAN = 'hasan';           // Good
    case DAIF = 'daif';             // Weak
    case MAWDU = 'mawdu';           // Fabricated
    case UNKNOWN = 'unknown';       // Grade not determined

    /**
     * Get human-readable label in Arabic
     */
    public function labelArabic(): string
    {
        return match ($this) {
            self::SAHIH => 'صحيح',
            self::HASAN => 'حسن',
            self::DAIF => 'ضعيف',
            self::MAWDU => 'موضوع',
            self::UNKNOWN => 'غير محدد',
        };
    }

    /**
     * Get human-readable label in English
     */
    public function labelEnglish(): string
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
     * Get label in specified language
     */
    public function label(string $locale = 'ar'): string
    {
        return match ($locale) {
            'ar', 'ur' => $this->labelArabic(),
            default => $this->labelEnglish(),
        };
    }

    /**
     * Get reliability score (higher is better)
     */
    public function getReliabilityScore(): int
    {
        return match ($this) {
            self::SAHIH => 100,
            self::HASAN => 80,
            self::DAIF => 40,
            self::MAWDU => 0,
            self::UNKNOWN => 50,
        };
    }

    /**
     * Get badge color for UI display
     */
    public function getBadgeColor(): string
    {
        return match ($this) {
            self::SAHIH => 'green',
            self::HASAN => 'blue',
            self::DAIF => 'yellow',
            self::MAWDU => 'red',
            self::UNKNOWN => 'gray',
        };
    }

    /**
     * Get all grade values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if grade is reliable (Sahih or Hasan)
     */
    public function isReliable(): bool
    {
        return in_array($this, [self::SAHIH, self::HASAN]);
    }

    /**
     * Check if grade is weak or fabricated
     */
    public function isUnreliable(): bool
    {
        return in_array($this, [self::DAIF, self::MAWDU]);
    }
}
