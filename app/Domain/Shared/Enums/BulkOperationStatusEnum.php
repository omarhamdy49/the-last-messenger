<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Bulk Operation Status Enum
 * Defines operation statuses
 */
enum BulkOperationStatusEnum: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case PARTIALLY_COMPLETED = 'partially_completed';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
            self::PARTIALLY_COMPLETED => 'Partially Completed',
        };
    }

    /**
     * Get CSS color class for status
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::PENDING => 'text-yellow-600',
            self::PROCESSING => 'text-blue-600',
            self::COMPLETED => 'text-green-600',
            self::FAILED => 'text-red-600',
            self::CANCELLED => 'text-gray-600',
            self::PARTIALLY_COMPLETED => 'text-orange-600',
        };
    }

    /**
     * Get all status values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if status is final (operation completed or terminated)
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
            self::PARTIALLY_COMPLETED,
        ], true);
    }

    /**
     * Check if status is in progress
     */
    public function isInProgress(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::PROCESSING,
        ], true);
    }
}
