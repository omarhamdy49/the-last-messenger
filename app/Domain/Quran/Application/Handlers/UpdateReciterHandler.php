<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\UpdateReciterCommand;
use App\Domain\Quran\Application\DTOs\ReciterDTO;
use App\Domain\Quran\Exceptions\ReciterNotFoundException;
use App\Domain\Quran\Repositories\ReciterRepositoryInterface;

/**
 * Handler for updating an existing Quran reciter
 */
final readonly class UpdateReciterHandler
{
    public function __construct(
        private ReciterRepositoryInterface $reciterRepository,
    ) {}

    public function handle(UpdateReciterCommand $command): ReciterDTO
    {
        $reciter = $this->reciterRepository->getById($command->id);

        if ($reciter === null) {
            throw ReciterNotFoundException::withId($command->id);
        }

        $data = array_filter([
            'name' => $command->name,
            'slug' => $command->slug,
            'description' => $command->description,
            'style' => $command->style,
            'country' => $command->country,
            'profile_image' => $command->profileImage,
            'is_featured' => $command->isFeatured,
            'is_active' => $command->isActive,
            'display_order' => $command->displayOrder,
        ], fn ($value) => $value !== null);

        $this->reciterRepository->update($reciter, $data);

        // Refresh the model
        $reciter = $this->reciterRepository->getById($command->id);

        return ReciterDTO::fromModel($reciter);
    }
}
