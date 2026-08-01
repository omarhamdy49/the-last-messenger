<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Infrastructure\Llm\ChatClientInterface;
use App\Domain\Quran\Infrastructure\Llm\ChatResult;
use App\Domain\Quran\Infrastructure\Llm\FallbackChatClient;
use Tests\TestCase;

final class FallbackChatClientTest extends TestCase
{
    public function test_it_uses_the_primary_while_it_works(): void
    {
        $client = new FallbackChatClient($this->answering('primary'), $this->answering('fallback'));

        $this->assertSame('primary', $client->chat([])->provider);
    }

    public function test_it_falls_back_when_the_primary_fails(): void
    {
        $client = new FallbackChatClient($this->failing(), $this->answering('fallback'));

        $this->assertSame('fallback', $client->chat([])->provider);
    }

    public function test_the_fallback_is_sticky_for_the_rest_of_the_inquiry(): void
    {
        $primary = $this->failing();
        $client = new FallbackChatClient($primary, $this->answering('fallback'));

        $client->chat([]);
        $client->chat([]);

        $this->assertSame(1, $primary->calls, 'a downed primary must not be retried every turn');
    }

    public function test_it_strips_provider_specific_extra_content_before_the_fallback_sees_it(): void
    {
        $fallback = $this->answering('fallback');
        $client = new FallbackChatClient($this->failing(), $fallback);

        $client->chat([
            ['role' => 'user', 'content' => 'hi'],
            [
                'role' => 'assistant',
                'extra_content' => ['google' => ['thought_signature' => 'sig']],
                'tool_calls' => [[
                    'id' => 'c1',
                    'extra_content' => ['google' => ['thought_signature' => 'sig']],
                    'function' => ['name' => 'fetch_quran', 'arguments' => '{}'],
                ]],
            ],
        ]);

        $sent = $fallback->messages;
        $this->assertArrayNotHasKey('extra_content', $sent[1]);
        $this->assertArrayNotHasKey('extra_content', $sent[1]['tool_calls'][0]);
        $this->assertSame('c1', $sent[1]['tool_calls'][0]['id'], 'the portable fields must survive');
    }

    public function test_streaming_falls_back_when_nothing_was_emitted_yet(): void
    {
        $client = new FallbackChatClient($this->failing(), $this->answering('fallback'));

        $result = $client->streamChat([], [], 'auto', static fn (string $t) => null);

        $this->assertSame('fallback', $result->provider);
    }

    public function test_streaming_does_not_fall_back_once_tokens_reached_the_user(): void
    {
        $primary = new class implements ChatClientInterface
        {
            public function chat(array $messages, array $tools = [], string $toolChoice = 'auto'): ChatResult
            {
                throw new \RuntimeException('quran_ai_llm_failed');
            }

            public function streamChat(array $messages, array $tools, string $toolChoice, callable $onContentDelta): ChatResult
            {
                $onContentDelta('half an answer');

                throw new \RuntimeException('quran_ai_llm_failed');
            }
        };

        $fallback = $this->answering('fallback');
        $client = new FallbackChatClient($primary, $fallback);

        // Restarting would repeat text already on screen, so the failure surfaces.
        $this->expectException(\RuntimeException::class);

        try {
            $client->streamChat([], [], 'auto', static fn (string $t) => null);
        } finally {
            $this->assertSame(0, $fallback->calls);
        }
    }

    private function answering(string $provider): ChatClientInterface
    {
        return new class($provider) implements ChatClientInterface
        {
            public int $calls = 0;

            /** @var array<int, array<string, mixed>> */
            public array $messages = [];

            public function __construct(private readonly string $provider) {}

            public function chat(array $messages, array $tools = [], string $toolChoice = 'auto'): ChatResult
            {
                $this->calls++;
                $this->messages = $messages;

                return new ChatResult(['role' => 'assistant', 'content' => 'ok'], [], $this->provider);
            }

            public function streamChat(array $messages, array $tools, string $toolChoice, callable $onContentDelta): ChatResult
            {
                return $this->chat($messages, $tools, $toolChoice);
            }
        };
    }

    private function failing(): ChatClientInterface
    {
        return new class implements ChatClientInterface
        {
            public int $calls = 0;

            public function chat(array $messages, array $tools = [], string $toolChoice = 'auto'): ChatResult
            {
                $this->calls++;

                throw new \RuntimeException('quran_ai_llm_failed');
            }

            public function streamChat(array $messages, array $tools, string $toolChoice, callable $onContentDelta): ChatResult
            {
                return $this->chat($messages, $tools, $toolChoice);
            }
        };
    }
}
