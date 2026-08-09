<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\People\Enums\RelationshipType;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Models\MuslimRelationship;
use App\Domain\People\Repositories\MuslimRepositoryInterface;
use App\Domain\People\Services\GenealogyTreeService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The public family tree, rebuilt on `muslims`.
 *
 * WHAT THIS PAGE ACTUALLY SHOWS
 * -----------------------------
 * A nasab is an *ascent*: محمد ﷺ ← عبد الله ← عبد المطلب ← … ← عدنان. That is
 * what the corpus holds and what the page is titled after, and it is what this
 * controller now sends to the view as `lineage`.
 *
 * The previous version sent only `tree` — descendants, walked down
 * `father_id`/`mother_id`. The Prophet ﷺ has no children linked in the corpus,
 * so that tree was a single childless node, `hasTree` came out false, and the
 * page silently fell through to its list fallback. The ancestry was in the
 * database the whole time; nothing ever asked for it.
 *
 * Both directions are sent now, because both are real:
 *   - `lineage`  — ancestors, oldest first, for the vertical spine.
 *   - `tree`     — descendants, for the branch below the focus node.
 * Either may be empty, and the view renders whichever exists.
 *
 * Fields the old `people` table carried and `muslims` does not (tribe, person
 * type, gregorian dates) resolve to null; the view already guards them.
 */
final class GenealogyController extends Controller
{
    /** How many generations of descendants the landing tree renders. */
    private const LANDING_DEPTH = 3;

    /**
     * How far up the nasab to walk. The Prophet's ﷺ chain to عدنان is 21
     * generations; this leaves room for corpora that record more without
     * letting a bad edge run away. GenealogyTreeService::MAX_DEPTH is the
     * hard ceiling and this stays under it.
     */
    private const LINEAGE_DEPTH = 40;

    public function __construct(
        private readonly MuslimRepositoryInterface $muslims,
        private readonly GenealogyTreeService $tree,
    ) {}

    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $rootSlug = (string) $request->query('root', '');

        $payload = $rootSlug !== ''
            ? $this->buildPayload($locale, $rootSlug)
            : Cache::remember(
                "public_genealogy_v2_{$locale}",
                1800,
                fn (): array => $this->buildPayload($locale, null),
            );

