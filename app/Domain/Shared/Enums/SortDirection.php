<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Sort Direction Enum
 */
enum SortDirection: string
{
    case ASC = 'asc';
    case DESC = 'desc';

    /**
     * Get direction label
     */
    public function label(): string
    {
        return match ($this) {
            self::ASC => 'Ascending',
            self::DESC => 'Descending',
        };
    }
}
