<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * A non-2xx answer from the Quran Foundation API.
 *
 * `type` is QF's own error type (`unauthorized`, `forbidden`, `insufficient_scope`,
 * `rate_limit_exceeded`, …). The message never contains request headers, the
 * token, or the response body — only the endpoint, status and type.
 */
final class QfApiException extends RuntimeException
{
    public function __construct(
        public readonly string $endpoint,
        public readonly int $status,
        public readonly string $type,
    ) {
        parent::__construct(sprintf('Quran Foundation API %s returned %d (%s).', $endpoint, $status, $type), $status);
    }

    public static function fromResponse(string $endpoint, Response $response): self
    {
        $type = null;
        try {
            $body = $response->json();
            if (is_array($body) && is_string($body['type'] ?? null) && $body['type'] !== '') {
                $type = $body['type'];
            }
        } catch (\Throwable) {
            // A non-JSON error body still yields a status-derived type below.
        }

        return new self($endpoint, $response->status(), $type ?? self::typeForStatus($response->status()));
    }

    public static function typeForStatus(int $status): string
    {
        return match (true) {
            $status === 400 => 'invalid_request',
            $status === 401 => 'unauthorized',
            $status === 403 => 'forbidden',
            $status === 404 => 'not_found',
            $status === 422 => 'unprocessable_entity',
            $status === 429 => 'rate_limit_exceeded',
            $status === 502 => 'bad_gateway',
            $status === 503 => 'service_unavailable',
            $status === 504 => 'gateway_timeout',
            $status >= 500 => 'internal_server_error',
            default => 'service_error',
        };
    }

    public function isRetryable(): bool
    {
        return in_array($this->status, [429, 502, 503, 504], true);
    }
}
