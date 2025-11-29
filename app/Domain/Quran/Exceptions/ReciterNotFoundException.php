<?php

declare(strict_types=1);

namespace App\Domain\Quran\Exceptions;

use Exception;

/**
 * Exception thrown when a Quran reciter is not found
 */
final class ReciterNotFoundException extends Exception
{
    public static function withId(int $id): self
    {
        return new self("Reciter with ID {$id} not found.");
    }

    public static function withSlug(string $slug): self
    {
        return new self("Reciter with slug '{$slug}' not found.");
    }
}
