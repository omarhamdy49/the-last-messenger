<?php

declare(strict_types=1);

namespace App\Domain\Blog\Enums;

/**
 * Article Status Enum
 * Represents the publication status of an article
 */
enum ArticleStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

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
            self::ARCHIVED => 'Archived',
        };
    }

    /**
     * Check if article is published
     */
    public function isPublished(): bool
    {
        return $this === self::PUBLISHED;
    }
}
