<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\TranslationResource;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTranslation;
use App\Domain\Quran\Models\Word;
use App\Domain\Quran\Services\QuranCacheKeys;
use App\Domain\Shared\Helpers\FullTextSearchHelper;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;

class VerseRepository extends BaseRepository implements VerseRepositoryInterface
{
    /**
     * Cache TTL in seconds (1 hour - Quran data rarely changes)
     */
    private const CACHE_TTL = 3600;

    /**
     * Columns to select for verse queries (no select * anywhere).
     * Used by methods that query quran_verses alone (no JOIN).
     */
    private const VERSE_COLUMNS = [
        'id',
        'surah_id',
        'verse_number',
        'verse_key',
        'juz_number',
        'hizb_number',
        'rub_el_hizb_number',
        'ruku_number',
        'manzil_number',
        'sajdah_number',
        'page_number',
    ];

    /**
     * Fully-qualified verse columns for queries that JOIN quran_verse_translations.
     * Without the table prefix, 'id' would be ambiguous between the two joined tables.
     */
    private const VERSE_COLUMNS_QUALIFIED = [
        'quran_verses.id',
        'quran_verses.surah_id',
        'quran_verses.verse_number',
        'quran_verses.verse_key',
        'quran_verses.juz_number',
        'quran_verses.hizb_number',
        'quran_verses.rub_el_hizb_number',
        'quran_verses.ruku_number',
        'quran_verses.manzil_number',
        'quran_verses.sajdah_number',
        'quran_verses.page_number',
    ];

    /**
     * Constrained surah eager load columns
     */
    private const SURAH_EAGER = 'surah:id,revelation_order,revelation_place,name_ar,name_en,transliteration,verses_count';

    /**
     * Constrained translations eager load columns
     */
    private const TRANSLATIONS_EAGER = 'translations:id,verse_id,resource_id,lang,text,text_notashkeel';

    /**
     * Resource (edition) of each translation — lets the DTO pick the default
     * edition per language deterministically when several editions exist.
     */
    // `slug` and `name` are required, not optional: TranslationResource emits both, and
    // TranslationResource::getName() reads `name[$locale]` with `slug` as its last-resort
    // fallback. Selecting only id/is_default/language left them null — a constrained query
    // feeding a Resource that reads unselected columns, which fails silently.
    private const TRANSLATION_RESOURCE_EAGER = 'translations.resource:id,slug,name,language,is_default';

    /**
     * Get model instance
     */
    protected function getModel(): Model
    {
        return new Verse;
    }

    /**
     * Active translation editions (resources) for the reader's translation
     * selector, excluding the Arabic source text. Cached — metadata changes rarely.
     *
     * @return Collection<int, TranslationResource>
     */
    public function getActiveTranslationResources(): Collection
    {
        return Cache::remember(QuranCacheKeys::activeTranslationResources(), self::CACHE_TTL, fn () => TranslationResource::query()
            ->where('is_active', true)
            ->where('language', '!=', 'ar')
            ->orderBy('language')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get(['id', 'slug', 'name', 'language', 'direction', 'is_default']));
    }

    /**
     * One translation edition's text for a whole surah, keyed by verse id. Cached
     * per (surah, resource) so non-default editions load lazily without inlining
     * every edition into the surah page.
     *
     * @return array<int, string>
     */
    public function getSurahTranslationByResource(int $surahNumber, int $resourceId): array
    {
        return Cache::remember(
            QuranCacheKeys::surahTranslationByResource($surahNumber, $resourceId),
            self::CACHE_TTL,
            function () use ($surahNumber, $resourceId): array {
                $verseIds = Verse::query()
                    ->where('surah_id', $surahNumber)
                    ->pluck('id');

                return VerseTranslation::query()
                    ->whereIn('verse_id', $verseIds)
                    ->where('resource_id', $resourceId)
                    ->select(['verse_id', 'text'])
                    ->get()
                    ->pluck('text', 'verse_id')
                    ->all();
            }
        );
    }

