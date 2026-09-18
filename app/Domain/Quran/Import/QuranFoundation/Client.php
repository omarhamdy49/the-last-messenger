<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation;

use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfApiException;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfPayloadException;
use App\Domain\Quran\Import\Support\Throttle;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;

/**
 * HTTP client for the Quran Foundation Content API.
 *
 * - Sends `x-auth-token` + `x-client-id` on every call.
 * - Stays under `quran.qf.rate_limit_per_second`.
 * - 401: forget the token and retry exactly once.
 * - 429 / 502 / 503 / 504 and connection failures: bounded retries with backoff.
 * - Any other non-2xx: QfApiException carrying QF's error `type`, no retry.
 *
 * Failures are logged as {endpoint, status, type, attempt} only — never headers,
 * tokens, secrets or bodies.
 */
final class Client
{
    public function __construct(
        private readonly TokenProvider $tokens,
        private readonly Throttle $throttle,
    ) {}

    /**
     * GET a path relative to the Content API root (`chapters`, `verses/by_chapter/1`, …).
     *
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request($this->contentUrl($path), $path, $query);
    }

    /**
     * GET an absolute URL on a QF gateway (e.g. Quran Reflect) with the same auth.
     *
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    public function getUrl(string $url, string $label, array $query = []): array
    {
        return $this->request($url, $label, $query);
    }

    /**
     * Sync responses return relative `/api/v4/...` paths (next_page_url,
     * snapshot_url). Resolve one against the Content API root.
     */
    public function absoluteUrl(string $relativeApiPath): string
    {
        $apiUrl = rtrim((string) config('quran.qf.api_url'), '/');

        if (str_starts_with($relativeApiPath, 'http://') || str_starts_with($relativeApiPath, 'https://')) {
            return $relativeApiPath;
        }

        // api_url is ".../content/api/v4"; strip the "/api/v4" tail and append the path.
        $base = preg_replace('#/api/v\d+$#', '', $apiUrl) ?? $apiUrl;

        return $base.'/'.ltrim($relativeApiPath, '/');
    }

    private function contentUrl(string $path): string
    {
        return rtrim((string) config('quran.qf.api_url'), '/').'/'.ltrim($path, '/');
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    private function request(string $url, string $label, array $query): array
    {
        $maxRetries = (int) config('quran.qf.max_retries', 3);
        $refreshedToken = false;
        $attempt = 0;

        while (true) {
            $attempt++;
            $this->throttle->wait();

            try {
                $response = $this->send($url, $query);
            } catch (ConnectionException) {
                if ($attempt > $maxRetries) {
                    $this->logFailure($label, 0, 'connection_failed', $attempt);
                    throw new QfApiException($label, 0, 'connection_failed');
                }
                $this->throttle->backoff($attempt, null);

                continue;
            }

            if ($response->successful()) {
                return $this->decode($label, $response);
            }

            $error = QfApiException::fromResponse($label, $response);

            if ($response->status() === 401 && ! $refreshedToken) {
                $refreshedToken = true;
                $this->tokens->forget();
                $attempt--; // the token refresh does not consume a backoff attempt

                continue;
            }

            if ($error->isRetryable() && $attempt <= $maxRetries) {
                $this->throttle->backoff($attempt, $response->header('Retry-After') ?: null);

                continue;
            }

            $this->logFailure($label, $error->status, $error->type, $attempt);

            throw $error;
        }
    }

    /**
     * @param  array<string, scalar|null>  $query
     */
    private function send(string $url, array $query): Response
    {
        // Passing a query array makes Guzzle replace the URL's own query string, which
        // would silently drop the `cursor` of a sync next_page_url. Merge them instead.
        $parts = explode('?', $url, 2);
        if (isset($parts[1])) {
            parse_str($parts[1], $urlQuery);
            $url = $parts[0];
            $query = array_merge($urlQuery, $query);
        }

        return Http::acceptJson()
            ->timeout((int) config('quran.qf.timeout', 30))
            ->withHeaders([
                'x-auth-token' => $this->tokens->token(),
                'x-client-id' => $this->tokens->clientId(),
            ])
            ->get($url, array_filter($query, static fn ($v): bool => $v !== null));
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $label, Response $response): array
    {
        try {
            $decoded = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new QfPayloadException($label, '(valid JSON body)');
        }

        if (! is_array($decoded)) {
            throw new QfPayloadException($label, '(JSON object)');
        }

        return $decoded;
    }

    private function logFailure(string $endpoint, int $status, string $type, int $attempt): void
    {
        Log::warning('quran.qf.request_failed', compact('endpoint', 'status', 'type', 'attempt'));
    }
}
