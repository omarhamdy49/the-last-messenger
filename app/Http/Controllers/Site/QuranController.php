<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Quran\Application\Handlers\ListRevelationContextsHandler;
use App\Domain\Quran\Application\Queries\ListRevelationContextsQuery;
use App\Domain\Quran\Services\QuranQueryService;
use App\Domain\Quran\Services\ReciterService;
use App\Domain\Quran\Services\VerseAudioUrlResolver;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class QuranController extends Controller
{
    public function __construct(
        private readonly QuranQueryService $quranQueryService,
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
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [5, 10, 20, 50], true) ? $perPage : 10;

        $cacheKey = "public_quran_surah_{$surahNumber}_{$locale}_{$page}_{$perPage}";

        $payload = Cache::remember($cacheKey, 3600, function () use ($surahNumber, $locale, $page, $perPage) {
            try {
                $surahDto = $this->quranQueryService->getSurahByNumber(
                    number: $surahNumber,
                    locale: $locale,
                );

                // Fetch all verses for this surah then paginate manually
                // (ListVersesBySurah returns a Collection with all verses)
                $allVerses = $this->quranQueryService->listVersesBySurah(
                    surahId: $surahNumber,
                    locale: $locale,
                    withTranslations: true,
                );

                $total = $allVerses->count();
                $offset = ($page - 1) * $perPage;
                $pageVerses = $allVerses->slice($offset, $perPage)->values();

                // Build verse array with tafsir loaded per-verse from cache
                $versesData = $pageVerses->map(function ($verse) {
                    $tafsirText = null;
                    try {
                        $tafsir = $this->quranQueryService->getTafsirByVerse(
                            verseId: $verse->id,
                            locale: 'ar', // Muyassar tafsir is Arabic only
                        );
                        $tafsirText = $tafsir->first()?->text;
                    } catch (Throwable) {
                        $tafsirText = null;
                    }

                    return [
                        'id' => $verse->id,
                        'number' => $verse->verseNumber,
                        'arabic' => $verse->textArabic,
                        'transliteration' => $this->cleanTransliteration($verse->translations['en-tl'] ?? null),
                        'translation_ar' => $verse->translations['ar'] ?? null,
                        'translation_en' => $verse->translations['en'] ?? null,
                        'translation_fr' => $verse->translations['fr'] ?? null,
                        'translation_tr' => $verse->translations['tr'] ?? null,
                        'translation_ur' => $verse->translations['ur'] ?? null,
                        'tafsir' => $tafsirText,
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

                $totalPages = (int) ceil($total / $perPage);

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
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'total_pages' => $totalPages,
                        'has_prev' => $page > 1,
                        'has_next' => $page < $totalPages,
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

        // Get the first reciter's first-surah recitation URL for the main player
        $firstRecitationUrl = $this->getFirstRecitationUrl($payload['surah']['number']);

        return view('public.quran.surah', array_merge($payload, [
            'reciters' => $reciters,
            'firstRecitationUrl' => $firstRecitationUrl,
            'currentPage' => $page,
            'perPage' => $perPage,
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
                    'featured' => collect($allReciters)
                        ->filter(fn ($r) => $r->isFeatured)
                        ->take(3)
                        ->map($mapReciter)
                        ->values()
                        ->all(),
                    'all' => collect($allReciters)
                        ->map($mapReciter)
                        ->values()
                        ->all(),
                    'total' => collect($allReciters)->count(),
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
    private function getFirstRecitationUrl(int $surahNumber): ?string
    {
        return Cache::remember("public_quran_recitation_url_{$surahNumber}", 86400, function () use ($surahNumber): ?string {
            try {
                $featured = $this->reciterService->getFeatured();
                $reciter = collect($featured)->first();
                if ($reciter === null) {
                    return null;
                }

                // Check recitations table first
                $recitation = $reciter->recitations()
                    ->where('surah_id', $surahNumber)
                    ->select(['audio_url'])
                    ->first();

                return $recitation?->audio_url;
            } catch (Throwable) {
                return null;
            }
        });
    }
}
