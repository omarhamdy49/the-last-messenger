<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\CreateVerseTranslationCommand;
use App\Domain\Quran\Exceptions\VerseNotFoundException;
use App\Domain\Quran\Models\VerseTranslation;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;

/**
 * Handler for creating a verse translation
 */
final readonly class CreateVerseTranslationHandler
{
    public function __construct(
        private VerseRepositoryInterface $verseRepository,
    ) {}

    public function handle(CreateVerseTranslationCommand $command): VerseTranslation
    {
        // Verify verse exists
        $verse = $this->verseRepository->find($command->verseId);

        if ($verse === null) {
            throw VerseNotFoundException::withId($command->verseId);
        }

        return VerseTranslation::create([
            'verse_id' => $command->verseId,
            'lang' => $command->lang,
            'text' => $command->text,
            'translator_name' => $command->translatorName,
            'active' => $command->isActive,
        ]);
    }
}
