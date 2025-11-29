<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to get a random hadith
 */
final readonly class GetRandomHadithQuery implements Query
{
    public function __construct(
        public ?string $gradingStatus = 'sahih',
        public ?int $collectionId = null,
        public bool $withTranslations = true,
        public ?string $locale = 'ar'
    ) {}
}
