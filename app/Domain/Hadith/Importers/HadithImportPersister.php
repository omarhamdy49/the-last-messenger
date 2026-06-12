<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Importers;

use App\Domain\Hadith\Models\Companion;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithApplication;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithCommentary;
use App\Domain\Hadith\Models\HadithFiqhRuling;
use App\Domain\Hadith\Models\HadithGrading;
use App\Domain\Hadith\Models\HadithGradingLevel;
use App\Domain\Hadith\Models\HadithIsnad;
use App\Domain\Hadith\Models\HadithKeyword;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Hadith\Models\HadithRelatedVerse;
use App\Domain\Hadith\Models\HadithScholar;
use App\Domain\Hadith\Models\HadithSource;
use App\Domain\Hadith\Models\HadithSyncLog;
use App\Domain\Hadith\Models\HadithTheme;
use App\Domain\Hadith\Models\HadithTranslation;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * HadithImportPersister
 *
 * Responsible for all database writes during a hadith JSON import.
 *
 * Accepts the normalised payloads produced by HadithImportMapper and:
 *   - Upserts core entities (collections, books, chapters, hadiths).
 *   - Writes rich metadata (translators, narrators, themes, keywords,
 *     gradings, scholarly commentary, related verses, applications,
 *     fiqh rulings).
 *   - Counts successes and errors for each category.
 *   - Reports progress to an optional HadithSyncLog.
 *
 * No file I/O or JSON decoding happens here.
 */
