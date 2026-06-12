<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Domain\Content\Enums\MenuLocation;
use App\Domain\Content\Services\MenuService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\StoreMenuItemRequest;
use App\Http\Requests\Admin\Content\UpdateMenuItemRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin — public-site navigation menus, managed per location
 * (header / footer columns / social row).
 */
class MenuItemController extends Controller
{
    public function __construct(
        protected MenuService $service,
    ) {}

    /**
     * List every item of one location (defaults to the header menu).
     */
    public function index(Request $request): View
    {
        $location = MenuLocation::tryFrom($request->string('location')->toString())
            ?? MenuLocation::HEADER;

        return view('admin.content.menus.index', [
            'locations' => MenuLocation::cases(),
            'activeLocation' => $location,
            'items' => $this->service->itemsForAdmin($location),
        ]);
    }

    /**
     * Show the create form.
     */
    public function create(Request $request): View
    {
        $location = MenuLocation::tryFrom($request->string('location')->toString())
            ?? MenuLocation::HEADER;

        return view('admin.content.menus.create', [
            'locations' => MenuLocation::cases(),
            'activeLocation' => $location,
            'parents' => $this->service->itemsForAdmin($location),
        ]);
    }

    /**
     * Store a new item.
     */
    public function store(StoreMenuItemRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->service->create(
                $this->attributesFrom($validated, $request),
                ['label' => $validated['label']],
            );

            return redirect()
                ->route('admin.content.menus.index', ['location' => $validated['location']])
                ->with('success', __('admin.content_menu_item_created'));
        } catch (\Throwable $exception) {
            \Log::error('admin.content.menu.create_failed', [
                'user_id' => auth()->id(),
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', __('admin.operation_failed'));
        }
    }

    /**
     * Show the edit form.
     */
    public function edit(int $menu): View
    {
        $item = $this->service->itemForAdmin($menu);

        abort_if($item === null, 404);

        $parents = $this->service->itemsForAdmin($item->location)
            ->reject(fn ($candidate): bool => $candidate->id === $item->id);

        return view('admin.content.menus.edit', [
            'locations' => MenuLocation::cases(),
            'activeLocation' => $item->location,
            'item' => $item,
            'parents' => $parents,
        ]);
    }

    /**
     * Update an item.
     */
    public function update(UpdateMenuItemRequest $request, int $menu): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $item = $this->service->update(
                $menu,
                $this->attributesFrom($validated, $request),
                ['label' => $validated['label']],
            );
        } catch (\Throwable $exception) {
            \Log::error('admin.content.menu.update_failed', [
                'user_id' => auth()->id(),
                'menu_item_id' => $menu,
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', __('admin.operation_failed'));
        }

        abort_if($item === null, 404);

        return redirect()
            ->route('admin.content.menus.index', ['location' => $validated['location']])
            ->with('success', __('admin.content_menu_item_updated'));
    }

    /**
     * Delete an item (children are re-parented to root).
     */
    public function destroy(int $menu): RedirectResponse
    {
        $deleted = $this->service->delete($menu);

        abort_unless($deleted, 404);

        return back()->with('success', __('admin.content_menu_item_deleted'));
    }

    /**
     * Move an item one position up or down among its siblings.
     */
    public function move(Request $request, int $menu): RedirectResponse
    {
        $direction = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ])['direction'];

        $this->service->move($menu, $direction);

        return back();
    }

    /**
     * Map validated input to menu item columns.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributesFrom(array $validated, Request $request): array
    {
        return [
            'location' => $validated['location'],
            'parent_id' => $validated['parent_id'] ?? null,
            'route_name' => $validated['route_name'] ?? null,
            'url' => $validated['url'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? null,
        ];
    }
}
