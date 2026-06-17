<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\AskQuranResultDTO;
use App\Domain\Quran\Application\Queries\AskQuranQuery;
use App\Domain\Quran\Exceptions\MonthlyQuotaExceededException;
use App\Domain\Quran\Models\AiInquiry;
use App\Domain\Quran\Repositories\AiInquiryQuotaRepositoryInterface;
use App\Domain\Quran\Repositories\AiInquiryRepositoryInterface;
use App\Domain\Quran\Services\AnswerHtmlRenderer;
use App\Domain\Quran\Services\AnswerVerseLinker;
use App\Domain\Quran\Services\QuranAiInquiryService;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Orchestration policy for "Ask Quran" AI:
 *   normalize → cache lookup (free) → reserve quota slot → LLM call →
 *   persist + cache → refund the slot on any failure.
 *
 * Cache hits never consume quota; only a successful DeepSeek call does, and a
 * failed call refunds the reserved slot so users are not charged for our errors.
 */
final readonly class AskQuranHandler
{
    public function __construct(
        private AiInquiryRepositoryInterface $inquiries,
        private AiInquiryQuotaRepositoryInterface $quota,
        private QuranAiInquiryService $service,
        private AnswerVerseLinker $linker,
        private AnswerHtmlRenderer $htmlRenderer,
    ) {}

    public function handle(AskQuranQuery $query): AskQuranResultDTO
    {
        $limit = (int) config('services.quran_ai.monthly_quota', 10);
        $period = Carbon::now()->format('Y-m');
        // A detailed answer is a separate, richer generation — it costs 2 quota
        // slots and is cached independently of the concise answer (hash folds the
        // mode in) so the two never overwrite or get served for one another.
        $cost = $query->detailed ? 2 : 1;

        $normalized = $this->normalize($query->question);
        $hash = $this->cacheHash($normalized, $query->locale, $query->detailed);
        $cacheKey = QuranCacheKeys::aiAnswer($hash, $query->locale);

        // 1. Free path — cross-user answer cache (no quota consumed).
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['answer'])) {
            $id = $this->persist($query, $normalized, $hash, (string) $cached['answer'], $cached['citations'] ?? [], AiInquiry::SOURCE_CACHE);

            return new AskQuranResultDTO(
                id: $id,
                answer: (string) $cached['answer'],
                html: $this->htmlRenderer->render((string) $cached['answer']),
                citations: $cached['citations'] ?? [],
                verses: $this->linker->fromAnswer((string) $cached['answer'], $cached['citations'] ?? []),
                source: AiInquiry::SOURCE_CACHE,
                remaining: $this->remaining($query->userId, $period, $limit),
                limit: $limit,
            );
        }

        // 2. Reserve the quota slot(s) atomically (2 for a detailed answer).
        if (! $this->quota->tryReserve($query->userId, $period, $limit, $cost)) {
            throw MonthlyQuotaExceededException::forPeriod($limit, $period);
        }

        // 3. Paid path — refund the slot(s) on ANY failure.
        try {
            $answer = $this->service->answer($query->question, $query->locale, $query->detailed);

            if ($answer->answer === '') {
                throw new \RuntimeException('quran_ai_empty_answer');
            }
        } catch (\Throwable $e) {
            $this->quota->refund($query->userId, $period, $cost);
            throw $e;
        }

        // 4. Persist the durable record + populate the cross-user cache.
        $id = $this->persist(
            $query, $normalized, $hash, $answer->answer, $answer->citations, AiInquiry::SOURCE_LLM,
            model: $answer->model,
            inputTokens: $answer->inputTokens,
            outputTokens: $answer->outputTokens,
            cachedTokens: $answer->cachedTokens,
            toolCalls: $answer->toolCalls,
            costUsd: $answer->costUsd,
        );

        Cache::put(
            $cacheKey,
            ['answer' => $answer->answer, 'citations' => $answer->citations],
            Carbon::now()->addDays((int) config('services.quran_ai.answer_cache_days', 60)),
        );

        return new AskQuranResultDTO(
            id: $id,
            answer: $answer->answer,
            html: $this->htmlRenderer->render($answer->answer),
            citations: $answer->citations,
            verses: $this->linker->fromAnswer($answer->answer, $answer->citations),
            source: AiInquiry::SOURCE_LLM,
            remaining: $this->remaining($query->userId, $period, $limit),
            limit: $limit,
        );
    }

    /**
     * Streaming counterpart of {@see handle()}: same policy (cache → reserve →
     * generate → persist/cache → refund on failure) but the answer is streamed to
     * `$emit` token-by-token. The controller turns each event into an SSE frame.
     *
     * Emitted events (all passed straight to `$emit`):
     *   ['type' => 'status', ...]                              — a grounding step
     *   ['type' => 'delta',  'text' => string]                — an answer token
     *   ['type' => 'done',   'verses' => [...], 'remaining' => int, 'source' => string]
     *   ['type' => 'quota',  'limit' => int]                  — monthly quota exhausted
     *   ['type' => 'error']                                   — generation failed
     *
     * @param  callable(array<string, mixed>): void  $emit
     */
    public function handleStreaming(AskQuranQuery $query, callable $emit): void
    {
        $limit = (int) config('services.quran_ai.monthly_quota', 10);
        $period = Carbon::now()->format('Y-m');
        $cost = $query->detailed ? 2 : 1;

        $normalized = $this->normalize($query->question);
        $hash = $this->cacheHash($normalized, $query->locale, $query->detailed);
        $cacheKey = QuranCacheKeys::aiAnswer($hash, $query->locale);

        // 1. Free path — replay the cached answer (no quota consumed).
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['answer'])) {
            $answer = (string) $cached['answer'];
            $citations = $cached['citations'] ?? [];
            $id = $this->persist($query, $normalized, $hash, $answer, $citations, AiInquiry::SOURCE_CACHE);

            $emit(['type' => 'delta', 'text' => $answer]);
            $emit([
                'type' => 'done',
                'id' => $id,
                'html' => $this->htmlRenderer->render($answer),
                'verses' => $this->linker->fromAnswer($answer, $citations),
                'remaining' => $this->remaining($query->userId, $period, $limit),
                'source' => AiInquiry::SOURCE_CACHE,
            ]);

            return;
        }

        // 2. Reserve the quota slot(s) atomically (2 for a detailed answer).
        if (! $this->quota->tryReserve($query->userId, $period, $limit, $cost)) {
            $emit(['type' => 'quota', 'limit' => $limit]);

            return;
        }

        // 3. Paid path — refund the slot(s) on ANY failure.
        try {
            $answer = $this->service->streamAnswer($query->question, $query->locale, $emit, $query->detailed);

            if ($answer->answer === '') {
                throw new \RuntimeException('quran_ai_empty_answer');
            }
        } catch (\Throwable $e) {
            $this->quota->refund($query->userId, $period, $cost);
            report($e);
            $emit(['type' => 'error']);

            return;
        }

        // 4. Persist + populate the cross-user cache.
        $id = $this->persist(
            $query, $normalized, $hash, $answer->answer, $answer->citations, AiInquiry::SOURCE_LLM,
            model: $answer->model,
            inputTokens: $answer->inputTokens,
            outputTokens: $answer->outputTokens,
            cachedTokens: $answer->cachedTokens,
            toolCalls: $answer->toolCalls,
            costUsd: $answer->costUsd,
        );

        Cache::put(
            $cacheKey,
            ['answer' => $answer->answer, 'citations' => $answer->citations],
            Carbon::now()->addDays((int) config('services.quran_ai.answer_cache_days', 60)),
        );

        $emit([
            'type' => 'done',
            'id' => $id,
            'html' => $this->htmlRenderer->render($answer->answer),
            'verses' => $this->linker->fromAnswer($answer->answer, $answer->citations),
            'remaining' => $this->remaining($query->userId, $period, $limit),
            'source' => AiInquiry::SOURCE_LLM,
        ]);
    }

    /**
     * @param  list<array{tool: string, args: array<string, mixed>}>  $citations
     */
    private function persist(
        AskQuranQuery $query,
        string $normalized,
        string $hash,
        string $answer,
        array $citations,
        string $source,
        ?string $model = null,
        int $inputTokens = 0,
        int $outputTokens = 0,
        int $cachedTokens = 0,
        int $toolCalls = 0,
        float $costUsd = 0.0,
    ): int {
        return (int) $this->inquiries->create([
            'user_id' => $query->userId,
            'locale' => $query->locale,
            'question' => mb_substr($query->question, 0, 255),
            'normalized_question' => mb_substr($normalized, 0, 255),
            'question_hash' => $hash,
            'answer' => $answer,
            'citations' => $citations,
            'source' => $source,
            'model' => $model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cached_tokens' => $cachedTokens,
            'cost_usd' => $costUsd,
            'tool_calls' => $toolCalls,
            'geo' => $query->geo,
        ])->id;
    }

    private function remaining(int $userId, string $period, int $limit): int
    {
        return max(0, $limit - $this->quota->used($userId, $period));
    }

    /**
     * Normalize a question for the cross-user cache key: strip Arabic diacritics
     * and tatweel, lowercase, and collapse whitespace. Mirrors the normalization
     * used by the canonical inquiry search so equivalent questions share a cache.
     */
    private function normalize(string $question): string
    {
        $stripped = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $question) ?? $question;
        $collapsed = preg_replace('/\s+/u', ' ', $stripped) ?? $stripped;

        return mb_strtolower(trim($collapsed));
    }

    /**
     * Cross-user cache / dedup key. Folds in the answer mode ('c' concise / 'd'
     * detailed) so the two variants of the same question are cached separately
     * and a concise answer is never served for a detailed request (or vice versa).
     */
    private function cacheHash(string $normalized, string $locale, bool $detailed): string
    {
        return hash('sha256', $normalized.'|'.$locale.'|'.($detailed ? 'd' : 'c'));
    }
}
