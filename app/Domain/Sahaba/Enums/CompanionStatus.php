<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Enums;

/**
 * Companion Status Enum
 * Represents the status/rank of a Sahabi (Companion)
 */
enum CompanionStatus: string
{
    case PROPHET_COMPANION = 'prophet_companion';
    case TEN_PROMISED_PARADISE = 'ten_promised_paradise';
    case BADR_PARTICIPANT = 'badr_participant';
    case UHUD_PARTICIPANT = 'uhud_participant';
    case EARLY_MUSLIM = 'early_muslim';
    case MUHAJIR = 'muhajir';
    case ANSAR = 'ansar';
    case TABI = 'tabi';

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
            self::PROPHET_COMPANION => 'Companion of the Prophet',
            self::TEN_PROMISED_PARADISE => 'Ten Promised Paradise',
            self::BADR_PARTICIPANT => 'Participant of Badr',
            self::UHUD_PARTICIPANT => 'Participant of Uhud',
            self::EARLY_MUSLIM => 'Early Muslim',
            self::MUHAJIR => 'Muhajir (Emigrant)',
            self::ANSAR => 'Ansar (Helper)',
            self::TABI => "Tabi'i (Follower)",
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
            self::PROPHET_COMPANION => 'صحابي',
            self::TEN_PROMISED_PARADISE => 'العشرة المبشرون بالجنة',
            self::BADR_PARTICIPANT => 'من أهل بدر',
            self::UHUD_PARTICIPANT => 'من أهل أحد',
            self::EARLY_MUSLIM => 'السابقون الأولون',
            self::MUHAJIR => 'مهاجر',
            self::ANSAR => 'أنصاري',
            self::TABI => 'تابعي',
        };
    }

    /**
     * Get importance rank (higher = more important)
     *
     * @return int
     */
    public function rank(): int
    {
        return match ($this) {
            self::TEN_PROMISED_PARADISE => 10,
            self::BADR_PARTICIPANT => 9,
            self::EARLY_MUSLIM => 8,
            self::MUHAJIR => 7,
            self::UHUD_PARTICIPANT => 6,
            self::ANSAR => 5,
            self::PROPHET_COMPANION => 4,
            self::TABI => 3,
        };
    }
}

