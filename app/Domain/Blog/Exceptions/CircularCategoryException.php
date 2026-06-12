<?php

declare(strict_types=1);

namespace App\Domain\Blog\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class CircularCategoryException extends DomainException
{
    public static function selfParent(int $id): self
    {
        return new self(
            message: 'Category cannot be its own parent.',
            context: ['category_id' => $id],
            code: 422,
        );
    }

    public static function parentNotFound(int $parentId): self
    {
        return new self(
            message: "Parent category with ID {$parentId} does not exist.",
            context: ['parent_id' => $parentId],
            code: 422,
        );
    }

    public static function descendantLoop(int $categoryId, int $parentId): self
    {
        return new self(
            message: 'Circular reference detected. A category cannot be a child of its own descendant.',
            context: ['category_id' => $categoryId, 'attempted_parent_id' => $parentId],
            code: 422,
        );
    }
}
