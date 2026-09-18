<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Shared\Contracts\ApiClientAuthenticator;
use App\Domain\Shared\ValueObjects\ApiClientIdentity;
use App\Models\ApiClient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Lifecycle and authentication of public-API clients. Plain secrets are returned to
 * the caller exactly once (create, rotate) and never persisted or logged.
 */
final class ApiClientService implements ApiClientAuthenticator
{
    private const CACHE_SECONDS = 300;

    public function authenticate(string $key, string $secret): ?ApiClientIdentity
    {
        if ($key === '' || $secret === '' || strlen($key) > 40) {
            return null;
        }

        // Cached by key; every save/delete of a client forgets its entry.
        $client = Cache::remember(ApiClient::cacheKey($key), self::CACHE_SECONDS, static fn (): ?ApiClient => ApiClient::query()
            ->select(['id', 'name', 'key', 'secret_hash', 'scopes', 'allowed_origins', 'rate_limit_per_minute', 'is_active', 'expires_at', 'last_used_at'])
            ->where('key', $key)
            ->first());

        if (! $client instanceof ApiClient || ! $client->isUsable() || ! $client->secretMatches($secret)) {
            return null;
        }

        return new ApiClientIdentity(
            id: $client->id,
            name: $client->name,
            scopes: array_values($client->scopes ?? []),
            allowedOrigins: $client->allowed_origins === null ? null : array_values($client->allowed_origins),
            rateLimitPerMinute: $client->rate_limit_per_minute,
        );
    }

    public function recordUse(ApiClientIdentity $client): void
    {
        // At most one write a minute per client.
        if (Cache::add('api_client_used:'.$client->id, true, 60)) {
            ApiClient::query()->whereKey($client->id)->update(['last_used_at' => now()]);
        }
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return ApiClient::query()
            ->select(['id', 'name', 'key', 'scopes', 'allowed_origins', 'rate_limit_per_minute', 'is_active', 'expires_at', 'last_used_at', 'created_at'])
            ->latest('id')
            ->paginate(min(100, max(1, $perPage)));
    }

    public function findOrFail(int $id): ApiClient
    {
        return ApiClient::query()->findOrFail($id);
    }

    /**
     * @param  array{name: string, scopes: list<string>, allowed_origins?: list<string>|string|null, rate_limit_per_minute: int, expires_at?: string|null, is_active?: bool}  $data
     * @return array{client: ApiClient, secret: string}
     */
    public function create(array $data, ?int $userId): array
    {
        return DB::transaction(function () use ($data, $userId): array {
            $client = new ApiClient([
                'name' => $data['name'],
                'scopes' => array_values(array_unique($data['scopes'])),
                'allowed_origins' => $this->origins($data['allowed_origins'] ?? null),
                'rate_limit_per_minute' => $data['rate_limit_per_minute'],
                'expires_at' => $data['expires_at'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $userId,
            ]);
            $secret = $client->issueCredentials();
            $client->save();

            return ['client' => $client, 'secret' => $secret];
        });
    }

    /**
     * @param  array{name: string, scopes: list<string>, allowed_origins?: list<string>|string|null, rate_limit_per_minute: int, expires_at?: string|null, is_active?: bool}  $data
     */
    public function update(int $id, array $data): ApiClient
    {
        $client = $this->findOrFail($id);
        $client->fill([
            'name' => $data['name'],
            'scopes' => array_values(array_unique($data['scopes'])),
            'allowed_origins' => $this->origins($data['allowed_origins'] ?? null),
            'rate_limit_per_minute' => $data['rate_limit_per_minute'],
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => $data['is_active'] ?? $client->is_active,
        ])->save();

        return $client;
    }

    /**
     * New secret, same key: consumers only replace the secret.
     *
     * @return array{client: ApiClient, secret: string}
     */
    public function rotateSecret(int $id): array
    {
        $client = $this->findOrFail($id);
        $secret = $client->issueCredentials(newKey: false);
        $client->save();

        return ['client' => $client, 'secret' => $secret];
    }

    public function toggle(int $id): ApiClient
    {
        $client = $this->findOrFail($id);
        $client->forceFill(['is_active' => ! $client->is_active])->save();

        return $client;
    }

    public function delete(int $id): void
    {
        $this->findOrFail($id)->delete();
    }

    /**
     * @param  list<string>|string|null  $origins
     * @return list<string>|null
     */
    private function origins(array|string|null $origins): ?array
    {
        if (is_string($origins)) {
            $origins = preg_split('/[\s,]+/', $origins) ?: [];
        }

        $clean = array_values(array_unique(array_filter(array_map(
            static fn (string $o): string => rtrim(trim($o), '/'),
            $origins ?? [],
        ))));

        return $clean === [] ? null : $clean;
    }
}
