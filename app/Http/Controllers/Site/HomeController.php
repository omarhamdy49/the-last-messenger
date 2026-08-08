<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Content\Enums\HomeModuleCardPlacement;
use App\Domain\Content\Services\HomeContentService;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\People\DTOs\MuslimListFilters;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Services\MuslimDirectoryService;
use App\Domain\Quran\Services\QuranQueryService;
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

        // Daily hadith — same contract as the verse: one random published row,
        // cached per day per locale. The read side of the Hadith module is not
        // rebuilt yet, so this reads the model directly instead of going
        // through a query service. The view still tolerates null.
        // ponytail: promote to a HadithQueryService when a second caller needs it.
        $dailyHadith = Cache::remember(
            "home_daily_hadith_{$locale}_".now()->format('Y-m-d'),
            86400,
            function () use ($locale): ?array {
                // Two steps on purpose: ORDER BY RAND() over id alone sorts 38k
                // integers, not 38k longtext bodies.
                $id = Hadith::query()->where('is_published', true)->inRandomOrder()->value('id');

                $hadith = $id ? Hadith::with('collection')->find($id) : null;

                if (! $hadith) {
                    return null;
                }

                // `is_published` already guarantees text_ar (DB CHECK); text_en
                // is genuinely missing on one record, so it is filtered out
                // rather than rendering an empty second line.
                return [
                    'texts' => array_values(array_filter([
                        ['locale' => 'ar', 'body' => $hadith->text_ar, 'narrated_by' => $hadith->narrated_by],
                        $hadith->text_en ? ['locale' => 'en', 'body' => $hadith->text_en] : null,
                    ])),
                    'display_grade' => $hadith->grade?->value,
                    'collection' => $hadith->collection?->slug,
                    'collection_name' => $hadith->collection?->title($locale),
                    'hadith_number' => $hadith->number,
                ];
            },
        );

        // Real derived counts (replaces the previous hardcoded numbers) —
        // each source is a single aggregate query, cached for 1 hour.
        $stats = Cache::remember('home_module_stats', 3600, fn (): array => [
            'surahs' => self::SURAH_COUNT,
            'seerah_events' => (int) ($this->seerahEventService->getIndexStats()['total'] ?? 0),
            'hadiths' => Hadith::query()->where('is_published', true)->count(),
            'sahaba' => Muslim::query()->where('is_published', true)->count(),
        ]);

        // Module cards + hero quick-filter chips — DB-managed Content domain
        // data (forever-cached per locale inside the service).
        $heroCards = $this->homeContentService->activeCards(HomeModuleCardPlacement::HERO, $locale);
        $moduleCards = $this->homeContentService->activeCards(HomeModuleCardPlacement::EXPLORE, $locale);
        $quickFilters = $this->homeContentService->activeChips($locale);

        // The Prophet ﷺ ID card. Every value used to be a hardcoded translation
        // string, so it could not be corrected without a deploy; it now reads
        // from his `muslims` record and its extended profile. Null when the
        // record is absent — the card falls back to the translations, so a
        // fresh install without the People seeders still renders.
        $prophet = Cache::remember(
            "home_prophet_{$locale}",
            1800,
            fn (): ?Muslim => Muslim::query()
                ->published()
                ->whereHas('roles', fn ($roles) => $roles->where('islamic_roles.slug', 'prophet'))
                ->orderBy('id')
                ->first(),
        );

        return view('public.home', compact(
            'prophet',
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
