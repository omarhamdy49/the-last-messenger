<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\Services\AiInquiryAdminService;
use App\Domain\Quran\Services\AnswerVerseLinker;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quran\PublishInquiryAsFaqRequest;
use App\Http\Requests\Admin\Quran\UpdateInquiryAnswerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin — "Ask Quran" AI inquiry moderation: review, edit the answer, delete,
 * and publish an inquiry into the FAQ module.
 *
 * Authorization is enforced by the admin route group (web + auth + admin role).
 * Each action delegates to exactly one service method (no Eloquent here).
 */
class AiInquiryController extends Controller
{
    public function __construct(
        protected AiInquiryAdminService $service,
        protected AnswerVerseLinker $verseLinker,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'source' => (string) $request->query('source', ''),
            'published' => (string) $request->query('published', ''),
            'search' => trim((string) $request->query('q', '')),
        ];

        return view('admin.quran.ai-inquiries.index', [
            'inquiries' => $this->service->paginate(20, $filters)->appends($request->query()),
            'filters' => $filters,
        ]);
    }

    public function show(int $inquiry): View
    {
        $record = $this->service->find($inquiry);

        abort_if($record === null, 404);

        // Seed the WYSIWYG editor: wrap a plain-text answer as HTML so the editor
        // preserves its line breaks; leave already-HTML answers untouched.
        $answer = (string) $record->answer;
        $seedAnswer = preg_match('/<\/?[a-z][\s\S]*>/i', $answer) === 1
            ? $answer
            : '<p>'.nl2br(e($answer)).'</p>';

        return view('admin.quran.ai-inquiries.show', [
            'inquiry' => $record,
            'seedAnswer' => $seedAnswer,
            'verses' => $this->verseLinker->fromAnswer((string) $record->answer, $record->citations ?? []),
        ]);
    }

    public function updateAnswer(UpdateInquiryAnswerRequest $request, int $inquiry): RedirectResponse
    {
        $updated = $this->service->updateAnswer(
            $inquiry,
            (string) $request->validated('question'),
            (string) $request->validated('answer'),
            (int) auth()->id(),
        );

        abort_unless($updated, 404);

        return redirect()
            ->route('admin.quran.ai-inquiries.show', $inquiry)
            ->with('success', __('admin.quran_ai_answer_updated'));
    }

    /**
     * Existing FAQ categories — lazy-loaded by the publish modal on first open.
     */
    public function categories(): JsonResponse
    {
        return response()->json(['categories' => $this->service->faqCategories()]);
    }

    public function publishAsFaq(PublishInquiryAsFaqRequest $request, int $inquiry): RedirectResponse|JsonResponse
    {
        try {
            $faqId = $this->service->publishAsFaq(
                $inquiry,
                $request->validated('category'),
                $request->boolean('is_active'),
            );
        } catch (\Throwable $exception) {
            report($exception);

            if ($request->expectsJson()) {
                return response()->json(['message' => __('admin.operation_failed')], 422);
            }

            return back()->with('error', __('admin.operation_failed'));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'published_faq_id' => $faqId,
                'message' => __('admin.quran_ai_published_faq'),
            ]);
        }

        return redirect()
            ->route('admin.quran.ai-inquiries.show', $inquiry)
            ->with('success', __('admin.quran_ai_published_faq'));
    }

    public function destroy(int $inquiry): RedirectResponse
    {
        abort_unless($this->service->delete($inquiry), 404);

        return redirect()
            ->route('admin.quran.ai-inquiries.index')
            ->with('success', __('admin.quran_ai_inquiry_deleted'));
    }
}
