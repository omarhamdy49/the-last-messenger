<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\People\DTOs\MuslimListFilters;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Repositories\MuslimRepositoryInterface;
use App\Domain\People\Services\GenealogyTreeService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The public companions surface.
 *
 * `muslims` is the internal identity table — a companion is a row in it
 * carrying the `sahabi` role. The URLs, views and view-data contract are the
 * ones that existed before this rebuild; only the source of the data changed,
 * so `resources/views/public/sahaba/*` is used unmodified.
 *
 * Fields the old `sahaba` table carried and `muslims` does not (tribe, clan,
 * conversion order, battles, categories, media gallery) resolve to null or an
 * empty array. Every view already guards those sections, so they simply do not
 * render — see Owner Decisions 2, 4 and 6.
 */
final class SahabaController extends Controller
{
    private const PER_PAGE_OPTIONS = [20, 40, 60];

    public function __construct(
        private readonly MuslimRepositoryInterface $muslims,
        private readonly GenealogyTreeService $tree,
    ) {}

    public function index(Request $request): View
    {
        $locale = app()->getLocale();

        $perPage = (int) $request->query('per_page', 20);
        $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 20;

        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $filter = (string) $request->query('filter', 'all');
        $filter = in_array($filter, ['all', 'featured'], true) ? $filter : 'all';

        $paginator = $this->muslims->paginate(new MuslimListFilters(
            roleSlug: 'sahabi',
            featured: $filter === 'featured' ? true : null,
            search: $search !== '' ? $search : null,
            perPage: $perPage,
        ));

        $stats = Cache::remember(
            "public_sahaba_stats_{$locale}",
            1800,
            fn (): array => $this->resolveStats(),
        );

        return view('public.sahaba.index', [
            'companions' => $paginator->getCollection(),
            'paginator' => $paginator,
            'stats' => $stats,
            'filterPills' => $this->buildFilterPills($filter, $stats, $request),
            'activeFilter' => $filter,
            'search' => $search,
            'perPage' => $perPage,
            'currentPage' => $paginator->currentPage(),
        ]);
    }

    public function show(string $slug): View
    {
        $muslim = $this->muslims->findBySlug($slug);

        if ($muslim === null) {
            // Indistinguishable from "does not exist": a different response
            // would leak the existence of unpublished people.
            throw new NotFoundHttpException;
        }

        $locale = app()->getLocale();

        return view('public.sahaba.show', [
            'sahabi' => $muslim,
            'profile' => $this->mapProfile($muslim, $locale),
            'related' => $this->resolveRelated($muslim),
        ]);
    }

    /**
     * The family tree, rooted at this person. Uses the existing genealogy view.
     */
    public function tree(string $slug): RedirectResponse
    {
        $muslim = $this->muslims->findBySlug($slug);

        if ($muslim === null) {
            throw new NotFoundHttpException;
        }

        // Reuse the genealogy explorer, rooted at this person.
        return redirect()->route('public.genealogy', ['root' => $muslim->slug]);
    }

