<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to get a single Chapter by ID
 */
final readonly class GetChapterByIdQuery implements Query
{
    public function __construct(
        public int $chapterId,
        public bool $withHadiths = false,
        public bool $withBook = true,
        public ?string $locale = 'ar'
    ) {}
}
