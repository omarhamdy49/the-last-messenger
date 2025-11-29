<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\Commands\CreateNarratorCommand;
use App\Domain\Hadith\Application\DTOs\NarratorDTO;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Shared\Application\Commands\Command;
use App\Domain\Shared\Application\Commands\CommandHandler;

/**
 * Handler for CreateNarratorCommand
 *
 * @implements CommandHandler<CreateNarratorCommand, NarratorDTO>
 */
final class CreateNarratorHandler implements CommandHandler
{
    /**
     * Handle the command
     *
     * @param CreateNarratorCommand $command
     * @return NarratorDTO
     */
    public function handle(Command $command): NarratorDTO
    {
        /** @var CreateNarratorCommand $command */

        $narrator = HadithNarrator::create([
            'name_ar' => $command->nameAr,
            'name_en' => $command->nameEn,
            'kuniya' => $command->kuniya,
            'nasab' => $command->nasab,
            'laqab' => $command->laqab,
            'birth_year' => $command->birthYear,
            'death_year' => $command->deathYear,
            'biography_ar' => $command->biographyAr,
            'biography_en' => $command->biographyEn,
            'reliability' => $command->reliability,
        ]);

        return NarratorDTO::fromModel($narrator);
    }
}
