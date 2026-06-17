<?php

declare(strict_types=1);

namespace App\Domain\Quran\Infrastructure\Llm;

/**
 * An OpenAI-compatible chat-completions client with tool/function calling.
 *
 * Abstracted so the inquiry orchestration can be unit-tested with a fake and so
 * the concrete provider (DeepSeek V4 Flash) can be swapped without touching the
 * domain service.
 */
interface ChatClientInterface
{
    /**
     * @param  array<int, array<string, mixed>>  $messages  OpenAI-shaped message list.
     * @param  array<int, array<string, mixed>>  $tools  Function/tool definitions ([] to disable).
     * @param  string  $toolChoice  'auto' | 'none' | 'required'.
     *
     * @throws \RuntimeException on transport/non-2xx errors.
     */
    public function chat(array $messages, array $tools = [], string $toolChoice = 'auto'): ChatResult;

    /**
     * Streaming counterpart of {@see chat()}. Invokes `$onContentDelta` with each
     * answer-text chunk as it arrives, and returns the fully-assembled turn
     * (content + any reconstructed tool calls + usage) once the stream ends.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array<string, mixed>>  $tools
     * @param  string  $toolChoice  'auto' | 'none' | 'required'.
     * @param  callable(string): void  $onContentDelta
     *
     * @throws \RuntimeException on transport/non-2xx errors.
     */
    public function streamChat(array $messages, array $tools, string $toolChoice, callable $onContentDelta): ChatResult;
}
