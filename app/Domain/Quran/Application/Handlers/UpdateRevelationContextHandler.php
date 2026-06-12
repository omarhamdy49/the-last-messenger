<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\UpdateRevelationContextCommand;
use App\Domain\Quran\Models\RevelationContext;
use App\Domain\Quran\Repositories\RevelationContextRepositoryInterface;

/**
 * Handler for updating a revelation context (Asbab al-Nuzul)
 */
final readonly class UpdateRevelationContextHandler
{
    public function __construct(
        private RevelationContextRepositoryInterface $revelationContextRepository,
    ) {}

    /**
     * @return RevelationContext|null Updated model, or null when the ID does not exist
     */
    public function handle(UpdateRevelationContextCommand $command): ?RevelationContext
    {
        $context = $this->revelationContextRepository->getById($command->id);

        if ($context === null) {
            return null;
        }

        $this->revelationContextRepository->updateWithTranslations(
            $context,
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

        return $context->refresh()->load([
            'surah:id,revelation_order,name_ar,name_en,revelation_place',
            'translations:id,revelation_context_id,locale,title,narrative,source_reference,is_ready',
        ]);
    }
}
