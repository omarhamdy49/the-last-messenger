<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Genealogy\Services\GenealogyService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGenealogyRequest;
use App\Http\Requests\Admin\UpdateGenealogyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
    public function index(Request $request): View
    {
        $perPage = $request->integer('per_page', 15);
        $people = $this->genealogyService->getAllPeople($perPage);

        $stats = [
            'total' => $this->genealogyService->count(),
            'relationships' => $this->genealogyService->countRelationships(),
        ];

        return view('admin.genealogy.index', compact('people', 'stats'));
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
        $person = $this->genealogyService->createPerson($request->validated());

        // Log the creation
        $this->logger->logCreate($person, $request->user());

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
        $person = $this->genealogyService->getPerson($id);

        if (! $person) {
            abort(404, 'Person not found');
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
        $person = $this->genealogyService->getPerson($id);

        if (! $person) {
            abort(404, 'Person not found');
        }

        return view('admin.genealogy.edit', compact('person'));
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
            'ids.*' => ['integer', 'exists:people,id'],
        ]);

        $ids = $request->input('ids');
        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $this->genealogyService->deletePerson($id);
                $deleted++;
            } catch (\Exception $e) {
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

        return redirect()->route('admin.genealogy.index')
            ->with('success', "تم حذف {$deleted} شخص بنجاح");
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
