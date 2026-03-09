<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class SahabiNotFoundException extends DomainException
{
    public function __construct(
        public readonly int|string $identifier,
    ) {
        parent::__construct(
            message: "Sahabi [{$identifier}] not found.",
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
