<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Importers;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithCommentary;
use App\Domain\Hadith\Models\HadithGrading;
use App\Domain\Hadith\Models\HadithIsnad;
use App\Domain\Hadith\Models\HadithKeyword;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Hadith\Models\HadithRelatedVerse;
use App\Domain\Hadith\Models\HadithScholar;
use App\Domain\Hadith\Models\HadithSource;
use App\Domain\Hadith\Models\HadithSyncLog;
use App\Domain\Hadith\Models\HadithTheme;
use App\Domain\Hadith\Models\HadithTranslation;
use App\Domain\Hadith\Repositories\HadithRebuildRepository;
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
 *     gradings, scholarly commentary, related verses, and sources).
 *   - Counts successes and errors for each category.
 *   - Reports progress to an optional HadithSyncLog.
 *
 * No file I/O or JSON decoding happens here.
 */
class HadithImportPersister
{
    public function __construct(
        private readonly HadithRebuildRepository $rebuildRepository,
    ) {}

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

                    // 4. Primary narrator (the former companion role)
                    $primaryNarrator = null;
                    if (! empty($record['companion']) && ! $dryRun) {
                        $primaryNarrator = HadithNarrator::resolveOrCreate([
                            'name_ar' => $record['companion']['name_ar'] ?? $record['companion']['name'] ?? '',
                            'name_en' => $record['companion']['name_en'] ?? null,
                        ]);
                        if ($primaryNarrator) {
                            $summary['narrators']++;
                        }
                    }

                    // 5. Hadith core record
                    $hadithData = $record['hadith'];
                    $hadithData['primary_narrator_id'] = $primaryNarrator?->id;

