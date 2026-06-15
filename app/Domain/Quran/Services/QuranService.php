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

class QuranService extends BaseService
{
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
        $verses->getCollection()->load('translations');

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
        if ($truncate) {
            \DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Surah::truncate();
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $surahs = $data['data']['surahs'] ?? [];
        $created = 0;
        $skipped = 0;

        foreach ($surahs as $surahData) {
            $surah = Surah::updateOrCreate(
                ['id' => $surahData['number']],
                [
                    'name_ar' => $surahData['name'] ?? '',
                    'name_en' => $surahData['englishName'] ?? '',
                    'revelation_place' => $this->mapRevelationType($surahData['revelationType'] ?? ''),
                    'revelation_order' => $surahData['number'],
                    'verses_count' => count($surahData['ayahs'] ?? []),
                    'bismillah_pre' => true,
                ]
            );

            if ($surah->wasRecentlyCreated) {
                $created++;
            } else {
                $skipped++;
            }
        }

        return [
            'total' => count($surahs),
            'created' => $created,
            'updated' => $skipped,
            'failed' => 0,
        ];
    }

    /**
     * Import Verses from JSON data
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function importVerses(array $data, bool $truncate = false): array
    {
        if ($truncate) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Verse::truncate();
            VerseTranslation::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $surahs = $data['data']['surahs'] ?? [];
        $created = 0;
        $failed = 0;

        foreach ($surahs as $surahData) {
            $surahNumber = $surahData['number'];
            $surah = Surah::find($surahNumber);

            if (! $surah) {
                $failed += count($surahData['ayahs'] ?? []);

                continue;
            }

            foreach ($surahData['ayahs'] ?? [] as $ayahData) {
                try {
                    $verse = Verse::updateOrCreate(
                        [
                            'surah_id' => $surah->id,
                            'verse_number' => $ayahData['numberInSurah'],
                        ],
                        [
                            'verse_key' => "{$surahNumber}:{$ayahData['numberInSurah']}",
                            'juz_number' => $ayahData['juz'] ?? 1,
                            'hizb_number' => $ayahData['hizb'] ?? 1,
                            'rub_el_hizb_number' => $ayahData['hizbQuarter'] ?? 1,
                            'ruku_number' => $ayahData['ruku'] ?? 1,
                            'manzil_number' => $ayahData['manzil'] ?? null,
                            'sajdah_number' => $ayahData['sajda'] ? $ayahData['number'] : null,
                            'page_number' => $ayahData['page'] ?? 1,
                        ]
                    );

                    // Create translation (Arabic text)
                    VerseTranslation::updateOrCreate(
                        [
                            'verse_id' => $verse->id,
                            'lang' => 'ar',
                        ],
                        [
                            'text' => $ayahData['text'] ?? '',
                            'text_notashkeel' => $ayahData['text_notashkeel'] ?? '',
                        ]
                    );

                    if ($verse->wasRecentlyCreated) {
                        $created++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                }
            }
        }

        return [
            'total' => array_sum(array_map(fn ($s) => count($s['ayahs'] ?? []), $surahs)),
            'created' => $created,
            'failed' => $failed,
        ];
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
     * Map revelation type to revelation place
     */
    private function mapRevelationType(string $type): string
    {
        return match (strtolower($type)) {
            'meccan' => 'Mecca',
            'medinan' => 'Medina',
            default => 'Mecca',
        };
    }
}
