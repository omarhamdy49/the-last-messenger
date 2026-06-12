<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\ImportQuranFromJsonCommand;
use App\Domain\Quran\Services\QuranService;
use InvalidArgumentException;

/**
 * Handler for importing Quran data from JSON file
 */
final readonly class ImportQuranFromJsonHandler
{
    public function __construct(
        private QuranService $quranService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(ImportQuranFromJsonCommand $command): array
    {
        if (! file_exists($command->filePath)) {
            throw new InvalidArgumentException("File not found: {$command->filePath}");
        }

        $json = file_get_contents($command->filePath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON file: '.json_last_error_msg());
        }

        return match ($command->type) {
            'surahs' => $this->importSurahs($data, $command->truncateFirst),
            'verses' => $this->importVerses($data, $command->truncateFirst),
            'translations' => $this->importTranslations($data, $command->truncateFirst),
            'tafsir' => $this->importTafsir($data, $command->truncateFirst),
            'reciters' => $this->importReciters($data, $command->truncateFirst),
            default => throw new InvalidArgumentException("Unknown import type: {$command->type}"),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function importSurahs(array $data, bool $truncate): array
    {
        return $this->quranService->importSurahs($data, $truncate);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function importVerses(array $data, bool $truncate): array
    {
        return $this->quranService->importVerses($data, $truncate);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function importTranslations(array $data, bool $truncate): array
    {
        return $this->quranService->importTranslations($data, $truncate);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function importTafsir(array $data, bool $truncate): array
    {
        return $this->quranService->importTafsir($data, $truncate);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function importReciters(array $data, bool $truncate): array
    {
        return $this->quranService->importReciters($data, $truncate);
    }
}
