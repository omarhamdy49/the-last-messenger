<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\DTOs\AskQuranQuery;
use App\Domain\Quran\Exceptions\MonthlyQuotaExceededException;
use App\Domain\Quran\Infrastructure\Llm\ChatClientInterface;
use App\Domain\Quran\Infrastructure\Llm\ChatResult;
use App\Domain\Quran\Infrastructure\Mcp\QuranMcpClientInterface;
use App\Domain\Quran\Models\AiInquiry;
use App\Domain\Quran\Repositories\AiInquiryQuotaRepositoryInterface;
use App\Domain\Quran\Services\AskQuranService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

final class AskQuranServiceTest extends TestCase
{
    use RefreshDatabase;

    private function fakeMcp(): QuranMcpClientInterface
    {
        return new class implements QuranMcpClientInterface
        {
            public function grounding(): array
            {
                return ['rules' => 'GROUNDING RULES', 'nonce' => 'gnd-test'];
            }

            public function toolDefinitions(): array
            {
                return [];
            }

            public function callTool(string $name, array $arguments): string
            {
                return '';
            }
        };
    }

    /** A chat client that answers immediately with no tool calls. */
    private function fakeChatSucceeds(): ChatClientInterface
    {
        return new class implements ChatClientInterface
        {
            public function chat(array $messages, array $tools = [], string $toolChoice = 'auto'): ChatResult
            {
                return new ChatResult(
                    message: ['role' => 'assistant', 'content' => 'A grounded answer about [[2:255]]. Grounded in quran.ai: fetch_translation.'],
                    usage: ['prompt_tokens' => 1000, 'completion_tokens' => 200, 'prompt_cache_hit_tokens' => 800, 'prompt_cache_miss_tokens' => 200],
                );
            }

            public function streamChat(array $messages, array $tools, string $toolChoice, callable $onContentDelta): ChatResult
            {
                $content = 'A grounded answer about [[2:255]]. Grounded in quran.ai: fetch_translation.';
                $onContentDelta($content);

                return new ChatResult(
                    message: ['role' => 'assistant', 'content' => $content],
                    usage: ['prompt_tokens' => 1000, 'completion_tokens' => 200, 'prompt_cache_hit_tokens' => 800, 'prompt_cache_miss_tokens' => 200],
                );
            }
        };
    }

    /**
     * A chat client that emits a tool-use preamble + a tool call on the first
     * (tool) round, then the real answer on the second round — reproducing the
     * provider behaviour that leaked "let me fetch…" narration into saved answers.
     */
    private function fakeChatNarratesThenAnswers(): ChatClientInterface
    {
        return new class implements ChatClientInterface
        {
            private int $calls = 0;

            public function chat(array $messages, array $tools = [], string $toolChoice = 'auto'): ChatResult
            {
                return new ChatResult(message: ['role' => 'assistant', 'content' => 'unused'], usage: []);
            }

            public function streamChat(array $messages, array $tools, string $toolChoice, callable $onContentDelta): ChatResult
            {
                $this->calls++;

                if ($this->calls === 1) {
                    $preamble = 'Let me search for the verse and fetch its tafsir.';
                    $onContentDelta($preamble);

                    return new ChatResult(
                        message: [
                            'role' => 'assistant',
                            'content' => $preamble,
                            'tool_calls' => [[
                                'id' => 'call_1',
                                'type' => 'function',
                                'function' => ['name' => 'search_quran', 'arguments' => '{"query":"patience"}'],
                            ]],
                        ],
                        usage: ['prompt_tokens' => 500, 'completion_tokens' => 20, 'prompt_cache_hit_tokens' => 400, 'prompt_cache_miss_tokens' => 100],
                    );
                }

                $answer = 'Patience is praised in [[2:153]].';
                $onContentDelta($answer);

                return new ChatResult(
                    message: ['role' => 'assistant', 'content' => $answer],
                    usage: ['prompt_tokens' => 800, 'completion_tokens' => 30, 'prompt_cache_hit_tokens' => 700, 'prompt_cache_miss_tokens' => 100],
                );
            }
        };
    }

    private function bindClients(ChatClientInterface $chat): void
    {
        $this->app->instance(ChatClientInterface::class, $chat);
        $this->app->instance(QuranMcpClientInterface::class, $this->fakeMcp());
    }

