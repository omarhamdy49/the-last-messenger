<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

/**
 * Collection Not Empty Exception
 *
 * Thrown when a delete is attempted on a HadithCollection that still
 * contains one or more books.  The user-facing Arabic message is kept in
 * the controller so it can be localised; the exception message is English
 * for logging and debugging purposes.
 */
final class CollectionNotEmptyException extends DomainException
{
    /**
     * Create for a specific collection ID.
     *
     * The Arabic string is carried as the exception message because
     * AdminController::handleBulkOperation() surfaces $e->getMessage() directly
     * in the per-item error array returned to the caller.
     */
    public static function withCollectionId(int $collectionId): static
    {
        return new self(
            message: 'لا يمكن حذف مجموعة تحتوي على كتب',
            context: ['collection_id' => $collectionId],
            code: 422
        );
    }
}
