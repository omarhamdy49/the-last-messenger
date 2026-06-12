<?php

declare(strict_types=1);

namespace App\Domain\Blog\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class CategoryHasChildrenException extends DomainException
{
    public static function forDelete(int $id): self
    {
        return new self(
            message: 'Cannot delete category with child categories. Please delete or move child categories first.',
            context: ['category_id' => $id],
            code: 422,
        );
    }

    public static function forDeleteWithName(string $name, int $id): self
    {
        return new self(
            message: "Cannot delete category '{$name}' (ID: {$id}) because it has child categories.",
            context: ['category_id' => $id, 'category_name' => $name],
            code: 422,
        );
    }
}
