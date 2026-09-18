<?php

declare(strict_types=1);

namespace App\Domain\Quran\Enums;

/**
 * The external catalogue a reciter resource is fetched from on demand
 * (admin "Fetch data / audio / timestamps" actions).
 */
enum ExternalSource: string
{
    case QF = 'qf';
    case MP3QURAN = 'mp3quran';

    public function label(?string $locale = null): string
    {
        return (string) __('quran.external_sources.'.$this->value, [], $locale);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