        return view('public.genealogy.index', $payload);
    }

    /**
     * Person search behind the tree explorer's picker.
     * Public, so it serves published people only.
     */
    public function people(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $locale = app()->getLocale();

        return response()->json([
            'data' => $this->muslims->search($term, 20)
                ->map(fn (Muslim $m): array => [
                    'id' => $m->id,
                    'slug' => $m->slug,
                    'name' => $m->name($locale),
                    'title' => $m->getTranslation('kunya', $locale) ?: null,
                    'hasProfile' => true,
                ])->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(string $locale, ?string $rootSlug): array
    {
        $root = $rootSlug !== null
            ? $this->muslims->findBySlug($rootSlug)
            : $this->resolveDefaultRoot();

        if ($root === null) {
            return [
                'root' => null, 'rootNode' => null, 'tree' => null,
                'graph' => null, 'focusId' => null,
                'siblings' => [], 'unclesAunts' => [], 'cousins' => [],
                'siblingIds' => [], 'uncleAuntIds' => [], 'cousinIds' => [],
                'lineage' => [], 'lineageComplete' => true,
                'spouses' => [], 'people' => [],
                'hasTree' => false, 'hasLineage' => false,
                'spouseIds' => [], 'childrenIds' => [], 'grandchildrenIds' => [],
                'lineageIds' => [],
                'stats' => ['people' => 0, 'children' => 0, 'generations' => 0],
            ];
        }

        // mapPerson() reports `isProphet` only from an already-loaded roles
        // relation, so it silently answered false for every person on the page
        // — including the Prophet ﷺ himself, whose card therefore never got its
        // badge. Every path that maps a person now loads roles first.
        $root->loadMissing('roles');

        $tree = $this->buildNode($root, $locale, self::LANDING_DEPTH, true);
        ['nodes' => $lineage, 'complete' => $lineageComplete] = $this->buildLineage($root, $locale);

        $spouses = $this->spousesOf($root, $locale);
        $kin = $this->buildKin($root, $locale);
        $graph = $this->buildGraph($root, $lineage, $tree, $locale);

        if ($graph !== null) {
            $this->groupFocusChildrenByMother($graph, $locale);
        }

        // Everyone this page can actually open a detail card for: the ancestry,
        // the descendants, and the spouses. `people` used to be flatten($tree)
        // alone, which on the real corpus was one record — while the counter
        // above it read "1 of 27" from a site-wide published count. The list is
        // now backed by the set it actually renders.
        // The graph goes FIRST into the merge: its copy of each person carries
        // `spouses`, which the detail modal's marriages section reads, and
        // dedupeById keeps the first copy it sees. The other sources cover
        // people the graph does not reach (lineage mothers, the root's own
        // spouses when the graph is absent).
        $people = $this->dedupeById(array_merge(
            $graph !== null ? $this->flatten($graph) : [],
            // The people married INTO the family (عثمان, أبو العاص…) have no
            // descent edge, so they exist only inside some node's `spouses`
            // list — surfaced here so tapping their chip finds a record.
            $graph !== null ? $this->flattenSpouses($graph) : [],
            $this->flatten($tree),
            array_map(
                static fn (array $node): array => $node['mother'] ?? [],
                array_filter($lineage, static fn (array $n): bool => ($n['mother'] ?? null) !== null),
            ),
            $lineage,
            $spouses,
            $kin['siblings'],
            $kin['unclesAunts'],
            $kin['cousins'],
        ));

        return [
            'root' => $root,
            'rootNode' => $tree,
            'tree' => $tree,
            'graph' => $graph,
            'focusId' => $root->id,
            'lineage' => $lineage,
            'lineageComplete' => $lineageComplete,
            'spouses' => $spouses,
            'siblings' => $kin['siblings'],
            'unclesAunts' => $kin['unclesAunts'],
            'cousins' => $kin['cousins'],
            'people' => $people,
            'hasTree' => $tree['children'] !== [],
            'hasLineage' => $lineage !== [],
            'spouseIds' => array_column($spouses, 'id'),
            'lineageIds' => array_column($lineage, 'id'),
            'siblingIds' => array_column($kin['siblings'], 'id'),
            'uncleAuntIds' => array_column($kin['unclesAunts'], 'id'),
            'cousinIds' => array_column($kin['cousins'], 'id'),
            'childrenIds' => array_column($tree['children'], 'id'),
            'grandchildrenIds' => collect($tree['children'])
                ->flatMap(fn (array $c): array => array_column($c['children'], 'id'))
                ->all(),
            'stats' => [
                // The people this page renders, not the site-wide published
                // count — the hero must not advertise records the page cannot show.
                'people' => count($people),
                'children' => count($tree['children']),
                'generations' => count($lineage),
            ],
        ];
    }

    /**
     * The nasab: the paternal chain above the root, oldest first.
     *
     * The spine follows fathers, which is what a nasab is. A mother is not
     * dropped — she is attached to the generation she belongs to, so the view
     * can hang her off the rail beside her son rather than inventing a second
     * competing spine.
     *
     * The walk stops at the first ancestor that is unpublished or hidden and
     * reports `complete: false`, rather than skipping the gap. A chain with a
     * silent hole in it is indistinguishable from a complete one, which is
     * exactly the claim not worth making about a lineage.
     *
     * Both parents come back from GenealogyTreeService::ancestors() in one
     * recursive CTE — depth-capped, so a cycle that slipped past assignParent()
     * terminates instead of spinning. The chain is then ordered in memory off a
     * single bulk fetch, rather than one round trip per generation.
     *
     * @return array{nodes: array<int, array<string, mixed>>, complete: bool}
     */
    private function buildLineage(Muslim $root, string $locale): array
    {
        $result = $this->tree->ancestors($root, self::LINEAGE_DEPTH);
        $ids = array_column($result['nodes'], 'id');

        if ($ids === []) {
            return ['nodes' => [], 'complete' => true];
        }

        $byId = Muslim::query()
            ->whereIn('id', $ids)
            ->with('roles')
            ->get()
            ->keyBy('id');

        $nodes = [];
        $current = $root;
        $complete = true;

        // Follow fathers only. The CTE returns mothers too, and they hang off
        // the generation they belong to rather than extending the spine.
        for ($step = 1; $step <= self::LINEAGE_DEPTH; $step++) {
            $fatherId = $current->father_id;

            if ($fatherId === null) {
                break; // Top of the recorded chain — a complete answer, not a truncation.
            }

            $father = $byId->get($fatherId);

            if ($father === null || ! $father->is_published || $father->hide_from_tree) {
                $complete = false;
                break;
            }

            $node = $this->mapPerson($father, $locale);
            $node['generation'] = $step;
            $node['mother'] = $this->mapMotherOf($current, $byId, $locale);
            $node['childName'] = $current->name($locale);

            $nodes[] = $node;
            $current = $father;
        }

        // Oldest first: the spine reads top-down from عدنان to the focus node.
        return ['nodes' => array_reverse($nodes), 'complete' => $complete];
    }

    /**
     * @param  Collection<int, Muslim>  $byId
     * @return array<string, mixed>|null
     */
    private function mapMotherOf(Muslim $person, $byId, string $locale): ?array
    {
        if ($person->mother_id === null) {
            return null;
        }

        $mother = $byId->get($person->mother_id);

        if ($mother === null || ! $mother->is_published || $mother->hide_from_tree) {
            return null;
        }

        return $this->mapPerson($mother, $locale);
    }

    /**
     * The whole family as ONE rooted hierarchy, for the interactive tree.
     *
     * The ascent and the descent are not two trees. عدنان → … → عبد المطلب →
     * عبد الله → محمد ﷺ → his children is a single path through a single
     * hierarchy; the uncles branch off عبد المطلب, the cousins off them. Rooting
     * at the oldest recorded forefather is what turns the page's two separate
     * lists into one graph a tree layout can actually lay out.
     *
     * Breadth is bounded on purpose. Collateral branches (an uncle's line) stop
     * after their own children — otherwise a well-recorded cousin drags in his
     * whole posterity and the Prophet's ﷺ own line is lost in it. The focus
     * person keeps the full LANDING_DEPTH.
     *
     * Three bulk queries regardless of family size; the whole thing is cached.
     *
     * @param  array<int, array<string, mixed>>  $lineage  oldest first
     * @param  array<string, mixed>  $descendants  the focus person's own subtree
     * @return array<string, mixed>|null
     */
    private function buildGraph(Muslim $root, array $lineage, array $descendants, string $locale): ?array
    {
        if ($lineage === []) {
            return $descendants;
        }

        // The spine, oldest first, with the focus person appended: these are
        // the nodes whose children must be expanded in full.
        $chain = array_column($lineage, 'id');
        $chain[] = $root->id;
        $chainSet = array_flip($chain);

        // Everyone fathered/mothered by anyone on the spine — the spine members
        // themselves plus every collateral (the uncles and aunts).
        $chainChildren = $this->childrenByParent($chain, $locale);

        // One more hop, for the collaterals' own children (the cousins).
        $collateralIds = [];
        foreach ($chainChildren as $siblings) {
            foreach ($siblings as $child) {
                if (! isset($chainSet[$child['id']])) {
                    $collateralIds[] = $child['id'];
                }
            }
        }
        $cousinsByParent = $this->childrenByParent($collateralIds, $locale);

        $lineageById = [];
        foreach ($lineage as $node) {
            $lineageById[$node['id']] = $node;
        }

        // Walk the spine from the oldest forefather down to the focus person.
        $build = function (int $id, int $chainIndex) use (
            &$build, $chain, $chainChildren, $cousinsByParent, $lineageById, $descendants, $root
        ): array {
            // The focus person terminates the spine and carries the full
            // descendant subtree already built for the list view.
            if ($id === $root->id) {
                return $descendants + ['kind' => 'focus'];
            }

            $node = $lineageById[$id];
            $node['kind'] = 'ancestor';
            unset($node['mother'], $node['childName']);

            $nextOnSpine = $chain[$chainIndex + 1] ?? null;
            $node['children'] = [];

            foreach ($chainChildren[$id] ?? [] as $child) {
                if ($child['id'] === $nextOnSpine) {
                    $node['children'][] = $build($child['id'], $chainIndex + 1);

                    continue;
                }

                // A collateral line: keep its own children, then stop.
                $child['kind'] = 'collateral';
                $child['children'] = array_map(
                    static fn (array $c): array => $c + ['kind' => 'collateral', 'children' => []],
                    $cousinsByParent[$child['id']] ?? [],
                );
                $node['children'][] = $child;
            }

            return $node;
        };

        $graph = $build($chain[0], 0);
        $this->attachSpouses($graph, $locale);

        return $graph;
    }

    /**
     * Hang each person's marriages off their node — every node, not only the
     * root. A wife or husband is part of how a person is read in a family
     * tree; showing marriage only for the focus person made every other
     * couple in the record invisible.
     *
     * One bulk read of `muslim_relationships` for every id in the graph, then
     * one bulk read of the counterpart records. The edge is stored once and
     * read from both sides.
     */
    private function attachSpouses(array &$graph, string $locale): void
    {
        $ids = [];
        $collect = function (array $node) use (&$collect, &$ids): void {
            $ids[] = $node['id'];
            foreach ($node['children'] ?? [] as $child) {
                $collect($child);
            }
        };
        $collect($graph);

        $edges = MuslimRelationship::query()
            ->where('relationship_type', RelationshipType::Spouse->value)
            ->where('hide_from_tree', false)
            ->where(fn ($q) => $q->whereIn('muslim_id', $ids)->orWhereIn('related_muslim_id', $ids))
            ->orderBy('sort_order')
            ->get();

        if ($edges->isEmpty()) {
            return;
        }

        $counterpartIds = $edges
            ->flatMap(fn (MuslimRelationship $e): array => [$e->muslim_id, $e->related_muslim_id])
            ->unique();

        $peopleById = Muslim::query()
            ->published()
            ->with('roles')
            ->whereIn('id', $counterpartIds)
            ->get()
            ->keyBy('id');

        // person id → list of mapped spouses.
        $byPerson = [];
        $inGraph = array_flip($ids);

        foreach ($edges as $edge) {
            foreach ([[$edge->muslim_id, $edge->related_muslim_id], [$edge->related_muslim_id, $edge->muslim_id]] as [$self, $other]) {
                if (! isset($inGraph[$self])) {
                    continue;
                }

                $spouse = $peopleById->get($other);

                if ($spouse === null) {
                    continue; // Unpublished counterpart: the marriage is not shown rather than half-shown.
                }

                $byPerson[$self][] = $this->mapPerson($spouse, $locale);
            }
        }

        $decorate = function (array &$node) use (&$decorate, $byPerson): void {
            $node['spouses'] = $byPerson[$node['id']] ?? [];
            foreach ($node['children'] as &$child) {
                $decorate($child);
            }
            unset($child);
        };
        $decorate($graph);
    }

    /**
     * Published children of each of the given parents, grouped by parent id.
     *
     * A child appears under both parents when both are in the set, which is
     * what a tree layout needs; the caller decides which edge to draw.
     *
     * @param  array<int, int>  $parentIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function childrenByParent(array $parentIds, string $locale): array
    {
        if ($parentIds === []) {
            return [];
        }

        $grouped = [];

        $rows = Muslim::query()
            ->published()
            ->with('roles')
            ->where('hide_from_tree', false)
            ->where(fn ($q) => $q
                ->whereIn('father_id', $parentIds)
                ->orWhereIn('mother_id', $parentIds))
            ->orderByRaw('COALESCE(tree_display_order, 4294967295), id')
            ->get();

        $wanted = array_flip($parentIds);

        foreach ($rows as $row) {
            $mapped = $this->mapPerson($row, $locale);

            // Attach to the father where he is in the set, else the mother —
            // never to both, or the layout would draw the same person twice.
            $parent = isset($wanted[$row->father_id]) ? $row->father_id : $row->mother_id;

            if ($parent === null || ! isset($wanted[$parent])) {
                continue;
            }

            $grouped[$parent][] = $mapped;
        }

        return $grouped;
    }

    /**
     * The lateral family: siblings, paternal uncles and aunts, and cousins.
     *
     * NOTHING HERE IS STORED. Every one of these relations is derived from the
     * descent edges that already exist:
     *
     *   siblings     = the other children of my parents
     *   uncles/aunts = the other children of my grandparents, i.e. my
     *                  parents' siblings
     *   cousins      = the children of those
     *
     * `muslim_relationships` does carry `uncle`, `aunt` and `cousin` types, but
     * writing those rows by hand would duplicate a fact the graph already
     * knows, and a duplicated fact is a fact that can disagree with itself —
     * an `uncle` row surviving a correction to `father_id` would contradict the
     * tree it sits in. Marriage is the one relation genuinely not derivable
     * from descent, so that is the one relation read from the table.
     *
     * Three bulk queries regardless of family size, behind a 30-minute cache.
     *
     * @return array{siblings: array<int, array<string, mixed>>, unclesAunts: array<int, array<string, mixed>>, cousins: array<int, array<string, mixed>>}
     */
    private function buildKin(Muslim $root, string $locale): array
    {
        $empty = ['siblings' => [], 'unclesAunts' => [], 'cousins' => []];

        $parentIds = array_values(array_filter([$root->father_id, $root->mother_id]));

        if ($parentIds === []) {
            return $empty;
        }

        // Siblings: my parents' other children.
        $siblings = $this->childrenOf($parentIds, $locale, exclude: [$root->id]);

        // Uncles and aunts: my grandparents' other children — which is to say,
        // my parents' siblings. Excluding my own parents, who are of course
        // also their parents' children.
        $grandparentIds = Muslim::query()
            ->whereIn('id', $parentIds)
            ->get(['father_id', 'mother_id'])
            ->flatMap(fn (Muslim $p): array => array_filter([$p->father_id, $p->mother_id]))
            ->unique()
            ->values()
            ->all();

        if ($grandparentIds === []) {
            return ['siblings' => $siblings, 'unclesAunts' => [], 'cousins' => []];
        }

        $unclesAunts = $this->childrenOf($grandparentIds, $locale, exclude: $parentIds);

        // Cousins: the children of those uncles and aunts.
        $uncleIds = array_column($unclesAunts, 'id');
        $cousins = $uncleIds === [] ? [] : $this->childrenOf($uncleIds, $locale);

        return [
            'siblings' => $siblings,
            'unclesAunts' => $unclesAunts,
            'cousins' => $cousins,
        ];
    }

    /**
     * Published, tree-visible children of any of the given parents.
     *
     * @param  array<int, int>  $parentIds
     * @param  array<int, int>  $exclude
     * @return array<int, array<string, mixed>>
     */
    private function childrenOf(array $parentIds, string $locale, array $exclude = []): array
    {
        if ($parentIds === []) {
            return [];
        }

        return Muslim::query()
            ->published()
            ->with('roles')
            ->where('hide_from_tree', false)
            ->whereNotIn('id', $exclude)
            ->where(fn ($q) => $q
                ->whereIn('father_id', $parentIds)
                ->orWhereIn('mother_id', $parentIds))
            ->orderByRaw('COALESCE(tree_display_order, 4294967295), id')
            ->get()
            ->map(fn (Muslim $m): array => $this->mapPerson($m, $locale))
            ->all();
    }

    /**
     * Marriages — the one relation descent cannot derive.
     *
     * @return array<int, array<string, mixed>>
     */
    private function spousesOf(Muslim $person, string $locale): array
    {
        $slugs = collect($this->tree->relations($person, $locale))
            ->where('type', 'spouse');

        if ($slugs->isEmpty()) {
            return [];
        }

        // relations() returns slugs and display labels only. The cards and the
        // detail modal key off numeric ids and need bios and dates, so the
        // records are re-read here — `id` used to be filled with the *slug*,
        // which meant every spouse chip opened a modal that found nothing.
        $labels = $slugs->pluck('label', 'slug')->all();

        return Muslim::query()
            ->published()
            ->with('roles')
            ->whereIn('slug', array_keys($labels))
            ->get()
            ->map(function (Muslim $m) use ($locale, $labels): array {
                $node = $this->mapPerson($m, $locale);
                $node['title'] ??= $labels[$m->slug] ?? null;

                return $node;
            })
            ->all();
    }

    /**
     * The tree roots at the Prophet ﷺ.
     *
     * Resolved through the `prophet` role, not a hardcoded slug, so it survives
     * the record being renamed. Falling back to "most recorded children" makes
     * the landing tree depend on import order, so that is a last resort only.
     */
    private function resolveDefaultRoot(): ?Muslim
    {
        $prophetId = Muslim::query()
            ->published()
            ->whereHas('roles', fn ($roles) => $roles->where('islamic_roles.slug', 'prophet'))
            ->orderBy('id')
            ->value('id');

        if ($prophetId !== null) {
            return $this->muslims->findById((int) $prophetId);
        }

        $id = Muslim::query()
            ->published()
            ->whereNull('father_id')
            ->withCount('childrenByFather')
            ->orderByDesc('children_by_father_count')
            ->value('id');

        return $id === null
            ? Muslim::query()->published()->orderBy('id')->first()
            : $this->muslims->findById((int) $id);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNode(Muslim $person, string $locale, int $depth, bool $isRoot = false): array
    {
        $node = $this->mapPerson($person, $locale, $isRoot);
        $node['children'] = [];

        if ($depth <= 0) {
            return $node;
        }

        $children = Muslim::query()
            ->published()
            ->with('roles')
            ->where('hide_from_tree', false)
            ->where(fn ($q) => $q->where('father_id', $person->id)->orWhere('mother_id', $person->id))
            ->orderByRaw('COALESCE(tree_display_order, 4294967295), id')
            ->get();

        foreach ($children as $child) {
            $node['children'][] = $this->buildNode($child, $locale, $depth - 1);
        }

        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapPerson(Muslim $person, string $locale, bool $isRoot = false): array
    {
        return [
            'id' => $person->id,
            'slug' => $person->slug,
            'name' => $person->name($locale),
            'title' => $person->getTranslation('kunya', $locale) ?: null,
            'biography' => $person->getTranslation('bio', $locale) ?: null,
            'notes' => null,
            'gender' => $person->gender?->value,
            'isRoot' => $isRoot,
            // Explicit and opt-in. There is no `person_type` column any more,
            // so this is false until someone carries a `prophet` role. Never
            // inferred from being the tree root — see _node.blade.php.
            'isProphet' => $person->relationLoaded('roles')
                ? $person->roles->contains('slug', 'prophet')
                : false,
            // Every person now has a public profile at /sahaba/{slug}; there is
            // no separate "not a companion" case to guard against.
            'hasProfile' => true,
            'birthDateHijri' => $this->formatHijriYear($person->birth_year_hijri),
            'deathDateHijri' => $this->formatHijriYear($person->death_year_hijri),
            // The raw descent edges ride along so the graph can group a
            // node's children under their other parent (see motherGroups()).
            'fatherId' => $person->father_id,
            'motherId' => $person->mother_id,

            // Retired with the old `people` table.
            'tribe' => null,
            'type' => null,
            'typeLabel' => null,
            'birthDateGregorian' => null,
            'deathDateGregorian' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<int, array<string, mixed>>
     */
    private function flatten(array $node): array
    {
        $flat = [$node];

        foreach ($node['children'] as $child) {
            $flat = array_merge($flat, $this->flatten($child));
        }

        return $flat;
    }

    /**
     * Rewrite the focus person's children as marriages: each wife becomes a
     * `spouse` node in the hierarchy, carrying the children she is the mother
     * of. The tree then reads محمد ﷺ → خديجة → القاسم…فاطمة rather than seven
     * children on an undifferentiated rank with no clue who bore whom.
     *
     * Wives with no recorded children still appear — their card IS the record.
     * A mother who is not among the spouses (مارية القبطية is deliberately
     * carried as أم ولد, not among أمهات المؤمنين) still gets a group node, so
     * إبراهيم hangs under his mother rather than floating unexplained; her
     * label stays whatever her own record says. A child whose mother is not
     * published falls back to hanging directly off the focus person.
     *
     * FOCUS ONLY. Doing this to every married node would insert علي as a
     * spouse node under فاطمة while he already stands as a cousin under
     * أبو طالب — one person drawn twice at two ranks. The focus person is the
     * page's subject; everyone else keeps the ♥ line and the modal.
     */
    private function groupFocusChildrenByMother(array &$node, string $locale): void
    {
        if (($node['kind'] ?? null) === 'focus') {
            $node['children'] = $this->marriageGroups($node, $locale);

            return;
        }

        foreach ($node['children'] as &$child) {
            $this->groupFocusChildrenByMother($child, $locale);
        }
        unset($child);
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<int, array<string, mixed>>
     */
    private function marriageGroups(array $node, string $locale): array
    {
        $groups = [];

        // The recorded marriages, in their curated order (خديجة first).
        foreach ($node['spouses'] ?? [] as $spouse) {
            $spouse['kind'] = 'spouse';
            $spouse['children'] = [];
            $groups[$spouse['id']] = $spouse;
        }

        // Mothers of children who are not among the spouses.
        $unmatchedMotherIds = collect($node['children'])
            ->map(fn (array $c): ?int => $c['fatherId'] === $node['id'] ? $c['motherId'] : $c['fatherId'])
            ->filter(fn (?int $id): bool => $id !== null && ! isset($groups[$id]))
            ->unique()
            ->values();

        if ($unmatchedMotherIds->isNotEmpty()) {
            $mothers = Muslim::query()
                ->published()
                ->with('roles')
                ->whereIn('id', $unmatchedMotherIds)
                ->get();

            foreach ($mothers as $mother) {
                $group = $this->mapPerson($mother, $locale);
                $group['kind'] = 'spouse';
                $group['children'] = [];
                $groups[$group['id']] = $group;
            }
        }

        $direct = [];

        foreach ($node['children'] as $child) {
            $otherParent = $child['fatherId'] === $node['id'] ? $child['motherId'] : $child['fatherId'];

            if ($otherParent !== null && isset($groups[$otherParent])) {
                $groups[$otherParent]['children'][] = $child;
            } else {
                $direct[] = $child;
            }
        }

        return array_merge(array_values($groups), $direct);
    }

    /**
     * Hijri years are stored as signed integers — negative means before the
     * Hijra. Rendered raw, "-53" read as a typo; the sign is really an era.
     */
    private function formatHijriYear(?int $year): ?string
    {
        if ($year === null) {
            return null;
        }

        return $year < 0
            ? abs($year).' '.__('genealogy.public.before_hijra')
            : $year.' '.__('genealogy.public.year_hijri');
    }

    /**
     * Every spouse hanging off any node in the tree.
     *
     * @param  array<string, mixed>  $node
     * @return array<int, array<string, mixed>>
     */
    private function flattenSpouses(array $node): array
    {
        $found = $node['spouses'] ?? [];

        foreach ($node['children'] as $child) {
            $found = array_merge($found, $this->flattenSpouses($child));
        }

        return $found;
    }

    /**
     * @param  array<int, array<string, mixed>>  $people
     * @return array<int, array<string, mixed>>
     */
    private function dedupeById(array $people): array
    {
        $byId = [];

        foreach ($people as $person) {
            if ($person === [] || ! isset($person['id'])) {
                continue;
            }
            // First writer wins: the descendant tree carries `isRoot`, and a
            // later lineage/spouse copy of the same person must not clear it.
            $byId[$person['id']] ??= $person;
        }

        return array_values($byId);
    }
}
