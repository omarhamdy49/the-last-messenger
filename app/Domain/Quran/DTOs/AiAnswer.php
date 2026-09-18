<?php

declare(strict_types=1);

namespace App\Domain\Quran\DTOs;

/**
 * Result of one grounded LLM answer (the bridge's output): the answer text, the
 * grounding trail used as structured citations, and the token/cost ledger.
 */
final readonly class AiAnswer
{
    /**
     * @param  list<array{tool: string, args: array<string, mixed>}>  $citations
     */
    public function __construct(
        public string $answer,
        public array $citations,
        public int $inputTokens,
        public int $outputTokens,
        public int $cachedTokens,
        public int $toolCalls,
        public float $costUsd,
        public string $model,
    ) {}
}
