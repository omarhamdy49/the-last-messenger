<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\DTOs;

/**
 * What the AskQuranHandler returns to the HTTP layer: the answer, its citations,
 * whether it came from the cache or a fresh LLM call, and the user's remaining
 * monthly allowance (for the UI badge).
 */
final readonly class AskQuranResultDTO
{
    /**
     * @param  list<array{tool: string, args: array<string, mixed>}>  $citations
     * @param  list<array{key: string, surah: int, ayah: int, url: string}>  $verses
     * @param  'llm'|'cache'  $source
     */
    public function __construct(
        public int $id,
        public string $answer,
        public string $html,
        public array $citations,
        public array $verses,
        public string $source,
        public int $remaining,
        public int $limit,
    ) {}
}
