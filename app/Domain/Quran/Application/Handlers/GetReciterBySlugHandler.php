<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\ReciterDTO;
use App\Domain\Quran\Application\Queries\GetReciterBySlugQuery;
use App\Domain\Quran\Exceptions\ReciterNotFoundException;
use App\Domain\Quran\Repositories\ReciterRepositoryInterface;

/**
 * Handler for getting a reciter by slug
 */
final readonly class GetReciterBySlugHandler
{
    public function __construct(
        private ReciterRepositoryInterface $reciterRepository,
    ) {}

    public function handle(GetReciterBySlugQuery $query): ReciterDTO
    {
        $reciter = $this->reciterRepository->getBySlug($query->slug);

        if ($reciter === null) {
            throw ReciterNotFoundException::withSlug($query->slug);
        }

        return ReciterDTO::fromModel($reciter, $query->locale);
    }
}
