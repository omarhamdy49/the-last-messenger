<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seerah;

use App\Domain\Seerah\Models\SeerahMilestone;
use App\Domain\Seerah\Services\SeerahMilestoneService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeerahMilestoneRequest;
use App\Http\Requests\Admin\UpdateSeerahMilestoneRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Seerah Milestone Controller
 * CRUD for the Seerah milestones (الحِقَب) taxonomy.
 */
class SeerahMilestoneController extends Controller
{
    public function __construct(
        protected SeerahMilestoneService $milestoneService,
        protected AdminLoggerService $logger,
    ) {}

    /**
     * GET /su/portal/v1/admin/seerah-milestones
     */
    public function index(Request $request): View
    {
        $filters = array_filter([
            'search' => $request->string('search')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
        ]);

        $milestones = $this->milestoneService->paginateWithFilters($filters, $request->integer('per_page', 15));

        $stats = [
            'total' => $this->milestoneService->count(),
            'active' => $this->milestoneService->count(['is_active' => true]),
            'inactive' => $this->milestoneService->count(['is_active' => false]),
        ];

        return view('admin.seerah-milestones.index', compact('milestones', 'stats'));
    }

    /**
     * GET /su/portal/v1/admin/seerah-milestones/create
     */
    public function create(): View
    {
        return view('admin.seerah-milestones.create');
    }

    /**
     * POST /su/portal/v1/admin/seerah-milestones
     */
    public function store(StoreSeerahMilestoneRequest $request): RedirectResponse
    {
        $milestone = $this->milestoneService->create($this->payload($request));
        $this->syncTranslations($milestone, $request);

        $this->logger->logCreate($milestone, $request->user());

        return redirect()->route('admin.seerah-milestones.index')
            ->with('success', __('admin.milestone_created'));
    }

    /**
     * GET /su/portal/v1/admin/seerah-milestones/{id}
     */
    public function show(int $id): View
    {
        $milestone = $this->milestoneService->findOrFail($id);
        $eventsCount = $milestone->events()->count();

        return view('admin.seerah-milestones.show', compact('milestone', 'eventsCount'));
    }

    /**
     * GET /su/portal/v1/admin/seerah-milestones/{id}/edit
     */
    public function edit(int $id): View
    {
        $milestone = $this->milestoneService->findOrFail($id);

        return view('admin.seerah-milestones.edit', compact('milestone'));
    }

    /**
     * PUT/PATCH /su/portal/v1/admin/seerah-milestones/{id}
     */
    public function update(UpdateSeerahMilestoneRequest $request, int $id): RedirectResponse
    {
        /** @var SeerahMilestone $milestone */
        $milestone = $this->milestoneService->findOrFail($id);
        $oldValues = $milestone->toArray();

        $this->milestoneService->update($id, $this->payload($request));
        $this->syncTranslations($milestone, $request);
        $milestone->refresh();

        $this->logger->logUpdate($milestone, $oldValues, $milestone->toArray(), $request->user());

        return redirect()->route('admin.seerah-milestones.index')
            ->with('success', __('admin.milestone_updated'));
    }

    /**
     * DELETE /su/portal/v1/admin/seerah-milestones/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        /** @var SeerahMilestone $milestone */
        $milestone = $this->milestoneService->findOrFail($id);

        $this->milestoneService->delete($id);
        $this->logger->logDelete($milestone, request()->user());

        return redirect()->route('admin.seerah-milestones.index')
            ->with('success', __('admin.milestone_deleted'));
    }

    /**
     * POST /su/portal/v1/admin/seerah-milestones/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:seerah_milestones,id'],
        ]);

        $ids = array_map('intval', $validated['ids']);
        $deleted = $this->milestoneService->bulkDelete($ids);

        $this->logger->logBulkAction('bulk_delete', SeerahMilestone::class, $ids, $request->user());

        return redirect()->route('admin.seerah-milestones.index')
            ->with('success', __('admin.milestones_deleted', ['count' => $deleted]));
    }

    /**
     * POST /su/portal/v1/admin/seerah-milestones/{id}/toggle-active
     */
    public function toggleActive(int $id, Request $request): RedirectResponse
    {
        $this->milestoneService->findOrFail($id);
        $isActive = $this->milestoneService->toggleActive($id);

        return redirect()->route('admin.seerah-milestones.index')
            ->with('success', $isActive ? __('admin.milestone_activated') : __('admin.milestone_deactivated'));
    }

    /**
     * Persist the translatable title / subtitle / description.
     */
    private function syncTranslations(SeerahMilestone $milestone, Request $request): void
    {
        $milestone->syncTranslations([
            'title' => $request->input('title', []),
            'subtitle' => $request->input('subtitle', []),
            'description' => $request->input('description', []),
        ]);
    }

    /**
     * Non-translatable persistable attributes.
     *
     * @return array<string, mixed>
     */
    private function payload(StoreSeerahMilestoneRequest|UpdateSeerahMilestoneRequest $request): array
    {
        $data = $request->validated();
        unset($data['title'], $data['subtitle'], $data['description']);
        $data['is_active'] = $request->boolean('is_active');
        $data['order_index'] = (int) ($data['order_index'] ?? 0);

        return $data;
    }
}
