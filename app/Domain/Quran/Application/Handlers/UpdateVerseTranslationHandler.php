<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\UpdateVerseTranslationCommand;
use App\Domain\Quran\Exceptions\VerseTranslationNotFoundException;
use App\Domain\Quran\Models\VerseTranslation;

/**
 * Handler for updating a verse translation
 */
final readonly class UpdateVerseTranslationHandler
{
    public function handle(UpdateVerseTranslationCommand $command): VerseTranslation
    {
        $translation = VerseTranslation::find($command->id);

        if ($translation === null) {
            throw VerseTranslationNotFoundException::withId($command->id);
        }

        $data = array_filter([
            'lang' => $command->lang,
            'text' => $command->text,
            'translator_name' => $command->translatorName,
            'active' => $command->isActive,
        ], fn ($value) => $value !== null);

        $translation->update($data);

        return $translation->fresh();
    }
}
