<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Content\Enums\HomeModuleCardPlacement;
use App\Domain\Content\Services\HomeContentService;
use App\Domain\Quran\Services\QuranQueryService;
use App\Domain\People\DTOs\MuslimListFilters;
use App\Domain\People\Services\MuslimDirectoryService;
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
        private readonly MuslimDirectoryService $muslimDirectory,
        private readonly QuranQueryService $quranQueryService,
        private readonly HomeContentService $homeContentService,
        private readonly SeerahEventService $seerahEventService,
        private readonly ArticleRepository $articleRepository,
    ) {}

    public function index(): View
    {
        $locale = app()->getLocale();

        // Featured companions for the carousel. A companion is now a `muslims`
        // row carrying the `sahabi` role, not a separate record.
        $featuredSahaba = Cache::remember(
            "home_featured_sahaba_{$locale}_8",
            1800,
            fn () => $this->muslimDirectory->list(new MuslimListFilters(
                roleSlug: 'sahabi',
                featured: true,
                perPage: 8,
            ))->items(),
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

        // The Hadith module is being rebuilt; there is no daily hadith until it
        // returns. The view already tolerates null.
        $dailyHadith = null;

        // Real derived counts (replaces the previous hardcoded numbers) —
        // each source is a single aggregate query, cached for 1 hour.
        $stats = Cache::remember('home_module_stats', 3600, fn (): array => [
            'surahs' => self::SURAH_COUNT,
            'seerah_events' => (int) ($this->seerahEventService->getIndexStats()['total'] ?? 0),
            'sahaba' => \App\Domain\People\Models\Muslim::query()->where('is_published', true)->count(),
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
