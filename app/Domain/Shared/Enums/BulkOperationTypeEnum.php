<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Bulk Operation Type Enum
 * Defines available bulk operation types
 */
enum BulkOperationTypeEnum: string
{
    case UPDATE = 'update';
    case DELETE = 'delete';
    case SOFT_DELETE = 'soft_delete';
    case RESTORE = 'restore';
    case PUBLISH = 'publish';
    case UNPUBLISH = 'unpublish';
    case ARCHIVE = 'archive';
    case CHANGE_CATEGORY = 'change_category';
    case ADD_TAGS = 'add_tags';
    case REMOVE_TAGS = 'remove_tags';
    case CHANGE_STATUS = 'change_status';
    case CHANGE_AUTHOR = 'change_author';
    case ASSIGN_USER = 'assign_user';
    case CHANGE_PERMISSIONS = 'change_permissions';
    case EXPORT = 'export';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::UPDATE => 'Bulk Update',
            self::DELETE => 'Bulk Delete (Permanent)',
            self::SOFT_DELETE => 'Bulk Delete (Soft)',
            self::RESTORE => 'Bulk Restore',
            self::PUBLISH => 'Bulk Publish',
            self::UNPUBLISH => 'Bulk Unpublish',
            self::ARCHIVE => 'Bulk Archive',
            self::CHANGE_CATEGORY => 'Change Category',
            self::ADD_TAGS => 'Add Tags',
            self::REMOVE_TAGS => 'Remove Tags',
            self::CHANGE_STATUS => 'Change Status',
            self::CHANGE_AUTHOR => 'Change Author',
            self::ASSIGN_USER => 'Assign to User',
            self::CHANGE_PERMISSIONS => 'Change Permissions',
            self::EXPORT => 'Export Data',
        };
    }

    /**
     * Get all operation types
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if operation is destructive
     */
    public function isDestructive(): bool
    {
        return in_array($this, [
            self::DELETE,
            self::SOFT_DELETE,
            self::ARCHIVE,
        ], true);
    }

    /**
     * Check if operation requires queue processing
     */
    public function requiresQueue(): bool
    {
        return in_array($this, [
            self::UPDATE,
            self::DELETE,
            self::EXPORT,
        ], true);
    }
}
