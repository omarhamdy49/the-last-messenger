<?php

declare(strict_types=1);

namespace App\Domain\Quran\Infrastructure\Llm;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Chat-completions client for any provider speaking the OpenAI wire format —
 * currently DeepSeek V4 Flash and Gemini (via its `/v1beta/openai` endpoint).
 * The provider names a `config('services.<provider>')` block; nothing else in
 * the bridge is provider-aware.
 *
 * Cost notes: the static prefix (system prompt + tool defs) must stay byte-stable
 * across requests so the provider's automatic prefix cache hits (DeepSeek
 * ~$0.0028/M vs $0.14/M). `max_tokens` and a low temperature bound the output
 * side; `reasoning_effort`, when the block sets it, bounds Gemini's thinking
 * tokens (which bill as output).
 *
 * NEEDS LIVE INTEGRATION VERIFICATION: requires a real API key for the provider.
 */
final class OpenAiCompatChatClient implements ChatClientInterface
{
    public function __construct(private readonly string $provider = 'deepseek') {}

    public function chat(array $messages, array $tools = [], string $toolChoice = 'auto'): ChatResult
    {
        $cfg = $this->config();
        $payload = $this->payload($cfg, $messages, $tools, $toolChoice, stream: false);

        try {
            $response = $this->request($cfg)
                ->acceptJson()
                ->retry(1, 500, throw: false) // idempotent (same prompt) — one retry on transient 5xx
                ->post('/chat/completions', $payload);
        } catch (\Throwable $e) {
            Log::warning('LLM chat unreachable', ['provider' => $this->provider, 'error' => $e->getMessage()]);
            throw new \RuntimeException('quran_ai_llm_unreachable', previous: $e);
        }

        if ($response->failed()) {
            Log::warning('LLM chat non-2xx', ['provider' => $this->provider, 'status' => $response->status(), 'body' => mb_substr($response->body(), 0, 400)]);
            throw new \RuntimeException('quran_ai_llm_failed');
        }

        $message = $response->json('choices.0.message');
        if (! is_array($message)) {
            throw new \RuntimeException('quran_ai_llm_malformed');
        }

        return new ChatResult(
            message: $message,
            usage: (array) ($response->json('usage') ?? []),
            provider: $this->provider,
        );
    }

    public function streamChat(array $messages, array $tools, string $toolChoice, callable $onContentDelta): ChatResult
    {
        $cfg = $this->config();
        $payload = $this->payload($cfg, $messages, $tools, $toolChoice, stream: true);

        try {
            $response = $this->request($cfg)
                ->withOptions(['stream' => true])
                ->withHeaders(['Accept' => 'text/event-stream'])
                ->post('/chat/completions', $payload);
        } catch (\Throwable $e) {
            Log::warning('LLM stream unreachable', ['provider' => $this->provider, 'error' => $e->getMessage()]);
            throw new \RuntimeException('quran_ai_llm_unreachable', previous: $e);
        }

        if ($response->failed()) {
            Log::warning('LLM stream non-2xx', ['provider' => $this->provider, 'status' => $response->status(), 'body' => mb_substr($response->body(), 0, 400)]);
            throw new \RuntimeException('quran_ai_llm_failed');
        }

        return $this->consumeStream($response, $onContentDelta);
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        $cfg = config("services.{$this->provider}");

        if (! is_array($cfg) || ($cfg['api_key'] ?? '') === '') {
            throw new \RuntimeException('quran_ai_llm_unconfigured');
        }

        return $cfg;
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function request(array $cfg): PendingRequest
    {
        return Http::baseUrl((string) $cfg['base_url'])
            ->withToken((string) $cfg['api_key'])
            ->timeout((int) ($cfg['timeout'] ?? 40));
    }

    /**
     * @param  array<string, mixed>  $cfg
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array<string, mixed>>  $tools
     * @return array<string, mixed>
     */
    private function payload(array $cfg, array $messages, array $tools, string $toolChoice, bool $stream): array
    {
        $payload = [
            'model' => $cfg['model'],
            'messages' => $messages,
            'temperature' => $cfg['temperature'] ?? 0.2,
            'max_tokens' => $cfg['max_output_tokens'] ?? 1200,
            'stream' => $stream,
        ];

        if ($stream) {
            $payload['stream_options'] = ['include_usage' => true];
        }

        // Only Gemini sets this; DeepSeek rejects unknown params on some routes.
        if (($cfg['reasoning_effort'] ?? '') !== '') {
            $payload['reasoning_effort'] = (string) $cfg['reasoning_effort'];
        }

        if ($tools !== []) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = $toolChoice;
        }

        return $payload;
    }

