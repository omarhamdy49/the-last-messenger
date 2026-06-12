<?php

declare(strict_types=1);

namespace App\Domain\Blog\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class ArticleOperationException extends DomainException
{
    public function __construct(
        public readonly string $operation,
        public readonly ?int $articleId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: "Article {$operation} failed".($articleId ? " for ID [{$articleId}]" : '').'.',
            context: array_filter(['operation' => $operation, 'article_id' => $articleId]),
            code: 0,
            previous: $previous,
        );
    }
}
