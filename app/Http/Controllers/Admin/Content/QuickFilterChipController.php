<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Domain\Content\Services\HomeContentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\StoreQuickFilterChipRequest;
use App\Http\Requests\Admin\Content\UpdateQuickFilterChipRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin — hero quick-search chips on the public home page.
 */
class QuickFilterChipController extends Controller
{
    public function __construct(
        protected HomeContentService $service,
    ) {}

    /**
     * List every chip.
     */
    public function index(): View
    {
        return view('admin.content.quick-filters.index', [
            'chips' => $this->service->chipsForAdmin(),
        ]);
    }

    /**
     * Show the create form.
     */
    public function create(): View
    {
        return view('admin.content.quick-filters.create');
    }

    /**
     * Store a new chip.
     */
    public function store(StoreQuickFilterChipRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->service->createChip(
                $this->attributesFrom($validated, $request),
                ['label' => $validated['label']],
            );

            return redirect()
                ->route('admin.content.quick-filters.index')
                ->with('success', __('admin.content_chip_created'));
        } catch (\Throwable $exception) {
            \Log::error('admin.content.quick_filter.create_failed', [
                'user_id' => auth()->id(),
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', __('admin.operation_failed'));
        }
    }

    /**
     * Show the edit form.
     */
    public function edit(int $quick_filter): View
    {
        $chip = $this->service->chipForAdmin($quick_filter);

        abort_if($chip === null, 404);

        return view('admin.content.quick-filters.edit', ['chip' => $chip]);
    }

    /**
     * Update a chip.
     */
    public function update(UpdateQuickFilterChipRequest $request, int $quick_filter): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $chip = $this->service->updateChip(
                $quick_filter,
                $this->attributesFrom($validated, $request),
                ['label' => $validated['label']],
            );
        } catch (\Throwable $exception) {
            \Log::error('admin.content.quick_filter.update_failed', [
                'user_id' => auth()->id(),
                'quick_filter_chip_id' => $quick_filter,
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', __('admin.operation_failed'));
        }

        abort_if($chip === null, 404);

        return redirect()
            ->route('admin.content.quick-filters.index')
            ->with('success', __('admin.content_chip_updated'));
    }

    /**
     * Delete a chip.
     */
    public function destroy(int $quick_filter): RedirectResponse
    {
        $deleted = $this->service->deleteChip($quick_filter);

        abort_unless($deleted, 404);

        return redirect()
            ->route('admin.content.quick-filters.index')
            ->with('success', __('admin.content_chip_deleted'));
    }

    /**
     * Map validated input to chip columns.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributesFrom(array $validated, Request $request): array
    {
        return [
            'key' => $validated['key'],
            'target_query' => $validated['target_query'] ?? null,
            'target_url' => $validated['target_url'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? null,
        ];
    }
}
