<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\DeleteRevelationContextCommand;
use App\Domain\Quran\Repositories\RevelationContextRepositoryInterface;

/**
 * Handler for deleting a revelation context (Asbab al-Nuzul)
 */
final readonly class DeleteRevelationContextHandler
{
    public function __construct(
        private RevelationContextRepositoryInterface $revelationContextRepository,
    ) {}

    /**
     * @return bool False when the ID does not exist
     */
    public function handle(DeleteRevelationContextCommand $command): bool
    {
        $context = $this->revelationContextRepository->getById($command->id);

        if ($context === null) {
            return false;
        }

        return $this->revelationContextRepository->deleteContext($context);
    }
}
