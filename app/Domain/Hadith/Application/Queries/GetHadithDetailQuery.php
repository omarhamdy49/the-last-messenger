<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to get the full public detail of a hadith:
 * translations, isnad, commentaries, scholar gradings and parallels count.
 */
final readonly class GetHadithDetailQuery implements Query
{
    public function __construct(
        public int $hadithId,
        public ?string $locale = 'ar',
    ) {}
}
