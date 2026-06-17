<?php

declare(strict_types=1);

namespace App\Domain\Quran\Infrastructure\Mcp;

/**
 * Thin client for the quran.ai MCP server (canonical Qur'an grounding).
 *
 * Abstracted so the inquiry orchestration is unit-testable with a fake and so a
 * self-hosted MCP instance can be swapped in without touching the domain service.
 */
interface QuranMcpClientInterface
{
    /**
     * The MCP grounding rules + the opaque nonce that suppresses re-injection of
     * those rules on subsequent tool calls. Cached (fetched ~once).
     *
     * @return array{rules: string, nonce: string|null}
     */
    public function grounding(): array;

    /**
     * The curated subset of MCP tools exposed to the LLM as function definitions
     * (OpenAI/DeepSeek shape). Intentionally minimal for cost/control.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toolDefinitions(): array;

    /**
     * Execute one MCP tool and return its result as text to feed back to the LLM.
     * Applies cost guardrails (tafsir-edition allowlist, verse cap) and injects
     * the grounding nonce. Returns a short error string (never throws) so the
     * model can adapt mid-loop.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function callTool(string $name, array $arguments): string;
}
