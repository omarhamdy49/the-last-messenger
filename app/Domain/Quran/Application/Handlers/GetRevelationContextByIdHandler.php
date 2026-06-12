<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Queries\GetRevelationContextByIdQuery;
use App\Domain\Quran\Models\RevelationContext;
use App\Domain\Quran\Repositories\RevelationContextRepositoryInterface;

/**
 * Handler for fetching a single revelation context (Asbab al-Nuzul)
 *
 * Returns the model (with translations + surah loaded) so admin forms can
 * access every locale, mirroring how reciter handlers expose models.
 */
final readonly class GetRevelationContextByIdHandler
{
    public function __construct(
        private RevelationContextRepositoryInterface $revelationContextRepository,
    ) {}

    public function handle(GetRevelationContextByIdQuery $query): ?RevelationContext
    {
        return $this->revelationContextRepository->getById($query->id);
    }
}
