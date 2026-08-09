<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\People\Enums\RelationshipType;
use App\Domain\People\Exceptions\GenealogyCycleException;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Models\MuslimRelationship;
use App\Domain\People\Repositories\MuslimRepositoryInterface;
use App\Domain\People\Services\GenealogyTreeService;
use App\Domain\People\Services\MuslimRelationshipService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMuslimRelationshipRequest;
use App\Http\Requests\Admin\UpdateGenealogyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin control over the family tree.
 *
 * Two halves, because the data has two halves:
 *
 * - DESCENT is `muslims.father_id` / `mother_id`. Every write goes through
 *   GenealogyTreeService::assignParent, never straight to the column — the
 *   ancestor walk is the only thing standing between an editor and a cyclic,
 *   unrenderable tree.
 * - NON-DESCENT edges (spouse, sibling, uncle, aunt, cousin) are rows in
 *   `muslim_relationships`, written through MuslimRelationshipService so pair
 *   order is normalised and the unique index can do its job.
 *
 * Everything here reads with publishedOnly: false. An unpublished person is
 * still a valid ancestor, and an editor who cannot see draft relatives cannot
 * tell an incomplete tree from a finished one.
 */
class GenealogyController extends Controller
{
    /** Default generations rendered either side of the root. */
    private const DEFAULT_DEPTH = 4;

    private const MAX_DEPTH = 12;

    public function __construct(
        private readonly MuslimRepositoryInterface $muslims,
        private readonly GenealogyTreeService $tree,
        private readonly MuslimRelationshipService $relationships,
    ) {}

    /**
     * The tree explorer. Any person can be the root; `?root=<slug>` picks one.
     */
    public function index(Request $request): View
    {
        $depth = $this->resolveDepth($request);
        $root = $this->resolveRoot($request);

        if ($root === null) {
            return view('admin.genealogy.index', [
                'root' => null,
                'depth' => $depth,
                'ancestors' => null,
                'descendantTree' => [],
                'descendants' => null,
                'relations' => [],
                'stats' => $this->stats(),
            ]);
        }

        $descendants = $this->tree->descendants($root, $depth);

        return view('admin.genealogy.index', [
            'root' => $root,
            'depth' => $depth,
            'ancestors' => $this->tree->ancestors($root, $depth),
            // Nested for rendering; the service returns a flat depth-ordered
            // list, which a tree view cannot indent on its own.
            'descendantTree' => $this->nestDescendants($root, $descendants['nodes']),
            'descendants' => $descendants,
            'relations' => $this->tree->relations($root, app()->getLocale(), publishedOnly: false),
            'stats' => $this->stats(),
        ]);
    }

    /**
     * One person's genealogy: parents, children, and non-descent edges.
     */
    public function show(Muslim $muslim): View
    {
        return view('admin.genealogy.show', [
            'muslim' => $muslim->load([
                'father:id,slug,name_ar,name_en,is_published',
                'mother:id,slug,name_ar,name_en,is_published',
            ]),
            'children' => $this->children($muslim),
            'relationships' => $this->relationshipRows($muslim),
            'relationshipTypes' => RelationshipType::options(app()->getLocale()),
            // Reported so an editor knows when a lineage is deeper than shown.
            'ancestors' => $this->tree->ancestors($muslim, self::DEFAULT_DEPTH),
        ]);
    }

    /**
     * Parents and tree placement.
     *
     * Parent columns never go through the repository directly — see the class
     * docblock. A rejected assignment comes back as a field error on the
     * offending picker, not a flash message, so the fix is where the mistake is.
     */
    public function update(UpdateGenealogyRequest $request, Muslim $muslim): RedirectResponse
    {
        $data = $request->validated();

        foreach (['father_id', 'mother_id'] as $column) {
            try {
                $this->tree->assignParent(
                    $muslim,
                    $column,
                    $data[$column] === null ? null : (int) $data[$column],
                );
            } catch (GenealogyCycleException $e) {
                return back()->withInput()->withErrors([$column => $e->getMessage()]);
            }
        }

        $this->muslims->update($muslim, [
            'tree_display_order' => $data['tree_display_order'],
            'hide_from_tree' => $data['hide_from_tree'],
        ]);

        return redirect()
            ->route('admin.genealogy.show', $muslim)
            ->with('success', __('sahaba.saved'));
    }

