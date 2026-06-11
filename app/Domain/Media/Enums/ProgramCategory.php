<?php

declare(strict_types=1);

namespace App\Domain\Media\Enums;

enum ProgramCategory: string
{
    case SEERAH = 'seerah';
    case QURAN = 'quran';
    case HADITH = 'hadith';
    case KIDS = 'kids';
    case DOCUMENTARY = 'documentary';

    public function label(): string
    {
        return match ($this) {
            self::SEERAH => 'Seerah',
            self::QURAN => 'Quran',
            self::HADITH => 'Hadith',
            self::KIDS => 'Kids',
            self::DOCUMENTARY => 'Documentary',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::SEERAH => 'السيرة النبوية',
            self::QURAN => 'القرآن الكريم',
            self::HADITH => 'الحديث الشريف',
            self::KIDS => 'أطفال',
            self::DOCUMENTARY => 'وثائقي',
        };
    }
}
