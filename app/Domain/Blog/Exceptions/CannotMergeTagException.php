<?php

declare(strict_types=1);

namespace App\Domain\Blog\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class CannotMergeTagException extends DomainException
{
    public static function selfMerge(): self
    {
        return new self(
            message: 'Cannot merge a tag with itself.',
            context: [],
            code: 422,
        );
    }

    public static function tagNotFound(int $id): self
    {
        return new self(
            message: "Tag [{$id}] not found.",
            context: ['tag_id' => $id],
            code: 404,
        );
    }
}