    public function storeRelationship(StoreMuslimRelationshipRequest $request, Muslim $muslim): RedirectResponse
    {
        $data = $request->validated();

        $this->relationships->create(
            muslimId: $muslim->id,
            relatedMuslimId: (int) $data['related_muslim_id'],
            type: RelationshipType::from($data['relationship_type']),
            notes: $data['notes'] ?? null,
            sortOrder: (int) ($data['sort_order'] ?? 0),
            hideFromTree: (bool) ($data['hide_from_tree'] ?? false),
        );

        return redirect()
            ->route('admin.genealogy.show', $muslim)
            ->with('success', __('sahaba.relationship_added'));
    }

    public function updateRelationship(
        StoreMuslimRelationshipRequest $request,
        Muslim $muslim,
        MuslimRelationship $relationship,
    ): RedirectResponse {
        $this->assertBelongsTo($relationship, $muslim);

        $data = $request->validated();

        $this->relationships->update(
            $relationship,
            RelationshipType::from($data['relationship_type']),
            $data['notes'] ?? null,
            (int) ($data['sort_order'] ?? 0),
            (bool) ($data['hide_from_tree'] ?? false),
        );

        return redirect()
            ->route('admin.genealogy.show', $muslim)
            ->with('success', __('sahaba.saved'));
    }

    public function destroyRelationship(Muslim $muslim, MuslimRelationship $relationship): RedirectResponse
    {
        $this->assertBelongsTo($relationship, $muslim);

        $this->relationships->delete($relationship);

        return redirect()
            ->route('admin.genealogy.show', $muslim)
            ->with('success', __('sahaba.relationship_deleted'));
    }

    /**
     * A relationship reached through the wrong person is a 404, not a silent
     * edit of someone else's data — either column may be the known side.
     */
    private function assertBelongsTo(MuslimRelationship $relationship, Muslim $muslim): void
    {
        abort_unless(
            $relationship->muslim_id === $muslim->id || $relationship->related_muslim_id === $muslim->id,
            404,
        );
    }

    /**
     * `?root=` accepts a slug; otherwise the tree roots at the Prophet ﷺ.
     *
     * The root is the person carrying the `prophet` role rather than a hardcoded
     * slug, so it stays correct if the record is renamed or re-slugged. Only if
     * nobody carries that role does it fall back to the person with the most
     * recorded children — which makes the landing tree depend on import order,
     * so it is a last resort, not the intent.
     */
    private function resolveRoot(Request $request): ?Muslim
    {
        $slug = $request->query('root');

        if (is_string($slug) && $slug !== '') {
            $root = $this->muslims->findBySlug($slug, publishedOnly: false);

            if ($root !== null) {
                return $root;
            }
        }

        $prophetId = Muslim::query()
            ->whereHas('roles', fn (Builder $roles) => $roles->where('islamic_roles.slug', 'prophet'))
            ->orderBy('id')
            ->value('id');

        if ($prophetId !== null) {
            return $this->muslims->findById((int) $prophetId);
        }

        $id = Muslim::query()
            ->whereNull('father_id')
            ->withCount('childrenByFather')
            ->orderByDesc('children_by_father_count')
            ->value('id');

        return $id === null
            ? Muslim::query()->orderBy('id')->first()
            : $this->muslims->findById((int) $id);
    }

    private function resolveDepth(Request $request): int
    {
        return min(max((int) $request->query('depth', self::DEFAULT_DEPTH), 1), self::MAX_DEPTH);
    }

    /**
     * Direct children of one person, drafts included.
     *
     * @return Collection<int, Muslim>
     */
    private function children(Muslim $muslim): Collection
    {
        return Muslim::query()
            ->select(['id', 'slug', 'name_ar', 'name_en', 'is_published', 'hide_from_tree', 'tree_display_order'])
            ->where(fn ($query) => $query
                ->where('father_id', $muslim->id)
                ->orWhere('mother_id', $muslim->id))
            ->orderByRaw('COALESCE(tree_display_order, 4294967295), id')
            ->get();
    }

