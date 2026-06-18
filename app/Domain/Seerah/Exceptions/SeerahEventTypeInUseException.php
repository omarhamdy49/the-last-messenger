<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Exceptions;

use RuntimeException;

/**
 * Thrown when an attempt is made to delete a Seerah event type that still has
 * events linked to it.
 */
class SeerahEventTypeInUseException extends RuntimeException
{
    public static function withCount(int $count): self
    {
        return new self("Cannot delete event type: {$count} event(s) still reference it.");
    }
}
