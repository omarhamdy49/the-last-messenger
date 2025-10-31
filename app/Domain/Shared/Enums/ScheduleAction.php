<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum ScheduleAction: string
{
    case PUBLISH = 'publish';
    case UNPUBLISH = 'unpublish';
    case UPDATE = 'update';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PUBLISH => 'نشر',
            self::UNPUBLISH => 'إلغاء النشر',
            self::UPDATE => 'تحديث',
        };
    }

    /**
     * Get icon for action
     */
    public function icon(): string
    {
        return match ($this) {
            self::PUBLISH => 'eye',
            self::UNPUBLISH => 'eye-off',
            self::UPDATE => 'edit',
        };
    }

    /**
     * Get color class for action badge
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::PUBLISH => 'bg-green-100 text-green-800',
            self::UNPUBLISH => 'bg-orange-100 text-orange-800',
            self::UPDATE => 'bg-blue-100 text-blue-800',
        };
    }

    /**
     * Check if action requires data
     */
    public function requiresData(): bool
    {
        return $this === self::UPDATE;
    }
}
