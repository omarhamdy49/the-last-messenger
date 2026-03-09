<?php

declare(strict_types=1);

namespace App\Domain\Blog\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class CategoryNotFoundException extends DomainException
{
    public function __construct(
        public readonly int|string $identifier,
    ) {
        parent::__construct(
            message: "Category [{$identifier}] not found.",
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
