<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Content\Services\SiteSettingsService;
use App\Domain\Genealogy\Enums\PersonType;
use App\Domain\Genealogy\Models\Person;
use App\Domain\Genealogy\Services\GenealogyService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class GenealogyController extends Controller
{
    /**
     * The stats card advertises the true total (10k+ people), but nothing
     * short of real pagination can let a visitor actually reach all of
     * them — this is both the initial list-view page size and the page
     * size used by loadMore()/search().
     */
    private const PEOPLE_PER_PAGE = 30;

    /**
     * Fallbacks used when the site settings are missing/unseeded, or hold a
     * value outside sane bounds — the tree must always render something
     * reasonable, never an unbounded or empty level.
     */
    private const DEFAULT_MAX_CHILDREN_PER_LEVEL = 10;

    private const DEFAULT_MAX_SPOUSES_SHOWN = 12;

    public function __construct(
        private readonly GenealogyService $genealogyService,
        private readonly SiteSettingsService $siteSettings,
    ) {}

    /**
     * Admin-configurable cap (Site Settings → genealogy_tree group) on how
     * many children/grandchildren render per tree level. Admins curate
     * *which* ones make the cut via each person's tree_display_order /
     * hide_from_tree (see PersonRepository).
     */
    private function maxChildrenPerLevel(): int
    {
        return $this->clampedSetting('genealogy_tree_max_children_per_level', self::DEFAULT_MAX_CHILDREN_PER_LEVEL);
    }

    /**
     * Admin-configurable cap on how many spouses render above the tree.
     */
    private function maxSpousesShown(): int
    {
        return $this->clampedSetting('genealogy_tree_max_spouses_shown', self::DEFAULT_MAX_SPOUSES_SHOWN);
    }

    private function clampedSetting(string $key, int $default): int
    {
        $value = $this->siteSettings->get($key);

        if ($value === null || ! is_numeric($value)) {
            return $default;
        }

        return max(1, min(50, (int) $value));
    }

    /**
     * Genealogy index — the Prophet's family tree (شجرة النسب النبوي).
     *
     * Renders the family tree rooted at the Prophet ﷺ when available;
     * if the tree cannot be built it degrades to a flat grid of all people
     * so the page always has real content to show.
     */
    public function index(): View
    {
        $locale = app()->getLocale();

        // Versioned so a payload-shape change (e.g. new fields added to
        // mapPerson()) can't serve a stale array missing those keys to this
        // method until the 1-hour TTL naturally expires.
        $payload = Cache::remember(
            "public_genealogy_index_v3_{$locale}",
            3600,
            fn (): array => $this->buildPayload($locale),
        );

        return view('public.genealogy.index', [
            'root' => $payload['root'],
            'tree' => $payload['tree'],
            'spouses' => $payload['spouses'],
            'people' => $payload['people'],
            'stats' => $payload['stats'],
            'hasTree' => $payload['hasTree'],
            'spouseIds' => $payload['spouseIds'],
            'childrenIds' => $payload['childrenIds'],
            'grandchildrenIds' => $payload['grandchildrenIds'],
        ]);
    }

    /**
     * Assemble the view payload: root node, nested tree, flat people list and stats.
     *
     * @return array<string, mixed>
     */
    private function buildPayload(string $locale): array
    {
        $totalPeople = $this->countPeople();

        $rootPerson = $this->resolveRootPerson($locale);

        $tree = null;
        $hasTree = false;

        if ($rootPerson !== null) {
            try {
                $familyTree = $this->genealogyService->getFamilyTree($rootPerson['id'], 3, $this->maxChildrenPerLevel());
                $tree = $this->mapTreeNode($familyTree['person'], $familyTree['descendants'] ?? [], $locale);
                $hasTree = ! empty($tree['children']);
            } catch (Throwable $e) {
                report($e);
                $tree = null;
                $hasTree = false;
            }
        }

        // Spouses come from the `relationships` table (relationship_type =
        // spouse), not father_id/mother_id — marriage isn't a descent edge,
        // so it was entirely invisible to a tree built only from parentage.
        $spouses = [];
        if ($rootPerson !== null) {
            try {
                $spouses = $this->genealogyService->getSpouses($rootPerson['id'], $this->maxSpousesShown())
                    ->map(fn (Person $spouse): array => $this->mapPerson($spouse, $locale))
                    ->values()
                    ->all();
            } catch (Throwable $e) {
                report($e);
            }
        }

        // Flat list used by the list view, the search modal and (via
        // childrenIds/grandchildrenIds/spouseIds) the filter pills.
        // getAllPeople() orders by latest() and is capped, so the tree's
        // own root/children/grandchildren/spouses — usually seeded long
        // ago — are rarely among the "latest" batch and would otherwise be
        // entirely absent from the list view, search, and filters despite
        // being right there in the tree above it. Merge them in first so
        // they're always present.
        $people = $this->mapPeople($locale);
        if ($tree !== null) {
            $people = collect($this->flattenTreeNodes($tree))
                ->concat($spouses)
                ->concat($people)
                ->unique('id')
                ->values()
                ->all();
        }

        $childCount = $tree !== null ? count($tree['children']) : 0;

        return [
            'root' => $rootPerson,
            'tree' => $tree,
            'spouses' => $spouses,
            'people' => $people,
            'hasTree' => $hasTree,
            'childrenIds' => $tree !== null ? array_column($tree['children'], 'id') : [],
            'grandchildrenIds' => $tree !== null ? $this->grandchildrenIds($tree) : [],
            'spouseIds' => array_column($spouses, 'id'),
            'stats' => [
                'people' => $totalPeople,
                'children' => $childCount,
                'generations' => 3,
            ],
        ];
    }

    /**
     * IDs of every node two levels below the root — the "children" filter
     * pill in the list view needs these to actually filter (previously a
     * no-op: the flat people list has no depth-relative-to-root info of
     * its own).
     *
     * @param  array<string, mixed>  $tree
     * @return array<int, int>
     */
    private function grandchildrenIds(array $tree): array
    {
        return collect($tree['children'])
            ->flatMap(fn (array $child): array => array_column($child['children'], 'id'))
            ->all();
    }

    /**
     * Flatten a tree node and every descendant into a list of plain person
     * arrays matching mapPerson()'s shape (the nested 'children' key is
     * dropped from each entry — it's tree-structure metadata, not a person
     * field, and keeping it would duplicate whole subtrees into the flat
     * list).
     *
     * @param  array<string, mixed>  $tree
     * @return array<int, array<string, mixed>>
     */
    private function flattenTreeNodes(array $tree): array
    {
        $children = $tree['children'] ?? [];
        $node = $tree;
        unset($node['children']);

        return array_reduce(
            $children,
            fn (array $carry, array $child): array => array_merge($carry, $this->flattenTreeNodes($child)),
            [$node],
        );
    }

    /**
     * Resolve the tree root (the Prophet ﷺ) as a plain array, or null when absent.
     *
     * @return array<string, mixed>|null
     */
    private function resolveRootPerson(string $locale): ?array
    {
        try {
            $prophet = $this->genealogyService
                ->getPeopleByType(PersonType::PROPHET, 1)
                ->first();
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        if (! $prophet instanceof Person) {
            return null;
        }

        return $this->mapPerson($prophet, $locale, isRoot: true);
    }

    /**
     * Recursively map a tree node (person + descendants) into a plain nested array.
     *
     * @param  array<int, array<string, mixed>>  $descendants
     * @return array<string, mixed>
     */
    private function mapTreeNode(Person $person, array $descendants, string $locale): array
    {
        $node = $this->mapPerson($person, $locale);
        $node['children'] = array_map(
            fn (array $child): array => $this->mapTreeNode(
                $child['person'],
                $child['descendants'] ?? [],
                $locale,
            ),
            $descendants,
        );

        return $node;
    }

    /**
     * Map the full people list (real data only) to plain arrays for the list view.
     *
     * @return array<int, array<string, mixed>>
     */
    private function mapPeople(string $locale): array
    {
        try {
            $paginator = $this->genealogyService->getAllPeople(self::PEOPLE_PER_PAGE);
        } catch (Throwable $e) {
            report($e);

            return [];
        }

        return collect($paginator->items())
            ->map(fn (Person $person): array => $this->mapPerson($person, $locale))
            ->values()
            ->all();
    }

    /**
     * Paginated + searchable JSON feed backing the list view's "load more"
     * and the search modal. The page's initial payload only ever carries
     * the tree nodes plus one page of "latest" people (see buildPayload) —
     * with 10k+ genealogy records that stats card total was previously
     * unreachable beyond whatever happened to be preloaded. This is the
     * only way the rest of the family becomes actually browsable/findable.
     *
     * GET /genealogy/people?page=2
     * GET /genealogy/people?q=name&page=1
     */
    public function people(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $locale = app()->getLocale();
        $query = trim((string) ($validated['q'] ?? ''));

        try {
            $paginator = $query !== ''
                ? $this->genealogyService->searchPeople($query, self::PEOPLE_PER_PAGE)
                : $this->genealogyService->getAllPeople(self::PEOPLE_PER_PAGE);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['data' => [], 'hasMore' => false, 'nextPage' => null], 500);
        }

        return response()->json([
            'data' => collect($paginator->items())
                ->map(fn (Person $person): array => $this->mapPerson($person, $locale))
                ->values(),
            'hasMore' => $paginator->hasMorePages(),
            'nextPage' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
            'total' => $paginator->total(),
        ]);
    }

    /**
     * Map a single Person model into the plain array shape the view consumes.
     *
     * @return array<string, mixed>
     */
    private function mapPerson(Person $person, string $locale, bool $isRoot = false): array
    {
        $type = $person->person_type;

        return [
            'id' => $person->id,
            'slug' => $person->slug,
            'name' => $this->localized($person, 'name', $locale),
            'title' => $this->localized($person, 'title', $locale),
            'biography' => $this->localized($person, 'biography', $locale),
            'notes' => $this->localized($person, 'notes', $locale),
            'tribe' => $person->tribe,
            'gender' => $person->gender,
            'type' => $type?->value,
            'typeLabel' => $type?->label()[$locale] ?? ($type?->label()['ar'] ?? null),
            'isRoot' => $isRoot,
            'isProphet' => $type === PersonType::PROPHET,
            // Only real Companions have a public Sahaba profile page — linking
            // there for a tribe/historical-figure entry would 404, since
            // SahabiRepository::findBySlug() is scoped to onlySahaba().
            'hasProfile' => (bool) $person->is_sahabi,
            'birthDateHijri' => $person->birth_date_hijri,
            'deathDateHijri' => $person->death_date_hijri,
            'birthDateGregorian' => $person->birth_date_gregorian?->format('Y-m-d'),
            'deathDateGregorian' => $person->death_date_gregorian?->format('Y-m-d'),
        ];
    }

    /**
     * Read a translatable field with Arabic fallback; never returns null to the view.
     */
    private function localized(Person $person, string $field, string $locale): string
    {
        return $person->getTranslation($field, $locale)
            ?: $person->getTranslation($field, 'ar')
            ?: '';
    }

    /**
     * Total number of people in the genealogy, falling back to relationship count.
     */
    private function countPeople(): int
    {
        try {
            return $this->genealogyService->getAllPeople(1)->total();
        } catch (Throwable $e) {
            report($e);

            try {
                return $this->genealogyService->countRelationships();
            } catch (Throwable) {
                return 0;
            }
        }
    }
}
