<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Enums\RevelationPlace;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTranslation;
use App\Domain\Quran\Repositories\SurahRepositoryInterface;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * @extends BaseService<SurahRepositoryInterface>
 */
class QuranService extends BaseService
{
    /** Locale of the Arabic source text rows in quran_verse_translations. */
    private const SOURCE_TEXT_LANG = 'ar';

    /**
     * QuranService constructor
     */
    public function __construct(
        private readonly SurahRepositoryInterface $surahRepository,
        private readonly VerseRepositoryInterface $verseRepository
    ) {
        // Note: QuranService uses multiple repositories, not extending BaseService fully
        // The surahRepository will be used as the primary repository for paginate() calls
        $this->repository = $surahRepository;
    }

    /**
     * Paginate verses for the admin list with surah + translations eager-loaded.
     *
     * The inherited paginate() uses the surah repository; verse listings must go
     * through the verse repository. Translations are batch-loaded (single query)
     * so the blade's per-row $verse->translations access does not trigger N+1.
     */
    public function paginateVerses(int $perPage = 15): LengthAwarePaginator
    {
        $verses = $this->verseRepository->paginateWithFilters($perPage, '', 'verse_key', []);
        $verses->getCollection()->load(Verse::readerTranslationsEagerLoad(app()->getLocale()));

        return $verses;
    }

    // ===================== SURAH METHODS =====================

    /**
     * Get all surahs
     */
    public function getAllSurahs(): Collection
    {
        return $this->surahRepository->all();
    }

    /**
     * Get surah by number
     *
     * @throws ResourceNotFoundException
     */
    public function getSurahByNumber(int $number): Surah
    {
        if ($number < 1 || $number > 114) {
            throw new ResourceNotFoundException("Invalid surah number: {$number}. Must be between 1 and 114.");
        }

        $surah = $this->surahRepository->getByNumber($number);

        if (! $surah) {
            throw new ResourceNotFoundException("Surah not found: {$number}");
        }

        return $surah;
    }

    /**
     * Get surahs by revelation place
     */
    public function getSurahsByRevelationPlace(RevelationPlace|string $place): Collection
    {
        return $this->surahRepository->getByRevelationPlace($place);
    }

    /**
     * Get surahs by Juz
     */
    public function getSurahsByJuz(int $juz): Collection
    {
        if ($juz < 1 || $juz > 30) {
            throw new ResourceNotFoundException("Invalid juz number: {$juz}. Must be between 1 and 30.");
        }

        return $this->surahRepository->getByJuz($juz);
    }

    // ===================== VERSE METHODS =====================

    /**
     * Get verses by surah number
     *
     * @throws ResourceNotFoundException
     */
    public function getVersesBySurahNumber(int $surahNumber): Collection
    {
        $surah = $this->getSurahByNumber($surahNumber);

        return $this->verseRepository->getBySurah($surah->id);
    }

    /**
     * Get single verse by surah and verse number
     *
     * @throws ResourceNotFoundException
     */
    public function getVerse(int $surahNumber, int $verseNumber): Verse
    {
        $verse = $this->verseRepository->getBySurahAndVerse($surahNumber, $verseNumber);

        if (! $verse) {
            throw new ResourceNotFoundException("Verse not found: {$surahNumber}:{$verseNumber}");
        }

        return $verse;
    }

    /**
     * Get verses by Juz
     */
    public function getVersesByJuz(int $juz, int $perPage = 50): LengthAwarePaginator
    {
        if ($juz < 1 || $juz > 30) {
            throw new ResourceNotFoundException("Invalid juz number: {$juz}. Must be between 1 and 30.");
        }

        return $this->verseRepository->getByJuz($juz, $perPage);
    }

    /**
     * Get verses by page number
     */
    public function getVersesByPage(int $page): Collection
    {
        if ($page < 1 || $page > 604) {
            throw new ResourceNotFoundException("Invalid page number: {$page}. Must be between 1 and 604.");
        }

        return $this->verseRepository->getByPage($page);
    }

