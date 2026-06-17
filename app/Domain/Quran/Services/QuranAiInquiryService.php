<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Application\DTOs\AiAnswerDTO;
use App\Domain\Quran\Infrastructure\Llm\ChatClientInterface;
use App\Domain\Quran\Infrastructure\Mcp\QuranMcpClientInterface;

/**
 * The DeepSeek <-> quran.ai MCP bridge.
 *
 * Runs a bounded tool-calling loop: the LLM may call MCP tools (search / fetch
 * verse / translation / tafsir) to ground its answer; we execute each call and
 * feed the result back. The loop is capped (`max_tool_calls`) and the final
 * iteration forces an answer (`tool_choice: none`) so it always terminates.
 *
 * Cost: the system prompt (grounding rules + persona) is byte-stable so DeepSeek
 * prefix-caches it; tafsir editions and verse counts are clamped in the MCP
 * client; output is capped in the chat client.
 */
final class QuranAiInquiryService
{
    // DeepSeek V4 Flash pricing, USD per 1M tokens. For the cost ledger /
    // observability only — not billing. Keep in sync with the provider.
    private const PRICE_INPUT = 0.14;

    private const PRICE_CACHED_INPUT = 0.0028;

    private const PRICE_OUTPUT = 0.28;

    private const LOCALE_NAMES = [
        'ar' => 'Arabic', 'en' => 'English', 'fr' => 'French', 'tr' => 'Turkish', 'ur' => 'Urdu',
    ];

    public function __construct(
        private readonly ChatClientInterface $chat,
        private readonly QuranMcpClientInterface $mcp,
    ) {}

