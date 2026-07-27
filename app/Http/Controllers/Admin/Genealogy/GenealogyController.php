<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Genealogy;

use App\Domain\Genealogy\DTOs\CreatePersonData;
use App\Domain\Genealogy\DTOs\PersonListFilters;
use App\Domain\Genealogy\Services\GenealogyService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterGenealogyRequest;
use App\Http\Requests\Admin\StoreGenealogyRelationshipRequest;
use App\Http\Requests\Admin\StoreGenealogyRequest;
use App\Http\Requests\Admin\UpdateGenealogyRelationshipRequest;
use App\Http\Requests\Admin\UpdateGenealogyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Admin Genealogy Controller
 * Handles genealogy/family tree management for admin portal
 */
class GenealogyController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected GenealogyService $genealogyService,
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of all genealogy records
     *
     * GET /su/portal/v1/admin/genealogy
     */
    public function index(FilterGenealogyRequest $request): View|JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 15), 10), 100);
        $filters = PersonListFilters::fromValidated($request->validated());

        $people = $this->genealogyService->getAdminPersonList($filters, $perPage);

        $stats = [
            'total' => $this->genealogyService->count(),
            'relationships' => $this->genealogyService->countRelationships(),
        ];

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $people->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $people->currentPage(),
                    'per_page' => $people->perPage(),
                    'total' => $people->total(),
                    'last_page' => $people->lastPage(),
                ],
            ]);
        }

        return view('admin.genealogy.index', compact('people', 'stats', 'filters'));
    }

    /**
     * Show the form for creating a new person
     *
     * GET /su/portal/v1/admin/genealogy/create
     */
    public function create(): View
    {
        return view('admin.genealogy.create');
    }

    /**
     * Store a newly created genealogy record
     *
     * POST /su/portal/v1/admin/genealogy
     */
    public function store(StoreGenealogyRequest $request): RedirectResponse
    {
        $person = $this->genealogyService->createPerson(CreatePersonData::fromRequest($request));

        // Log the creation
        $this->logger->logCreate($person, $request->user());
        $this->forgetPublicGenealogyCache();

        return redirect()->route('admin.genealogy.index')
            ->with('success', 'تم إنشاء الشخص بنجاح');
    }

    /**
     * Display the specified genealogy record
     *
     * GET /su/portal/v1/admin/genealogy/{id}
     */
    public function show(int $id): View
    {
        try {
            $person = $this->genealogyService->getPerson($id);
        } catch (\App\Exceptions\Domain\ResourceNotFoundException $e) {
            abort(404, $e->getMessage());
        }

        return view('admin.genealogy.show', compact('person'));
    }

    /**
     * Show the form for editing a person
     *
     * GET /su/portal/v1/admin/genealogy/{id}/edit
     */
    public function edit(int $id): View
    {
        try {
            $person = $this->genealogyService->getPerson($id);
        } catch (\App\Exceptions\Domain\ResourceNotFoundException $e) {
            abort(404, $e->getMessage());
        }

        $spouseRelationships = $this->genealogyService->getSpouseRelationships($id);

        return view('admin.genealogy.edit', compact('person', 'spouseRelationships'));
    }

    /**
     * Update the specified genealogy record
     *
     * PUT/PATCH /su/portal/v1/admin/genealogy/{id}
     */
    public function update(UpdateGenealogyRequest $request, int $id): RedirectResponse
    {
        // Get old values before update
        $oldPerson = $this->genealogyService->getPerson($id);

        if (! $oldPerson) {
            abort(404, 'Person not found');
        }

        $oldValues = $oldPerson->toArray();

        // Perform update
        $this->genealogyService->updatePerson($id, $request->validated());

        $person = $this->genealogyService->getPerson($id);

        // Log the update with diff
        $this->logger->logUpdate($person, $oldValues, $person->toArray(), $request->user());
        $this->forgetPublicGenealogyCache();

        return redirect()->route('admin.genealogy.index')
            ->with('success', 'تم تحديث الشخص بنجاح');
    }

    /**
     * Remove the specified genealogy record
     *
     * DELETE /su/portal/v1/admin/genealogy/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        // Get model before deletion
        $person = $this->genealogyService->getPerson($id);

        if (! $person) {
            abort(404, 'Person not found');
        }

        // Perform deletion
        $this->genealogyService->deletePerson($id);

        // Log the deletion
        $this->logger->logDelete($person, request()->user());
        $this->forgetPublicGenealogyCache();

        return redirect()->route('admin.genealogy.index')
            ->with('success', 'تم حذف الشخص بنجاح');
    }

    /**
     * Bulk delete genealogy records
     *
     * POST /su/portal/v1/admin/genealogy/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:sahaba,id'],
        ]);

        $ids = $request->input('ids');
        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $this->genealogyService->deletePerson($id);
                $deleted++;
            } catch (\Throwable $e) {
                // Continue deleting
            }
        }

        // Log bulk action
        $this->logger->logBulkAction(
            'bulk_delete',
            \App\Domain\Genealogy\Models\Person::class,
            $ids,
            $request->user()
        );
        $this->forgetPublicGenealogyCache();

        return redirect()->route('admin.genealogy.index')
            ->with('success', "تم حذف {$deleted} شخص بنجاح");
    }

    /**
     * Add a spouse relationship to a person.
     *
     * POST /su/portal/v1/admin/genealogy/{person}/relationships
     */
    public function storeRelationship(StoreGenealogyRelationshipRequest $request, int $person): RedirectResponse
    {
        try {
            $relationship = $this->genealogyService->addSpouse($person, (int) $request->validated('spouse_id'));
        } catch (\App\Exceptions\Domain\ResourceNotFoundException $e) {
            abort(404, $e->getMessage());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['spouse_id' => $e->getMessage()])->withInput();
        }

        $this->logger->logCreate($relationship, $request->user());
        $this->forgetPublicGenealogyCache();

        return redirect()->route('admin.genealogy.edit', $person)
            ->with('success', 'تمت إضافة العلاقة الزوجية بنجاح');
    }

    /**
     * Update a relationship's tree display order and visibility.
     *
     * PUT /su/portal/v1/admin/genealogy/relationships/{relationship}
     */
    public function updateRelationship(UpdateGenealogyRelationshipRequest $request, int $relationship): RedirectResponse
    {
        $displayOrder = $request->validated('display_order');

        $updated = $this->genealogyService->updateRelationshipDisplay(
            $relationship,
            $displayOrder !== null ? (int) $displayOrder : null,
            $request->boolean('hide_from_tree'),
        );

        $this->logger->logUpdate($updated, [], $updated->toArray(), $request->user());
        $this->forgetPublicGenealogyCache();

        $returnTo = $request->integer('return_to') ?: $updated->person_id;

        return redirect()->route('admin.genealogy.edit', $returnTo)
            ->with('success', 'تم تحديث العلاقة بنجاح');
    }

    /**
     * Remove a spouse relationship.
     *
     * DELETE /su/portal/v1/admin/genealogy/relationships/{relationship}
     */
    public function destroyRelationship(Request $request, int $relationship): RedirectResponse
    {
        $existing = $this->genealogyService->getRelationship($relationship);
        $returnTo = $request->integer('return_to') ?: $existing->person_id;

        $this->genealogyService->removeRelationship($relationship);

        $this->logger->logDelete($existing, $request->user());
        $this->forgetPublicGenealogyCache();

        return redirect()->route('admin.genealogy.edit', $returnTo)
            ->with('success', 'تم حذف العلاقة بنجاح');
    }

    /**
     * The public family-tree page caches its full payload per locale for an
     * hour (see Site\GenealogyController::index()) under a key independent
     * of PersonRepository's own version-based cache — bust it for every
     * supported locale so genealogy edits show up immediately instead of
     * after the TTL expires. Uses the static locale list (not
     * Language::getActiveCodes(), which reads the `languages` table) so
     * cache invalidation never silently no-ops if that table is empty,
     * unseeded, or its own 5-minute cache is stale.
     */
    private function forgetPublicGenealogyCache(): void
    {
        foreach (config('translatable.locales') as $locale) {
            Cache::forget("public_genealogy_index_v3_{$locale}");
        }
    }

    /**
     * Search genealogy records
     *
     * GET /su/portal/v1/admin/genealogy/search
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $request->input('q');
        $perPage = $request->integer('per_page', 15);

        $people = $this->genealogyService->searchPeople($query, $perPage);

        return response()->json([
            'success' => true,
            'data' => $people->items(),
            'meta' => [
                'current_page' => $people->currentPage(),
                'last_page' => $people->lastPage(),
                'per_page' => $people->perPage(),
                'total' => $people->total(),
            ],
        ]);
    }
}
