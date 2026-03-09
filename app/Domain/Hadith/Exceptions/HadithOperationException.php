<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class HadithOperationException extends DomainException
{
    public function __construct(
        public readonly string $operation,
        public readonly string $reason,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: "Hadith operation [{$operation}] failed: {$reason}",
            context: ['operation' => $operation, 'reason' => $reason],
            code: 0,
            previous: $previous,
        );
    }
}