class HadithImportPersister
{
    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Persist a single-hadith payload.
     *
     * @param  array  $payload  Output of HadithImportMapper::map() with type='single_hadith'.
     * @param  HadithSyncLog|null  $syncLog  Optional progress tracker.
     * @param  bool  $dryRun  When true, no writes are performed.
     * @return array Summary statistics keyed by entity type.
     */
    public function persistSingleHadith(array $payload, ?HadithSyncLog $syncLog, bool $dryRun): array
    {
        $summary = $this->emptySummary();

        foreach ($payload['records'] as $record) {
            try {
                DB::transaction(function () use ($record, &$summary, $syncLog, $dryRun): void {
                    // 1. Collection
                    $collection = $this->upsertCollection($record['collection'], $dryRun);
                    if ($collection) {
                        $summary['collections']++;
                    }

                    // 2. Book
                    $book = $this->upsertBook($record['book'], $collection?->id, $dryRun);
                    if ($book) {
                        $summary['books']++;
                    }

                    // 3. Chapter (optional)
                    $chapter = null;
                    if (! empty($record['chapter'])) {
                        $chapter = $this->upsertChapter($record['chapter'], $book?->id, $dryRun);
                        if ($chapter) {
                            $summary['chapters']++;
                        }
                    }

                    // 4. Companion (optional)
                    $companion = null;
                    if (! empty($record['companion']) && ! $dryRun) {
                        $companion = Companion::findOrCreateByName($record['companion']);
                        if ($companion) {
                            $summary['companions']++;
                        }
                    }

                    // 5. Hadith core record
                    $hadithData = $record['hadith'];
                    $hadithData['companion_id'] = $companion?->id;

                    // Link the hadith to its resolved chapter. Only set the key when a
                    // chapter was resolved so re-imports of chapterless variants of the
                    // same hadith never null out an existing linkage (idempotent).
                    if ($chapter !== null) {
                        $hadithData['chapter_id'] = $chapter->id;
                    }
                    $hadithData['source_file'] = $this->compactSourceFile($record['source_file'] ?? null);
                    $hadithData['text_hash'] ??= $this->textHashFromTranslations($record['translations']);

                    if (($hadithData['verification_status'] ?? null) === 'source_verified' && empty($hadithData['source_verified_at'])) {
                        $hadithData['source_verified_at'] = now();
                    }

                    if (! empty($record['historical_context'])) {
                        $hadithData['historical_context_ar'] = $record['historical_context']['occasion_ar'] ?? '';
                        $hadithData['historical_context_en'] = $record['historical_context']['occasion_en'] ?? '';
                    }

                    $hadith = $this->upsertHadith(
                        $hadithData,
                        $book?->id,
                        $dryRun,
                        $this->primaryTranslationText($record['translations']),
                    );
                    if ($hadith) {
                        $summary['hadiths']++;
                    }

                    // Skip rich metadata on dry-run or when hadith write failed.
                    if ($dryRun || ! $hadith) {
                        return;
                    }

                    // 6–14. Rich metadata
                    $summary['translations'] += $this->importTranslations($record['translations'], $hadith->id);
                    $summary['narrators'] += $this->importNarrators($record['narrators'], $hadith->id);
                    $summary['themes'] += $this->importThemes($record['themes'], $hadith->id);
                    $summary['keywords'] += $this->importKeywords($record['keywords'], $hadith->id);
                    $summary['gradings'] += $this->importGrading($record['grading'], $hadith->id);
                    $summary['scholarly_comments'] += $this->importScholarlyComments($record['scholarly_comments'], $hadith->id);
                    $summary['related_verses'] += $this->importRelatedVerses($record['related_verses'], $hadith->id);
                    $summary['applications'] += $this->importApplications($record['applications'], $hadith->id);
                    $summary['fiqh_rulings'] += $this->importFiqhRulings($record['fiqh_rulings'], $hadith->id);
                    $summary['sources'] += $this->importSources($record['sources'], $hadith->id, $record['source_file'] ?? null);

                    if ($syncLog) {
                        $syncLog->updateProgress($summary);
                    }
                });
            } catch (Exception $e) {
                Log::error('Failed to import single hadith record', [
                    'collection' => $record['collection']['name_en'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);

                if ($syncLog) {
                    $syncLog->incrementFailed();
                }
                // Continue with the next record.
            }
        }

        return $summary;
    }

    /**
     * Persist a collection-based payload.
     *
     * @param  array  $payload  Output of HadithImportMapper::map() with type='collection_based'.
     * @param  HadithSyncLog|null  $syncLog  Optional progress tracker.
     * @param  bool  $dryRun  When true, no writes are performed.
     * @return array Summary statistics keyed by entity type.
     *
     * @throws Exception On unrecoverable failures (re-thrown after logging).
     */
    public function persistCollectionBased(array $payload, ?HadithSyncLog $syncLog, bool $dryRun): array
    {
        $summary = [
            'collections' => 0,
            'books' => 0,
            'chapters' => 0,
            'hadiths' => 0,
            'translations' => 0,
            'sources' => 0,
        ];

        try {
            $collection = $this->upsertCollection($payload['collection'], $dryRun);
            if ($collection) {
                $summary['collections'] = 1;
            }

            $book = $this->upsertBook($payload['book'], $collection?->id, $dryRun);
            if ($book) {
                $summary['books'] = 1;
            }

            foreach ($payload['chapters'] as $chapterData) {
                $chapter = $this->upsertChapter($chapterData, $book?->id, $dryRun);
                if ($chapter) {
                    $summary['chapters']++;
                }
            }

            foreach ($payload['hadiths'] as $hadithRecord) {
                $hadithData = $hadithRecord['hadith'];
                $hadithData['source_file'] = $this->compactSourceFile($hadithRecord['source_file'] ?? null);
                $hadithData['text_hash'] ??= $this->textHashFromTranslations($hadithRecord['translations']);

                $hadith = $this->upsertHadith(
                    $hadithData,
                    $book?->id,
                    $dryRun,
                    $this->primaryTranslationText($hadithRecord['translations']),
                );
                if ($hadith) {
                    $summary['hadiths']++;

                    if (! $dryRun) {
                        $summary['translations'] += $this->importTranslations(
                            $hadithRecord['translations'],
                            $hadith->id
                        );
                        $summary['sources'] += $this->importSources(
                            $hadithRecord['sources'] ?? [],
                            $hadith->id,
                            $hadithRecord['source_file'] ?? null
                        );
                    }
                }
            }

            if ($syncLog) {
                $syncLog->updateProgress($summary['hadiths'], 0, $summary);
            }
        } catch (Exception $e) {
            Log::error('Failed to import collection-based format', [
                'error' => $e->getMessage(),
            ]);

            if ($syncLog) {
                $syncLog->markFailed($e->getMessage());
            }

            throw $e;
        }

        return $summary;
    }

    // =========================================================================
    // UPSERT METHODS — CORE ENTITIES
    // =========================================================================

    protected function upsertCollection(array $data, bool $dryRun): ?HadithCollection
    {
        if ($dryRun) {
            Log::info('[DRY RUN] Would upsert collection', $data);

            return null;
        }

        return HadithCollection::updateOrCreate(
            ['name_en' => $data['name_en']],
            $data
        );
    }

    protected function upsertBook(array $data, ?int $collectionId, bool $dryRun): ?HadithBook
    {
        if ($dryRun || ! $collectionId) {
            Log::info('[DRY RUN] Would upsert book', $data);

            return null;
        }

        $data['collection_id'] = $collectionId;

        // Use collection_id + title_en as unique key; fall back to title_ar.
        $uniqueKey = [
            'collection_id' => $collectionId,
            'title_en' => $data['title_en'] ?? null,
        ];

        if (empty($uniqueKey['title_en']) && ! empty($data['title_ar'])) {
            $uniqueKey = [
                'collection_id' => $collectionId,
                'title_ar' => $data['title_ar'],
            ];
        }

        return HadithBook::updateOrCreate($uniqueKey, $data);
    }

    protected function upsertChapter(array $data, ?int $bookId, bool $dryRun): ?HadithChapter
    {
        if ($dryRun || ! $bookId) {
            Log::info('[DRY RUN] Would upsert chapter', $data);

            return null;
        }

        $data['book_id'] = $bookId;

        return HadithChapter::updateOrCreate(
            [
                'book_id' => $bookId,
                'chapter_number' => $data['chapter_number'],
            ],
            $data
        );
    }

    protected function upsertHadith(array $data, ?int $bookId, bool $dryRun, ?string $primaryText = null): ?Hadith
    {
        if ($dryRun || ! $bookId) {
            Log::info('[DRY RUN] Would upsert hadith', $data);

            return null;
        }

        $data['book_id'] = $bookId;

        $hadithNumber = (string) ($data['hadith_number'] ?? '');

        // text_hash values in this table were written by two historical formulas
        // (trim-only vs inner-whitespace-collapsed). Match against both so a hadith
        // that is genuinely the same is never treated as new.
        $candidateHashes = $this->equivalentTextHashes($primaryText, $data['text_hash'] ?? null);

        $existing = Hadith::query()
            ->where('book_id', $bookId)
            ->where('hadith_number', $hadithNumber)
            ->first();

        // No row at this (book, hadith_number) slot, or the slot already holds the
        // same text — safe to upsert in place (idempotent, never duplicates).
        if ($existing === null || $candidateHashes === [] || in_array($existing->text_hash, $candidateHashes, true)) {
            return Hadith::updateOrCreate(
                [
                    'book_id' => $bookId,
                    'hadith_number' => $hadithNumber,
                ],
                $data
            );
        }

        // The slot is occupied by a DIFFERENT hadith. This happens when the source
        // assigns the same reference number to two distinct hadiths. Overwriting
        // would silently drop a real hadith, so instead place this distinct text
        // under a non-colliding, suffixed number — matching the existing
        // "<number> <letter>" convention already present in this data.
        $sameText = Hadith::query()
            ->where('book_id', $bookId)
            ->whereIn('text_hash', $candidateHashes)
            ->first();

        if ($sameText !== null) {
            // This exact text is already stored for this book under another number —
            // update it in place rather than creating a duplicate row.
            $sameText->update($data);

            return $sameText;
        }

        $data['hadith_number'] = $this->nextAvailableHadithNumber($bookId, $hadithNumber);

        return Hadith::create($data);
    }

    /**
     * Find the next free "<base> <letter>" hadith number for a book so a distinct
     * hadith colliding on a duplicated source reference can be stored without
     * violating the unique (book_id, hadith_number) constraint or losing data.
     */
    private function nextAvailableHadithNumber(int $bookId, string $baseNumber): string
    {
        foreach (range('a', 'z') as $suffix) {
            $candidate = "{$baseNumber} {$suffix}";

            $taken = Hadith::query()
                ->where('book_id', $bookId)
                ->where('hadith_number', $candidate)
                ->exists();

            if (! $taken) {
                return $candidate;
            }
        }

        // Exhausted single letters (extremely unlikely): fall back to a unique
        // numeric suffix derived from the book scope.
        return $baseNumber.' '.(Hadith::query()->where('book_id', $bookId)->max('id') + 1);
    }

    /**
     * Return every text_hash variant that represents the same hadith text.
     *
     * Historical rows were hashed with two different normalisations (plain trim
     * vs inner-whitespace collapse). Returning both — plus any precomputed hash —
     * lets collision detection treat a genuinely identical hadith as a match
     * regardless of which formula wrote the existing row.
     *
     * @return list<string>
     */
    private function equivalentTextHashes(?string $primaryText, ?string $precomputedHash): array
    {
        $hashes = [];

        if (is_string($precomputedHash) && $precomputedHash !== '') {
            $hashes[] = $precomputedHash;
        }

        if (is_string($primaryText) && trim($primaryText) !== '') {
            $hashes[] = hash('sha256', trim($primaryText));
            $hashes[] = hash('sha256', trim((string) preg_replace('/\s+/', ' ', $primaryText)));
        }

        return array_values(array_unique($hashes));
    }

    /**
     * Extract the first non-empty translation text from a record's translation
     * list, used as the source for equivalent-hash computation during dedup.
     */
    private function primaryTranslationText(array $translations): ?string
    {
        foreach ($translations as $translation) {
            $text = $translation['text'] ?? $translation['matn'] ?? null;
            if (is_string($text) && trim($text) !== '') {
                return $text;
            }
        }

        return null;
    }

    // =========================================================================
    // IMPORT METHODS — RICH METADATA
    // =========================================================================

    protected function importTranslations(array $translations, int $hadithId): int
    {
        $count = 0;

        foreach ($translations as $translationData) {
            try {
                if (isset($translationData['matn']) && ! isset($translationData['text'])) {
                    $translationData['text'] = $translationData['matn'];
                    unset($translationData['matn']);
                }

                if (empty($translationData['text'] ?? null)) {
                    continue;
                }

                $translationData['hadith_id'] = $hadithId;
                $translationData['verified'] = (bool) ($translationData['verified'] ?? false);
                $translationData['is_primary'] = (bool) ($translationData['is_primary'] ?? false);

                HadithTranslation::updateOrCreate(
                    [
                        'hadith_id' => $hadithId,
                        'language_code' => $translationData['language_code'],
                    ],
                    $translationData
                );

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import translation', [
                    'hadith_id' => $hadithId,
                    'language' => $translationData['language_code'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function importNarrators(array $narrators, int $hadithId): int
    {
        $count = 0;

        foreach ($narrators as $narratorData) {
            try {
                $nameAr = trim((string) ($narratorData['name_ar'] ?? ''));
                $nameEn = trim((string) ($narratorData['name_en'] ?? ''));
                $sourceSystem = $narratorData['source_system'] ?? null;
                $sourceNarratorId = $narratorData['source_narrator_id'] ?? null;

                if ($nameAr === '' && $nameEn === '' && empty($sourceNarratorId)) {
                    continue;
                }

                $uniqueKey = ! empty($sourceSystem) && ! empty($sourceNarratorId)
                    ? ['source_system' => $sourceSystem, 'source_narrator_id' => (string) $sourceNarratorId]
                    : ['name_ar' => $nameAr !== '' ? $nameAr : $nameEn];

                $narrator = HadithNarrator::updateOrCreate(
                    $uniqueKey,
                    [
                        'name_ar' => $nameAr !== '' ? $nameAr : $nameEn,
                        'name_en' => $nameEn !== '' ? $nameEn : null,
                        'full_name_ar' => $narratorData['full_name_ar'] ?? null,
                        'full_name_en' => $narratorData['full_name_en'] ?? null,
                        'birth_year_hijri' => $narratorData['birth_year_hijri'] ?? null,
                        'death_year_hijri' => $narratorData['death_year_hijri'] ?? null,
                        'reliability_grade' => $narratorData['reliability_grade'] ?? null,
                        'biography' => $narratorData['biography_ar'] ?? $narratorData['biography_en'] ?? null,
                        'source_system' => $sourceSystem,
                        'source_narrator_id' => $sourceNarratorId,
                    ]
                );

                HadithIsnad::updateOrCreate(
                    [
                        'hadith_id' => $hadithId,
                        'narrator_id' => $narrator->id,
                        'position_in_chain' => (int) ($narratorData['position_in_chain'] ?? 0),
                    ],
                    [
                        'transmission_method' => $narratorData['transmission_method'] ?? null,
                    ]
                );

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import narrator', [
                    'hadith_id' => $hadithId,
                    'narrator' => $narratorData['name_en'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function importThemes(array $themes, int $hadithId): int
    {
        $count = 0;

        foreach ($themes as $themeData) {
            try {
                if (empty($themeData['name_ar']) && empty($themeData['name_en'])) {
                    continue;
                }

                $theme = HadithTheme::updateOrCreate(
                    ['theme_ar' => $themeData['name_ar']],
                    [
                        'theme_en' => $themeData['name_en'],
                        'review_status' => $this->reviewStatus($themeData),
                        'source_reference' => $themeData['source_reference'] ?? null,
                    ]
                );

                $hadith = Hadith::find($hadithId);
                if ($hadith && ! $hadith->themes()->where('hadith_theme_id', $theme->id)->exists()) {
                    $hadith->themes()->attach($theme->id);
                }

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import theme', [
                    'hadith_id' => $hadithId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function importKeywords(array $keywords, int $hadithId): int
    {
        $count = 0;

        foreach ($keywords as $keywordData) {
            try {
                $keyword = $keywordData['keyword'] ?? null;
                $language = $keywordData['language_code'] ?? 'ar';

                if (empty($keyword)) {
                    continue;
                }

                $data = ['hadith_id' => $hadithId];

                if ($language === 'ar') {
                    $data['keyword_ar'] = $keyword;
                } else {
                    $data['keyword_en'] = $keyword;
                }

                $data['review_status'] = $this->reviewStatus($keywordData);
                $data['source_reference'] = $keywordData['source_reference'] ?? null;

                $column = $language === 'ar' ? 'keyword_ar' : 'keyword_en';
                $existing = HadithKeyword::where('hadith_id', $hadithId)
                    ->where($column, $keyword)
                    ->first();

                if (! $existing) {
                    HadithKeyword::create($data);
                    $count++;
                }
            } catch (Exception $e) {
                Log::error('Failed to import keyword', [
                    'hadith_id' => $hadithId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function importGrading(array $grading, int $hadithId): int
    {
        $count = 0;

        foreach ($grading as $gradingData) {
            try {
                // Support multiple field name conventions from the transformer.
                $scholarName = $gradingData['scholar_name']
                    ?? $gradingData['scholar_name_en']
                    ?? $gradingData['scholar_name_ar']
                    ?? null;

                if (empty($scholarName)) {
                    continue;
                }

                $scholar = HadithScholar::updateOrCreate(
                    ['name_en' => $scholarName],
                    ['name_ar' => $gradingData['scholar_name_ar'] ?? $scholarName]
                );

                $gradeEn = $gradingData['grading_level']
                    ?? $gradingData['grade_en']
                    ?? $gradingData['grade']
                    ?? 'unknown';
                $gradeAr = $gradingData['grade_ar'] ?? null;
                $gradeCode = strtolower(str_replace([' ', "'", '-'], ['_', '', '_'], $gradeEn));

                $gradingLevel = HadithGradingLevel::firstOrCreate(
                    ['grade_en' => $gradeCode],
                    [
                        'grade_ar' => $gradeAr ?? $gradeEn,
                        'description' => null,
                        'rank' => $this->getGradeRank($gradeCode),
                    ]
                );

                $notes = $gradingData['notes_en']
                    ?? $gradingData['notes_ar']
                    ?? $gradingData['notes']
                    ?? null;

                HadithGrading::updateOrCreate(
                    [
                        'hadith_id' => $hadithId,
                        'scholar_id' => $scholar->id,
                    ],
                    [
                        'grading_level_id' => $gradingLevel->id,
                        'grading_notes' => $notes,
                        'source_reference' => $gradingData['source_reference'] ?? null,
                        'source_url' => $gradingData['source_url'] ?? null,
                        'source_name' => $gradingData['source_name'] ?? null,
                        'review_status' => $this->reviewStatus($gradingData),
                    ]
                );

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import grading', [
                    'hadith_id' => $hadithId,
                    'scholar' => $gradingData['scholar_name_en'] ?? $gradingData['scholar_name_ar'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function importScholarlyComments(array $comments, int $hadithId): int
    {
        $count = 0;

        foreach ($comments as $commentData) {
            try {
                $scholarName = $commentData['scholar_name_en'] ?? $commentData['scholar_name_ar'];
                if (empty($scholarName)) {
                    continue;
                }

                $scholar = HadithScholar::updateOrCreate(
                    ['name_en' => $scholarName],
                    [
                        'name_ar' => $commentData['scholar_name_ar'] ?? $scholarName,
                        'full_name_ar' => $commentData['scholar_name_ar'] ?? null,
                        'full_name_en' => $commentData['scholar_name_en'] ?? null,
                    ]
                );

                if (! empty($commentData['commentary_ar'])) {
                    HadithCommentary::updateOrCreate(
                        [
                            'hadith_id' => $hadithId,
                            'author' => $scholarName,
                            'language_code' => 'ar',
                        ],
                        [
                            'scholar_id' => $scholar->id,
                            'commentary_text' => $commentData['commentary_ar'],
                            'source' => $commentData['source_reference'] ?? null,
                            'source_url' => $commentData['source_url'] ?? null,
                            'verified' => (bool) ($commentData['verified'] ?? false),
                            'review_status' => $this->reviewStatus($commentData),
                        ]
                    );
                }

                if (! empty($commentData['commentary_en'])) {
                    HadithCommentary::updateOrCreate(
                        [
                            'hadith_id' => $hadithId,
                            'author' => $scholarName,
                            'language_code' => 'en',
                        ],
                        [
                            'scholar_id' => $scholar->id,
                            'commentary_text' => $commentData['commentary_en'],
                            'source' => $commentData['source_reference'] ?? null,
                            'source_url' => $commentData['source_url'] ?? null,
                            'verified' => (bool) ($commentData['verified'] ?? false),
                            'review_status' => $this->reviewStatus($commentData),
                        ]
                    );
                }

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import scholarly comment', [
                    'hadith_id' => $hadithId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function importRelatedVerses(array $verses, int $hadithId): int
    {
        $count = 0;

        foreach ($verses as $verseData) {
            try {
                HadithRelatedVerse::updateOrCreate(
                    [
                        'hadith_id' => $hadithId,
                        'surah' => $verseData['surah'],
                        'ayah' => $verseData['ayah'],
                    ],
                    [
                        'surah_name_ar' => $verseData['surah_name_ar'],
                        'surah_name_en' => $verseData['surah_name_en'],
                        'text_ar' => $verseData['text_ar'],
                        'text_en' => $verseData['text_en'],
                        'relevance' => $verseData['relevance'],
                        'quran_verse_id' => $verseData['quran_verse_id'] ?? null,
                        'review_status' => $this->reviewStatus($verseData),
                        'source_reference' => $verseData['source_reference'] ?? null,
                        'source_url' => $verseData['source_url'] ?? null,
                    ]
                );

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import related verse', [
                    'hadith_id' => $hadithId,
                    'surah' => $verseData['surah'] ?? 'unknown',
                    'ayah' => $verseData['ayah'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function importApplications(array $applications, int $hadithId): int
    {
        $count = 0;
        $order = 0;

        foreach ($applications as $appData) {
            try {
                $uniqueKey = ['hadith_id' => $hadithId, 'category_en' => $appData['category_en']];

                if (empty($appData['category_en']) && ! empty($appData['category_ar'])) {
                    $uniqueKey = ['hadith_id' => $hadithId, 'category_ar' => $appData['category_ar']];
                }

                HadithApplication::updateOrCreate(
                    $uniqueKey,
                    [
                        'category_ar' => $appData['category_ar'],
                        'category_en' => $appData['category_en'],
                        'description_ar' => $appData['description_ar'],
                        'description_en' => $appData['description_en'],
                        'display_order' => $order++,
                        'review_status' => $this->reviewStatus($appData),
                        'source_reference' => $appData['source_reference'] ?? null,
                        'source_url' => $appData['source_url'] ?? null,
                    ]
                );

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import application', [
                    'hadith_id' => $hadithId,
                    'category' => $appData['category_en'] ?? $appData['category_ar'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function importFiqhRulings(array $rulings, int $hadithId): int
    {
        $count = 0;
        $order = 0;

        foreach ($rulings as $rulingData) {
            try {
                HadithFiqhRuling::updateOrCreate(
                    [
                        'hadith_id' => $hadithId,
                        'principle' => $rulingData['principle'],
                        'language_code' => $rulingData['language_code'],
                    ],
                    [
                        'scholar_id' => $rulingData['scholar_id'] ?? null,
                        'display_order' => $order++,
                        'review_status' => $this->reviewStatus($rulingData),
                        'source_reference' => $rulingData['source_reference'] ?? null,
                        'source_url' => $rulingData['source_url'] ?? null,
                    ]
                );

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import fiqh ruling', [
                    'hadith_id' => $hadithId,
                    'principle' => mb_substr($rulingData['principle'] ?? '', 0, 50),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Return an initialised summary array covering every entity type that the
     * single-hadith path tracks.
     */
    private function emptySummary(): array
    {
        return [
            'collections' => 0,
            'books' => 0,
            'chapters' => 0,
            'hadiths' => 0,
            'translations' => 0,
            'narrators' => 0,
            'companions' => 0,
            'themes' => 0,
            'keywords' => 0,
            'gradings' => 0,
            'scholarly_comments' => 0,
            'related_verses' => 0,
            'applications' => 0,
            'fiqh_rulings' => 0,
            'sources' => 0,
        ];
    }

    private function importSources(array $sources, int $hadithId, ?string $sourceFile): int
    {
        $count = 0;

        foreach ($sources as $sourceData) {
            try {
                $url = $sourceData['url'] ?? null;
                $reference = $sourceData['reference'] ?? null;

                if (empty($url) && empty($reference)) {
                    continue;
                }

                $sourceData['hadith_id'] = $hadithId;
                $sourceData['source_file'] = $this->compactSourceFile($sourceFile);
                $sourceData['source_type'] = $sourceData['source_type'] ?? 'source';
                $sourceData['title'] = $sourceData['title'] ?? 'Unknown source';
                $sourceData['verified_at'] = $sourceData['verified_at'] ?? null;

                HadithSource::updateOrCreate(
                    [
                        'hadith_id' => $hadithId,
                        'source_file' => $sourceData['source_file'],
                        'url' => $url,
                    ],
                    $sourceData
                );

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import hadith source', [
                    'hadith_id' => $hadithId,
                    'url' => $sourceData['url'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    private function textHashFromTranslations(array $translations): ?string
    {
        foreach ($translations as $translation) {
            $text = $translation['text'] ?? $translation['matn'] ?? null;
            if (is_string($text) && trim($text) !== '') {
                return hash('sha256', trim($text));
            }
        }

        return null;
    }

    private function reviewStatus(array $data, string $default = 'generated_unreviewed'): string
    {
        if (! empty($data['review_status'])) {
            return (string) $data['review_status'];
        }

        if (! empty($data['verified'])) {
            return 'source_verified';
        }

        return $default;
    }

    private function compactSourceFile(?string $filePath): ?string
    {
        if (empty($filePath)) {
            return null;
        }

        $parent = basename(dirname($filePath));
        $file = basename($filePath);
        $compact = $parent.'/'.$file;

        return mb_substr($compact, 0, 255);
    }

    /**
     * Return a numeric rank for a normalised grade code (lower = more authentic).
     */
    protected function getGradeRank(string $gradeCode): int
    {
        $ranks = [
            'sahih' => 1,
            'sahih_lighairihi' => 2,
            'hasan' => 3,
            'hasan_lighairihi' => 4,
            'hasan_sahih' => 3,
            'daif' => 5,
            'daif_jiddan' => 6,
            'very_weak' => 6,
            'munkar' => 7,
            'shadh' => 7,
            'mursal' => 8,
            'munqati' => 8,
            'mudallas' => 8,
            'mawdu' => 9,
            'fabricated' => 9,
        ];

        return $ranks[$gradeCode] ?? 10;
    }
}
