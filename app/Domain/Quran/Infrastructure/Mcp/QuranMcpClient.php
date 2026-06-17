<?php

declare(strict_types=1);

namespace App\Domain\Quran\Infrastructure\Mcp;

use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * quran.ai MCP client over JSON-RPC 2.0 / streamable-HTTP.
 *
 * Cost guardrails live here (the dominant LLM cost is tafsir tokens, which vary
 * ~40x by edition): tafsir editions are clamped to an allowlist and verse fetches
 * are capped. The grounding nonce is injected on every call to suppress the
 * ~2.5K-token rules block from being re-sent.
 *
 * NEEDS LIVE INTEGRATION VERIFICATION against mcp.quran.ai (handshake + SSE
 * framing). For production, self-hosting the MCP is recommended (no third-party
 * rate limiter) — see TODO_ask-quran-ai.md lever #10.
 */
final class QuranMcpClient implements QuranMcpClientInterface
{
    /** Curated tool subset exposed to the LLM (fewer tokens, fewer round-trips). */
    private const EXPOSED_TOOLS = [
        'search_quran', 'search_translation', 'fetch_translation', 'fetch_tafsir', 'fetch_quran',
    ];

    private ?string $sessionId = null;

    private bool $initialized = false;

    private int $rpcId = 0;

    public function grounding(): array
    {
        /** @var array{rules: string, nonce: string|null} $grounding */
        $grounding = Cache::remember(QuranCacheKeys::aiGroundingRules(), now()->addHours(6), function (): array {
            $text = $this->callTool('fetch_grounding_rules', [], withNonce: false);
            $nonce = null;
            if (preg_match('/gnd-[a-f0-9]+/i', $text, $m) === 1) {
                $nonce = $m[0];
            }

            return ['rules' => $text, 'nonce' => $nonce];
        });

        return $grounding;
    }

    public function toolDefinitions(): array
    {
        $verseRef = [
            'type' => 'string',
            'description' => "Verse reference(s): single ('2:255'), range ('2:255-257'), or comma-separated.",
        ];

        return [
            $this->fn('search_quran', 'Search the Arabic Quran text for a query to discover relevant verse references.', [
                'query' => ['type' => 'string', 'description' => 'Search text.'],
            ], ['query']),
            $this->fn('search_translation', 'Search Quran translations for a query to discover relevant verses.', [
                'query' => ['type' => 'string', 'description' => 'Search text.'],
            ], ['query']),
            $this->fn('fetch_translation', 'Fetch the canonical translation text for one or more ayat.', [
                'ayahs' => $verseRef,
            ], ['ayahs']),
            $this->fn('fetch_tafsir', 'Fetch scholarly tafsir (commentary) for one or more ayat. Edition is clamped to an approved English edition.', [
                'ayahs' => $verseRef,
            ], ['ayahs']),
            $this->fn('fetch_quran', 'Fetch the canonical Arabic text for one or more ayat.', [
                'ayahs' => $verseRef,
            ], ['ayahs']),
        ];
    }

    public function callTool(string $name, array $arguments, bool $withNonce = true): string
    {
        if ($name !== 'fetch_grounding_rules' && ! in_array($name, self::EXPOSED_TOOLS, true)) {
            return "[tool_error] tool '{$name}' is not available.";
        }

        $arguments = $this->applyGuardrails($name, $arguments);

        if ($withNonce) {
            $nonce = $this->grounding()['nonce'];
            if ($nonce !== null) {
                $arguments['grounding_nonce'] = $nonce;
            }
        }

        try {
            $result = $this->rpc('tools/call', [
                'name' => $name,
                'arguments' => (object) $arguments,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Quran MCP tool call failed', ['tool' => $name, 'error' => $e->getMessage()]);

            return '[tool_error] the grounding source is temporarily unavailable.';
        }

        return $this->extractText($result);
    }

    /**
     * Clamp arguments to the cost guardrails before they reach the server.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function applyGuardrails(string $name, array $arguments): array
    {
        if ($name === 'fetch_tafsir') {
            /** @var list<string> $allowed */
            $allowed = (array) config('services.quran_mcp.allowed_tafsir', []);
            $default = (string) config('services.quran_mcp.default_tafsir');

            $requested = $arguments['editions'] ?? null;
            $requested = is_array($requested)
                ? $requested
                : (is_string($requested) ? array_map('trim', explode(',', $requested)) : []);

            $clamped = array_values(array_intersect($requested, $allowed));
            $arguments['editions'] = $clamped !== [] ? $clamped : [$default];
        }

        // Best-effort verse cap (the system prompt also instructs the model).
        $maxVerses = (int) config('services.quran_mcp.max_verses', 6);
        if (isset($arguments['ayahs']) && is_array($arguments['ayahs'])) {
            $arguments['ayahs'] = array_slice($arguments['ayahs'], 0, $maxVerses);
        }

        return $arguments;
    }

