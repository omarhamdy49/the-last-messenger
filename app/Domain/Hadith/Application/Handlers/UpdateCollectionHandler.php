<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\Commands\UpdateCollectionCommand;
use App\Domain\Hadith\Application\DTOs\CollectionDTO;
use App\Domain\Hadith\Exceptions\CollectionNotFoundException;
use App\Domain\Hadith\Repositories\CollectionRepositoryInterface;
use App\Domain\Hadith\Repositories\ScholarRepositoryInterface;
use App\Domain\Shared\Application\Commands\Command;
use App\Domain\Shared\Application\Commands\CommandHandler;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\Exceptions\ValidationException;

/**
 * Handler for UpdateCollectionCommand
 *
 * @implements CommandHandler<UpdateCollectionCommand, CollectionDTO>
 */
final class UpdateCollectionHandler implements CommandHandler
{
    public function __construct(
        private readonly CollectionRepositoryInterface $collectionRepository,
        private readonly ScholarRepositoryInterface $scholarRepository,
    ) {}

    /**
     * Handle the command
     *
     * @param  UpdateCollectionCommand  $command
     *
     * @throws CollectionNotFoundException
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function handle(Command $command): CollectionDTO
    {
        /** @var UpdateCollectionCommand $command */
        $collection = $this->collectionRepository->find($command->collectionId);
        if ($collection === null) {
            throw CollectionNotFoundException::withCollectionId($command->collectionId);
        }

        // Check for duplicate slug if changing
        if ($command->slug !== null && $command->slug !== $collection->slug) {
            $existing = $this->collectionRepository->getBySlug($command->slug);
            if ($existing !== null) {
                throw ValidationException::withErrors([
                    'slug' => ["Collection with slug '{$command->slug}' already exists"],
                ]);
            }
        }

        // Validate scholar if changing
        if ($command->scholarId !== null) {
            $scholar = $this->scholarRepository->find($command->scholarId);
            if ($scholar === null) {
                throw EntityNotFoundException::forEntity('HadithScholar', $command->scholarId);
            }
        }

        // Update fields
        if ($command->name !== null) {
            $collection->name = $command->name;
        }

        if ($command->slug !== null) {
            $collection->slug = $command->slug;
        }

        if ($command->description !== null) {
            $collection->description = $command->description;
        }

        if ($command->isActive !== null) {
            $collection->active = $command->isActive;
        }

        $collection->save();

        return CollectionDTO::fromModel($collection);
    }
}
