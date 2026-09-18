<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation;

use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfApiException;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfPayloadException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OAuth2 client-credentials token for the QF Content API (scope `content`).
 *
 * Tokens live 3600 s and there is no refresh token: request a new one about 30 s
 * before expiry. Refreshes are serialised with a cache lock so concurrent workers
 * do not each request a token. The token and secret are never logged or included
 * in exception messages.
 */
final class TokenProvider
{
    private const CACHE_KEY = 'quran:qf:token';

    private const LOCK_KEY = 'quran:qf:token:lock';

    public function token(): string
    {
        $cached = $this->validCached();
        if ($cached !== null) {
            return $cached;
        }

        return Cache::lock(self::LOCK_KEY, 15)->block(15, function (): string {
            // Another worker may have refreshed while we waited for the lock.
            return $this->validCached() ?? $this->requestToken();
        });
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function clientId(): string
    {
        $id = (string) config('quran.qf.client_id', '');
        if ($id === '') {
            throw new RuntimeException('Quran Foundation client id is not configured (QURAN_API_KEY).');
        }

        return $id;
    }

    private function validCached(): ?string
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (! is_array($cached) || ! is_string($cached['access_token'] ?? null) || ! is_int($cached['expires_at'] ?? null)) {
            return null;
        }

        $margin = (int) config('quran.qf.token_early_refresh_seconds', 30);

        return time() < $cached['expires_at'] - $margin ? $cached['access_token'] : null;
    }

    private function requestToken(): string
    {
        $secret = (string) config('quran.qf.client_secret', '');
        if ($secret === '') {
            throw new RuntimeException('Quran Foundation client secret is not configured (QURAN_API_SECRET).');
        }

        $endpoint = 'oauth2/token';

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout((int) config('quran.qf.timeout', 30))
                ->withBasicAuth($this->clientId(), $secret)
                ->post((string) config('quran.qf.auth_url'), [
                    'grant_type' => 'client_credentials',
                    'scope' => 'content',
                ]);
        } catch (ConnectionException) {
            throw new QfApiException($endpoint, 0, 'connection_failed');
        }

        if (! $response->successful()) {
            throw QfApiException::fromResponse($endpoint, $response);
        }

        $token = $response->json('access_token');
        $expiresIn = $response->json('expires_in');
        if (! is_string($token) || $token === '') {
            throw new QfPayloadException($endpoint, 'access_token');
        }

        $ttl = is_numeric($expiresIn) ? (int) $expiresIn : 3600;

        Cache::put(self::CACHE_KEY, ['access_token' => $token, 'expires_at' => time() + $ttl], $ttl);

        return $token;
    }
}
