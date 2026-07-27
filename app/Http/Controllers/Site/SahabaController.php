<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Sahaba\Enums\CompanionStatus;
use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Sahaba\Services\SahabiService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class SahabaController extends Controller
{
    public function __construct(
        private readonly SahabiService $sahabiService,
    ) {}

    /**
     * Companions listing — hero + stats, search, status/featured filter pills,
     * grid of sahaba cards, pagination.
     */
    public function index(Request $request): View
    {
        $locale = app()->getLocale();

        $perPage = (int) $request->query('per_page', 20);
        $perPage = in_array($perPage, [20, 40, 60], true) ? $perPage : 20;
        $page = max(1, (int) $request->query('page', 1));

        $search = trim((string) $request->query('q', ''));
        $search = mb_substr($search, 0, 100);

        $filter = (string) $request->query('filter', 'all');
        $allowedFilters = ['all', 'featured', CompanionStatus::ANSAR->value, CompanionStatus::MUHAJIR->value];
        $filter = in_array($filter, $allowedFilters, true) ? $filter : 'all';

        // Aggregate stats — cached, single query inside the service.
        $stats = Cache::remember(
            "public_sahaba_stats_{$locale}",
            1800,
            fn (): array => $this->resolveStats(),
        );

        // The companion paginator. Search bypasses the cache (highly variable
        // input); the cheap filtered/all listings are cached per page+locale.
        $paginator = $this->resolvePaginator($search, $filter, $perPage);

        $companions = $paginator->getCollection();

        // Filter pills with live counts derived from cached stats.
        $filterPills = $this->buildFilterPills($filter, $stats, $request);

        return view('public.sahaba.index', [
            'companions' => $companions,
            'paginator' => $paginator,
            'stats' => $stats,
            'filterPills' => $filterPills,
            'activeFilter' => $filter,
            'search' => $search,
            'perPage' => $perPage,
            'currentPage' => $page,
        ]);
    }

    /**
     * Single companion profile — story/virtues/martyrdom/timeline/related,
     * matching the sahabaah-single prototype.
     */
    public function show(string $slug): View
    {
        $locale = app()->getLocale();

        try {
            $sahabi = $this->sahabiService->getCompanionBySlug($slug);
        } catch (Throwable) {
            abort(404);
        }

        $sahabi->loadMissing([
            'translations',
            'battles',
            'timelineItems.translations',
            'categories',
        ]);

        $profile = $this->mapProfile($sahabi, $locale);

        // Related companions — featured set excluding the current one.
        $related = Cache::remember(
            "public_sahaba_related_{$locale}_{$sahabi->id}",
            1800,
            fn (): array => $this->resolveRelated($sahabi),
        );

        return view('public.sahaba.show', [
            'sahabi' => $sahabi,
            'profile' => $profile,
            'related' => $related,
        ]);
    }

    /**
     * Resolve the paginator for the current search/filter combination.
     */
    private function resolvePaginator(string $search, string $filter, int $perPage): \Illuminate\Pagination\LengthAwarePaginator
    {
        try {
            if ($search !== '') {
                return $this->sahabiService->searchCompanions($search, $perPage);
            }

            if ($filter === 'featured') {
                // Featured returns a Collection; wrap it in a paginator for a
                // consistent view contract.
                $featured = $this->sahabiService->getFeaturedCompanions(48);

                return $this->paginateCollection($featured, $perPage);
            }

            if (in_array($filter, [CompanionStatus::ANSAR->value, CompanionStatus::MUHAJIR->value], true)) {
                return $this->sahabiService->getCompanionsByCategory($filter, $perPage);
            }

            return $this->sahabiService->getAllCompanions($perPage);
        } catch (Throwable $e) {
            report($e);

            return $this->paginateCollection(collect(), $perPage);
        }
    }

    /**
     * Wrap a collection in a LengthAwarePaginator using the current page.
     */
    private function paginateCollection(\Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $items, int $perPage): \Illuminate\Pagination\LengthAwarePaginator
    {
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $total = $items->count();
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()],
        );
    }

    /**
     * Build the status filter pills with counts and query-preserving URLs.
     *
     * @param  array{total:int,male:int,female:int,featured:int,ansar:int,muhajir:int}  $stats
     * @return array<int, array<string, mixed>>
     */
    private function buildFilterPills(string $active, array $stats, Request $request): array
    {
        $base = $request->except(['filter', 'page']);

        $make = fn (string $value): string => $request->fullUrlWithQuery(
            array_merge($base, ['filter' => $value, 'page' => 1]),
        );

        return [
            [
                'value' => 'all',
                'label' => __('sahaba.filter_all'),
                'icon' => 'users',
                'count' => $stats['total'],
                'url' => $make('all'),
            ],
            [
                'value' => 'featured',
                'label' => __('sahaba.filter_featured'),
                'icon' => 'star',
                'count' => $stats['featured'],
                'url' => $make('featured'),
            ],
            [
                'value' => CompanionStatus::ANSAR->value,
                'label' => __('sahaba.filter_ansar'),
                'icon' => 'shield',
                'count' => $stats['ansar'],
                'url' => $make(CompanionStatus::ANSAR->value),
            ],
            [
                'value' => CompanionStatus::MUHAJIR->value,
                'label' => __('sahaba.filter_muhajir'),
                'icon' => 'route',
                'count' => $stats['muhajir'],
                'url' => $make(CompanionStatus::MUHAJIR->value),
            ],
        ];
    }

    /**
     * Resolve aggregate stats for the hero + filter counts.
     *
     * @return array{total:int,male:int,female:int,featured:int,ansar:int,muhajir:int}
     */
    private function resolveStats(): array
    {
        try {
            $base = $this->sahabiService->getPublicIndexStats();

            return [
                'total' => $base['total'] ?? 0,
                'male' => $base['male'] ?? 0,
                'female' => $base['female'] ?? 0,
                'featured' => $this->sahabiService->getFeaturedCompanions(48)->count(),
                'ansar' => $this->sahabiService->getCompanionsByCategory(CompanionStatus::ANSAR, 1)->total(),
                'muhajir' => $this->sahabiService->getCompanionsByCategory(CompanionStatus::MUHAJIR, 1)->total(),
            ];
        } catch (Throwable $e) {
            report($e);

            return ['total' => 0, 'male' => 0, 'female' => 0, 'featured' => 0, 'ansar' => 0, 'muhajir' => 0];
        }
    }

    /**
     * Resolve a small set of related companions for the single page.
     *
     * @return array<int, array<string, mixed>>
     */
    private function resolveRelated(Sahabi $current): array
    {
        try {
            return $this->sahabiService->getFeaturedCompanions(8)
                ->reject(fn (Sahabi $s) => $s->id === $current->id)
                ->take(4)
                ->map(fn (Sahabi $s) => [
                    'slug' => $s->slug,
                    'name' => $s->getTranslation('name', app()->getLocale()) ?: $s->getTranslation('name', 'ar'),
                    'laqab' => $s->getTranslation('laqab', app()->getLocale()) ?: $s->getTranslation('laqab', 'ar'),
                    'isFemale' => $s->gender === 'female',
                ])
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Map a companion model into the plain array the profile view consumes.
     *
     * @return array<string, mixed>
     */
    private function mapProfile(Sahabi $sahabi, string $locale): array
    {
        $tr = fn (string $field): ?string => $sahabi->getTranslation($field, $locale)
            ?: $sahabi->getTranslation($field, 'ar');

        // A Sahabi can hold several historical-rank categories at once (e.g. a
        // Muhajir who also fought at Badr) — join every label rather than
        // picking just one, since the old single-value `status` column could
        // only ever show one even when several applied.
        $categoryLabels = $sahabi->categories
            ->sortByDesc('rank')
            ->map(fn ($category) => CompanionStatus::tryFrom($category->key))
            ->filter()
            ->map(fn (CompanionStatus $category) => $locale === 'ar' ? $category->labelArabic() : $category->label())
            ->values()
            ->all();

        $battles = $sahabi->battles->pluck('battle_name')->filter()->values()->all();

        $timeline = $sahabi->timelineItems
            ->map(fn ($item) => [
                'title' => $item->getTranslation('title', $locale) ?: $item->getTranslation('title', 'ar'),
                'description' => $item->getTranslation('description', $locale) ?: $item->getTranslation('description', 'ar'),
                'hijriDate' => $item->hijri_date,
                'hijriYear' => $item->hijri_year,
                'color' => $item->color ?: 'sky',
            ])
            ->all();

        return [
            'name' => $tr('name'),
            'fullName' => $tr('full_name'),
            'kunya' => $tr('kunya'),
            'laqab' => $tr('laqab'),
            'biography' => $tr('biography'),
            'virtues' => $tr('virtues'),
            'famousNarrations' => $tr('famous_narrations'),
            'martyrdom' => $tr('martyrdom'),
            'isFemale' => $sahabi->gender === 'female',
            'statusLabel' => $categoryLabels !== [] ? implode('، ', $categoryLabels) : null,
            'categories' => $categoryLabels,
            'tribe' => $sahabi->tribe,
            'clan' => $sahabi->clan,
            'birthPlace' => $sahabi->birth_place,
            'deathPlace' => $sahabi->death_place,
            'burialPlace' => $sahabi->burial_place,
            'birthDateHijri' => $sahabi->birth_date_hijri,
            'deathDateHijri' => $sahabi->death_date_hijri,
            'conversionDateHijri' => $sahabi->conversion_date_hijri,
            'conversionOrder' => $sahabi->conversion_order,
            'ageAtDeath' => $sahabi->age_at_death,
            'hadithCount' => $sahabi->reported_hadith_count,
            'roles' => is_array($sahabi->roles) ? $sahabi->roles : [],
            'battles' => $battles,
            'battlesCount' => count($battles),
            'timeline' => $timeline,
            'article' => null,
        ];
    }
}
