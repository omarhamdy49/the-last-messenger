<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Application\DTOs\HadithLookupData;
use App\Domain\Hadith\Models\Hadith;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HadithDeduplicationService
{
    /**
     * Calculate SHA256 hash of hadith text for deduplication
     */
    public function calculateTextHash(string $text): string
    {
        // Normalize text: remove extra whitespace, trim
        $normalized = trim(preg_replace('/\s+/', ' ', $text));

        return hash('sha256', $normalized);
    }

    /**
     * Check if hadith with same text already exists
     */
    public function findDuplicate(string $text): ?Hadith
    {
        $hash = $this->calculateTextHash($text);

        return Hadith::where('text_hash', $hash)
            ->with(['translations' => fn ($q) => $q->where('language_code', 'ar')])
            ->first();
    }

    /**
     * Get or create hadith, checking for duplicates by text
     *
     * @param  HadithLookupData  $hadithData  Typed hadith attributes (book_id, hadith_number, etc.)
     * @param  string  $arabicText  Arabic translation text
     * @return Hadith The existing or newly created hadith
     */
    public function getOrCreateByText(HadithLookupData $hadithData, string $arabicText): Hadith
    {
        $hash = $this->calculateTextHash($arabicText);

        // Check if this exact text already exists
        $existing = Hadith::where('text_hash', $hash)->first();

        if ($existing) {
            Log::info('hadith.deduplication.found_existing', [
                'existing_id' => $existing->id,
                'book_id' => $hadithData->bookId,
                'hadith_number' => $hadithData->hadithNumber,
                'text_hash' => $hash,
            ]);

            return $existing;
        }

        // Create new hadith; append the derived text_hash to the payload
        $hadith = Hadith::create(array_merge($hadithData->toArray(), ['text_hash' => $hash]));

        Log::info('hadith.deduplication.created_new', [
            'hadith_id' => $hadith->id,
            'book_id' => $hadithData->bookId,
            'hadith_number' => $hadithData->hadithNumber,
            'text_hash' => $hash,
        ]);

        return $hadith;
    }

    /**
     * Recalculate text hashes for all hadiths
     * Call this to populate text_hash for existing records
     */
    public function recalculateAllHashes(): array
    {
        $stats = [
            'total' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        Hadith::chunk(100, function ($hadiths) use (&$stats) {
            foreach ($hadiths as $hadith) {
                $stats['total']++;

                try {
                    $hadith->load(['translations' => fn ($q) => $q->where('language_code', 'ar')]);

                    $arabicTranslation = $hadith->translations->first();
                    if (! $arabicTranslation) {
                        $stats['errors'][] = "Hadith {$hadith->id} has no Arabic translation";

                        continue;
                    }

                    $hash = $this->calculateTextHash($arabicTranslation->text);

                    if ($hadith->text_hash !== $hash) {
                        $hadith->update(['text_hash' => $hash]);
                        $stats['updated']++;
                    }
                } catch (\Exception $e) {
                    $stats['failed']++;
                    $stats['errors'][] = "Hadith {$hadith->id}: {$e->getMessage()}";
                }
            }
        });

        return $stats;
    }

    /**
     * Find and report duplicate hadiths
     */
    public function findDuplicates(): array
    {
        $duplicates = [];

        DB::table('hadith_hadiths')
            ->whereNotNull('text_hash')
            ->groupBy('text_hash')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('text_hash')
            ->each(function ($hash) use (&$duplicates) {
                $group = Hadith::where('text_hash', $hash)
                    ->with([
                        'book:id,title_ar,collection_id',
                        'translations' => fn ($q) => $q->where('language_code', 'ar')->select('id', 'hadith_id', 'text'),
                    ])
                    ->get()
                    ->map(fn ($h) => [
                        'id' => $h->id,
                        'book_id' => $h->book_id,
                        'book_title' => $h->book?->title_ar,
                        'hadith_number' => $h->hadith_number,
                        'text_preview' => substr($h->translations->first()?->text ?? '', 0, 100).'...',
                    ])
                    ->toArray();

                if (count($group) > 1) {
                    $duplicates[] = [
                        'text_hash' => $hash,
                        'count' => count($group),
                        'hadiths' => $group,
                    ];
                }
            });

        return $duplicates;
    }

    /**
     * Merge duplicate hadiths, keeping the oldest
     * Updates all references to point to primary hadith
     */
    public function mergeDuplicates(string $textHash, int $keepHadithId): array
    {
        $stats = [
            'merged' => 0,
            'failed' => 0,
        ];

        $duplicates = Hadith::where('text_hash', $textHash)
            ->where('id', '!=', $keepHadithId)
            ->get();

        foreach ($duplicates as $duplicate) {
            try {
                // Any future references would go to $keepHadithId
                // For now, just soft-delete or mark as merged
                DB::transaction(function () use ($duplicate, $keepHadithId) {
                    Log::info('hadith.deduplication.merging', [
                        'duplicate_id' => $duplicate->id,
                        'keep_id' => $keepHadithId,
                    ]);

                    // Keep record of merge
                    DB::table('hadith_hadiths')
                        ->where('id', $duplicate->id)
                        ->update(['merged_into_id' => $keepHadithId]);

                    $stats['merged']++;
                });
            } catch (\Exception $e) {
                Log::error('hadith.deduplication.merge_failed', [
                    'duplicate_id' => $duplicate->id,
                    'error' => $e->getMessage(),
                ]);
                $stats['failed']++;
            }
        }

        return $stats;
    }
}
