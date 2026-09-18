<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Exceptions;

use RuntimeException;

/**
 * A 2xx response whose body is not the shape the importer relies on.
 */
final class QfPayloadException extends RuntimeException
{
    public function __construct(
        public readonly string $endpoint,
        public readonly string $missingKey,
    ) {
        parent::__construct(sprintf('Quran Foundation API %s returned an unexpected payload (missing "%s").', $endpoint, $missingKey));
    }
}