    public function test_llm_path_reserves_quota_persists_and_caches(): void
    {
        config()->set('services.quran_ai.monthly_quota', 10);
        $this->bindClients($this->fakeChatSucceeds());
        $user = User::factory()->create();

        $result = app(AskQuranService::class)->handle(new AskQuranQuery(
            userId: $user->id,
            question: 'What does the Quran say about patience?',
            locale: 'en',
            geo: ['country' => 'EG'],
        ));

        $this->assertSame('llm', $result->source);
        $this->assertSame(9, $result->remaining);
        $this->assertSame(10, $result->limit);

        $this->assertDatabaseHas('ai_inquiries', [
            'user_id' => $user->id,
            'source' => 'llm',
            'locale' => 'en',
        ]);
        $this->assertSame(1, app(AiInquiryQuotaRepositoryInterface::class)->used($user->id, now()->format('Y-m')));
    }

    public function test_streaming_path_emits_deltas_done_with_verses_and_persists(): void
    {
        config()->set('services.quran_ai.monthly_quota', 10);
        $this->bindClients($this->fakeChatSucceeds());
        $user = User::factory()->create();

        $events = [];
        app(AskQuranService::class)->handleStreaming(
            new AskQuranQuery($user->id, 'What does the Quran say about patience while streaming?', 'en'),
            function (array $event) use (&$events): void {
                $events[] = $event;
            },
        );

        $types = array_column($events, 'type');
        $this->assertContains('delta', $types, 'the answer must stream as deltas');
        $this->assertContains('done', $types);

        $done = end($events);
        $this->assertSame('done', $done['type']);
        $this->assertSame('llm', $done['source']);
        // The [[2:255]] marker becomes a structured go-to-verse link in the done event.
        $this->assertSame('2:255', $done['verses'][0]['key']);
        $this->assertStringContainsString('/quran/2#v255', $done['verses'][0]['url']);

        $this->assertDatabaseHas('ai_inquiries', ['user_id' => $user->id, 'source' => 'llm']);
        $this->assertSame(1, app(AiInquiryQuotaRepositoryInterface::class)->used($user->id, now()->format('Y-m')));
    }

    public function test_streaming_discards_tool_round_narration_from_the_saved_answer(): void
    {
        config()->set('services.quran_ai.monthly_quota', 10);
        $this->bindClients($this->fakeChatNarratesThenAnswers());
        $user = User::factory()->create();

        $events = [];
        app(AskQuranService::class)->handleStreaming(
            new AskQuranQuery($user->id, 'What does the Quran say about patience in trials?', 'en'),
            function (array $event) use (&$events): void {
                $events[] = $event;
            },
        );

        // The persisted/cached answer must be only the final round — the tool-use
        // preamble that streamed live must NOT survive into the saved answer.
        $inquiry = AiInquiry::where('user_id', $user->id)->firstOrFail();
        $this->assertStringNotContainsString('Let me search', $inquiry->answer);
        $this->assertStringContainsString('[[2:153]]', $inquiry->answer);

        $done = end($events);
        $this->assertSame('done', $done['type']);
        $this->assertSame('2:153', $done['verses'][0]['key']);
    }

    public function test_streaming_path_emits_a_quota_event_when_exhausted(): void
    {
        config()->set('services.quran_ai.monthly_quota', 1);
        $this->bindClients($this->fakeChatSucceeds());
        $user = User::factory()->create();
        $handler = app(AskQuranService::class);

        $handler->handle(new AskQuranQuery($user->id, 'First unique streaming question on zakat', 'en'));

        $events = [];
        $handler->handleStreaming(
            new AskQuranQuery($user->id, 'Second unique streaming question on hajj', 'en'),
            function (array $event) use (&$events): void {
                $events[] = $event;
            },
        );

        $this->assertSame('quota', $events[0]['type']);
        $this->assertSame(1, $events[0]['limit']);
    }

    public function test_cache_hit_is_free_and_does_not_consume_quota(): void
    {
        config()->set('services.quran_ai.monthly_quota', 10);
        $this->bindClients($this->fakeChatSucceeds());
        $user = User::factory()->create();
        $handler = app(AskQuranService::class);
        $question = 'What does the Quran say about mercy?';

        $first = $handler->handle(new AskQuranQuery($user->id, $question, 'en'));
        $second = $handler->handle(new AskQuranQuery($user->id, $question, 'en'));

        $this->assertSame('llm', $first->source);
        $this->assertSame('cache', $second->source);

        // Only the first (LLM) call consumed quota; the cache hit was free.
        $this->assertSame(1, app(AiInquiryQuotaRepositoryInterface::class)->used($user->id, now()->format('Y-m')));
        $this->assertSame(9, $second->remaining);
        $this->assertDatabaseCount('ai_inquiries', 2); // both persisted for history
    }