    /**
     * Get all Sajda verses
     */
    public function getSajdaVerses(): Collection
    {
        return $this->verseRepository->getSajdaVerses();
    }

    /**
     * Get random verse (Verse of the Day)
     */
    public function getRandomVerse(): ?Verse
    {
        return $this->verseRepository->getRandomVerse();
    }

    // ===================== SEARCH METHODS =====================

    /**
     * Search in Arabic text
     */
    public function searchArabic(string $query, int $perPage = 20): LengthAwarePaginator
    {
        return $this->verseRepository->searchArabic($query, $perPage);
    }

    /**
     * Search in translations
     */
    public function searchTranslation(string $query, string $language = 'en', int $perPage = 20): LengthAwarePaginator
    {
        return $this->verseRepository->searchTranslation($query, $language, $perPage);
    }

    // ===================== IMPORT METHODS =====================

    /**
     * Import Surahs from JSON data
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function importSurahs(array $data, bool $truncate = false): array
    {
        $surahs = $data['data']['surahs'] ?? [];

        // Nothing to import: return before touching the table. The previous
        // version truncated first, so an empty payload wiped all 114 surahs and
        // cascaded to every verse under them.
        if ($surahs === []) {
            return ['total' => 0, 'created' => 0, 'updated' => 0, 'failed' => 0];
        }

        $created = 0;
        $updated = 0;
        $importedIds = [];

        DB::transaction(function () use ($surahs, &$created, &$updated, &$importedIds): void {
            foreach ($surahs as $surahData) {
                $number = (int) $surahData['number'];

                // The id IS the surah number, but it is not mass-assignable, so
                // updateOrCreate(['id' => …]) silently dropped it on insert and
                // let auto-increment pick the value — correct only by accident
                // when importing 1..114 in order into an emptied table.
                $surah = Surah::find($number) ?? tap(new Surah, fn (Surah $new) => $new->id = $number);
                $exists = $surah->exists;

                $surah->fill([
                    'name_ar' => $surahData['name'] ?? '',
                    'name_en' => $surahData['englishName'] ?? '',
                    'revelation_place' => $this->mapRevelationType($surahData['revelationType'] ?? ''),
                    'revelation_order' => $number,
                    'verses_count' => count($surahData['ayahs'] ?? []),
                    'bismillah_pre' => true,
                ])->save();

                $importedIds[] = $surah->id;
                $exists ? $updated++ : $created++;
            }
        });

        // Prune AFTER a successful import rather than truncating before one.
        // TRUNCATE is DDL and implicitly commits, so it could never have been
        // rolled back if the import then failed.
        if ($truncate) {
            Surah::query()->whereNotIn('id', $importedIds)->delete();
        }

        return [
            'total' => count($surahs),
            'created' => $created,
            'updated' => $updated,
            'failed' => 0,
        ];
    }

    /**
     * Import Verses (and their Arabic source text) from JSON data.
     *
     * Each chapter is written in one transaction with batched upserts, so a
     * mid-import failure rolls that chapter back and leaves every previously
     * imported chapter intact — the table is never left empty.
     *
     * @param  array<string, mixed>  $data
     * @return array{total:int, created:int, failed:int, removed:int}
     */
    public function importVerses(array $data, bool $truncate = false): array
    {
        /** @var array<int, array<string, mixed>> $surahs */
        $surahs = $data['data']['surahs'] ?? [];
        $knownSurahIds = Surah::query()->pluck('id')->flip();

        $total = 0;
        $created = 0;
        $failed = 0;
        $importedVerseIds = [];

        foreach ($surahs as $surahData) {
            $surahNumber = (int) ($surahData['number'] ?? 0);
            /** @var array<int, array<string, mixed>> $ayahs */
            $ayahs = $surahData['ayahs'] ?? [];
            $total += count($ayahs);

            $result = $knownSurahIds->has($surahNumber) && $ayahs !== []
                ? $this->importChapter($surahNumber, $ayahs)
                : $this->reportUnimportableChapter($surahNumber, $ayahs);

            if ($result === null) {
                $failed += count($ayahs);

                continue;
            }

            $created += $result['created'];
            $importedVerseIds = array_merge($importedVerseIds, $result['verse_ids']);
        }

        return [
            'total' => $total,
            'created' => $created,
            'failed' => $failed,
            'removed' => $truncate ? $this->pruneVersesNotImported($importedVerseIds, $failed) : 0,
        ];
    }