                    // Link the hadith to its resolved chapter. Only set the key when a
                    // chapter was resolved so re-imports of chapterless variants of the
                    // same hadith never null out an existing linkage (idempotent).
                    if ($chapter !== null) {
                        $hadithData['chapter_id'] = $chapter->id;
                    }
                    $hadithData['text_hash'] ??= $this->textHashFromTranslations($record['translations']);

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
                    $syncLog->markFailed($e->getMessage());
                }
                // Continue with the next record.
            }
        }

        return $summary;
    }

    public function quranVerseIds(array $coordinates): array
    {
        return $this->rebuildRepository->quranVerseIds($coordinates);
    }

    public function persistRebuildCollection(array $package): array
    {
        return $this->rebuildRepository->replaceCollection(
            $package['collection'],
            $package['tables'],
        );
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
                $syncLog->updateProgress($summary);
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

        $code = strtolower((string) ($data['code'] ?? $data['canonical_code'] ?? $data['source_slug'] ?? $data['slug_en'] ?? ''));
        $code = mb_substr($code, 0, 50);

        return HadithCollection::updateOrCreate(
            ['code' => $code],
            [
                'slug' => $data['slug'] ?? $data['slug_en'] ?? $data['slug_ar'] ?? $code,
                'name' => array_filter([
                    'ar' => $data['name_ar'] ?? null,
                    'en' => $data['name_en'] ?? null,
                ]),
                'description' => array_filter([
                    'ar' => $data['description_ar'] ?? null,
                    'en' => $data['description_en'] ?? null,
                ]),
                'abbreviation' => $data['abbreviation'] ?? null,
                'featured' => (bool) ($data['featured'] ?? false),
                'active' => (bool) ($data['active'] ?? true),
                'display_order' => (int) ($data['display_order'] ?? 0),
            ],
        );
    }

    protected function upsertBook(array $data, ?int $collectionId, bool $dryRun): ?HadithBook
    {
        if ($dryRun || ! $collectionId) {
            Log::info('[DRY RUN] Would upsert book', $data);

            return null;
        }

        $bookNumber = (string) ($data['book_number'] ?? '1');

        return HadithBook::updateOrCreate(
            ['collection_id' => $collectionId, 'book_number' => $bookNumber],
            [
                'title' => array_filter([
                    'ar' => $data['title_ar'] ?? null,
                    'en' => $data['title_en'] ?? null,
                ]),
                'description' => array_filter([
                    'ar' => $data['description_ar'] ?? $data['chapter_title_ar'] ?? null,
                    'en' => $data['description_en'] ?? $data['chapter_title_en'] ?? null,
                ]),
            ],
        );
    }

    protected function upsertChapter(array $data, ?int $bookId, bool $dryRun): ?HadithChapter
    {
        if ($dryRun || ! $bookId) {
            Log::info('[DRY RUN] Would upsert chapter', $data);

            return null;
        }

        return HadithChapter::updateOrCreate(
            [
                'book_id' => $bookId,
                'chapter_number' => $data['chapter_number'],
            ],
            [
                'name' => array_filter([
                    'ar' => $data['name_ar'] ?? $data['title_ar'] ?? null,
                    'en' => $data['name_en'] ?? $data['title_en'] ?? null,
                ]),
                'description' => array_filter([
                    'ar' => $data['description_ar'] ?? $data['intro_ar'] ?? null,
                    'en' => $data['description_en'] ?? $data['intro_en'] ?? null,
                ]),
                'display_order' => (int) ($data['display_order'] ?? $data['chapter_number']),
            ],
        );
    }

    protected function upsertHadith(array $data, ?int $bookId, bool $dryRun, ?string $primaryText = null): ?Hadith
    {
        if ($dryRun || ! $bookId) {
            Log::info('[DRY RUN] Would upsert hadith', $data);

            return null;
        }

        $hadithNumber = (string) ($data['hadith_number'] ?? '');
        $chapterId = isset($data['chapter_id'])
            ? (int) $data['chapter_id']
            : (int) HadithChapter::query()
                ->where('book_id', $bookId)
                ->orderBy('display_order')
                ->value('id');

        if ($chapterId === 0) {
            throw new \InvalidArgumentException("Hadith {$hadithNumber} has no target chapter");
        }

        $targetData = [
            'chapter_id' => $chapterId,
            'primary_narrator_id' => $data['primary_narrator_id'] ?? null,
            'hadith_number' => $hadithNumber,
            'text_hash' => $data['text_hash'] ?? null,
        ];

        // text_hash values in this table were written by two historical formulas
        // (trim-only vs inner-whitespace-collapsed). Match against both so a hadith
        // that is genuinely the same is never treated as new.
        $candidateHashes = $this->equivalentTextHashes($primaryText, $data['text_hash'] ?? null);

        $existing = Hadith::query()
            ->where('chapter_id', $chapterId)
            ->where('hadith_number', $hadithNumber)
            ->first();

        // No row at this (book, hadith_number) slot, or the slot already holds the
        // same text — safe to upsert in place (idempotent, never duplicates).
        if ($existing === null || $candidateHashes === [] || in_array($existing->text_hash, $candidateHashes, true)) {
            return Hadith::updateOrCreate(
                [
                    'chapter_id' => $chapterId,
                    'hadith_number' => $hadithNumber,
                ],
                $targetData,
            );
        }

        // The slot is occupied by a DIFFERENT hadith. This happens when the source
        // assigns the same reference number to two distinct hadiths. Overwriting
        // would silently drop a real hadith, so instead place this distinct text
        // under a non-colliding, suffixed number — matching the existing
        // "<number> <letter>" convention already present in this data.
        $sameText = Hadith::query()
            ->byBook($bookId)
            ->whereIn('text_hash', $candidateHashes)
            ->first();

        if ($sameText !== null) {
            // This exact text is already stored for this book under another number —
            // update it in place rather than creating a duplicate row.
            $sameText->update($targetData);

            return $sameText;
        }

        $targetData['hadith_number'] = $this->nextAvailableHadithNumber($bookId, $hadithNumber);

        return Hadith::create($targetData);
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
                ->byBook($bookId)
                ->where('hadith_number', $candidate)
                ->exists();

            if (! $taken) {
                return $candidate;
            }
        }

        // Exhausted single letters (extremely unlikely): fall back to a unique
        // numeric suffix derived from the book scope.
        return $baseNumber.' '.(Hadith::query()->byBook($bookId)->max('id') + 1);
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

                $locale = $translationData['locale'] ?? $translationData['language_code'] ?? 'ar';

                HadithTranslation::updateOrCreate(
                    [
                        'hadith_id' => $hadithId,
                        'locale' => $locale,
                    ],
                    [
                        'text' => $translationData['text'],
                        'search_text' => $translationData['search_text'] ?? null,
                        'translator_name' => $translationData['translator_name'] ?? $translationData['translator'] ?? null,
                        'source_urn' => $translationData['source_urn'] ?? $translationData['urn'] ?? null,
                        'source_url' => $translationData['source_url'] ?? null,
                        'notes' => $translationData['notes'] ?? null,
                    ],
                );

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import translation', [
                    'hadith_id' => $hadithId,
                    'language' => $translationData['locale'] ?? $translationData['language_code'] ?? 'unknown',
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
                if ($nameAr === '' && $nameEn === '') {
                    continue;
                }

                $narrator = HadithNarrator::resolveOrCreate(
                    [
                        'name_ar' => $nameAr !== '' ? $nameAr : $nameEn,
                        'name_en' => $nameEn !== '' ? $nameEn : null,
                        'full_name_ar' => $narratorData['full_name_ar'] ?? null,
                        'full_name_en' => $narratorData['full_name_en'] ?? null,
                        'birth_year_hijri' => $narratorData['birth_year_hijri'] ?? null,
                        'death_year_hijri' => $narratorData['death_year_hijri'] ?? null,
                        'reliability_grade' => $narratorData['reliability_grade'] ?? null,
                        'biography_ar' => $narratorData['biography_ar'] ?? null,
                        'biography_en' => $narratorData['biography_en'] ?? null,
                    ],
                );

                HadithIsnad::updateOrCreate(
                    [
                        'hadith_id' => $hadithId,
                        'narrator_id' => $narrator->id,
                    ],
                    [
                        'position_in_chain' => max(1, (int) ($narratorData['position_in_chain'] ?? 1)),
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
                    ['slug' => $themeData['slug'] ?? null],
                    [
                        'name' => array_filter([
                            'ar' => $themeData['name_ar'] ?? null,
                            'en' => $themeData['name_en'] ?? null,
                        ]),
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
                $language = $keywordData['locale'] ?? $keywordData['language_code'] ?? 'ar';

                if (empty($keyword)) {
                    continue;
                }

                HadithKeyword::query()->firstOrCreate(
                    ['hadith_id' => $hadithId, 'locale' => $language, 'keyword' => $keyword],
                    [
                        'review_status' => $this->reviewStatus($keywordData),
                        'source_reference' => $keywordData['source_reference'] ?? null,
                    ],
                );
                $count++;
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

                $scholar = $this->findOrCreateScholar(
                    (string) ($gradingData['scholar_name_ar'] ?? $scholarName),
                    (string) ($gradingData['scholar_name_en'] ?? $scholarName),
                );

                $gradeEn = $gradingData['grading_level']
                    ?? $gradingData['grade_en']
                    ?? $gradingData['grade']
                    ?? 'unknown';
                $gradeCode = $this->canonicalGradeKey((string) $gradeEn);

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
                        'grade_key' => $gradeCode,
                        'raw_grade' => (string) ($gradingData['grade_ar'] ?? $gradeEn),
                        'notes' => $notes,
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

                $scholar = $this->findOrCreateScholar(
                    (string) ($commentData['scholar_name_ar'] ?? $scholarName),
                    (string) ($commentData['scholar_name_en'] ?? $scholarName),
                );

                if (! empty($commentData['commentary_ar'])) {
                    HadithCommentary::updateOrCreate(
                        [
                            'hadith_id' => $hadithId,
                            'author_name' => $scholarName,
                            'locale' => 'ar',
                        ],
                        [
                            'scholar_id' => $scholar->id,
                            'text' => $commentData['commentary_ar'],
                            'source_reference' => $commentData['source_reference'] ?? null,
                            'source_url' => $commentData['source_url'] ?? null,
                            'review_status' => $this->reviewStatus($commentData),
                        ]
                    );
                }

                if (! empty($commentData['commentary_en'])) {
                    HadithCommentary::updateOrCreate(
                        [
                            'hadith_id' => $hadithId,
                            'author_name' => $scholarName,
                            'locale' => 'en',
                        ],
                        [
                            'scholar_id' => $scholar->id,
                            'text' => $commentData['commentary_en'],
                            'source_reference' => $commentData['source_reference'] ?? null,
                            'source_url' => $commentData['source_url'] ?? null,
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
                $quranVerseId = $verseData['quran_verse_id'] ?? DB::table('quran_verses')
                    ->where('surah_id', $verseData['surah'] ?? 0)
                    ->where('verse_number', $verseData['ayah'] ?? 0)
                    ->value('id');

                if ($quranVerseId === null) {
                    continue;
                }

                HadithRelatedVerse::updateOrCreate(
                    [
                        'hadith_id' => $hadithId,
                        'quran_verse_id' => $quranVerseId,
                    ],
                    [
                        'relevance' => is_array($verseData['relevance'] ?? null)
                            ? $verseData['relevance']
                            : array_filter(['ar' => $verseData['relevance'] ?? null]),
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
            'themes' => 0,
            'keywords' => 0,
            'gradings' => 0,
            'scholarly_comments' => 0,
            'related_verses' => 0,
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

                $sourceSystem = (string) ($sourceData['source_system'] ?? 'json_import');
                $externalId = (string) ($sourceData['external_id']
                    ?? $reference
                    ?? $url
                    ?? $this->compactSourceFile($sourceFile).":{$hadithId}");

                HadithSource::updateOrCreate(
                    [
                        'source_system' => $sourceSystem,
                        'external_id' => mb_substr($externalId, 0, 100),
                    ],
                    [
                        'hadith_id' => $hadithId,
                        'source_type' => $sourceData['source_type'] ?? 'source',
                        'source_file' => $this->compactSourceFile($sourceFile),
                        'title' => $sourceData['title'] ?? null,
                        'author' => $sourceData['author'] ?? null,
                        'reference' => $reference,
                        'url' => $url,
                        'verification_method' => $sourceData['verification_method'] ?? null,
                        'verified_at' => $sourceData['verified_at'] ?? null,
                    ],
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

    private function findOrCreateScholar(string $arabicName, string $englishName): HadithScholar
    {
        $scholar = HadithScholar::query()
            ->where('name->ar', $arabicName)
            ->orWhere('name->en', $englishName)
            ->first();

        return $scholar ?? HadithScholar::query()->create([
            'name' => array_filter(['ar' => $arabicName, 'en' => $englishName]),
        ]);
    }

    private function canonicalGradeKey(string $rawGrade): string
    {
        $normalized = strtolower($rawGrade);

        return match (true) {
            str_contains($normalized, 'mawdu'), str_contains($normalized, 'fabricat') => 'mawdu',
            str_contains($normalized, 'daif'), str_contains($normalized, 'weak') => 'daif',
            str_contains($normalized, 'hasan') => 'hasan',
            str_contains($normalized, 'sahih'), str_contains($normalized, 'authentic') => 'sahih',
            default => 'unknown',
        };
    }
}
