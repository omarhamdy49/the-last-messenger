<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\CreateTafsirCommand;
use App\Domain\Quran\Application\DTOs\TafsirDTO;
use App\Domain\Quran\Exceptions\VerseNotFoundException;
use App\Domain\Quran\Repositories\TafseerRepositoryInterface;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;

/**
 * Handler for creating a tafsir (verse interpretation)
 */
final readonly class CreateTafsirHandler
{
    public function __construct(
        private TafseerRepositoryInterface $tafseerRepository,
        private VerseRepositoryInterface $verseRepository,
    ) {}

    public function handle(CreateTafsirCommand $command): TafsirDTO
    {
        // Verify verse exists
        $verse = $this->verseRepository->find($command->verseId);

        if ($verse === null) {
            throw VerseNotFoundException::withId($command->verseId);
        }

        $tafsir = $this->tafseerRepository->create([
            'verse_id' => $command->verseId,
            'verse_key' => $command->verseKey,
            'lang' => $command->lang,
            'text' => $command->text,
            'active' => $command->isActive,
        ]);

        return TafsirDTO::fromModel($tafsir);
    }
}
