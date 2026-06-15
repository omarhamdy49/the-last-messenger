<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Quran\Application\Handlers\ListRevelationContextsHandler;
use App\Domain\Quran\Application\Queries\ListRevelationContextsQuery;
use App\Domain\Quran\Services\QuranQueryService;
use App\Domain\Quran\Services\QuranReaderService;
use App\Domain\Quran\Services\ReciterService;
use App\Domain\Quran\Services\VerseAudioUrlResolver;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class QuranController extends Controller
{
    public function __construct(
        private readonly QuranQueryService $quranQueryService,
        private readonly QuranReaderService $quranReaderService,
        private readonly ReciterService $reciterService,
        private readonly VerseAudioUrlResolver $audioUrlResolver,
        private readonly ListRevelationContextsHandler $listRevelationContextsHandler,
    ) {}

    /**
     * Quran index — list all 114 surahs.
     * All 114 server-rendered; Alpine handles client-side search/filter/sort.
     */
    public function index(): View
    {
        $locale = app()->getLocale();

        $surahs = Cache::remember(
            "public_quran_index_{$locale}",
            3600,
            fn () => $this->quranQueryService->listSurahs(locale: $locale)
                ->map(fn ($s) => [
                    'number' => $s->number,
                    'nameAr' => $s->nameAr,
                    'nameEn' => $s->nameEn,
                    'transliteration' => $s->transliteration,
                    'verses' => $s->versesCount,
                    'type' => $this->isMakki($s->revelationPlace) ? 'makki' : 'madani',
                    'juz' => $this->getFirstJuz($s->number),
                ])
                ->values()
                ->all(),
        );

        $stats = [
            'total' => 114,
            'verses' => 6236,
            'makki' => 86,
            'madani' => 28,
        ];

        return view('public.quran.index', compact('surahs', 'stats'));
    }

    /**
     * Individual surah reader page with paginated verses.
     */
    public function surah(Request $request, int $surahNumber): View
    {
        // Validate surah number range
        if ($surahNumber < 1 || $surahNumber > 114) {
            abort(404);
        }

        $locale = app()->getLocale();

        // Full surah, no pagination — verses carry their mushaf page so the
        // reader can separate them by Quran page (like a printed mushaf).
        $cacheKey = "public_quran_surah_full_{$surahNumber}_{$locale}";

        $payload = Cache::remember($cacheKey, 3600, function () use ($surahNumber, $locale) {
            try {
                $surahDto = $this->quranQueryService->getSurahByNumber(
                    number: $surahNumber,
                    locale: $locale,
                );

                $allVerses = $this->quranQueryService->listVersesBySurah(
                    surahId: $surahNumber,
                    locale: $locale,
                    withTranslations: true,
                );

                // Tafsir and non-default translation editions are loaded lazily per
                // selected edition (see surahTafsir / surahTranslation). The surah page
                // inlines only the default translation per language — inlining every
                // edition's full text for every verse bloats both the page payload and
                // its cached entry, and exhausts memory on large surahs.
                $versesData = $allVerses->map(function ($verse) use ($locale) {
                    return [
                        'id' => $verse->id,
                        'number' => $verse->verseNumber,
                        'verse_key' => $verse->verseKey,
                        'arabic' => $verse->textArabic,
                        'transliteration' => $this->cleanTransliteration($verse->translations['en-tl'] ?? null),
                        // The default translation (Saheeh International, English) is inlined;
                        // any other edition loads lazily when selected (see surahTranslation).
                        'translation' => $verse->translations['en'] ?? $verse->translations[$locale] ?? null,
                        'juz' => $verse->juzNumber,
                        'page' => $verse->pageNumber,
                        'has_sajdah' => $verse->hasSajdah(),
                    ];
                })->all();

                // Load surah info (description)
                $surahInfo = null;
                try {
                    $infoDto = $this->quranQueryService->getSurahInfo($surahNumber, 'ar');
                    $surahInfo = $infoDto->description ?? $infoDto->content ?? null;
                } catch (Throwable) {
                    $surahInfo = null;
                }

                return [
                    'surah' => [
                        'number' => $surahDto->number,
                        'nameAr' => $surahDto->nameAr,
                        'nameEn' => $surahDto->nameEn,
                        'transliteration' => $surahDto->transliteration,
                        'revelationPlace' => $surahDto->revelationPlace,
                        'revelationOrder' => $surahDto->revelationOrder,
                        'versesCount' => $surahDto->versesCount,
                        'bismillahPre' => $surahDto->bismillahPre,
                        'type' => $this->isMakki($surahDto->revelationPlace) ? 'makki' : 'madani',
                        'juz' => $this->getFirstJuz($surahDto->number),
                        'info' => $surahInfo,
                    ],
                    'verses' => $versesData,
                ];
            } catch (Throwable $e) {
                report($e);

                return null;
            }
        });

        if ($payload === null) {
            abort(404);
        }

        // Load reciters for the audio player dropdown (not cached per-verse)
        $reciters = Cache::remember('public_quran_reciters_audio_list', 3600, function () {
            try {
                $all = $this->quranQueryService->listReciters(
                    perPage: 50,
                    activeOnly: true,
                );

                // listReciters returns a paginator; collect($all) would serialize its
                // meta array (current_page, ...) and silently yield zero reciters.
                $items = $all instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
                    ? collect($all->items())
                    : collect($all);

                return $items->map(fn ($r) => [
                    'id' => $r->id,
                    'nameAr' => $r->getDisplayName('ar'),
                    'nameEn' => $r->getDisplayName('en'),
                    'everyayah_folder' => $r->everyayahFolder,
                ])->values()->all();
            } catch (Throwable) {
                return [];
            }
        });

        // Attach each reciter's per-surah audio URL so the dropdown can switch the
        // surah-level player (per-verse audio uses the everyayah resolver instead).
        $surahAudioUrls = $this->getSurahAudioUrls($payload['surah']['number']);
        $reciters = array_map(function (array $reciter) use ($surahAudioUrls) {
            $reciter['surahAudioUrl'] = $surahAudioUrls[$reciter['id']] ?? null;

            return $reciter;
        }, $reciters);

        $firstRecitationUrl = $reciters[0]['surahAudioUrl'] ?? null;

        $prevSurah = $surahNumber > 1 ? $surahNumber - 1 : null;
        $nextSurah = $surahNumber < 114 ? $surahNumber + 1 : null;

        // The edition whose text is inlined per verse: Saheeh International (the
        // default English edition). Other editions load lazily on selection.
        $translationResources = $this->getTranslationResources($locale);
        $defaultTranslationId = 0;
        foreach ($translationResources as $resource) {
            if ($resource['language'] === 'en' && $resource['is_default']) {
                $defaultTranslationId = $resource['id'];
                break;
            }
        }

        return view('public.quran.surah', array_merge($payload, [
            'reciters' => $reciters,
            'firstRecitationUrl' => $firstRecitationUrl,
            'tafsirResources' => $this->getTafsirResources($locale),
            'translationResources' => $translationResources,
            'currentLocale' => $locale,
            'defaultTranslationLang' => 'en',
            'defaultTranslationId' => $defaultTranslationId,
            'prevSurah' => $prevSurah,
            'nextSurah' => $nextSurah,
            'prevSurahName' => $this->surahDisplayName($prevSurah, $locale),
            'nextSurahName' => $this->surahDisplayName($nextSurah, $locale),
        ]));
    }

    /**
     * Localized surah name for prev/next navigation; null when out of range.
     */
    private function surahDisplayName(?int $number, string $locale): ?string
    {
        if ($number === null) {
            return null;
        }

        try {
            return $this->quranQueryService->getSurahByNumber($number, $locale)->nameAr;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Juz reader page — verses of a juz (1-30), paginated, grouped by surah.
     */
    public function juz(Request $request, int $juz): View
    {
        if ($juz < 1 || $juz > 30) {
            abort(404);
        }

        $locale = app()->getLocale();
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50], true) ? $perPage : 20;

        $cacheKey = "public_quran_juz_{$juz}_{$locale}_{$page}_{$perPage}";

        $payload = Cache::remember($cacheKey, 3600, function () use ($juz, $locale, $perPage) {
            try {
                $paginator = $this->quranQueryService->listVersesByJuz($juz, $perPage, $locale, true);

                return [
                    'verses' => $this->mapReaderVerses($paginator->items(), $locale),
                    'pagination' => [
                        'current_page' => $paginator->currentPage(),
                        'per_page' => $perPage,
                        'total' => $paginator->total(),
                        'total_pages' => $paginator->lastPage(),
                        'has_prev' => $paginator->currentPage() > 1,
                        'has_next' => $paginator->currentPage() < $paginator->lastPage(),
                    ],
                ];
            } catch (Throwable $e) {
                report($e);

                return null;
            }
        });

        if ($payload === null) {
            abort(404);
        }

        return view('public.quran.juz', array_merge($payload, [
            'juz' => $juz,
            'hasPrevJuz' => $juz > 1,
            'hasNextJuz' => $juz < 30,
            'currentPage' => $page,
            'perPage' => $perPage,
        ]));
    }

    /**
     * Hizb reader page — verses of a hizb (1-60), paginated, grouped by surah.
     */
    public function hizb(Request $request, int $hizb): View
    {
        if ($hizb < 1 || $hizb > 60) {
            abort(404);
        }

        $locale = app()->getLocale();
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50], true) ? $perPage : 20;

        $cacheKey = "public_quran_hizb_{$hizb}_{$locale}_{$page}_{$perPage}";

        $payload = Cache::remember($cacheKey, 3600, function () use ($hizb, $locale, $perPage) {
            try {
                $paginator = $this->quranQueryService->listVersesByHizb($hizb, $perPage, $locale, true);

                return [
                    'verses' => $this->mapReaderVerses($paginator->items(), $locale),
                    'pagination' => [
                        'current_page' => $paginator->currentPage(),
                        'per_page' => $perPage,
                        'total' => $paginator->total(),
                        'total_pages' => $paginator->lastPage(),
                        'has_prev' => $paginator->currentPage() > 1,
                        'has_next' => $paginator->currentPage() < $paginator->lastPage(),
                    ],
                ];
            } catch (Throwable $e) {
                report($e);

                return null;
            }
        });

        if ($payload === null) {
            abort(404);
        }

        return view('public.quran.hizb', array_merge($payload, [
            'hizb' => $hizb,
            'hasPrevHizb' => $hizb > 1,
            'hasNextHizb' => $hizb < 60,
            'currentPage' => $page,
            'perPage' => $perPage,
        ]));
    }

    /**
     * Mushaf page reader — all verses on a printed page (1-604), grouped by surah.
     */
    public function page(int $page): View
    {
        if ($page < 1 || $page > 604) {
            abort(404);
        }

        $locale = app()->getLocale();
        $cacheKey = "public_quran_mushaf_page_{$page}_{$locale}";

        $payload = Cache::remember($cacheKey, 3600, function () use ($page, $locale) {
            try {
                $verses = $this->quranQueryService->listVersesByPage($page, $locale, true);

                if ($verses->isEmpty()) {
                    return null;
                }

                return ['verses' => $this->mapReaderVerses($verses, $locale)];
            } catch (Throwable $e) {
                report($e);

                return null;
            }
        });

        if ($payload === null) {
            abort(404);
        }

        return view('public.quran.page', array_merge($payload, [
            'pageNumber' => $page,
            'hasPrevPage' => $page > 1,
            'hasNextPage' => $page < 604,
        ]));
    }

    /**
     * Reciters listing page.
     */
    public function reciters(): View
    {
        $locale = app()->getLocale();

        $recitersData = Cache::remember("public_quran_reciters_{$locale}", 3600, function () use ($locale): array {
            try {
                $all = $this->reciterService->getFeatured();
                $featured = collect($all)->take(3);

                $allReciters = $this->quranQueryService->listReciters(
                    perPage: 50,
                    activeOnly: true,
                    locale: $locale,
                );

                // listReciters returns a paginator; collect($all) would serialize its
                // meta array (current_page, ...) and silently yield zero reciters.
                $items = $allReciters instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
                    ? collect($allReciters->items())
                    : collect($allReciters);

                $mapReciter = fn ($r) => [
                    'id' => $r->id,
                    'nameAr' => $r->getDisplayName('ar'),
                    'nameEn' => $r->getDisplayName('en'),
                    'country' => $r->country,
                    'style' => $r->style,
                    'isFeatured' => $r->isFeatured,
                    'followersCount' => $r->recitationsCount, // recitations_count available
                    'profileImage' => $r->profileImage,
                    'description' => $r->getDescription('ar'),
                    'slug' => $r->slug,
                    'recitationsCount' => $r->recitationsCount,
                    'firstSurahAudioUrl' => null, // resolved client-side
                ];

                return [
                    'featured' => $items
                        ->filter(fn ($r) => $r->isFeatured)
                        ->take(3)
                        ->map($mapReciter)
                        ->values()
                        ->all(),
                    'all' => $items
                        ->map($mapReciter)
                        ->values()
                        ->all(),
                    'total' => $items->count(),
                ];
            } catch (Throwable) {
                return ['featured' => [], 'all' => [], 'total' => 0];
            }
        });

        return view('public.quran.reciters', [
            'featuredReciters' => $recitersData['featured'],
            'reciters' => $recitersData['all'],
            'totalReciters' => $recitersData['total'],
        ]);
    }

    /**
     * Quran Live page — YouTube embed with static chat notice.
     */
    public function live(): View
    {
        return view('public.quran.live');
    }

    /**
     * Asbab al-Nuzul page — server-side filtered timeline of revelation contexts.
     */
    public function asbab(Request $request): View
    {
        $locale = app()->getLocale();

        $period = $request->query('period');
        $period = in_array($period, ['makki', 'madani'], true) ? $period : null;

        $surahNumber = (int) $request->query('surah', 0);
        $surahNumber = ($surahNumber >= 1 && $surahNumber <= 114) ? $surahNumber : null;

        $search = trim((string) $request->query('q', ''));
        $search = $search !== '' ? mb_substr($search, 0, 100) : null;

        $sort = $request->query('sort') === 'chronological' ? 'chronological' : 'mushaf';

        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [5, 10, 20, 50], true) ? $perPage : 10;

        $contexts = $this->listRevelationContextsHandler->handle(new ListRevelationContextsQuery(
            period: $period,
            surahId: $surahNumber,
            search: $search,
            sort: $sort,
            perPage: $perPage,
            activeOnly: true,
            locale: $locale,
            withExcerpts: true,
        ));

        // Surah list for the filter dropdown (cached, shared shape with the index page)
        $surahOptions = Cache::remember("public_quran_asbab_surahs_{$locale}", 3600, function () use ($locale) {
            try {
                return $this->quranQueryService->listSurahs(locale: $locale)
                    ->map(fn ($s) => ['number' => $s->number, 'nameAr' => $s->nameAr])
                    ->values()
                    ->all();
            } catch (Throwable) {
                return [];
            }
        });

        return view('public.quran.asbab', [
            'contexts' => $contexts,
            'surahOptions' => $surahOptions,
            'filters' => [
                'period' => $period,
                'surah' => $surahNumber,
                'q' => $search,
                'sort' => $sort,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Shape verse DTOs for the juz/page reader views. Tafsir is intentionally
     * omitted here (it would be a per-verse query across a whole juz); the surah
     * page remains the place for tafsir. The Arabic translation equals the verse
     * text, so it is nulled to avoid showing the same line twice.
     *
     * @param  iterable<\App\Domain\Quran\Application\DTOs\VerseDTO>  $verses
     * @return array<int, array<string, mixed>>
     */
    private function mapReaderVerses(iterable $verses, string $locale): array
    {
        $out = [];

        foreach ($verses as $verse) {
            $surahNumber = (int) explode(':', $verse->verseKey)[0];

            $out[] = [
                'surahNumber' => $surahNumber,
                'surahName' => $verse->surahName,
                'number' => $verse->verseNumber,
                'verseKey' => $verse->verseKey,
                'arabic' => $verse->textArabic,
                'translation' => $locale === 'ar' ? null : $verse->getTranslation($locale),
                'transliteration' => $this->cleanTransliteration($verse->translations['en-tl'] ?? null),
                'has_sajdah' => $verse->hasSajdah(),
            ];
        }

        return $out;
    }

    /**
     * The imported transliteration carries tajwid markup tags (<u>/<b>) that leak as
     * literal text in the UI. Strip them and collapse whitespace for a clean reading line.
     */
    private function cleanTransliteration(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = preg_replace('/\s+/', ' ', strip_tags($value));

        return trim((string) $clean) ?: null;
    }

    /**
     * Normalize the stored revelation place to a Makki/Madani boolean.
     * DB stores human labels ("Mecca"/"Medina"); accept common variants defensively.
     */
    private function isMakki(?string $place): bool
    {
        return in_array(
            mb_strtolower((string) $place),
            ['mecca', 'meccan', 'makkah', 'makki', 'makki', 'مكة', 'مكية'],
            true,
        );
    }

    /**
     * Return the first juz for a given surah number.
     * Based on standard mushaf mapping (simplified).
     */
    private function getFirstJuz(int $surahNumber): int
    {
        // Standard surah-to-juz mapping based on first verse of each surah
        $juzMap = [
            1 => 1, 2 => 1, 3 => 3, 4 => 4, 5 => 6, 6 => 7, 7 => 8, 8 => 9, 9 => 10,
            10 => 11, 11 => 11, 12 => 12, 13 => 13, 14 => 13, 15 => 14, 16 => 14,
            17 => 15, 18 => 15, 19 => 16, 20 => 16, 21 => 17, 22 => 17, 23 => 18,
            24 => 18, 25 => 18, 26 => 19, 27 => 19, 28 => 20, 29 => 20, 30 => 21,
            31 => 21, 32 => 21, 33 => 21, 34 => 22, 35 => 22, 36 => 22, 37 => 23,
            38 => 23, 39 => 23, 40 => 24, 41 => 24, 42 => 25, 43 => 25, 44 => 25,
            45 => 25, 46 => 26, 47 => 26, 48 => 26, 49 => 26, 50 => 26, 51 => 26,
            52 => 27, 53 => 27, 54 => 27, 55 => 27, 56 => 27, 57 => 27, 58 => 28,
            59 => 28, 60 => 28, 61 => 28, 62 => 28, 63 => 28, 64 => 28, 65 => 28,
            66 => 28, 67 => 29, 68 => 29, 69 => 29, 70 => 29, 71 => 29, 72 => 29,
            73 => 29, 74 => 29, 75 => 29, 76 => 29, 77 => 29, 78 => 30, 79 => 30,
            80 => 30, 81 => 30, 82 => 30, 83 => 30, 84 => 30, 85 => 30, 86 => 30,
            87 => 30, 88 => 30, 89 => 30, 90 => 30, 91 => 30, 92 => 30, 93 => 30,
            94 => 30, 95 => 30, 96 => 30, 97 => 30, 98 => 30, 99 => 30, 100 => 30,
            101 => 30, 102 => 30, 103 => 30, 104 => 30, 105 => 30, 106 => 30,
            107 => 30, 108 => 30, 109 => 30, 110 => 30, 111 => 30, 112 => 30,
            113 => 30, 114 => 30,
        ];

        return $juzMap[$surahNumber] ?? 1;
    }

    /**
     * Get first recitation URL for the surah audio player.
     */
    /**
     * Per-surah recitation URLs keyed by reciter id, so the audio dropdown can
     * switch the surah-level player between reciters.
     *
     * @return array<int, string>
     */
    private function getSurahAudioUrls(int $surahNumber): array
    {
        return $this->quranReaderService->getSurahAudioUrls($surahNumber);
    }

    /**
     * Active tafsir sources for the reader's source selector.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getTafsirResources(string $locale): array
    {
        return $this->quranReaderService->getTafsirResources($locale);
    }

    /**
     * Per-verse tafsir endpoint: one verse's text for one edition. The reader
     * fetches this only when a verse's tafsir accordion is opened, so a verbose
     * edition never ships a whole-surah blob.
     */
    public function verseTafsir(int $verseId, int $resourceId): JsonResponse
    {
        return response()->json(['text' => $this->quranReaderService->getVerseTafsir($verseId, $resourceId)]);
    }

    /**
     * Active translation editions for the reader's translation selector.
     * The Arabic source text (ar-uthmani) is excluded — it is the verse text,
     * not a switchable translation.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getTranslationResources(string $locale): array
    {
        return $this->quranReaderService->getTranslationResources($locale);
    }

    /**
     * Lazy translation endpoint: one edition's text for a whole surah, grouped
     * [verse_id => text]. The reader fetches this on demand only when a NON-default
     * translation edition is selected; the default edition per language is inlined
     * in the surah payload for an instant first render. This keeps the surah page
     * (and its cached payload) from carrying every edition's full text.
     */
    public function surahTranslation(int $surahNumber, int $resourceId): JsonResponse
    {
        if ($surahNumber < 1 || $surahNumber > 114) {
            abort(404);
        }

        return response()->json($this->quranReaderService->getSurahTranslation($surahNumber, $resourceId));
    }
}
