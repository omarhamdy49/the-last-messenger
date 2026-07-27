<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Content\Enums\HomeModuleCardPlacement;
use App\Domain\Content\Services\HomeContentService;
use App\Domain\Hadith\Services\HadithDashboardStatsProvider;
use App\Domain\Hadith\Services\HadithQueryService;
use App\Domain\Quran\Services\QuranQueryService;
use App\Domain\Sahaba\Services\SahabiService;
use App\Domain\Seerah\Services\SeerahEventService;
use App\Http\Controllers\Controller;
use App\Repositories\ArticleRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class HomeController extends Controller
{
    /**
     * The Mushaf always has 114 surahs — a domain constant, not a DB count.
     */
    private const SURAH_COUNT = 114;

    public function __construct(
        private readonly SahabiService $sahabiService,
        private readonly QuranQueryService $quranQueryService,
        private readonly HadithQueryService $hadithQueryService,
        private readonly HomeContentService $homeContentService,
        private readonly SeerahEventService $seerahEventService,
        private readonly HadithDashboardStatsProvider $hadithStatsProvider,
        private readonly ArticleRepository $articleRepository,
    ) {}

    public function index(): View
    {
        $locale = app()->getLocale();

        // Featured sahaba for the carousel — cached 30 min, explicit limit 8
        $featuredSahaba = Cache::remember(
            "home_featured_sahaba_{$locale}_8",
            1800,
            fn () => $this->sahabiService->getFeaturedCompanions(8),
        );

        $featuredArticles = Cache::remember(
            "home_featured_articles_{$locale}_3",
            1800,
            fn () => $this->articleRepository->getHomeFeaturedArticles($locale, 3),
        );

        // Daily verse — random, cached per day per locale
        $dailyVerse = Cache::remember(
            "home_daily_verse_{$locale}_".now()->format('Y-m-d'),
            86400,
            function () use ($locale): ?array {
                try {
                    $dto = $this->quranQueryService->getRandomVerse(
                        locale: $locale,
                        withTranslations: true,
                    );

                    return $dto->toArray();
                } catch (Throwable) {
                    return null;
                }
            },
        );

        // Daily hadith — random sahih, cached per day
        $dailyHadith = Cache::remember(
            "home_daily_hadith_{$locale}_".now()->format('Y-m-d'),
            86400,
            function () use ($locale): ?array {
                try {
                    $dto = $this->hadithQueryService->getRandomHadith(
                        gradingStatus: 'sahih',
                        withTranslations: true,
                        locale: $locale,
                    );

                    return $dto->toArray();
                } catch (Throwable) {
                    return null;
                }
            },
        );

        // Real derived counts (replaces the previous hardcoded numbers) —
        // each source is a single aggregate query, cached for 1 hour.
        $stats = Cache::remember('home_module_stats', 3600, fn (): array => [
            'hadiths' => (int) ($this->hadithStatsProvider->getStats()['total'] ?? 0),
            'surahs' => self::SURAH_COUNT,
            'seerah_events' => (int) ($this->seerahEventService->getIndexStats()['total'] ?? 0),
            'sahaba' => (int) ($this->sahabiService->getIndexStats()['total'] ?? 0),
        ]);

        // Module cards + hero quick-filter chips — DB-managed Content domain
        // data (forever-cached per locale inside the service).
        $heroCards = $this->homeContentService->activeCards(HomeModuleCardPlacement::HERO, $locale);
        $moduleCards = $this->homeContentService->activeCards(HomeModuleCardPlacement::EXPLORE, $locale);
        $quickFilters = $this->homeContentService->activeChips($locale);

        return view('public.home', compact(
            'featuredSahaba',
            'featuredArticles',
            'dailyVerse',
            'dailyHadith',
            'stats',
            'heroCards',
            'moduleCards',
            'quickFilters',
        ));
    }
}
