<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Domain\Content\Services\FaqService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\StoreFaqRequest;
use App\Http\Requests\Admin\Content\UpdateFaqRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin — FAQs with category, ordering, and per-locale
 * question / rich-text answer.
 */
class FaqController extends Controller
{
    public function __construct(
        protected FaqService $service,
    ) {}

    /**
     * List every FAQ.
     */
    public function index(): View
    {
        return view('admin.content.faqs.index', [
            'faqs' => $this->service->faqsForAdmin(),
        ]);
    }

    /**
     * Show the create form.
     */
    public function create(): View
    {
        return view('admin.content.faqs.create');
    }

    /**
     * Store a new FAQ (answer HTML is sanitized by the FormRequest).
     */
    public function store(StoreFaqRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->service->create(
                $this->attributesFrom($validated, $request),
                ['question' => $validated['question'], 'answer' => $validated['answer']],
            );

            return redirect()
                ->route('admin.content.faqs.index')
                ->with('success', __('admin.content_faq_created'));
        } catch (\Throwable $exception) {
            \Log::error('admin.content.faq.create_failed', [
                'user_id' => auth()->id(),
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', __('admin.operation_failed'));
        }
    }

    /**
     * Show the edit form.
     */
    public function edit(int $faq): View
    {
        $record = $this->service->faqForAdmin($faq);

        abort_if($record === null, 404);

        return view('admin.content.faqs.edit', ['faq' => $record]);
    }

    /**
     * Update a FAQ (answer HTML is sanitized by the FormRequest).
     */
    public function update(UpdateFaqRequest $request, int $faq): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $record = $this->service->update(
                $faq,
                $this->attributesFrom($validated, $request),
                ['question' => $validated['question'], 'answer' => $validated['answer']],
            );
        } catch (\Throwable $exception) {
            \Log::error('admin.content.faq.update_failed', [
                'user_id' => auth()->id(),
                'faq_id' => $faq,
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', __('admin.operation_failed'));
        }

        abort_if($record === null, 404);

        return redirect()
            ->route('admin.content.faqs.index')
            ->with('success', __('admin.content_faq_updated'));
    }

    /**
     * Delete a FAQ.
     */
    public function destroy(int $faq): RedirectResponse
    {
        $deleted = $this->service->delete($faq);

        abort_unless($deleted, 404);

        return redirect()
            ->route('admin.content.faqs.index')
            ->with('success', __('admin.content_faq_deleted'));
    }

    /**
     * Map validated input to FAQ columns.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributesFrom(array $validated, Request $request): array
    {
        return [
            'category' => $validated['category'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? null,
        ];
    }
}
