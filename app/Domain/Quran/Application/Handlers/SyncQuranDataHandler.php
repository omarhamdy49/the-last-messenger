<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\SyncQuranDataCommand;
use App\Domain\Quran\Services\QuranService;

/**
 * Handler for syncing Quran data from external API
 */
final readonly class SyncQuranDataHandler
{
    public function __construct(
        private QuranService $quranService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(SyncQuranDataCommand $command): array
    {
        $results = [
            'surahs' => 0,
            'verses' => 0,
            'translations' => 0,
            'reciters' => 0,
            'errors' => [],
        ];

        if ($command->syncSurahs) {
            try {
                $results['surahs'] = $this->quranService->syncSurahs();
            } catch (\Throwable $e) {
                $results['errors'][] = "Surahs sync failed: {$e->getMessage()}";
            }
        }

        if ($command->syncVerses) {
            try {
                $results['verses'] = $this->quranService->syncVerses(
                    $command->surahId
                );
            } catch (\Throwable $e) {
                $results['errors'][] = "Verses sync failed: {$e->getMessage()}";
            }
        }

        if ($command->syncTranslations && $command->language) {
            try {
                $results['translations'] = $this->quranService->syncTranslations(
                    $command->language,
                    $command->surahId
                );
            } catch (\Throwable $e) {
                $results['errors'][] = "Translations sync failed: {$e->getMessage()}";
            }
        }

        if ($command->syncReciters) {
            try {
                $results['reciters'] = $this->quranService->syncReciters();
            } catch (\Throwable $e) {
                $results['errors'][] = "Reciters sync failed: {$e->getMessage()}";
            }
        }

        return $results;
    }
}
