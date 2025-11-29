<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\CreateReciterCommand;
use App\Domain\Quran\Application\DTOs\ReciterDTO;
use App\Domain\Quran\Repositories\ReciterRepositoryInterface;

/**
 * Handler for creating a new Quran reciter
 */
final readonly class CreateReciterHandler
{
    public function __construct(
        private ReciterRepositoryInterface $reciterRepository,
    ) {}

    public function handle(CreateReciterCommand $command): ReciterDTO
    {
        $reciter = $this->reciterRepository->create([
            'name' => $command->name,
            'slug' => $command->slug,
            'description' => $command->description,
            'style' => $command->style,
            'country' => $command->country,
            'profile_image' => $command->profileImage,
            'is_featured' => $command->isFeatured,
            'is_active' => $command->isActive,
            'display_order' => $command->displayOrder,
        ]);

        return ReciterDTO::fromModel($reciter);
    }
}
