<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\Commands\CreateCollectionCommand;
use App\Domain\Hadith\Application\DTOs\CollectionDTO;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Repositories\CollectionRepositoryInterface;
use App\Domain\Hadith\Repositories\ScholarRepositoryInterface;
use App\Domain\Shared\Application\Commands\Command;
use App\Domain\Shared\Application\Commands\CommandHandler;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\Exceptions\ValidationException;

/**
 * Handler for CreateCollectionCommand
 *
 * @implements CommandHandler<CreateCollectionCommand, CollectionDTO>
 */
final class CreateCollectionHandler implements CommandHandler
{
    public function __construct(
        private readonly CollectionRepositoryInterface $collectionRepository,
        private readonly ScholarRepositoryInterface $scholarRepository,
    ) {}

    /**
     * Handle the command
     *
     * @param  CreateCollectionCommand  $command
     *
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function handle(Command $command): CollectionDTO
    {
        /** @var CreateCollectionCommand $command */

        // Check for duplicate slug
        $existing = $this->collectionRepository->getBySlug($command->slug);
        if ($existing !== null) {
            throw ValidationException::withErrors([
                'slug' => ["Collection with slug '{$command->slug}' already exists"],
            ]);
        }

        // Validate scholar if provided
        if ($command->scholarId !== null) {
            $scholar = $this->scholarRepository->find($command->scholarId);
            if ($scholar === null) {
                throw EntityNotFoundException::forEntity('HadithScholar', $command->scholarId);
            }
        }

        $collection = HadithCollection::create([
            'name_ar' => $command->name['ar'] ?? '',
            'name_en' => $command->name['en'] ?? null,
            'slug' => $command->slug,
            'description_ar' => $command->description['ar'] ?? null,
            'description_en' => $command->description['en'] ?? null,
            'author_id' => $command->scholarId,
            'hadith_count' => $command->hadithCount,
            'is_active' => $command->isActive,
        ]);

        return CollectionDTO::fromModel($collection);
    }
}
