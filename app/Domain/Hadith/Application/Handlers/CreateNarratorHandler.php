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
     * @param  CreateNarratorCommand  $command
     */
    public function handle(Command $command): NarratorDTO
    {
        /** @var CreateNarratorCommand $command */
        $narrator = HadithNarrator::resolveOrCreate([
            'name_ar' => $command->nameAr,
            'name_en' => $command->nameEn,
            'kunya' => $command->kuniya,
            'full_name_ar' => $command->nasab,
            'birth_year_hijri' => $command->birthYear,
            'death_year_hijri' => $command->deathYear,
            'biography' => $command->biographyAr ?: $command->biographyEn,
            'reliability_grade' => $command->reliability,
        ]);

        return NarratorDTO::fromModel($narrator);
    }
}
