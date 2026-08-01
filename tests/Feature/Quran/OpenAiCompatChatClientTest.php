<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Infrastructure\Llm\OpenAiCompatChatClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Stream reassembly across both providers' SSE dialects. DeepSeek keys tool-call
 * fragments by `index`; Gemini omits `index`, emits each call whole with its own
 * `id`, and attaches a `thought_signature` that must survive into the replayed
 * assistant message or the next turn is rejected with a 400.
 */
final class OpenAiCompatChatClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.fake_llm', [
            'api_key' => 'test-key',
            'base_url' => 'https://llm.test',
            'model' => 'fake-model',
            'timeout' => 5,
            'max_output_tokens' => 100,
            'prices' => ['input' => 1.0, 'cached_input' => 0.1, 'output' => 2.0],
        ]);
    }

    public function test_it_splits_index_less_tool_calls_by_id(): void
    {
        $this->fakeStream([
            $this->chunk(['tool_calls' => [[
                'id' => 'call_a', 'type' => 'function',
                'function' => ['name' => 'fetch_quran', 'arguments' => '{"ayahs":"2:153"}'],
                'extra_content' => ['google' => ['thought_signature' => 'sig-a']],
            ]]]),
            $this->chunk(['tool_calls' => [[
                'id' => 'call_b', 'type' => 'function',
                'function' => ['name' => 'fetch_tafsir', 'arguments' => '{"ayahs":"2:153"}'],
            ]]]),
        ]);

        $calls = $this->stream()->toolCalls();

        $this->assertCount(2, $calls, 'index-less fragments with distinct ids must not merge');
        $this->assertSame('fetch_quran', $calls[0]['function']['name']);
        $this->assertSame('{"ayahs":"2:153"}', $calls[0]['function']['arguments']);
        $this->assertSame('sig-a', $calls[0]['extra_content']['google']['thought_signature']);
        $this->assertSame('fetch_tafsir', $calls[1]['function']['name']);
    }

    public function test_it_reassembles_indexed_tool_call_fragments(): void
    {
        $this->fakeStream([
            $this->chunk(['tool_calls' => [[
                'index' => 0, 'id' => 'call_a', 'type' => 'function',
                'function' => ['name' => 'fetch_quran', 'arguments' => '{"ayahs"'],
            ]]]),
            $this->chunk(['tool_calls' => [['index' => 0, 'function' => ['arguments' => ':"2:153"}']]]]),
        ]);

        $calls = $this->stream()->toolCalls();

        $this->assertCount(1, $calls);
        $this->assertSame('{"ayahs":"2:153"}', $calls[0]['function']['arguments']);
    }

    public function test_it_keeps_the_message_level_thought_signature_and_streams_content(): void
    {
        $this->fakeStream([
            $this->chunk(['content' => 'Patience '], usage: null),
            $this->chunk(['content' => 'is sabr.', 'extra_content' => ['google' => ['thought_signature' => 'sig-z']]]),
        ]);

        $deltas = [];
        $result = $this->stream(static function (string $text) use (&$deltas): void {
            $deltas[] = $text;
        });

        $this->assertSame(['Patience ', 'is sabr.'], $deltas);
        $this->assertSame('Patience is sabr.', $result->content());
        $this->assertSame('sig-z', $result->message['extra_content']['google']['thought_signature']);
        $this->assertSame(9, $result->usage['total_tokens']);
    }

    private function stream(?callable $onDelta = null): \App\Domain\Quran\Infrastructure\Llm\ChatResult
    {
        return (new OpenAiCompatChatClient('fake_llm'))->streamChat(
            [['role' => 'user', 'content' => 'hi']],
            [],
            'auto',
            $onDelta ?? static fn (string $text) => null,
        );
    }

    /**
     * @param  array<string, mixed>  $delta
     */
    private function chunk(array $delta, ?int $usage = 9): string
    {
        $payload = ['choices' => [['index' => 0, 'delta' => $delta + ['role' => 'assistant']]]];

        if ($usage !== null) {
            $payload['usage'] = ['prompt_tokens' => 5, 'completion_tokens' => 4, 'total_tokens' => $usage];
        }

        return 'data: '.json_encode($payload);
    }

    /**
     * @param  list<string>  $chunks
     */
    private function fakeStream(array $chunks): void
    {
        Http::fake([
            'llm.test/*' => Http::response(implode("\n\n", $chunks)."\n\ndata: [DONE]\n\n", 200),
        ]);
    }
}