    /**
     * Get verses by surah
     */
    public function getBySurah(int $surahId): Collection
    {
        return Cache::remember(
            QuranCacheKeys::versesBySurah($surahId),
            self::CACHE_TTL,
            fn () => $this->model
                ->select(self::VERSE_COLUMNS)
                ->where('surah_id', $surahId)
                ->with([self::TRANSLATIONS_EAGER, self::TRANSLATION_RESOURCE_EAGER])
                ->orderBy('verse_number')
                ->get()
        );
    }

    /**
     * One surah's verses, paginated, carrying only what a listing row shows.
     */
    public function paginateBySurah(int $surahId, int $perPage): LengthAwarePaginator
    {
        return $this->model
            ->select(['id', 'surah_id', 'verse_number', 'verse_key'])
            ->where('surah_id', $surahId)
            ->with(['translations:id,verse_id,lang,text'])
            ->orderBy('verse_number')
            ->paginate($perPage);
    }

    /**
     * Get verse by surah number and verse number.
     * surahNumber is the mushaf number, i.e. quran_surahs.id.
     */
    public function getBySurahAndVerse(int $surahNumber, int $verseNumber): ?Verse
    {
        return Cache::remember(
            QuranCacheKeys::verseBySurahAndNumber($surahNumber, $verseNumber),
            self::CACHE_TTL,
            fn () => $this->model
                ->select(self::VERSE_COLUMNS)
                ->where('surah_id', $surahNumber)
                ->where('verse_number', $verseNumber)
                ->with([self::SURAH_EAGER, self::TRANSLATIONS_EAGER, self::TRANSLATION_RESOURCE_EAGER])
                ->first()
        );
    }

    /**
     * Get verses by Juz
     */
    public function getByJuz(int $juz, int $perPage = 50): LengthAwarePaginator
    {
        return $this->model
            ->select(self::VERSE_COLUMNS)
            ->where('juz_number', $juz)
            ->with([self::SURAH_EAGER])
            ->orderBy('surah_id')
            ->orderBy('verse_number')
            ->paginate($perPage);
    }

    /**
     * Get verses by hizb
     */
    public function getByHizb(int $hizb, int $perPage = 50): LengthAwarePaginator
    {
        return $this->model
            ->select(self::VERSE_COLUMNS)
            ->where('hizb_number', $hizb)
            ->with([self::SURAH_EAGER])
            ->orderBy('surah_id')
            ->orderBy('verse_number')
            ->paginate($perPage);
    }

    /**
     * Get verses by page
     */
    public function getByPage(int $page): Collection
    {
        return Cache::remember(
            QuranCacheKeys::versesByPage($page),
            self::CACHE_TTL,
            fn () => $this->model
                ->select(self::VERSE_COLUMNS)
                ->where('page_number', $page)
                ->with([self::SURAH_EAGER])
                ->orderBy('surah_id')
                ->orderBy('verse_number')
                ->get()
        );
    }

    /**
     * Get all Sajda verses — sajdah_number IS NOT NULL indicates a sajda verse
     */
    public function getSajdaVerses(): Collection
    {
        return Cache::remember(
            QuranCacheKeys::sajdaVerses(),
            self::CACHE_TTL,
            fn () => $this->model
                ->select(self::VERSE_COLUMNS)
                ->whereNotNull('sajdah_number')
                ->with([self::SURAH_EAGER])
                ->orderBy('sajdah_number')
                ->get()
        );
    }