    public function test_quota_exhausted_throws(): void
    {
        config()->set('services.quran_ai.monthly_quota', 1);
        $this->bindClients($this->fakeChatSucceeds());
        $user = User::factory()->create();
        $handler = app(AskQuranService::class);

        $handler->handle(new AskQuranQuery($user->id, 'First unique question about charity', 'en'));

        $this->expectException(MonthlyQuotaExceededException::class);
        $handler->handle(new AskQuranQuery($user->id, 'Second unique question about fasting', 'en'));
    }

    public function test_llm_failure_refunds_the_reserved_slot(): void
    {
        config()->set('services.quran_ai.monthly_quota', 10);
        $throwingChat = new class implements ChatClientInterface
        {
            public function chat(array $messages, array $tools = [], string $toolChoice = 'auto'): ChatResult
            {
                throw new RuntimeException('quran_ai_llm_failed');
            }

            public function streamChat(array $messages, array $tools, string $toolChoice, callable $onContentDelta): ChatResult
            {
                throw new RuntimeException('quran_ai_llm_failed');
            }
        };
        $this->bindClients($throwingChat);
        $user = User::factory()->create();

        try {
            app(AskQuranService::class)->handle(new AskQuranQuery($user->id, 'A question that will fail', 'en'));
            $this->fail('Expected the LLM failure to bubble up.');
        } catch (RuntimeException) {
            // expected
        }

        // The reserved slot must have been refunded — user lost nothing.
        $this->assertSame(0, app(AiInquiryQuotaRepositoryInterface::class)->used($user->id, now()->format('Y-m')));
        $this->assertDatabaseCount('ai_inquiries', 0);
    }

    public function test_detailed_answer_reserves_two_quota_slots(): void
    {
        config()->set('services.quran_ai.monthly_quota', 10);
        $this->bindClients($this->fakeChatSucceeds());
        $user = User::factory()->create();

        $result = app(AskQuranService::class)->handle(new AskQuranQuery(
            userId: $user->id,
            question: 'Explain patience in the Quran in detail',
            locale: 'en',
            detailed: true,
        ));

        $this->assertSame('llm', $result->source);
        $this->assertSame(8, $result->remaining); // 10 - 2
        $this->assertSame(2, app(AiInquiryQuotaRepositoryInterface::class)->used($user->id, now()->format('Y-m')));
    }

    public function test_detailed_answer_is_blocked_when_fewer_than_two_slots_remain(): void
    {
        config()->set('services.quran_ai.monthly_quota', 1);
        $this->bindClients($this->fakeChatSucceeds());
        $user = User::factory()->create();

        $this->expectException(MonthlyQuotaExceededException::class);
        app(AskQuranService::class)->handle(new AskQuranQuery(
            userId: $user->id,
            question: 'A detailed question with only one slot left',
            locale: 'en',
            detailed: true,
        ));
    }

    public function test_detailed_and_concise_answers_are_cached_separately(): void
    {
        config()->set('services.quran_ai.monthly_quota', 10);
        $this->bindClients($this->fakeChatSucceeds());
        $user = User::factory()->create();
        $handler = app(AskQuranService::class);
        $question = 'What is the meaning of sabr?';

        $concise = $handler->handle(new AskQuranQuery($user->id, $question, 'en'));
        $detailed = $handler->handle(new AskQuranQuery($user->id, $question, 'en', detailed: true));

        // Different cache keys → both hit the LLM; quota used = 1 (concise) + 2 (detailed).
        $this->assertSame('llm', $concise->source);
        $this->assertSame('llm', $detailed->source);
        $this->assertSame(3, app(AiInquiryQuotaRepositoryInterface::class)->used($user->id, now()->format('Y-m')));

        // Re-asking each variant is a free cache hit (no further quota consumed).
        $this->assertSame('cache', $handler->handle(new AskQuranQuery($user->id, $question, 'en'))->source);
        $this->assertSame('cache', $handler->handle(new AskQuranQuery($user->id, $question, 'en', detailed: true))->source);
        $this->assertSame(3, app(AiInquiryQuotaRepositoryInterface::class)->used($user->id, now()->format('Y-m')));
    }
}
