<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Infrastructure\Llm\ChatClientInterface;
use App\Domain\Quran\Infrastructure\Llm\ChatResult;
use App\Domain\Quran\Infrastructure\Mcp\QuranMcpClientInterface;
use App\Domain\Quran\Services\QuranAiInquiryService;
use Tests\TestCase;

/**
 * When a fallback provider is configured, one inquiry can be served by two
 * providers whose prices differ by ~30x. Each turn must be billed at the prices
 * of the provider that actually ran it — pricing the whole inquiry at either
 * provider's rate would be wrong by an order of magnitude.
 */
final class MixedProviderCostLedgerTest extends TestCase
{
    public function test_each_turn_is_priced_at_the_provider_that_served_it(): void
    {
        config()->set('services.quran_mcp.max_tool_calls', 4);

        // Turn 1 on the expensive primary (a tool call), turn 2 on the cheap
        // fallback (the answer) — the shape of a mid-loop failover.
        $service = new QuranAiInquiryService(
            $this->chatServing([
                new ChatResult(
                    ['role' => 'assistant', 'tool_calls' => [[
                        'id' => 'c1', 'type' => 'function',
                        'function' => ['name' => 'fetch_quran', 'arguments' => '{"ayahs":"2:153"}'],
                    ]]],
                    ['prompt_tokens' => 10_000, 'completion_tokens' => 100, 'total_tokens' => 10_400],
                    'gemini',
                ),
                new ChatResult(
                    ['role' => 'assistant', 'content' => 'Answer [[2:153]].'],
                    ['prompt_tokens' => 12_000, 'completion_tokens' => 200, 'prompt_cache_hit_tokens' => 8_000, 'prompt_cache_miss_tokens' => 4_000],
                    'deepseek',
                ),
            ]),
            $this->fakeMcp(),
            'gemini',
        );

        $answer = $service->answer('ما معنى الصبر؟', 'ar');

        // Gemini turn: 10,000 uncached in @ $1.50/M, and 100 + 300 hidden thinking
        // tokens out @ $7.50/M.
        $geminiCost = 10_000 / 1e6 * 1.50 + 400 / 1e6 * 7.50;
        // DeepSeek turn: 4,000 miss @ $0.14/M, 8,000 cached @ $0.0028/M, 200 out @ $0.28/M.
        $deepseekCost = 4_000 / 1e6 * 0.14 + 8_000 / 1e6 * 0.0028 + 200 / 1e6 * 0.28;

        $this->assertSame(round($geminiCost + $deepseekCost, 6), $answer->costUsd);
        $this->assertSame(22_000, $answer->inputTokens);
        $this->assertSame(600, $answer->outputTokens, 'Gemini thinking tokens bill as output');
        $this->assertSame(8_000, $answer->cachedTokens);

        // The recorded model is the one that actually wrote the answer.
        $this->assertSame('deepseek-chat', $answer->model);
    }

    public function test_a_single_provider_inquiry_is_priced_at_its_own_rates(): void
    {
        $service = new QuranAiInquiryService(
            $this->chatServing([
                new ChatResult(
                    ['role' => 'assistant', 'content' => 'Answer [[2:153]].'],
                    ['prompt_tokens' => 12_000, 'completion_tokens' => 200, 'prompt_cache_hit_tokens' => 8_000, 'prompt_cache_miss_tokens' => 4_000],
                    'deepseek',
                ),
            ]),
            $this->fakeMcp(),
            'gemini', // configured primary is irrelevant — the turn says who served it
        );

        $answer = $service->answer('ما معنى الصبر؟', 'ar');

        $this->assertSame(
            round(4_000 / 1e6 * 0.14 + 8_000 / 1e6 * 0.0028 + 200 / 1e6 * 0.28, 6),
            $answer->costUsd,
        );
    }

    /**
     * @param  list<ChatResult>  $turns
     */
    private function chatServing(array $turns): ChatClientInterface
    {
        return new class($turns) implements ChatClientInterface
        {
            private int $turn = 0;

            /** @param list<ChatResult> $turns */
            public function __construct(private readonly array $turns) {}

            public function chat(array $messages, array $tools = [], string $toolChoice = 'auto'): ChatResult
            {
                return $this->turns[$this->turn++];
            }

            public function streamChat(array $messages, array $tools, string $toolChoice, callable $onContentDelta): ChatResult
            {
                return $this->chat($messages, $tools, $toolChoice);
            }
        };
    }

    private function fakeMcp(): QuranMcpClientInterface
    {
        return new class implements QuranMcpClientInterface
        {
            public function grounding(): array
            {
                return ['rules' => 'Ground every claim.', 'nonce' => null];
            }

            public function toolDefinitions(): array
            {
                return [['type' => 'function', 'function' => ['name' => 'fetch_quran', 'parameters' => []]]];
            }

            public function callTool(string $name, array $arguments): string
            {
                return '{"text":"O you who believe, seek help through patience and prayer."}';
            }
        };
    }
}
