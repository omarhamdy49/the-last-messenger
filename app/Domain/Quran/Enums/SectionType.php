<?php

declare(strict_types=1);

namespace App\Domain\Quran\Enums;

/**
 * The five structural axes of the mushaf that share one QF shape
 * (`verse_mapping`, first/last verse id, verses count). Pages are mushaf-specific
 * and are modelled separately.
 */
enum SectionType: string
{
    case JUZ = 'juz';
    case HIZB = 'hizb';
    case RUB_EL_HIZB = 'rub_el_hizb';
    case MANZIL = 'manzil';
    case RUKU = 'ruku';

    /**
     * Highest valid number on this axis.
     */
    public function max(): int
    {
        return match ($this) {
            self::JUZ => 30,
            self::HIZB => 60,
            self::RUB_EL_HIZB => 240,
            self::MANZIL => 7,
            self::RUKU => 558,
        };
    }

    /**
     * The quran_verses column holding this axis number.
     */
    public function verseColumn(): string
    {
        return $this->value.'_number';
    }

    /**
     * QF list endpoint path (`juzs`, `hizbs`, `rub_el_hizbs`, `manzils`, `rukus`),
     * which is also the JSON key of the list response.
     */
    public function qfPath(): string
    {
        return $this->value.'s';
    }

    public function label(?string $locale = null): string
    {
        return (string) __('quran.sections.'.$this->value, [], $locale);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
