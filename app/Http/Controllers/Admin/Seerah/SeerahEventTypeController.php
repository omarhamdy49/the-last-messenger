<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seerah;

use App\Domain\Seerah\Exceptions\SeerahEventTypeInUseException;
use App\Domain\Seerah\Models\SeerahEventType;
use App\Domain\Seerah\Services\SeerahEventTypeService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeerahEventTypeRequest;
use App\Http\Requests\Admin\UpdateSeerahEventTypeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Seerah Event Type Controller
 * CRUD for the DB-driven Seerah event taxonomy.
 */
class SeerahEventTypeController extends Controller
{
    public function __construct(
        protected SeerahEventTypeService $typeService,
        protected AdminLoggerService $logger,
    ) {}

    /**
     * GET /su/portal/v1/admin/seerah-event-types
     */
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
        ];

        $types = $this->typeService->paginateWithFilters(array_filter($filters), $request->integer('per_page', 15));

        $stats = [
            'total' => $this->typeService->count(),
            'active' => $this->typeService->count(['is_active' => true]),
            'inactive' => $this->typeService->count(['is_active' => false]),
        ];

        return view('admin.seerah-event-types.index', compact('types', 'stats'));
    }

    /**
     * GET /su/portal/v1/admin/seerah-event-types/create
     */
    public function create(): View
    {
        return view('admin.seerah-event-types.create');
    }

    /**
     * POST /su/portal/v1/admin/seerah-event-types
     */
    public function store(StoreSeerahEventTypeRequest $request): RedirectResponse
    {
        $type = $this->typeService->create($this->payload($request));

        $this->logger->logCreate($type, $request->user());

        return redirect()->route('admin.seerah-event-types.index')
            ->with('success', __('admin.event_type_created'));
    }

    /**
     * GET /su/portal/v1/admin/seerah-event-types/{id}
     */
    public function show(int $id): View
    {
        $type = $this->typeService->findOrFail($id);
        $eventsCount = $type->events()->count();

        return view('admin.seerah-event-types.show', compact('type', 'eventsCount'));
    }

    /**
     * GET /su/portal/v1/admin/seerah-event-types/{id}/edit
     */
    public function edit(int $id): View
    {
        $type = $this->typeService->findOrFail($id);

        return view('admin.seerah-event-types.edit', compact('type'));
    }

    /**
     * PUT/PATCH /su/portal/v1/admin/seerah-event-types/{id}
     */
    public function update(UpdateSeerahEventTypeRequest $request, int $id): RedirectResponse
    {
        /** @var SeerahEventType $type */
        $type = $this->typeService->findOrFail($id);
        $oldValues = $type->toArray();

        $this->typeService->update($id, $this->payload($request));
        $type->refresh();

        $this->logger->logUpdate($type, $oldValues, $type->toArray(), $request->user());

        return redirect()->route('admin.seerah-event-types.index')
            ->with('success', __('admin.event_type_updated'));
    }

    /**
     * DELETE /su/portal/v1/admin/seerah-event-types/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        /** @var SeerahEventType $type */
        $type = $this->typeService->findOrFail($id);

        try {
            $this->typeService->deleteType($id);
        } catch (SeerahEventTypeInUseException $e) {
            report($e);

            return redirect()->route('admin.seerah-event-types.index')
                ->with('error', __('admin.type_in_use_cannot_delete'));
        }

        $this->logger->logDelete($type, request()->user());

        return redirect()->route('admin.seerah-event-types.index')
            ->with('success', __('admin.event_type_deleted'));
    }

    /**
     * POST /su/portal/v1/admin/seerah-event-types/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:seerah_event_types,id'],
        ]);

        $result = $this->typeService->bulkDelete($validated['ids']);

        $this->logger->logBulkAction('bulk_delete', SeerahEventType::class, $validated['ids'], $request->user());

        $message = __('admin.event_types_deleted', ['count' => $result['deleted']]);
        if ($result['skipped'] > 0) {
            $message .= ' '.__('admin.event_types_skipped_in_use', ['count' => $result['skipped']]);
        }

        return redirect()->route('admin.seerah-event-types.index')->with('success', $message);
    }

    /**
     * POST /su/portal/v1/admin/seerah-event-types/{id}/toggle-active
     */
    public function toggleActive(int $id, Request $request): RedirectResponse
    {
        $this->typeService->findOrFail($id);
        $isActive = $this->typeService->toggleActive($id);

        return redirect()->route('admin.seerah-event-types.index')
            ->with('success', $isActive ? __('admin.event_type_activated') : __('admin.event_type_deactivated'));
    }

    /**
     * Normalise the validated request into a persistable payload.
     *
     * @return array<string, mixed>
     */
    private function payload(StoreSeerahEventTypeRequest|UpdateSeerahEventTypeRequest $request): array
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        foreach (['locale', 'metadata'] as $json) {
            if (array_key_exists($json, $data)) {
                $data[$json] = array_filter((array) $data[$json], fn ($value): bool => filled($value)) ?: null;
            }
        }

        return $data;
    }
}
