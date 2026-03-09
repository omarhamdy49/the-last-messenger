<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HadithImportService
{
    public function __construct(
        private readonly HadithDeduplicationService $deduplicationService,
    ) {}

    /**
     * Import hadith from JSON file with deduplication
     *
     * @param string $filePath Path to JSON file
     * @param int $collectionId Collection ID for these hadiths
     * @return array Import statistics
     */
    public function importFromJsonFile(string $filePath, int $collectionId): array
    {
        $stats = [
            'total' => 0,
            'created' => 0,
            'duplicates' => 0,
            'linked' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        if (!file_exists($filePath)) {
            $stats['errors'][] = "File not found: {$filePath}";
            return $stats;
        }

        $json = json_decode(file_get_contents($filePath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $stats['errors'][] = "Invalid JSON: " . json_last_error_msg();
            return $stats;
        }

        $collection = HadithCollection::find($collectionId);
        if (!$collection) {
            $stats['errors'][] = "Collection {$collectionId} not found";
            return $stats;
        }

        // Process hadiths from JSON
        foreach ($json as $item) {
            $stats['total']++;

            try {
                DB::transaction(function () use ($item, $collection, &$stats) {
                    // Extract data
                    $bookTitle = $item['hadith_book_title_ar'] ?? null;
                    $hadithNumber = $item['hadith_number'] ?? null;
                    $arabicText = $item['hadith_ar_text'] ?? null;

                    if (!$bookTitle || !$arabicText) {
                        $stats['errors'][] = "Missing required fields in item: " . json_encode(array_keys($item));
                        $stats['failed']++;
                        return;
                    }

                    // Find or create book
                    $book = HadithBook::where('collection_id', $collection->id)
                        ->where('title_ar', $bookTitle)
                        ->first();

                    if (!$book) {
                        $book = HadithBook::create([
                            'collection_id' => $collection->id,
                            'title_ar' => $bookTitle,
                            'title_en' => $item['hadith_book_title_en'] ?? $bookTitle,
                        ]);
                    }

                    // Check for duplicate by text
                    $existing = $this->deduplicationService->findDuplicate($arabicText);

                    if ($existing) {
                        // Hadith text already exists in database
                        $stats['duplicates']++;
                        $stats['linked']++;

                        Log::info('hadith.import.linked_existing', [
                            'collection_id' => $collection->id,
                            'existing_hadith_id' => $existing->id,
                            'book_id' => $book->id,
                            'hadith_number' => $hadithNumber,
                        ]);

                        return;
                    }

                    // Create new hadith
                    $hadith = Hadith::create([
                        'book_id' => $book->id,
                        'hadith_number' => $hadithNumber,
                        'grading_status' => $item['grading_status'] ?? null,
                        'text_hash' => $this->deduplicationService->calculateTextHash($arabicText),
                    ]);

                    // Store translations
                    $this->storeTranslations($hadith, $item);

                    $stats['created']++;

                    Log::info('hadith.import.created', [
                        'hadith_id' => $hadith->id,
                        'book_id' => $book->id,
                        'hadith_number' => $hadithNumber,
                    ]);
                });
            } catch (\Exception $e) {
                $stats['failed']++;
                $stats['errors'][] = "Row {$stats['total']}: {$e->getMessage()}";
                Log::error('hadith.import.failed', [
                    'row' => $stats['total'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Store hadith translations
     */
    private function storeTranslations(Hadith $hadith, array $item): void
    {
        $translations = [
            'ar' => $item['hadith_ar_text'] ?? null,
            'ar_notashkeel' => $item['hadith_ar_notashkeel_text'] ?? null,
            'en' => $item['hadith_en_text'] ?? null,
            'tr' => $item['hadith_tr_text'] ?? null,
            'fr' => $item['hadith_fr_text'] ?? null,
            'ur' => $item['hadith_ur_text'] ?? null,
        ];

        foreach ($translations as $languageCode => $text) {
            if ($text) {
                $hadith->translations()->create([
                    'language_code' => $languageCode,
                    'text' => $text,
                ]);
            }
        }
    }

    /**
     * Import all hadiths from a directory
     */
    public function importFromDirectory(string $directoryPath): array
    {
        $stats = [
            'total_files' => 0,
            'files_processed' => 0,
            'total_created' => 0,
            'total_duplicates' => 0,
            'total_linked' => 0,
            'total_failed' => 0,
            'file_stats' => [],
        ];

        if (!is_dir($directoryPath)) {
            return ['error' => "Directory not found: {$directoryPath}"];
        }

        $files = glob($directoryPath . '/*.json');
        $stats['total_files'] = count($files);

        foreach ($files as $file) {
            $filename = basename($file, '.json');

            // Map filename to collection
            // Example: "sahih_bukhari" -> "Sahih Bukhari"
            $collectionName = ucwords(str_replace('_', ' ', $filename));
            $collection = HadithCollection::whereRaw('LOWER(name_en) = ?', [strtolower($collectionName)])
                ->orWhereRaw('LOWER(abbreviation) = ?', [strtolower($filename)])
                ->first();

            if (!$collection) {
                $stats['file_stats'][$filename] = [
                    'status' => 'skipped',
                    'reason' => 'Collection not found',
                ];
                continue;
            }

            $fileStats = $this->importFromJsonFile($file, $collection->id);
            $stats['files_processed']++;

            $stats['file_stats'][$filename] = $fileStats;
            $stats['total_created'] += $fileStats['created'];
            $stats['total_duplicates'] += $fileStats['duplicates'];
            $stats['total_linked'] += $fileStats['linked'];
            $stats['total_failed'] += $fileStats['failed'];
        }

        return $stats;
    }
}
