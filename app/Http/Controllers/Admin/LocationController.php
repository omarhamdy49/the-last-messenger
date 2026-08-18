<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\DTOs\LocationData;
use App\Domain\Shared\Enums\LocationType;
use App\Domain\Shared\Exceptions\BusinessRuleViolationException;
use App\Domain\Shared\Models\Location;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Domain\Shared\Services\LocationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLocationRequest;
use App\Http\Requests\Admin\UpdateLocationRequest;
use App\Http\Resources\LocationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Locations Controller
 *
 * CRUD for the shared canonical place vocabulary. Modelled on
 * SeerahEventTypeController — the closest existing shape.
 */
class LocationController extends Controller
{
    /**
     * Sort columns are interpolated into query structure, so they come from an
     * allowlist and never from raw input (constitution VII).
     *
     * @var array<int, string>
     */
    private const SORTABLE = ['display_order', 'slug', 'type', 'depth', 'created_at'];

    public function __construct(
        protected LocationService $locations,
        protected AdminLoggerService $logger,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'q' => $request->string('q')->toString() ?: null,
            'type' => $this->allowedType($request->string('type')->toString()),
            'parent_id' => $request->filled('parent_id') ? $request->integer('parent_id') : null,
            'is_active' => $request->filled('is_active') ? $request->boolean('is_active') : null,
            'has_coordinates' => $request->filled('has_coordinates') ? $request->boolean('has_coordinates') : null,
        ];

        $perPage = min(max($request->integer('per_page', 25), 1), 100);

        $locations = $this->locations->paginate(array_filter(
            $filters,
            static fn ($value): bool => $value !== null
        ), $perPage);

        $types = LocationType::cases();

        return view('admin.locations.index', compact('locations', 'types', 'filters'));
    }

    public function create(): View
    {
        return view('admin.locations.create', [
            'types' => LocationType::cases(),
            'parents' => $this->locations->tree(),
        ]);
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        try {
            $location = $this->locations->create(LocationData::fromValidated($request->validated()));
        } catch (BusinessRuleViolationException $e) {
            report($e);

            return back()->withInput()->withErrors(['aliases' => $e->getMessage()]);
        }

        $this->logger->logCreate($location, $request->user());

        return redirect()->route('admin.locations.index')
            ->with('success', __('locations.messages.created'));
    }

    public function show(int $id): View
    {
        /** @var Location $location */
        $location = $this->locations->tree()->firstWhere('id', $id) ?? abort(404);

        return view('admin.locations.show', [
            'location' => $location,
            'references' => $this->locations->referenceCounts($id),
        ]);
    }

    public function edit(int $id): View
    {
        /** @var Location $location */
        $location = Location::query()->with('translations')->findOrFail($id);

        return view('admin.locations.edit', [
            'location' => $location,
            'types' => LocationType::cases(),
            'parents' => $this->locations->tree()->reject(fn (Location $l): bool => $l->id === $id),
        ]);
    }

    public function update(UpdateLocationRequest $request, int $id): RedirectResponse
    {
        try {
            $location = $this->locations->update($id, LocationData::fromValidated($request->validated()));
        } catch (BusinessRuleViolationException $e) {
            report($e);

            return back()->withInput()->withErrors(['parent_id' => $e->getMessage()]);
        }

        $this->logger->logUpdate($location, [], $location->toArray(), $request->user());

        return redirect()->route('admin.locations.index')
            ->with('success', __('locations.messages.updated'));
    }

    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        /** @var Location $location */
        $location = Location::query()->findOrFail($id);

        try {
            $this->locations->delete($id);
        } catch (BusinessRuleViolationException $e) {
            report($e);

            $detail = $this->describeReferences($this->locations->referenceCounts($id));

            // Content-negotiate: an HTML form must never receive raw JSON.
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('locations.errors.in_use'),
                    'errors' => ['location' => [__('locations.errors.in_use_detail', ['details' => $detail])]],
                ], 422);
            }

            return redirect()->route('admin.locations.index')
                ->with('error', __('locations.errors.in_use').' '.__('locations.errors.in_use_detail', ['details' => $detail]));
        }

        $this->logger->logDelete($location, $request->user());

        return redirect()->route('admin.locations.index')
            ->with('success', __('locations.messages.deleted'));
    }

    /**
     * GET /locations/search — feeds the place pickers on the content forms.
     */
    public function search(Request $request): JsonResponse
    {
        $term = $request->string('q')->toString();
        $limit = min(max($request->integer('limit', 20), 1), 50);

        return response()->json([
            'success' => true,
            'data' => LocationResource::collection($this->locations->search($term, $limit))->resolve(),
        ]);
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:locations,id'],
        ]);

        $deleted = 0;
        $skipped = [];

        foreach ($validated['ids'] as $id) {
            try {
                $this->locations->delete((int) $id);
                $deleted++;
            } catch (BusinessRuleViolationException $e) {
                report($e);
                $skipped[] = $id;
            }
        }

        $this->logger->logBulkAction('bulk_delete', Location::class, $validated['ids'], $request->user());

        // Constitution X: a skipped row is named, never silently dropped.
        $message = __('locations.messages.deleted').' ('.$deleted.')';

        if ($skipped !== []) {
            $message .= ' — '.__('locations.errors.in_use').' ('.count($skipped).')';
        }

        return redirect()->route('admin.locations.index')->with('success', $message);
    }

    public function toggleActive(Request $request, int $id): RedirectResponse
    {
        $location = $this->locations->toggleActive($id);

        return redirect()->route('admin.locations.index')->with(
            'success',
            $location->is_active
                ? __('locations.messages.activated')
                : __('locations.messages.deactivated')
        );
    }

    private function allowedType(string $type): ?string
    {
        return in_array($type, LocationType::values(), true) ? $type : null;
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function describeReferences(array $counts): string
    {
        $parts = [];

        foreach ($counts as $table => $count) {
            $key = "locations.usage.{$table}";
            $translated = __($key, ['count' => $count]);

            $parts[] = $translated === $key ? "{$table}: {$count}" : $translated;
        }

        return implode(' + ', $parts);
    }
}
