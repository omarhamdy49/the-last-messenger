<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Enums;

/**
 * Seerah Event Status Enum
 * Publication status for timeline events (draft = hidden from public, published = visible).
 */
enum SeerahEventStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';

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
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
        };
    }

    /**
     * Get Arabic label
     */
    public function labelArabic(): string
    {
        return match ($this) {
            self::DRAFT => 'مسودة',
            self::PUBLISHED => 'منشور',
        };
    }

    /**
     * Get color token for UI badges
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'green',
        };
    }

    /**
     * Get icon for UI
     */
    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'file-edit',
            self::PUBLISHED => 'check-circle',
        };
    }
}
