<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\ReciterDTO;
use App\Domain\Quran\Application\Queries\GetReciterByIdQuery;
use App\Domain\Quran\Exceptions\ReciterNotFoundException;
use App\Domain\Quran\Repositories\ReciterRepositoryInterface;

/**
 * Handler for getting a reciter by ID
 */
final readonly class GetReciterByIdHandler
{
    public function __construct(
        private ReciterRepositoryInterface $reciterRepository,
    ) {}

    public function handle(GetReciterByIdQuery $query): ReciterDTO
    {
        $reciter = $this->reciterRepository->getById($query->id);

        if ($reciter === null) {
            throw ReciterNotFoundException::withId($query->id);
        }

        return ReciterDTO::fromModel($reciter, $query->locale);
    }
}
