<?php

declare(strict_types=1);

namespace App\Domain\Media\Enums;

enum MaturityRating: string
{
    case ALL = 'all';
    case TEEN = 'teen';
    case MATURE = 'mature';

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'All Ages',
            self::TEEN => 'Teen (13+)',
            self::MATURE => 'Mature (18+)',
        };
    }
}
