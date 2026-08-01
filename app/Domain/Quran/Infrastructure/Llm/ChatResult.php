<?php

declare(strict_types=1);

namespace App\Domain\Quran\Infrastructure\Llm;

/**
 * One assistant turn from an OpenAI-compatible chat completion.
 *
 * Holds the raw assistant `message` so the caller can echo it back verbatim on
 * the next turn (required for tool-call continuity), plus the `usage` block for
 * the cost ledger and the `provider` that produced it — a single inquiry can
 * span two providers when the primary fails mid-loop, and their prices differ by
 * ~30x, so each turn must be priced against the provider that actually ran it.
 */
final readonly class ChatResult
{
    /**
     * @param  array<string, mixed>  $message  The assistant message (content + optional tool_calls).
     * @param  array<string, mixed>  $usage  Token usage (prompt/completion/cache_hit/...).
     * @param  string|null  $provider  The `services.*` key that served this turn.
     */
    public function __construct(
        public array $message,
        public array $usage,
        public ?string $provider = null,
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