    /**
     * Read the OpenAI-style SSE stream: forward content deltas live, reassemble
     * any tool-call deltas (keyed by index), and capture the final usage block.
     *
     * @param  callable(string): void  $onContentDelta
     */
    private function consumeStream(Response $response, callable $onContentDelta): ChatResult
    {
        $body = $response->toPsrResponse()->getBody();

        $content = '';
        $toolCalls = [];
        $extraContent = [];
        $usage = [];
        $buffer = '';

        while (! $body->eof()) {
            $buffer .= $body->read(8192);

            // Process complete lines; keep the trailing partial line in the buffer.
            while (($newline = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $newline));
                $buffer = substr($buffer, $newline + 1);

                if (! str_starts_with($line, 'data:')) {
                    continue;
                }

                $data = trim(substr($line, 5));
                if ($data === '' || $data === '[DONE]') {
                    continue;
                }

                $chunk = json_decode($data, true);
                if (! is_array($chunk)) {
                    continue;
                }

                if (isset($chunk['usage']) && is_array($chunk['usage'])) {
                    $usage = $chunk['usage'];
                }

                /** @var array<string, mixed> $delta */
                $delta = $chunk['choices'][0]['delta'] ?? [];

                $text = $delta['content'] ?? null;
                if (is_string($text) && $text !== '') {
                    $content .= $text;
                    $onContentDelta($text);
                }

                // Gemini 3.x carries a thought_signature here and rejects a
                // replayed assistant turn that drops it — keep it on the message.
                if (is_array($delta['extra_content'] ?? null)) {
                    $extraContent = array_replace_recursive($extraContent, $delta['extra_content']);
                }

                foreach ((array) ($delta['tool_calls'] ?? []) as $part) {
                    $this->mergeToolCallDelta($toolCalls, (array) $part);
                }
            }
        }

        $message = ['role' => 'assistant', 'content' => $content];
        if ($extraContent !== []) {
            $message['extra_content'] = $extraContent;
        }
        if ($toolCalls !== []) {
            ksort($toolCalls);
            $message['tool_calls'] = array_values($toolCalls);
        }

        return new ChatResult(message: $message, usage: $usage, provider: $this->provider);
    }

    /**
     * Fold one streamed tool-call fragment into the accumulator keyed by index;
     * the id/name arrive once and the arguments stream in across fragments.
     *
     * @param  array<int, array<string, mixed>>  $toolCalls
     * @param  array<string, mixed>  $part
     */
    private function mergeToolCallDelta(array &$toolCalls, array $part): void
    {
        $index = isset($part['index'])
            ? (int) $part['index']
            : $this->indexForUnkeyedPart($toolCalls, $part);

        $toolCalls[$index] ??= [
            'id' => '',
            'type' => 'function',
            'function' => ['name' => '', 'arguments' => ''],
        ];

        if (isset($part['id'])) {
            $toolCalls[$index]['id'] = (string) $part['id'];
        }
        if (is_array($part['extra_content'] ?? null)) {
            $toolCalls[$index]['extra_content'] = array_replace_recursive(
                (array) ($toolCalls[$index]['extra_content'] ?? []),
                $part['extra_content'],
            );
        }
        if (isset($part['function']['name'])) {
            $toolCalls[$index]['function']['name'] = (string) $part['function']['name'];
        }
        if (isset($part['function']['arguments'])) {
            $toolCalls[$index]['function']['arguments'] .= (string) $part['function']['arguments'];
        }
    }

    /**
     * DeepSeek keys streamed tool-call fragments by `index`; Gemini omits that
     * field entirely and emits each call whole, carrying its own `id`. Defaulting
     * to index 0 there merged parallel calls into one with concatenated arguments
     * — the resulting message is invalid JSON and the provider rejects the next
     * turn with a 400. A fragment bearing a new `id` therefore starts a new call.
     *
     * @param  array<int, array<string, mixed>>  $toolCalls
     * @param  array<string, mixed>  $part
     */
    private function indexForUnkeyedPart(array $toolCalls, array $part): int
    {
        if ($toolCalls === []) {
            return 0;
        }

        $last = (int) array_key_last($toolCalls);
        $id = (string) ($part['id'] ?? '');

        return $id !== '' && $id !== ($toolCalls[$last]['id'] ?? '') ? $last + 1 : $last;
    }
}
