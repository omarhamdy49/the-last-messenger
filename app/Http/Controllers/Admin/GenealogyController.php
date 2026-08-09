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
        $slug = $request->query('root');
        $root = $this->tree->resolveRoot(is_string($slug) ? $slug : null);

        if ($root === null) {
            return view('admin.genealogy.index', [
                'root' => null,
                'depth' => $depth,
                'ancestors' => null,
                'descendantTree' => [],
                'descendants' => null,
                'relations' => [],
                'stats' => $this->tree->stats(),
            ]);
        }

        $descendants = $this->tree->descendants($root, $depth);

        return view('admin.genealogy.index', [
            'root' => $root,
            'depth' => $depth,
            'ancestors' => $this->tree->ancestors($root, $depth),
            // Nested for rendering; the service returns a flat depth-ordered
            // list, which a tree view cannot indent on its own.
            'descendantTree' => $this->tree->nestDescendants($root, $descendants['nodes']),
            'descendants' => $descendants,
            'relations' => $this->tree->relations($root, app()->getLocale(), publishedOnly: false),
            'stats' => $this->tree->stats(),
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
            'children' => $this->tree->children($muslim),
            'relationships' => $this->tree->relationshipRows($muslim, app()->getLocale()),
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

    private function resolveDepth(Request $request): int
    {
        return min(max((int) $request->query('depth', self::DEFAULT_DEPTH), 1), self::MAX_DEPTH);
    }
}
