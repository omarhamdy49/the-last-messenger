<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Quran\Application\DTOs\VerseDTO;
use App\Domain\Quran\Application\Handlers\AskQuranHandler;
use App\Domain\Quran\Application\Handlers\ListRevelationContextsHandler;
use App\Domain\Quran\Application\Queries\AskQuranQuery;
use App\Domain\Quran\Application\Queries\ListRevelationContextsQuery;
use App\Domain\Quran\Exceptions\MonthlyQuotaExceededException;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Repositories\AiInquiryQuotaRepositoryInterface;
use App\Domain\Quran\Repositories\AiInquiryRepositoryInterface;
use App\Domain\Quran\Services\AnswerHtmlRenderer;
use App\Domain\Quran\Services\AnswerVerseLinker;
use App\Domain\Quran\Services\QuranCacheKeys;
use App\Domain\Quran\Services\QuranInquirySearchService;
use App\Domain\Quran\Services\QuranQueryService;
use App\Domain\Quran\Services\QuranReaderService;
use App\Domain\Quran\Services\ReciterService;
use App\Domain\Quran\Services\VerseAudioUrlResolver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\AskQuranRequest;
use App\Http\Support\QuestionSanitizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class QuranController extends Controller
{
    public function __construct(
        private readonly QuranQueryService $quranQueryService,
        private readonly QuranReaderService $quranReaderService,
        private readonly ReciterService $reciterService,
        private readonly VerseAudioUrlResolver $audioUrlResolver,
        private readonly ListRevelationContextsHandler $listRevelationContextsHandler,
        private readonly QuranInquirySearchService $inquirySearchService,
        private readonly AskQuranHandler $askQuranHandler,
        private readonly AiInquiryQuotaRepositoryInterface $quota,
        private readonly AiInquiryRepositoryInterface $inquiries,
        private readonly AnswerVerseLinker $verseLinker,
        private readonly AnswerHtmlRenderer $answerHtmlRenderer,
    ) {}

    /**
     * "Ask the Quran" — conversational inquiry-search page.
     *
     * Renders the thread shell; the initial inquiry (?q=) and every follow-up are
     * resolved client-side against {@see askSearch()} so the page reads like a chat
     * without a full reload. No LLM — each inquiry is a canonical verse search.
     */
    public function ask(Request $request): View
    {
        $maxChars = (int) config('services.quran_ai.max_chars', 200);
        $minChars = (int) config('services.quran_ai.min_chars', 10);
        $limit = (int) config('services.quran_ai.monthly_quota', 10);

        $initialQuery = trim((string) $request->query('q', ''));
        if (mb_strlen($initialQuery) > $maxChars) {
            $initialQuery = mb_substr($initialQuery, 0, $maxChars);
        }

        $user = $request->user();
        $remaining = $user !== null
            ? max(0, $limit - $this->quota->used((int) $user->id, now()->format('Y-m')))
            : $limit;

        // Deduplicated ask history for the ChatGPT-style aside (registered users).
        $history = $user !== null
            ? $this->inquiries->historyForUser((int) $user->id)
                ->map(static fn ($i): array => ['id' => $i->id, 'question' => $i->question])
                ->all()
            : [];

        // Deep link: ?inquiry=<id> reopens a specific saved Q&A on load/refresh.
        // Only honoured when it belongs to the user (IDOR-safe); else ignored.
        $initialInquiryId = null;
        if ($user !== null) {
            $requested = (int) $request->query('inquiry', 0);
            if ($requested > 0 && $this->inquiries->findForUser($requested, (int) $user->id) !== null) {
                $initialInquiryId = $requested;
            }
        }

        return view('public.quran.ask', [
            'initialQuery' => $initialQuery,
            'isAuthenticated' => $user !== null,
            'remaining' => $remaining,
            'limit' => $limit,
            'minChars' => $minChars,
            'maxChars' => $maxChars,
            'history' => $history,
            'initialInquiryId' => $initialInquiryId,
        ]);
    }

    /**
     * Return one of the current user's past inquiries (Q&A) for the history
     * aside. IDOR-safe — scoped to the authenticated user.
     */
    public function askHistory(Request $request, int $inquiry): JsonResponse
    {
        $record = $this->inquiries->findForUser($inquiry, (int) $request->user()->id);

        if ($record === null) {
            return response()->json(['message' => __('quran.public.ask_error')], 404);
        }

        return response()->json([
            'question' => $record->question,
            'answer' => $record->answer,
            // Already-formatted premium HTML, rendered by the backend (no LLM) so
            // the saved answer renders instantly without re-parsing on the client.
            'html' => $this->answerHtmlRenderer->render((string) $record->answer),
            'citations' => $record->citations ?? [],
            'verses' => $this->verseLinker->fromAnswer((string) $record->answer, $record->citations ?? []),
            'source' => $record->source,
        ]);
    }

    /**
     * JSON endpoint backing the inquiry thread. Rate limited (throttle:search).
     */
    public function askSearch(Request $request): JsonResponse
    {
        // Sanitise to the same allowlist as the AI question before validating.
        $request->merge(['q' => QuestionSanitizer::sanitize((string) $request->input('q', ''))]);

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:150', 'regex:'.QuestionSanitizer::PATTERN],
        ]);

        try {
            $payload = $this->inquirySearchService->search(
                query: $validated['q'],
                locale: app()->getLocale(),
                limit: 20,
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => __('quran.public.ask_error'),
            ], 502);
        }

        return response()->json($payload);
    }

    /**
     * "Ask Quran" AI endpoint — registered users only, monthly-quota limited.
     *
     * Grounds the answer via DeepSeek + the quran.ai MCP. Auth + per-minute burst
     * throttle are applied at the route; the monthly quota is enforced in the
     * handler. Returns a flat JSON shape the Alpine composer consumes.
     */
    public function askAi(AskQuranRequest $request): JsonResponse
    {
        try {
            $result = $this->askQuranHandler->handle(new AskQuranQuery(
                userId: (int) $request->user()->id,
                question: (string) $request->validated('question'),
                locale: app()->getLocale(),
                geo: $this->extractGeo($request),
                detailed: $request->boolean('detailed'),
            ));
        } catch (MonthlyQuotaExceededException $e) {
            return response()->json([
                'message' => __('quran.public.ask_quota_exceeded'),
                'remaining' => 0,
                'limit' => $e->limit,
            ], 429);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => __('quran.public.ask_error')], 502);
        }

        return response()->json([
            'id' => $result->id,
            'answer' => $result->answer,
            'html' => $result->html,
            'citations' => $result->citations,
            'verses' => $result->verses,
            'source' => $result->source,
            'remaining' => $result->remaining,
            'limit' => $result->limit,
        ]);
    }

    /**
     * Streaming "Ask Quran" AI endpoint (Server-Sent Events).
     *
     * Same auth + quota policy as {@see askAi()}, but the answer streams to the
     * browser token-by-token (with grounding-progress events) for a live,
     * ChatGPT-style experience. The client falls back to {@see askAi()} when
     * streaming is unavailable.
     */
    public function askAiStream(AskQuranRequest $request): StreamedResponse
    {
        $userId = (int) $request->user()->id;
        $question = (string) $request->validated('question');
        $locale = app()->getLocale();
        $geo = $this->extractGeo($request);
        $detailed = $request->boolean('detailed');

        // Release the session lock so the long-lived stream cannot block the
        // user's other requests (history aside, navigation) for its duration.
        if ($request->hasSession()) {
            $request->session()->save();
        }

        return response()->stream(function () use ($userId, $question, $locale, $geo, $detailed): void {
            $emit = static function (array $event): void {
                echo 'data: '.json_encode($event, JSON_UNESCAPED_UNICODE)."\n\n";
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            };

            try {
                $this->askQuranHandler->handleStreaming(new AskQuranQuery(
                    userId: $userId,
                    question: $question,
                    locale: $locale,
                    geo: $geo,
                    detailed: $detailed,
                ), $emit);
            } catch (Throwable $e) {
                report($e);
                $emit(['type' => 'error']);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Whitelist the advisory client geo headers — country + city only (see AQ-18).
     * Untrusted and spoofable — stored for analytics only, never used for
     * auth/quota/pricing.
     *
     * @return array<string, string>|null
     */
    private function extractGeo(Request $request): ?array
    {
        $country = strtoupper(substr((string) $request->header('X-Geo-Country', ''), 0, 2));

        $geo = array_filter([
            'country' => preg_match('/^[A-Z]{2}$/', $country) === 1 ? $country : null,
            'city' => $this->clipHeader($request->header('X-Geo-City'), 64),
        ], static fn ($value): bool => $value !== null);

        return $geo === [] ? null : $geo;
    }

    private function clipHeader(?string $value, int $max): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    /**
     * Quran index — list all 114 surahs.
     * All 114 server-rendered; Alpine handles client-side search/filter/sort.
     */
    public function index(): View
    {
        $surahs = $this->cachedSurahList(app()->getLocale());

        $stats = [
            'total' => 114,
            'verses' => 6236,
            'makki' => 86,
            'madani' => 28,
        ];

        return view('public.quran.index', compact('surahs', 'stats'));
    }

    /**
     * Lightweight JSON list of all 114 surahs for the reader's lazy-loaded surah
     * switcher modal (fetched on first open, then cached client-side).
     */
    public function surahsJson(): JsonResponse
    {
        return response()->json([
            'surahs' => $this->cachedSurahList(app()->getLocale()),
        ]);
    }

    /**
     * Cached, presentation-shaped list of all 114 surahs (shared by the index
     * page and the switcher JSON endpoint).
     *
     * @return array<int, array<string, mixed>>
     */
    private function cachedSurahList(string $locale): array
    {
        return Cache::remember(
            QuranCacheKeys::publicSurahList($locale),
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
        $cacheKey = QuranCacheKeys::publicSurahFull($surahNumber, $locale);

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
                } catch (Throwable $e) {
                    report($e);
                    $surahInfo = null;
                }

                return [
                    'surah' => [
                        'number' => $surahDto->number,
                        'nameAr' => $surahDto->nameAr,
                        'nameEn' => $surahDto->nameEn,
                        'transliteration' => $surahDto->transliteration,
                        'revelationPlace' => $surahDto->revelationPlace,
                        // The classification (مكية/مدنية) above; the place name
                        // below. They answer different questions and are shown
                        // together, never one instead of the other.
                        'revelationPlaceName' => $surahDto->revelationPlaceName,
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
        $reciters = Cache::remember(QuranCacheKeys::publicRecitersAudioList(), 3600, function () {
            try {
                $all = $this->quranQueryService->listReciters(
                    // Must exceed the catalogue size — a short page silently drops
                    // reciters from the picker rather than paginating it.
                    perPage: 200,
                    activeOnly: true,
                    kind: 'recitation',
                );

                // listReciters returns a paginator; collect($all) would serialize its
                // meta array (current_page, ...) and silently yield zero reciters.
                $items = $all instanceof LengthAwarePaginator
                    ? collect($all->items())
                    : collect($all);

                return $items->map(fn ($r) => [
                    'id' => $r->id,
                    'nameAr' => $r->getDisplayName('ar'),
                    'nameEn' => $r->getDisplayName('en'),
                    'everyayah_folder' => $r->everyayahFolder,
                ])->values()->all();
            } catch (Throwable $e) {
                report($e);

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

        // Alphabetical picker (A→Z / أ→ي). Hamza-carrying alefs are folded to bare
        // alef in the sort key only, so إبراهيم / أبو بكر land under ا with the
        // rest instead of ahead of the whole list.
        usort($reciters, static function (array $a, array $b): int {
            $key = static function (array $r): string {
                // The picker labels itself with nameAr (nameEn only when there is
                // no Arabic name), so sort on the same string the reader sees.
                $name = $r['nameAr'] ?: ($r['nameEn'] ?? '');
                $name = str_replace(['أ', 'إ', 'آ', 'ٱ', 'ى'], ['ا', 'ا', 'ا', 'ا', 'ي'], $name);

                // Arabic names first, Latin-labelled leftovers after them.
                return (preg_match('/^\p{Arabic}/u', $name) === 1 ? '0' : '1').$name;
            };

            return strcmp($key($a), $key($b));
        });

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
            'mushaf' => $this->getMushafLayout($surahNumber),
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
        } catch (Throwable $e) {
            report($e);

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

        $cacheKey = QuranCacheKeys::publicJuz($juz, $locale, $page, $perPage);

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

        $cacheKey = QuranCacheKeys::publicHizb($hizb, $locale, $page, $perPage);

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
        $cacheKey = QuranCacheKeys::publicMushafPage($page, $locale);

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

        $recitersData = Cache::remember(QuranCacheKeys::publicReciters($locale), 3600, function () use ($locale): array {
            try {
                $all = $this->reciterService->getFeatured();
                $featured = collect($all)->take(3);

                $allReciters = $this->quranQueryService->listReciters(
                    perPage: 200,
                    activeOnly: true,
                    locale: $locale,
                    // Recited translations share this table but are not qaris;
                    // the public reciters page lists reciters only.
                    kind: 'recitation',
                );

                // listReciters returns a paginator; collect($all) would serialize its
                // meta array (current_page, ...) and silently yield zero reciters.
                $items = $allReciters instanceof LengthAwarePaginator
                    ? collect($allReciters->items())
                    : collect($allReciters);

                $mapReciter = fn ($r) => [
                    'id' => $r->id,
                    'nameAr' => $r->getDisplayName('ar'),
                    'nameEn' => $r->getDisplayName('en'),
                    'country' => $r->country,
                    'countryFlag' => country_flag($r->country),
                    'countryName' => country_name($r->country, $locale),
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
            } catch (Throwable $e) {
                report($e);

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
     * Single reciter profile — bio, meta chips, and per-surah recitations.
     */
    public function reciter(string $slug): View
    {
        $reciter = Reciter::query()->active()->where('slug', $slug)
            ->with('translations')->first();

        if ($reciter === null) {
            abort(404);
        }

        $locale = app()->getLocale();

        $recitations = $reciter->recitations()
            ->with('surah')
            ->get()
            ->sortBy(fn ($recitation) => $recitation->surah?->id ?? PHP_INT_MAX)
            ->map(fn ($recitation): array => [
                'surah_number' => $recitation->surah?->id,
                'surah_name' => $recitation->surah?->getDisplayName($locale) ?? '',
                'audio_url' => $recitation->audio_url,
            ])->values()->all();

        return view('public.quran.reciter', [
            'reciter' => [
                'name' => $reciter->getDisplayName($locale),
                'description' => $reciter->getDescriptionText($locale),
                'image' => $reciter->profile_image_url,
                'country' => $reciter->country,
                'country_flag' => country_flag($reciter->country),
                'country_name' => country_name($reciter->country, $locale),
                'style' => $reciter->style,
                'riwayah' => $reciter->riwayah,
                'is_featured' => (bool) $reciter->is_featured,
                'followers_count' => (int) $reciter->followers_count,
            ],
            'recitations' => $recitations,
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
        $surahOptions = Cache::remember(QuranCacheKeys::publicAsbabSurahs($locale), 3600, function () use ($locale) {
            try {
                return $this->quranQueryService->listSurahs(locale: $locale)
                    ->map(fn ($s) => ['number' => $s->number, 'nameAr' => $s->nameAr])
                    ->values()
                    ->all();
            } catch (Throwable $e) {
                report($e);

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
     * @param  iterable<VerseDTO>  $verses
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
     * Printed-mushaf word layout (pages → lines → words) for the reader's
     * mushaf view. Empty when the layout hasn't been imported yet.
     */
    private function getMushafLayout(int $surahNumber): array
    {
        return $this->quranReaderService->getMushafLayout($surahNumber);
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
