<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shared\Contracts\ApiClientAuthenticator;
use App\Domain\Shared\ValueObjects\ApiClientIdentity;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifies the calling application from X-Api-Key + X-Api-Secret.
 *
 * - Missing/unknown key, wrong secret, revoked or expired client → 401.
 * - Browser Origin outside the client's allow-list → 403.
 * - Failed attempts are rate limited per IP (brute-force protection).
 * The secret is compared in constant time and never logged.
 */
final class AuthenticateApiClient
{
    public const ATTRIBUTE = 'api_client';

    private const FAILED_LIMIT_PER_MINUTE = 20;

    public function __construct(private readonly ApiClientAuthenticator $clients) {}

    public function handle(Request $request, Closure $next): Response
    {
        $failureKey = 'api-client-failed:'.$request->ip();
        if (RateLimiter::tooManyAttempts($failureKey, self::FAILED_LIMIT_PER_MINUTE)) {
            return $this->error(429, 'rate_limit_exceeded', __('api.too_many_attempts'))
                ->header('Retry-After', (string) RateLimiter::availableIn($failureKey));
        }

        $client = $this->clients->authenticate(
            (string) $request->header('X-Api-Key', ''),
            (string) $request->header('X-Api-Secret', ''),
        );

        if (! $client instanceof ApiClientIdentity) {
            RateLimiter::hit($failureKey, 60);

            return $this->error(401, 'invalid_api_credentials', __('api.invalid_credentials'));
        }

        if (! $client->allowsOrigin($request->header('Origin'))) {
            return $this->error(403, 'origin_not_allowed', __('api.origin_not_allowed'));
        }

        $request->attributes->set(self::ATTRIBUTE, $client);
        $this->clients->recordUse($client);

        return $next($request);
    }

    private function error(int $status, string $type, string $message): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'type' => $type], $status);
    }
}
