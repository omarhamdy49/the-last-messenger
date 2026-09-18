<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site\Quran;

use App\Domain\Quran\DTOs\AskQuranQuery;
use App\Domain\Quran\Exceptions\MonthlyQuotaExceededException;
use App\Domain\Quran\Repositories\AiInquiryQuotaRepositoryInterface;
use App\Domain\Quran\Repositories\AiInquiryRepositoryInterface;
use App\Domain\Quran\Services\AnswerHtmlRenderer;
use App\Domain\Quran\Services\AnswerVerseLinker;
use App\Domain\Quran\Services\AskQuranService;
use App\Domain\Quran\Services\QuranInquirySearchService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\AskQuranRequest;
use App\Http\Support\QuestionSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * "Ask the Quran": inquiry search and the AI answer (moved from the old QuranController).
 */
final class AskController extends Controller
{
    public function __construct(
        private readonly QuranInquirySearchService $inquirySearchService,
        private readonly AskQuranService $askQuran,
        private readonly AiInquiryQuotaRepositoryInterface $quota,
        private readonly AiInquiryRepositoryInterface $inquiries,
        private readonly AnswerVerseLinker $verseLinker,
        private readonly AnswerHtmlRenderer $answerHtmlRenderer,
    ) {}

    /**
     * "Ask the Quran" — conversational inquiry-search page.
     *
     * Renders the thread shell; the initial inquiry (?q=) and every follow-up are
     * resolved client-side against {@see askSearch()} so the page reads like a chat
     * without a full reload. No LLM — each inquiry is a canonical verse search.
     */
    public function ask(Request $request): View
    {
        $maxChars = (int) config('services.quran_ai.max_chars', 200);
        $minChars = (int) config('services.quran_ai.min_chars', 10);
        $limit = (int) config('services.quran_ai.monthly_quota', 10);

        $initialQuery = trim((string) $request->query('q', ''));
        if (mb_strlen($initialQuery) > $maxChars) {
            $initialQuery = mb_substr($initialQuery, 0, $maxChars);
        }

        $user = $request->user();
        $remaining = $user !== null
            ? max(0, $limit - $this->quota->used((int) $user->id, now()->format('Y-m')))
            : $limit;

        // Deduplicated ask history for the ChatGPT-style aside (registered users).
        $history = $user !== null
            ? $this->inquiries->historyForUser((int) $user->id)
                ->map(static fn ($i): array => ['id' => $i->id, 'question' => $i->question])
                ->all()
            : [];

        // Deep link: ?inquiry=<id> reopens a specific saved Q&A on load/refresh.
        // Only honoured when it belongs to the user (IDOR-safe); else ignored.
        $initialInquiryId = null;
        if ($user !== null) {
            $requested = (int) $request->query('inquiry', 0);
            if ($requested > 0 && $this->inquiries->findForUser($requested, (int) $user->id) !== null) {
                $initialInquiryId = $requested;
            }
        }

        return view('public.quran.ask', [
            'initialQuery' => $initialQuery,
            'isAuthenticated' => $user !== null,
            'remaining' => $remaining,
            'limit' => $limit,
            'minChars' => $minChars,
            'maxChars' => $maxChars,
            'history' => $history,
            'initialInquiryId' => $initialInquiryId,
        ]);
    }

    /**
     * Return one of the current user's past inquiries (Q&A) for the history
     * aside. IDOR-safe — scoped to the authenticated user.
     */
    public function askHistory(Request $request, int $inquiry): JsonResponse
    {
        $record = $this->inquiries->findForUser($inquiry, (int) $request->user()->id);

        if ($record === null) {
            return response()->json(['message' => __('quran.public.ask_error')], 404);
        }

        return response()->json([
            'question' => $record->question,
            'answer' => $record->answer,
            // Already-formatted premium HTML, rendered by the backend (no LLM) so
            // the saved answer renders instantly without re-parsing on the client.
            'html' => $this->answerHtmlRenderer->render((string) $record->answer),
            'citations' => $record->citations ?? [],
            'verses' => $this->verseLinker->fromAnswer((string) $record->answer, $record->citations ?? []),
            'source' => $record->source,
        ]);
    }

