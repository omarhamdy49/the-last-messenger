<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Enums;

/**
 * Historical-rank category a Sahabi can hold (badr, ansar, muhajir, ...).
 * A Sahabi may hold several at once via the `sahabi_category` pivot —
 * this enum is the closed set of valid category keys, not a single-value
 * status. Short, lowercase values match `sahabi_categories.key`.
 */
enum CompanionStatus: string
{
    case TEN_PROMISED = 'ten_promised';
    case BADR = 'badr';
    case UHUD = 'uhud';
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
     */
    public function label(): string
    {
        return match ($this) {
            self::TEN_PROMISED => 'Ten Promised Paradise',
            self::BADR => 'Participant of Badr',
            self::UHUD => 'Participant of Uhud',
            self::EARLY_MUSLIM => 'Early Muslim',
            self::MUHAJIR => 'Muhajir (Emigrant)',
            self::ANSAR => 'Ansar (Helper)',
            self::TABI => "Tabi'i (Follower)",
        };
    }

    /**
     * Get Arabic label
     */
    public function labelArabic(): string
    {
        return match ($this) {
            self::TEN_PROMISED => 'العشرة المبشرون بالجنة',
            self::BADR => 'من أهل بدر',
            self::UHUD => 'من أهل أحد',
            self::EARLY_MUSLIM => 'السابقون الأولون',
            self::MUHAJIR => 'مهاجر',
            self::ANSAR => 'أنصاري',
            self::TABI => 'تابعي',
        };
    }

    /**
     * Get importance rank (higher = more important)
     */
    public function rank(): int
    {
        return match ($this) {
            self::TEN_PROMISED => 10,
            self::BADR => 9,
            self::EARLY_MUSLIM => 8,
            self::MUHAJIR => 7,
            self::UHUD => 6,
            self::ANSAR => 5,
            self::TABI => 3,
        };
    }
}
