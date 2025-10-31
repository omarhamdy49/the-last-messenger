<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum ScheduleStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case PUBLISHED = 'published';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'قيد الانتظار',
            self::PROCESSING => 'جاري التنفيذ',
            self::PUBLISHED => 'تم النشر',
            self::FAILED => 'فشل',
            self::CANCELLED => 'ملغي',
        };
    }

    /**
     * Get color class for status badge
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-yellow-100 text-yellow-800',
            self::PROCESSING => 'bg-blue-100 text-blue-800',
            self::PUBLISHED => 'bg-green-100 text-green-800',
            self::FAILED => 'bg-red-100 text-red-800',
            self::CANCELLED => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Check if status is terminal (cannot be changed)
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::PUBLISHED, self::FAILED, self::CANCELLED]);
    }

    /**
     * Check if status allows retry
     */
    public function canRetry(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * Get all active statuses (not terminal)
     */
    public static function activeStatuses(): array
    {
        return [self::PENDING, self::PROCESSING];
    }
}
