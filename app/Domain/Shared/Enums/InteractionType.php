<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Interaction Type Enum
 * Defines all possible user interaction types
 */
enum InteractionType: string
{
    case VIEW = 'view';
    case LIKE = 'like';
    case SHARE = 'share';
    case BOOKMARK = 'bookmark';
    case COMMENT = 'comment';
    case DOWNLOAD = 'download';
    case PRINT = 'print';
    case SEARCH = 'search';
    case PROGRESS = 'progress';
    case COMPLETE = 'complete';

    /**
     * Get the point value for engagement scoring
     */
    public function getPoints(): int
    {
        return match ($this) {
            self::VIEW => 1,
            self::LIKE => 5,
            self::SHARE => 10,
            self::BOOKMARK => 3,
            self::COMMENT => 10,
            self::DOWNLOAD => 5,
            self::PRINT => 3,
            self::SEARCH => 1,
            self::PROGRESS => 2,
            self::COMPLETE => 15,
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::VIEW => 'View',
            self::LIKE => 'Like',
            self::SHARE => 'Share',
            self::BOOKMARK => 'Bookmark',
            self::COMMENT => 'Comment',
            self::DOWNLOAD => 'Download',
            self::PRINT => 'Print',
            self::SEARCH => 'Search',
            self::PROGRESS => 'Progress',
            self::COMPLETE => 'Complete',
        };
    }

    /**
     * Get all interaction types
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if interaction type counts towards engagement score
     */
    public function countsTowardsEngagement(): bool
    {
        return ! in_array($this, [self::VIEW, self::SEARCH]);
    }
}