    /**
     * The `$profile` array the existing show view consumes. Keys with no source
     * column in the new schema are returned as null / empty so the view's own
     * guards omit those blocks.
     *
     * @return array<string, mixed>
     */
    private function mapProfile(Muslim $muslim, string $locale): array
    {
        $muslim->loadMissing([
            'roles',
            // Named columns, and birth_place_id is already on the parent row.
            'birthPlace:id,slug,latitude,longitude',
            'birthPlace.translations' => fn ($relation) => $relation
                ->select(['id', 'location_id', 'locale', 'name'])
                ->whereIn('locale', array_unique([$locale, 'ar'])),
        ]);

        // The canonical place when the record resolved to one, otherwise the
        // original recorded text. The stored value is English-only across all
        // 6,677 populated rows, so on an Arabic page the place is the difference
        // between "Medinah" and "المدينة المنورة".
        $birthPlace = $muslim->birthPlace?->name($locale)
            ?: ($muslim->getTranslation('birth_place', $locale) ?: null);

        return [
            'name' => $muslim->name($locale),
            'fullName' => $muslim->name($locale),
            'kunya' => $muslim->getTranslation('kunya', $locale) ?: null,
            'biography' => $muslim->getTranslation('bio', $locale) ?: null,
            'birthPlace' => $birthPlace,
            'isFemale' => $muslim->gender?->value === 'female',
            'birthDateHijri' => $muslim->birth_date_hijri,
            'deathDateHijri' => $muslim->death_date_hijri,
            'hadithCount' => 0,
            'roles' => $muslim->roles->map(fn ($role): string => $role->name($locale))->all(),
            'statusLabel' => $muslim->roles->first()?->name($locale),

            // Retired with the Sahaba module (Owner Decisions 2, 4, 6).
            'laqab' => null,
            'virtues' => null,
            'famousNarrations' => null,
            'martyrdom' => null,
            'categories' => [],
            'tribe' => null,
            'clan' => null,
            'deathPlace' => null,
            'burialPlace' => null,
            'conversionDateHijri' => null,
            'conversionOrder' => null,
            'ageAtDeath' => null,
            'battles' => [],
            'battlesCount' => 0,
            'timeline' => [],
            'article' => null,

            // Presentation-only keys the view expects.
            'title' => $muslim->name($locale),
            'description' => $muslim->getTranslation('bio', $locale) ?: null,
            'hijriDate' => $muslim->death_date_hijri,
            'hijriYear' => $muslim->death_year_hijri,
            'color' => 'from-[#6f42c1] to-[#5a32a3]',
        ];
    }

    /**
     * Featured companions other than this one.
     *
     * @return array<int, Muslim>
     */
    private function resolveRelated(Muslim $muslim): array
    {
        return $this->muslims->paginate(new MuslimListFilters(
            roleSlug: 'sahabi',
            featured: true,
            perPage: 5,
        ))->getCollection()
            ->reject(fn (Muslim $m): bool => $m->id === $muslim->id)
            ->take(4)
            ->values()
            ->all();
    }

    /**
     * @return array{total:int,male:int,female:int,featured:int,ansar:int,muhajir:int}
     */
    private function resolveStats(): array
    {
        $base = Muslim::query()->published()->withRole('sahabi');

        return [
            'total' => (clone $base)->count(),
            'male' => (clone $base)->where('gender', 'male')->count(),
            'female' => (clone $base)->where('gender', 'female')->count(),
            'featured' => Muslim::query()->published()
                ->whereHas('roles', fn ($q) => $q->where('islamic_roles.slug', 'sahabi')
                    ->where('muslims_roles.is_featured', true))
                ->count(),
            // Ansar/Muhajir were companion categories, retired with them.
            'ansar' => 0,
            'muhajir' => 0,
        ];
    }

    /**
     * @param  array{total:int,male:int,female:int,featured:int,ansar:int,muhajir:int}  $stats
     * @return array<int, array<string, mixed>>
     */
    private function buildFilterPills(string $active, array $stats, Request $request): array
    {
        $base = $request->except(['filter', 'page']);
        $make = fn (string $value): string => $request->fullUrlWithQuery(
            array_merge($base, ['filter' => $value, 'page' => 1]),
        );

        // Only `all` and `featured` survive: the Ansar/Muhajir pills were driven
        // by companion categories, which are retired.
        return [
            ['value' => 'all', 'label' => __('sahaba.filter_all'), 'icon' => 'users',
                'count' => $stats['total'], 'url' => $make('all')],
            ['value' => 'featured', 'label' => __('sahaba.filter_featured'), 'icon' => 'star',
                'count' => $stats['featured'], 'url' => $make('featured')],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array<string, mixed>>
     */
    private function visible(array $nodes): array
    {
        return array_values(array_filter(
            $nodes,
            static fn (array $n): bool => (bool) $n['is_published'] && ! (bool) $n['hide_from_tree'],
        ));
    }
}