    /**
     * The person's edges with everything the table needs: the counterpart, how
     * the row reads from THIS person's side, and the raw type for the edit form.
     *
     * @return array<int, array<string, mixed>>
     */
    private function relationshipRows(Muslim $muslim): array
    {
        $locale = app()->getLocale();

        return MuslimRelationship::query()
            ->where('muslim_id', $muslim->id)
            ->orWhere('related_muslim_id', $muslim->id)
            ->with([
                'muslim:id,slug,name_ar,name_en,is_published',
                'relatedMuslim:id,slug,name_ar,name_en,is_published',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (MuslimRelationship $row) use ($muslim, $locale): array {
                $counterpart = $row->muslim_id === $muslim->id ? $row->relatedMuslim : $row->muslim;

                return [
                    'model' => $row,
                    'counterpart' => $counterpart,
                    'counterpart_name' => $counterpart?->name($locale) ?? '—',
                    // The stored type reads differently from each side, so the
                    // label shown must be the one for the person being edited.
                    'label' => $row->labelFor($muslim->id, $locale),
                    'is_reversed' => $row->muslim_id !== $muslim->id,
                ];
            })
            ->all();
    }

    /**
     * Turn the flat depth-ordered descendant list into a nested structure the
     * tree view can indent. One extra query for the whole subtree, not one per
     * node, and the service's `tree_display_order` sort is preserved because
     * rows are appended in the order they arrive.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function nestDescendants(Muslim $root, array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $inSubtree = array_fill_keys([...$ids, $root->id], true);

        $links = Muslim::query()
            ->whereIn('id', $ids)
            ->get(['id', 'father_id', 'mother_id'])
            ->keyBy('id');

        $childrenByParent = [];

        foreach ($rows as $row) {
            $link = $links->get((int) $row['id']);

            // A node can hang off either parent. Attach it to whichever one is
            // inside this subtree — otherwise a person whose father is outside
            // the rendered depth would be dropped instead of shown.
            $parentId = match (true) {
                isset($inSubtree[(int) $link?->father_id]) => (int) $link->father_id,
                isset($inSubtree[(int) $link?->mother_id]) => (int) $link->mother_id,
                default => $root->id,
            };

            $childrenByParent[$parentId][] = $row;
        }

        return $this->buildBranch($root->id, $childrenByParent);
    }

    /**
     * @param  array<int, array<int, array<string, mixed>>>  $childrenByParent
     * @return array<int, array<string, mixed>>
     */
    private function buildBranch(int $parentId, array $childrenByParent): array
    {
        $branch = [];

        foreach ($childrenByParent[$parentId] ?? [] as $row) {
            $row['children'] = $this->buildBranch((int) $row['id'], $childrenByParent);
            $branch[] = $row;
        }

        return $branch;
    }

    /**
     * @return array{people:int,linked:int,roots:int,edges:int,hidden:int}
     */
    private function stats(): array
    {
        // One pass over `muslims` with conditional aggregates instead of four
        // separate COUNT queries on the same table; `edges` lives elsewhere so
        // it stays its own query.
        $row = Muslim::query()->toBase()->selectRaw(
            'COUNT(*) AS people,'
            // Anyone with at least one recorded parent — the rest are floating
            // and will never appear in a tree that is not rooted at them.
            .' SUM(father_id IS NOT NULL OR mother_id IS NOT NULL) AS linked,'
            .' SUM(father_id IS NULL AND mother_id IS NULL) AS roots,'
            .' SUM(hide_from_tree = 1) AS hidden'
        )->first();

        return [
            'people' => (int) ($row->people ?? 0),
            'linked' => (int) ($row->linked ?? 0),
            'roots' => (int) ($row->roots ?? 0),
            'edges' => MuslimRelationship::query()->count(),
            'hidden' => (int) ($row->hidden ?? 0),
        ];
    }
}
