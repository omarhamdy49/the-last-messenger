<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Domain\Content\Enums\HomeModuleCardPlacement;
use App\Domain\Content\Services\HomeContentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\StoreHomeModuleCardRequest;
use App\Http\Requests\Admin\Content\UpdateHomeModuleCardRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin — homepage module-discovery cards (Seerah / Hadith / Quran / …).
 */
class HomeModuleCardController extends Controller
{
    public function __construct(
        protected HomeContentService $service,
    ) {}

    /**
     * List every card.
     */
    public function index(Request $request): View
    {
        $placement = HomeModuleCardPlacement::tryFrom($request->string('placement')->toString());

        return view('admin.content.home-cards.index', [
            'cards' => $this->service->cardsForAdmin($placement),
            'activePlacement' => $placement?->value ?? 'all',
        ]);
    }

    /**
     * Show the create form.
     */
    public function create(): View
    {
        return view('admin.content.home-cards.create');
    }

    /**
     * Store a new card.
     */
    public function store(StoreHomeModuleCardRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->service->createCard(
                $this->attributesFrom($validated, $request),
                $this->translationsFrom($validated),
            );

            return redirect()
                ->route('admin.content.home-cards.index')
                ->with('success', __('admin.content_card_created'));
        } catch (\Throwable $exception) {
            \Log::error('admin.content.home_card.create_failed', [
                'user_id' => auth()->id(),
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', __('admin.operation_failed'));
        }
    }

    /**
     * Show the edit form.
     */
    public function edit(int $home_card): View
    {
        $card = $this->service->cardForAdmin($home_card);

        abort_if($card === null, 404);

        return view('admin.content.home-cards.edit', ['card' => $card]);
    }

    /**
     * Update a card.
     */
    public function update(UpdateHomeModuleCardRequest $request, int $home_card): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $card = $this->service->updateCard(
                $home_card,
                $this->attributesFrom($validated, $request),
                $this->translationsFrom($validated),
            );
        } catch (\Throwable $exception) {
            \Log::error('admin.content.home_card.update_failed', [
                'user_id' => auth()->id(),
                'home_module_card_id' => $home_card,
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', __('admin.operation_failed'));
        }

        abort_if($card === null, 404);

        return redirect()
            ->route('admin.content.home-cards.index')
            ->with('success', __('admin.content_card_updated'));
    }

    /**
     * Delete a card.
     */
    public function destroy(int $home_card): RedirectResponse
    {
        $deleted = $this->service->deleteCard($home_card);

        abort_unless($deleted, 404);

        return redirect()
            ->route('admin.content.home-cards.index')
            ->with('success', __('admin.content_card_deleted'));
    }

    /**
     * Map validated input to card columns.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributesFrom(array $validated, Request $request): array
    {
        return [
            'key' => $validated['key'],
            'placement' => $validated['placement'],
            'icon' => $validated['icon'] ?? null,
            'image' => $validated['image'] ?? null,
            'target_url' => $validated['target_url'] ?? null,
            'accent_color' => $validated['accent_color'] ?? null,
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? null,
        ];
    }

    /**
     * Map validated input to the translatable field payload.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, array<string, string|null>>
     */
    private function translationsFrom(array $validated): array
    {
        return [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? [],
        ];
    }
}