    /**
     * Search in Arabic text via quran_verse_translations (lang='ar').
     *
     * Single JOIN query replaces the previous two-step pluck+whereIn approach,
     * which loaded thousands of IDs into PHP memory and produced a huge IN clause.
     * FULLTEXT index covers (text, text_notashkeel) on quran_verse_translations.
     * groupBy prevents duplicate verse rows when multiple translation rows match.
     */
    public function searchArabic(string $query, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model
            ->select(self::VERSE_COLUMNS_QUALIFIED)
            ->join('quran_verse_translations', 'quran_verses.id', '=', 'quran_verse_translations.verse_id')
            ->where('quran_verse_translations.lang', 'ar')
            ->where(function ($q) use ($query) {
                FullTextSearchHelper::apply(
                    $q,
                    ['quran_verse_translations.text', 'quran_verse_translations.text_notashkeel'],
                    $query
                );
            })
            ->groupBy('quran_verses.id')
            ->with([self::SURAH_EAGER, self::TRANSLATIONS_EAGER, self::TRANSLATION_RESOURCE_EAGER])
            ->orderBy('quran_verses.surah_id')
            ->orderBy('quran_verses.verse_number')
            ->paginate($perPage);
    }

    /**
     * Search in translations for a given language.
     *
     * Single JOIN query replaces the previous two-step pluck+whereIn approach.
     * FULLTEXT index covers (text, text_notashkeel) on quran_verse_translations.
     * groupBy prevents duplicate verse rows when multiple translation rows match.
     */
    public function searchTranslation(string $query, string $language, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model
            ->select(self::VERSE_COLUMNS_QUALIFIED)
            ->join('quran_verse_translations', 'quran_verses.id', '=', 'quran_verse_translations.verse_id')
            ->where('quran_verse_translations.lang', $language)
            ->where(function ($q) use ($query) {
                FullTextSearchHelper::apply(
                    $q,
                    ['quran_verse_translations.text', 'quran_verse_translations.text_notashkeel'],
                    $query
                );
            })
            ->groupBy('quran_verses.id')
            ->with([self::SURAH_EAGER, self::TRANSLATIONS_EAGER, self::TRANSLATION_RESOURCE_EAGER])
            ->orderBy('quran_verses.surah_id')
            ->orderBy('quran_verses.verse_number')
            ->paginate($perPage);
    }

    /**
     * Get random verse — picks a random record using inRandomOrder
     */
    public function getRandomVerse(): ?Verse
    {
        return $this->model
            ->select(self::VERSE_COLUMNS)
            ->with([self::SURAH_EAGER, self::TRANSLATIONS_EAGER, self::TRANSLATION_RESOURCE_EAGER])
            ->inRandomOrder()
            ->first();
    }

