<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use App\Domain\Shared\ValueObjects\ApiClientIdentity;

/**
 * Resolves X-Api-Key + X-Api-Secret to an active API client. Lets the HTTP
 * middleware authenticate without importing the ApiClient model (deptrac:
 * Middleware may depend on Shared only). Implemented by App\Services\ApiClientService.
 */
interface ApiClientAuthenticator
{
    /**
     * The client when the key exists, the secret matches (constant-time) and the
     * client is active and not expired; null otherwise.
     */
    public function authenticate(string $key, string $secret): ?ApiClientIdentity;

    /**
     * Record use (throttled internally so it is not a write per request).
     */
    public function recordUse(ApiClientIdentity $client): void;
}
