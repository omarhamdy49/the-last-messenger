<?php

declare(strict_types=1);

namespace App\Domain\Blog\Enums;

/**
 * Category Type Enum
 * Defines the type of content the category is for
 */
enum CategoryType: string
{
    case BLOG = 'blog';
    case MEDIA = 'media';
    case TIMELINE = 'timeline';

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
            self::BLOG => 'Blog/Seerah',
            self::MEDIA => 'Media/Programs',
            self::TIMELINE => 'Timeline Events',
        };
    }
}