    /**
     * Get paginated verses with filters.
     *
     * When a search term is present, a JOIN onto quran_verse_translations replaces
     * the previous two-step pluck+whereIn approach (which loaded thousands of IDs
     * into PHP memory and produced a huge IN clause).
     * groupBy('quran_verses.id') prevents duplicate rows from the JOIN.
     * Without a search term, no JOIN is performed and bare column names remain safe.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'verse_key',
        array $filters = []
    ): LengthAwarePaginator {
        $hasSearch = $search !== '';

        // Use qualified column names when a JOIN is present to avoid ambiguity.
        $query = $this->model
            ->select($hasSearch ? self::VERSE_COLUMNS_QUALIFIED : self::VERSE_COLUMNS)
            ->with([self::SURAH_EAGER]);

        // Search via translations table (language defaults to Arabic).
        // Single JOIN replaces the old pluck+whereIn.
        // FULLTEXT index covers (text, text_notashkeel) on quran_verse_translations.
        if ($hasSearch) {
            $searchLang = $filters['search_lang'] ?? 'ar';

            $query
                ->join('quran_verse_translations', 'quran_verses.id', '=', 'quran_verse_translations.verse_id')
                ->where('quran_verse_translations.lang', $searchLang)
                ->where(function ($q) use ($search) {
                    FullTextSearchHelper::apply(
                        $q,
                        ['quran_verse_translations.text', 'quran_verse_translations.text_notashkeel'],
                        $search
                    );
                })
                ->groupBy('quran_verses.id');
        }

        // Apply filters — prefix with table name when JOIN is active to avoid
        // ambiguity; quran_verse_translations has no surah_id/juz_number/etc.
        // so these are only ambiguous for 'id', which is not used here.
        $tablePrefix = $hasSearch ? 'quran_verses.' : '';

        if (! empty($filters['surah_id'])) {
            $query->where($tablePrefix.'surah_id', $filters['surah_id']);
        }

        if (! empty($filters['juz_number'])) {
            $query->where($tablePrefix.'juz_number', $filters['juz_number']);
        }

        if (! empty($filters['page_number'])) {
            $query->where($tablePrefix.'page_number', $filters['page_number']);
        }

        if (isset($filters['is_sajda']) && $filters['is_sajda'] !== '') {
            $isSajda = filter_var($filters['is_sajda'], FILTER_VALIDATE_BOOLEAN);
            if ($isSajda) {
                $query->whereNotNull($tablePrefix.'sajdah_number');
            } else {
                $query->whereNull($tablePrefix.'sajdah_number');
            }
        }

        // Apply sorting
        $allowedSorts = ['verse_key', 'verse_number', 'juz_number', 'page_number', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'verse_key';
        $query->orderBy($tablePrefix.$sortBy);

        return $query->paginate($perPage);
    }

    /**
     * Bulk delete verses
     *
     * @param  array<int>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * Search verses with filters.
     *
     * Single JOIN query replaces the previous two-step pluck+whereIn approach.
     * FULLTEXT index covers (text, text_notashkeel) on quran_verse_translations.
     * groupBy prevents duplicate verse rows when multiple translation rows match.
     */
    public function search(
        string $query,
        int $perPage = 15,
        ?int $surahId = null,
        string $language = 'ar'
    ): LengthAwarePaginator {
        $q = $this->model
            ->select(self::VERSE_COLUMNS_QUALIFIED)
            ->join('quran_verse_translations', 'quran_verses.id', '=', 'quran_verse_translations.verse_id')
            ->where('quran_verse_translations.lang', $language)
            ->where(function ($builder) use ($query) {
                FullTextSearchHelper::apply(
                    $builder,
                    ['quran_verse_translations.text', 'quran_verse_translations.text_notashkeel'],
                    $query
                );
            })
            ->groupBy('quran_verses.id')
            ->with([self::SURAH_EAGER]);

        if ($surahId !== null) {
            $q->where('quran_verses.surah_id', $surahId);
        }

        return $q->orderBy('quran_verses.surah_id')->orderBy('quran_verses.verse_number')->paginate($perPage);
    }

    /**
     * Count Sajda verses — sajdah_number IS NOT NULL
     */
    public function countSajdaVerses(): int
    {
        return (int) Cache::remember(
            QuranCacheKeys::sajdaVersesCount(),
            self::CACHE_TTL,
            fn () => $this->model->whereNotNull('sajdah_number')->count()
        );
    }

    /**
     * Printed-mushaf word layout for a surah, ordered for line-by-line rendering
     * (by page, then line, then word position). Cached — the layout is static.
     *
     * Returned as plain rows (`toBase()`), never Word models: البقرة is 6,402
     * words, and that many hydrated models serialize to ~11 MB — unserializing
     * the cache entry alone exhausted the 128 MB limit and 500'd the page.
     *
     * @return SupportCollection<int, object>
     */
    public function getMushafWordsBySurah(int $surahNumber): SupportCollection
    {
        return Cache::remember(
            QuranCacheKeys::mushafWordsBySurah($surahNumber),
            self::CACHE_TTL,
            fn () => Word::query()
                ->select([
                    'id',
                    'verse_id',
                    'surah_number',
                    'verse_number',
                    'position',
                    'location',
                    'text_uthmani',
                    'char_type',
                    'line_number',
                    'page_number',
                ])
                ->where('surah_number', $surahNumber)
                ->orderBy('page_number')
                ->orderBy('line_number')
                ->orderBy('verse_number') // verses share lines; order by verse before word position
                ->orderBy('position')
                ->toBase()
                ->get()
        );
    }
}
