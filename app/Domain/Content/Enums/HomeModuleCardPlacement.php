<?php

declare(strict_types=1);

namespace App\Domain\Content\Enums;

enum HomeModuleCardPlacement: string
{
    case HERO = 'hero';
    case EXPLORE = 'explore';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
