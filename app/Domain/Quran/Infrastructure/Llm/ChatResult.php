<?php

declare(strict_types=1);

namespace App\Domain\Quran\Infrastructure\Llm;

/**
 * One assistant turn from an OpenAI-compatible chat completion (DeepSeek).
 *
 * Holds the raw assistant `message` so the caller can echo it back verbatim on
 * the next turn (required for tool-call continuity), plus the `usage` block for
 * the cost ledger.
 */
final readonly class ChatResult
{
    /**
     * @param  array<string, mixed>  $message  The assistant message (content + optional tool_calls).
     * @param  array<string, mixed>  $usage  Token usage (prompt/completion/cache_hit/...).
     */
    public function __construct(
        public array $message,
        public array $usage,
    ) {}

    public function content(): ?string
    {
        $content = $this->message['content'] ?? null;

        return is_string($content) ? $content : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toolCalls(): array
    {
        $calls = $this->message['tool_calls'] ?? [];

        return is_array($calls) ? $calls : [];
    }
}
