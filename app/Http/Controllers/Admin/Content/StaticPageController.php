<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Domain\Content\Services\StaticPageService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\StoreStaticPageRequest;
use App\Http\Requests\Admin\Content\UpdateStaticPageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin — static content pages (about / privacy / terms / …)
 * with per-locale title, rich body and SEO fields.
 */
class StaticPageController extends Controller
{
    public function __construct(
        protected StaticPageService $service,
    ) {}

    /**
     * List every page.
     */
    public function index(): View
    {
        return view('admin.content.static-pages.index', [
            'pages' => $this->service->pagesForAdmin(),
        ]);
    }

    /**
     * Show the create form.
     */
    public function create(): View
    {
        return view('admin.content.static-pages.create');
    }

    /**
     * Store a new page (body HTML is sanitized by the FormRequest).
     */
    public function store(StoreStaticPageRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->service->create(
                $this->attributesFrom($validated, $request),
                $this->translationsFrom($validated),
            );

            return redirect()
                ->route('admin.content.static-pages.index')
                ->with('success', __('admin.content_page_created'));
        } catch (\Throwable $exception) {
            \Log::error('admin.content.static_page.create_failed', [
                'user_id' => auth()->id(),
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', __('admin.operation_failed'));
        }
    }

    /**
     * Show the edit form.
     */
    public function edit(int $static_page): View
    {
        $page = $this->service->pageForAdmin($static_page);

        abort_if($page === null, 404);

        return view('admin.content.static-pages.edit', ['page' => $page]);
    }

    /**
     * Update a page (body HTML is sanitized by the FormRequest).
     */
    public function update(UpdateStaticPageRequest $request, int $static_page): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $page = $this->service->update(
                $static_page,
                $this->attributesFrom($validated, $request),
                $this->translationsFrom($validated),
            );
        } catch (\Throwable $exception) {
            \Log::error('admin.content.static_page.update_failed', [
                'user_id' => auth()->id(),
                'static_page_id' => $static_page,
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', __('admin.operation_failed'));
        }

        abort_if($page === null, 404);

        return redirect()
            ->route('admin.content.static-pages.index')
            ->with('success', __('admin.content_page_updated'));
    }

    /**
     * Delete a page.
     */
    public function destroy(int $static_page): RedirectResponse
    {
        $deleted = $this->service->delete($static_page);

        abort_unless($deleted, 404);

        return redirect()
            ->route('admin.content.static-pages.index')
            ->with('success', __('admin.content_page_deleted'));
    }

    /**
     * Map validated input to page columns.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributesFrom(array $validated, Request $request): array
    {
        return [
            'slug' => $validated['slug'],
            'is_active' => $request->boolean('is_active'),
            'published_at' => $validated['published_at'] ?? null,
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
            'body' => $validated['body'] ?? [],
            'seo_title' => $validated['seo_title'] ?? [],
            'seo_description' => $validated['seo_description'] ?? [],
        ];
    }
}