    /**
     * JSON endpoint backing the inquiry thread. Rate limited (throttle:search).
     */
    public function askSearch(Request $request): JsonResponse
    {
        // Sanitise to the same allowlist as the AI question before validating.
        $request->merge(['q' => QuestionSanitizer::sanitize((string) $request->input('q', ''))]);

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:150', 'regex:'.QuestionSanitizer::PATTERN],
        ]);

        try {
            $payload = $this->inquirySearchService->search(
                query: $validated['q'],
                locale: app()->getLocale(),
                limit: 20,
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => __('quran.public.ask_error'),
            ], 502);
        }

        return response()->json($payload);
    }

    /**
     * "Ask Quran" AI endpoint — registered users only, monthly-quota limited.
     *
     * Grounds the answer via DeepSeek + the quran.ai MCP. Auth + per-minute burst
     * throttle are applied at the route; the monthly quota is enforced in the
     * handler. Returns a flat JSON shape the Alpine composer consumes.
     */
    public function askAi(AskQuranRequest $request): JsonResponse
    {
        try {
            $result = $this->askQuran->handle(new AskQuranQuery(
                userId: (int) $request->user()->id,
                question: (string) $request->validated('question'),
                locale: app()->getLocale(),
                geo: $this->extractGeo($request),
                detailed: $request->boolean('detailed'),
            ));
        } catch (MonthlyQuotaExceededException $e) {
            return response()->json([
                'message' => __('quran.public.ask_quota_exceeded'),
                'remaining' => 0,
                'limit' => $e->limit,
            ], 429);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => __('quran.public.ask_error')], 502);
        }

        return response()->json([
            'id' => $result->id,
            'answer' => $result->answer,
            'html' => $result->html,
            'citations' => $result->citations,
            'verses' => $result->verses,
            'source' => $result->source,
            'remaining' => $result->remaining,
            'limit' => $result->limit,
        ]);
    }

    /**
     * Streaming "Ask Quran" AI endpoint (Server-Sent Events).
     *
     * Same auth + quota policy as {@see askAi()}, but the answer streams to the
     * browser token-by-token (with grounding-progress events) for a live,
     * ChatGPT-style experience. The client falls back to {@see askAi()} when
     * streaming is unavailable.
     */
    public function askAiStream(AskQuranRequest $request): StreamedResponse
    {
        $userId = (int) $request->user()->id;
        $question = (string) $request->validated('question');
        $locale = app()->getLocale();
        $geo = $this->extractGeo($request);
        $detailed = $request->boolean('detailed');

        // Release the session lock so the long-lived stream cannot block the
        // user's other requests (history aside, navigation) for its duration.
        if ($request->hasSession()) {
            $request->session()->save();
        }

        return response()->stream(function () use ($userId, $question, $locale, $geo, $detailed): void {
            $emit = static function (array $event): void {
                echo 'data: '.json_encode($event, JSON_UNESCAPED_UNICODE)."\n\n";
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            };

            try {
                $this->askQuran->handleStreaming(new AskQuranQuery(
                    userId: $userId,
                    question: $question,
                    locale: $locale,
                    geo: $geo,
                    detailed: $detailed,
                ), $emit);
            } catch (Throwable $e) {
                report($e);
                $emit(['type' => 'error']);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Whitelist the advisory client geo headers — country + city only (see AQ-18).
     * Untrusted and spoofable — stored for analytics only, never used for
     * auth/quota/pricing.
     *
     * @return array<string, string>|null
     */
    private function extractGeo(Request $request): ?array
    {
        $country = strtoupper(substr((string) $request->header('X-Geo-Country', ''), 0, 2));

        $geo = array_filter([
            'country' => preg_match('/^[A-Z]{2}$/', $country) === 1 ? $country : null,
            'city' => $this->clipHeader($request->header('X-Geo-City'), 64),
        ], static fn ($value): bool => $value !== null);

        return $geo === [] ? null : $geo;
    }

    private function clipHeader(?string $value, int $max): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
