<?php

declare(strict_types=1);

namespace App\Domain\Quran\Infrastructure\Llm;

use Illuminate\Support\Facades\Log;

/**
 * Runs the primary LLM and falls back to a second one when it is unavailable —
 * a provider outage, a 5xx, or (the common case) a rate-limit/quota 429.
 *
 * The fallback is **sticky per instance**: once the primary has failed, the rest
 * of this inquiry goes to the fallback rather than retrying a provider that is
 * almost certainly still down. The chat client is bound per-resolve, so one
 * instance is one inquiry.
 *
 * Two rules make a mid-loop switch safe:
 *  - Message history is sanitised before it reaches the fallback. Gemini attaches
 *    `extra_content.google.thought_signature` to assistant turns; replaying that
 *    at a provider that never issued it is meaningless at best and rejected at
 *    worst.
 *  - Streaming only falls back if nothing has been emitted yet for this turn.
 *    Once tokens have reached the user, restarting would duplicate visible text,
 *    so the failure propagates instead.
 */
final class FallbackChatClient implements ChatClientInterface
{
    private bool $usingFallback = false;

    public function __construct(
        private readonly ChatClientInterface $primary,
        private readonly ChatClientInterface $fallback,
    ) {}

    public function chat(array $messages, array $tools = [], string $toolChoice = 'auto'): ChatResult
    {
        if ($this->usingFallback) {
            return $this->fallback->chat($this->sanitize($messages), $tools, $toolChoice);
        }

        try {
            return $this->primary->chat($messages, $tools, $toolChoice);
        } catch (\RuntimeException $e) {
            $this->switchToFallback($e);

            return $this->fallback->chat($this->sanitize($messages), $tools, $toolChoice);
        }
    }

    public function streamChat(array $messages, array $tools, string $toolChoice, callable $onContentDelta): ChatResult
    {
        if ($this->usingFallback) {
            return $this->fallback->streamChat($this->sanitize($messages), $tools, $toolChoice, $onContentDelta);
        }

        $emitted = false;
        $track = static function (string $text) use ($onContentDelta, &$emitted): void {
            $emitted = true;
            $onContentDelta($text);
        };

        try {
            return $this->primary->streamChat($messages, $tools, $toolChoice, $track);
        } catch (\RuntimeException $e) {
            if ($emitted) {
                // Partial answer already on the user's screen — a fallback run
                // would repeat it from the top. Fail instead.
                Log::warning('LLM primary failed mid-stream; not falling back', ['error' => $e->getMessage()]);

                throw $e;
            }

            $this->switchToFallback($e);

            return $this->fallback->streamChat($this->sanitize($messages), $tools, $toolChoice, $onContentDelta);
        }
    }

    private function switchToFallback(\RuntimeException $e): void
    {
        Log::warning('LLM primary failed; switching to fallback provider', ['error' => $e->getMessage()]);
        $this->usingFallback = true;
    }

    /**
     * Drop provider-specific `extra_content` from the assistant turns and their
     * tool calls, leaving the portable OpenAI-shaped message behind.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private function sanitize(array $messages): array
    {
        foreach ($messages as $index => $message) {
            unset($messages[$index]['extra_content']);

            foreach ((array) ($message['tool_calls'] ?? []) as $callIndex => $call) {
                unset($messages[$index]['tool_calls'][$callIndex]['extra_content']);
            }
        }

        return $messages;
    }
}
