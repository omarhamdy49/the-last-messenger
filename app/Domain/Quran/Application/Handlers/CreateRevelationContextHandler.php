<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\CreateRevelationContextCommand;
use App\Domain\Quran\Models\RevelationContext;
use App\Domain\Quran\Repositories\RevelationContextRepositoryInterface;

/**
 * Handler for creating a revelation context (Asbab al-Nuzul)
 */
final readonly class CreateRevelationContextHandler
{
    public function __construct(
        private RevelationContextRepositoryInterface $revelationContextRepository,
    ) {}

    public function handle(CreateRevelationContextCommand $command): RevelationContext
    {
        return $this->revelationContextRepository->createWithTranslations(
            [
                'surah_id' => $command->surahId,
                'verse_from' => $command->verseFrom,
                'verse_to' => $command->verseTo,
                'period' => $command->period,
                'event_key' => $command->eventKey,
                'display_order' => $command->displayOrder,
                'is_active' => $command->isActive,
            ],
            $command->translations,
        );
    }
}
