<?php

declare(strict_types=1);

namespace App\Domain\Blog\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class CategoryHasArticlesException extends DomainException
{
    public static function forDelete(int $id): self
    {
        return new self(
            message: 'Cannot delete category with articles. Please reassign or delete articles first.',
            context: ['category_id' => $id],
            code: 422,
        );
    }

    public static function forDeleteWithName(string $name, int $id): self
    {
        return new self(
            message: "Cannot delete category '{$name}' (ID: {$id}) because it has articles.",
            context: ['category_id' => $id, 'category_name' => $name],
            code: 422,
        );
    }
}
