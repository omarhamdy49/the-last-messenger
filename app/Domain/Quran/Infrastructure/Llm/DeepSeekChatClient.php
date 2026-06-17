<?php

declare(strict_types=1);

namespace App\Domain\Quran\Infrastructure\Llm;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DeepSeek V4 Flash chat-completions client (OpenAI-compatible wire format).
 *
 * Cost notes: the static prefix (system prompt + tool defs) must stay byte-stable
 * across requests so DeepSeek's automatic prefix cache hits (~$0.0028/M vs
 * $0.14/M). `max_tokens` and a low temperature bound the output side.
 *
 * NEEDS LIVE INTEGRATION VERIFICATION: requires a real DEEPSEEK_API_KEY.
 */
final class DeepSeekChatClient implements ChatClientInterface
{
    public function chat(array $messages, array $tools = [], string $toolChoice = 'auto'): ChatResult
    {
        /** @var array<string, mixed> $cfg */
        $cfg = config('services.deepseek');

        $payload = [
            'model' => $cfg['model'] ?? 'deepseek-chat',
            'messages' => $messages,
            'temperature' => $cfg['temperature'] ?? 0.2,
            'max_tokens' => $cfg['max_output_tokens'] ?? 1200,
            'stream' => false,
        ];

        if ($tools !== []) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = $toolChoice;
        }

        try {
            $response = Http::baseUrl((string) ($cfg['base_url'] ?? 'https://api.deepseek.com'))
                ->withToken((string) ($cfg['api_key'] ?? ''))
                ->acceptJson()
                ->timeout((int) ($cfg['timeout'] ?? 40))
                ->retry(1, 500, throw: false) // idempotent (same prompt) — one retry on transient 5xx
                ->post('/chat/completions', $payload);
        } catch (\Throwable $e) {
            Log::warning('DeepSeek chat unreachable', ['error' => $e->getMessage()]);
            throw new \RuntimeException('quran_ai_llm_unreachable', previous: $e);
        }

        if ($response->failed()) {
            Log::warning('DeepSeek chat non-2xx', ['status' => $response->status()]);
            throw new \RuntimeException('quran_ai_llm_failed');
        }

        $message = $response->json('choices.0.message');
        if (! is_array($message)) {
            throw new \RuntimeException('quran_ai_llm_malformed');
        }

        return new ChatResult(
            message: $message,
            usage: (array) ($response->json('usage') ?? []),
        );
    }

    public function streamChat(array $messages, array $tools, string $toolChoice, callable $onContentDelta): ChatResult
    {
        /** @var array<string, mixed> $cfg */
        $cfg = config('services.deepseek');

        $payload = [
            'model' => $cfg['model'] ?? 'deepseek-chat',
            'messages' => $messages,
            'temperature' => $cfg['temperature'] ?? 0.2,
            'max_tokens' => $cfg['max_output_tokens'] ?? 1200,
            'stream' => true,
            'stream_options' => ['include_usage' => true],
        ];

        if ($tools !== []) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = $toolChoice;
        }

        try {
            $response = Http::baseUrl((string) ($cfg['base_url'] ?? 'https://api.deepseek.com'))
                ->withToken((string) ($cfg['api_key'] ?? ''))
                ->withOptions(['stream' => true])
                ->withHeaders(['Accept' => 'text/event-stream'])
                ->timeout((int) ($cfg['timeout'] ?? 40))
                ->post('/chat/completions', $payload);
        } catch (\Throwable $e) {
            Log::warning('DeepSeek stream unreachable', ['error' => $e->getMessage()]);
            throw new \RuntimeException('quran_ai_llm_unreachable', previous: $e);
        }

        if ($response->failed()) {
            Log::warning('DeepSeek stream non-2xx', ['status' => $response->status()]);
            throw new \RuntimeException('quran_ai_llm_failed');
        }

        return $this->consumeStream($response, $onContentDelta);
    }

    /**
     * Read the OpenAI-style SSE stream: forward content deltas live, reassemble
     * any tool-call deltas (keyed by index), and capture the final usage block.
     *
     * @param  callable(string): void  $onContentDelta
     */
    private function consumeStream(\Illuminate\Http\Client\Response $response, callable $onContentDelta): ChatResult
    {
        $body = $response->toPsrResponse()->getBody();

        $content = '';
        $toolCalls = [];
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

                foreach ((array) ($delta['tool_calls'] ?? []) as $part) {
                    $this->mergeToolCallDelta($toolCalls, (array) $part);
                }
            }
        }

        $message = ['role' => 'assistant', 'content' => $content];
        if ($toolCalls !== []) {
            ksort($toolCalls);
            $message['tool_calls'] = array_values($toolCalls);
        }

        return new ChatResult(message: $message, usage: $usage);
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
        $index = (int) ($part['index'] ?? 0);

        $toolCalls[$index] ??= [
            'id' => '',
            'type' => 'function',
            'function' => ['name' => '', 'arguments' => ''],
        ];

        if (isset($part['id'])) {
            $toolCalls[$index]['id'] = (string) $part['id'];
        }
        if (isset($part['function']['name'])) {
            $toolCalls[$index]['function']['name'] = (string) $part['function']['name'];
        }
        if (isset($part['function']['arguments'])) {
            $toolCalls[$index]['function']['arguments'] .= (string) $part['function']['arguments'];
        }
    }
}