    public function answer(string $question, string $locale): AiAnswerDTO
    {
        $grounding = $this->mcp->grounding();
        $tools = $this->mcp->toolDefinitions();

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt($grounding['rules'], $locale)],
            ['role' => 'user', 'content' => $question],
        ];

        $maxToolCalls = (int) config('services.quran_mcp.max_tool_calls', 4);
        $usage = ['prompt' => 0, 'completion' => 0, 'cached' => 0, 'miss' => 0];
        $citations = [];
        $toolCallCount = 0;

        // One extra iteration past the cap with tools disabled forces a final answer.
        for ($iteration = 0; $iteration <= $maxToolCalls; $iteration++) {
            $toolsEnabled = $iteration < $maxToolCalls;

            $result = $this->chat->chat(
                $messages,
                $toolsEnabled ? $tools : [],
                $toolsEnabled ? 'auto' : 'none',
            );
            $this->accumulate($usage, $result->usage);

            $calls = $result->toolCalls();
            if ($calls === [] || ! $toolsEnabled) {
                return $this->finalize($result->content() ?? '', $usage, $citations, $toolCallCount);
            }

            $messages[] = $result->message; // echo assistant tool_calls verbatim

            foreach ($calls as $call) {
                $name = (string) ($call['function']['name'] ?? '');
                /** @var array<string, mixed> $args */
                $args = json_decode((string) ($call['function']['arguments'] ?? '{}'), true) ?: [];

                $citations[] = ['tool' => $name, 'args' => $args];
                $toolCallCount++;

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => (string) ($call['id'] ?? ''),
                    'content' => $this->mcp->callTool($name, $args),
                ];
            }
        }

        // Unreachable: the forced-answer iteration always returns above.
        return $this->finalize('', $usage, $citations, $toolCallCount);
    }

    /**
     * Streaming variant of {@see answer()}: runs the same bounded tool loop but
     * streams the model's tokens and progress to `$emit` for a live, ChatGPT-style
     * answer. Returns the same DTO once complete so the caller can persist/cache.
     *
     * `$emit` receives event arrays:
     *   ['type' => 'status', 'tool' => string, 'ref' => ?string]  — a grounding step
     *   ['type' => 'delta',  'text'  => string]                   — an answer token
     *
     * @param  callable(array<string, mixed>): void  $emit
     */
    public function streamAnswer(string $question, string $locale, callable $emit): AiAnswerDTO
    {
        $grounding = $this->mcp->grounding();
        $tools = $this->mcp->toolDefinitions();

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt($grounding['rules'], $locale)],
            ['role' => 'user', 'content' => $question],
        ];

        $maxToolCalls = (int) config('services.quran_mcp.max_tool_calls', 4);
        $usage = ['prompt' => 0, 'completion' => 0, 'cached' => 0, 'miss' => 0];
        $citations = [];
        $toolCallCount = 0;

        // Tokens of an answer round are streamed live; tool rounds emit no content
        // (the model either answers or calls tools), so this fires only on the answer.
        $answer = '';
        $onDelta = static function (string $text) use (&$answer, $emit): void {
            $answer .= $text;
            $emit(['type' => 'delta', 'text' => $text]);
        };

        for ($iteration = 0; $iteration <= $maxToolCalls; $iteration++) {
            $toolsEnabled = $iteration < $maxToolCalls;

            $result = $this->chat->streamChat(
                $messages,
                $toolsEnabled ? $tools : [],
                $toolsEnabled ? 'auto' : 'none',
                $onDelta,
            );
            $this->accumulate($usage, $result->usage);

            $calls = $result->toolCalls();
            if ($calls === [] || ! $toolsEnabled) {
                // A content round may have streamed via $onDelta; fall back to its
                // full content if the provider did not stream deltas this round.
                if ($answer === '') {
                    $answer = $result->content() ?? '';
                }

                return $this->finalize($answer, $usage, $citations, $toolCallCount);
            }

            $messages[] = $result->message;

            foreach ($calls as $call) {
                $name = (string) ($call['function']['name'] ?? '');
                /** @var array<string, mixed> $args */
                $args = json_decode((string) ($call['function']['arguments'] ?? '{}'), true) ?: [];

                $citations[] = ['tool' => $name, 'args' => $args];
                $toolCallCount++;

                $emit([
                    'type' => 'status',
                    'tool' => $name,
                    'ref' => is_string($args['ayahs'] ?? null)
                        ? $args['ayahs']
                        : (is_string($args['query'] ?? null) ? $args['query'] : null),
                ]);

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => (string) ($call['id'] ?? ''),
                    'content' => $this->mcp->callTool($name, $args),
                ];
            }
        }

        return $this->finalize($answer, $usage, $citations, $toolCallCount);
    }

    /**
     * @param  array{prompt:int, completion:int, cached:int, miss:int}  $usage
     * @param  list<array{tool: string, args: array<string, mixed>}>  $citations
     */
    private function finalize(string $answer, array $usage, array $citations, int $toolCalls): AiAnswerDTO
    {
        $cost = $usage['miss'] / 1_000_000 * self::PRICE_INPUT
            + $usage['cached'] / 1_000_000 * self::PRICE_CACHED_INPUT
            + $usage['completion'] / 1_000_000 * self::PRICE_OUTPUT;

        return new AiAnswerDTO(
            answer: trim($answer),
            citations: $citations,
            inputTokens: $usage['prompt'],
            outputTokens: $usage['completion'],
            cachedTokens: $usage['cached'],
            toolCalls: $toolCalls,
            costUsd: round($cost, 6),
            model: (string) config('services.deepseek.model', 'deepseek-chat'),
        );
    }

    /**
     * @param  array{prompt:int, completion:int, cached:int, miss:int}  $usage
     * @param  array<string, mixed>  $turnUsage
     */
    private function accumulate(array &$usage, array $turnUsage): void
    {
        $prompt = (int) ($turnUsage['prompt_tokens'] ?? 0);
        $cached = (int) ($turnUsage['prompt_cache_hit_tokens'] ?? 0);
        $miss = (int) ($turnUsage['prompt_cache_miss_tokens'] ?? max(0, $prompt - $cached));

        $usage['prompt'] += $prompt;
        $usage['completion'] += (int) ($turnUsage['completion_tokens'] ?? 0);
        $usage['cached'] += $cached;
        $usage['miss'] += $miss;
    }

    private function systemPrompt(string $groundingRules, string $locale): string
    {
        $language = self::LOCALE_NAMES[$locale] ?? 'Arabic';

        return $groundingRules."\n\n"
            ."You are a careful Qur'an study assistant. Use ONLY the provided tools to fetch canonical "
            ."Qur'an text, translations, and tafsir from quran.com — never quote scripture or tafsir from "
            .'memory. Search first to find the relevant ayat, then fetch their text/translation and at least '
            .'one tafsir before answering. Attribute every interpretive claim to the fetched tafsir by name; '
            .'if the sources do not support a claim, say so plainly. Keep the question focused — fetch only '
            ."the verses you need. Do not narrate your tool use; output only the final answer.\n\n"
            ."Answer in {$language} using clean Markdown so it renders as a polished article:\n"
            ."- Open with a one- or two-sentence direct answer.\n"
            ."- Use `## ` sub-headings to group ideas when the answer has multiple parts.\n"
            ."- Use `**bold**` for key terms and `- ` bullet lists for enumerations.\n"
            ."- Put any quoted verse translation on its own line as a Markdown blockquote (`> `).\n"
            .'- Cite EVERY verse with the exact inline marker `[[surah:ayah]]` placed right after you '
            .'mention it — e.g. `[[2:255]]`, or `[[2:255-257]]` for a consecutive range. ALWAYS use the '
            .'surah NUMBER and Western digits inside the marker, even when writing in Arabic; never cite a '
            ."verse by surah name in parentheses, and never write bare references, URLs, or HTML.\n"
            ."- Do not add a sources/grounding line, a horizontal rule (`---`), or any closing footer.\n\n"
            .'This is a study aid, not a fatwa.';
    }
}