    /**
     * @param  array<string, array<string, mixed>>  $properties
     * @param  list<string>  $required
     * @return array<string, mixed>
     */
    private function fn(string $name, string $description, array $properties, array $required): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => $description,
                'parameters' => [
                    'type' => 'object',
                    'properties' => $properties,
                    'required' => $required,
                ],
            ],
        ];
    }

    /**
     * Send one JSON-RPC request and return its `result` payload.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function rpc(string $method, array $params): array
    {
        $this->ensureSession();

        $response = $this->post([
            'jsonrpc' => '2.0',
            'id' => ++$this->rpcId,
            'method' => $method,
            'params' => $params,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException("MCP {$method} returned HTTP {$response->status()}");
        }

        $envelope = $this->decode($response);
        if (isset($envelope['error'])) {
            $message = $envelope['error']['message'] ?? 'unknown error';
            throw new \RuntimeException("MCP {$method} error: {$message}");
        }

        return (array) ($envelope['result'] ?? []);
    }

    private function ensureSession(): void
    {
        if ($this->initialized) {
            return;
        }

        $response = $this->post([
            'jsonrpc' => '2.0',
            'id' => ++$this->rpcId,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => (object) [],
                'clientInfo' => ['name' => 'the-last-messenger', 'version' => '1.0'],
            ],
        ]);

        $session = $response->header('Mcp-Session-Id');
        if ($session !== '') {
            $this->sessionId = $session;
        }

        // Best-effort "initialized" notification (no response expected).
        $this->post(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']);

        $this->initialized = true;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function post(array $body): Response
    {
        $headers = ['Accept' => 'application/json, text/event-stream'];
        if ($this->sessionId !== null) {
            $headers['Mcp-Session-Id'] = $this->sessionId;
        }

        return Http::baseUrl((string) config('services.quran_mcp.url'))
            ->timeout((int) config('services.quran_mcp.timeout', 10))
            ->withHeaders($headers)
            ->post('/', $body);
    }

    /**
     * Decode a JSON-RPC response that may arrive as plain JSON or as an SSE
     * stream (the streamable-HTTP transport may use either).
     *
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        $contentType = strtolower($response->header('Content-Type'));

        if (! str_contains($contentType, 'text/event-stream')) {
            return (array) ($response->json() ?? []);
        }

        // SSE: collect `data:` payloads, decode each, return the first JSON-RPC
        // message carrying a result or error.
        foreach (preg_split('/\r?\n/', $response->body()) ?: [] as $line) {
            if (! str_starts_with($line, 'data:')) {
                continue;
            }
            $json = trim(substr($line, 5));
            if ($json === '') {
                continue;
            }
            $decoded = json_decode($json, true);
            if (is_array($decoded) && (isset($decoded['result']) || isset($decoded['error']))) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Flatten an MCP tool result into text suitable as an LLM tool message.
     *
     * @param  array<string, mixed>  $result
     */
    private function extractText(array $result): string
    {
        // Prefer structured content when present; otherwise concatenate text blocks.
        if (isset($result['structuredContent'])) {
            return (string) json_encode($result['structuredContent'], JSON_UNESCAPED_UNICODE);
        }

        $parts = [];
        foreach ((array) ($result['content'] ?? []) as $block) {
            if (is_array($block) && ($block['type'] ?? null) === 'text' && isset($block['text'])) {
                $parts[] = (string) $block['text'];
            }
        }

        if ($parts !== []) {
            return implode("\n", $parts);
        }

        return $result === [] ? '[tool_error] empty result.' : (string) json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}