    /**
     * Import one chapter in its own transaction. Returns null (and logs) if the
     * chapter failed, so the run continues with the rest of the payload.
     *
     * @param  array<int, array<string, mixed>>  $ayahs
     * @return array{created:int, verse_ids:list<int>}|null
     */
    private function importChapter(int $surahNumber, array $ayahs): ?array
    {
        try {
            return DB::transaction(fn () => $this->importChapterVerses($surahNumber, $ayahs));
        } catch (Throwable $e) {
            // Log rather than swallow: the previous catch hid every error behind
            // a bare counter, so a schema or payload bug looked like a handful
            // of "failed" ayahs.
            Log::error('Quran verse import: chapter failed and was rolled back', [
                'surah' => $surahNumber,
                'ayahs' => count($ayahs),
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $ayahs
     */
    private function reportUnimportableChapter(int $surahNumber, array $ayahs): null
    {
        Log::warning('Quran verse import: skipped chapter', [
            'surah' => $surahNumber,
            'ayahs' => count($ayahs),
            'reason' => $ayahs === [] ? 'no ayahs in payload' : 'surah row not found',
        ]);

        return null;
    }

    /**
     * Upsert one chapter's verses plus their Arabic text.
     *
     * @param  array<int, array<string, mixed>>  $ayahs
     * @return array{created:int, verse_ids:list<int>}
     */
    private function importChapterVerses(int $surahNumber, array $ayahs): array
    {
        $existingIds = Verse::query()
            ->where('surah_id', $surahNumber)
            ->pluck('id', 'verse_number');

        $rows = [];
        foreach ($ayahs as $ayah) {
            $verseNumber = (int) ($ayah['numberInSurah'] ?? 0);
            if ($verseNumber < 1) {
                throw new InvalidArgumentException("Surah {$surahNumber}: ayah with missing numberInSurah.");
            }

            // Keyed by verse number so a duplicated ayah in the payload collapses
            // instead of blowing up the (surah_id, verse_number) unique index.
            $rows[$verseNumber] = [
                'surah_id' => $surahNumber,
                'verse_number' => $verseNumber,
                'verse_key' => "{$surahNumber}:{$verseNumber}",
                'juz_number' => (int) ($ayah['juz'] ?? 1),
                'hizb_number' => (int) ($ayah['hizb'] ?? 1),
                'rub_el_hizb_number' => (int) ($ayah['hizbQuarter'] ?? 1),
                'ruku_number' => (int) ($ayah['ruku'] ?? 1),
                'manzil_number' => isset($ayah['manzil']) ? (int) $ayah['manzil'] : null,
                'sajdah_number' => ($ayah['sajda'] ?? false) ? (int) $ayah['number'] : null,
                'page_number' => (int) ($ayah['page'] ?? 1),
            ];
        }

        Verse::query()->upsert(
            array_values($rows),
            ['surah_id', 'verse_number'],
            ['verse_key', 'juz_number', 'hizb_number', 'rub_el_hizb_number', 'ruku_number', 'manzil_number', 'sajdah_number', 'page_number'],
        );

        // Re-read: upsert returns an affected-row count, not the ids it minted.
        $verseIdByNumber = Verse::query()
            ->where('surah_id', $surahNumber)
            ->pluck('id', 'verse_number');

        $this->upsertArabicText($ayahs, $verseIdByNumber->all());

        $created = count(array_diff_key($rows, $existingIds->all()));

        return ['created' => $created, 'verse_ids' => array_values($verseIdByNumber->all())];
    }

    /**
     * Write the Arabic source text for a chapter's ayahs.
     *
     * These rows carry resource_id = NULL (the source text belongs to no
     * translation edition), and MySQL's (verse_id, resource_id) unique index
     * does not constrain NULLs — a plain upsert would duplicate every row on
     * re-import. So existing rows are matched by primary key instead.
     *
     * @param  array<int, array<string, mixed>>  $ayahs
     * @param  array<int, int>  $verseIdByNumber  verse_number => verse id
     */
    private function upsertArabicText(array $ayahs, array $verseIdByNumber): void
    {
        $existingRowIds = VerseTranslation::query()
            ->whereIn('verse_id', array_values($verseIdByNumber))
            ->where('lang', self::SOURCE_TEXT_LANG)
            ->whereNull('resource_id')
            ->pluck('id', 'verse_id');

        $now = now();
        $insert = [];
        $update = [];

        foreach ($ayahs as $ayah) {
            $verseId = $verseIdByNumber[(int) ($ayah['numberInSurah'] ?? 0)] ?? null;
            if ($verseId === null) {
                continue;
            }

            $row = [
                'verse_id' => $verseId,
                'resource_id' => null,
                'lang' => self::SOURCE_TEXT_LANG,
                'text' => (string) ($ayah['text'] ?? ''),
                'text_notashkeel' => (string) ($ayah['text_notashkeel'] ?? ''),
                'updated_at' => $now,
            ];

            if ($existingRowIds->has($verseId)) {
                $update[] = ['id' => $existingRowIds[$verseId]] + $row;
            } else {
                $insert[] = $row + ['created_at' => $now];
            }
        }

        if ($insert !== []) {
            VerseTranslation::query()->insert($insert);
        }

        if ($update !== []) {
            VerseTranslation::query()->upsert($update, ['id'], ['text', 'text_notashkeel', 'updated_at']);
        }
    }

    /**
     * Replacement for the old `truncate()` step, which wiped both tables up
     * front with FK checks disabled and no transaction — a failure mid-import
     * left the Quran gone. Pruning afterwards means the table is only ever
     * narrowed once the replacement rows are committed.
     *
     * Deletes cascade to translations, tafsirs and words of the removed verses;
     * unlike the old truncate, translation editions of surviving verses are kept.
     *
     * @param  list<int>  $importedVerseIds
     */
    private function pruneVersesNotImported(array $importedVerseIds, int $failed): int
    {
        // Never prune against a partial import — that is the data-loss case.
        if ($importedVerseIds === [] || $failed > 0) {
            Log::warning('Quran verse import: skipping prune after an incomplete import', [
                'imported' => count($importedVerseIds),
                'failed' => $failed,
            ]);

            return 0;
        }

        return Verse::query()->whereNotIn('id', $importedVerseIds)->delete();
    }

    /**
     * Import Translations (placeholder for future use)
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function importTranslations(array $data, bool $truncate = false): array
    {
        // TODO: Implement additional translation import from separate translation files
        return [
            'total' => 0,
            'created' => 0,
            'failed' => 0,
        ];
    }

    /**
     * Import Tafsir (placeholder for future use)
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function importTafsir(array $data, bool $truncate = false): array
    {
        // TODO: Implement tafsir import from separate tafsir files
        return [
            'total' => 0,
            'created' => 0,
            'failed' => 0,
        ];
    }

    /**
     * Import Reciters (placeholder for future use)
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function importReciters(array $data, bool $truncate = false): array
    {
        // TODO: Implement reciter import from separate files
        return [
            'total' => 0,
            'created' => 0,
            'failed' => 0,
        ];
    }

    /**
     * Map an external revelation type to the stored revelation place.
     *
     * Previously returned 'Mecca'/'Medina', which matched neither the stored
     * 'makkah'/'madinah' vocabulary nor the scopes and statistics that read it.
     */
    private function mapRevelationType(string $type): string
    {
        return RevelationPlace::fromExternal($type)->value;
    }
}
