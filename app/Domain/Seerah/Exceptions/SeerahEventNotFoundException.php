<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class SeerahEventNotFoundException extends DomainException
{
    public function __construct(
        public readonly int|string $identifier,
    ) {
        parent::__construct(
            message: "Seerah event [{$identifier}] not found.",
            context: ['identifier' => $identifier],
            code: 404,
        );
    }

    public static function byId(int $id): self
    {
        return new self($id);
    }

    public static function bySlug(string $slug): self
    {
        return new self($slug);
    }
}
