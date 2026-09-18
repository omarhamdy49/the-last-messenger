<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApiClient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiClient>
 */
class ApiClientFactory extends Factory
{
    protected $model = ApiClient::class;

    public function definition(): array
    {
        return [
            'name' => 'Client '.fake()->word(),
            'scopes' => ['*'],
            'allowed_origins' => null,
            'rate_limit_per_minute' => 60,
            'is_active' => true,
            'expires_at' => null,
        ];
    }

    /**
     * Give the client a known plain secret (tests send it as X-Api-Secret).
     */
    public function withSecret(string $secret = 'test-secret'): static
    {
        return $this->afterMaking(function (ApiClient $client) use ($secret): void {
            $client->key ??= ApiClient::generateKey();
            $client->secret_hash = ApiClient::hashSecret($secret);
        });
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ApiClient $client): void {
            $client->key ??= ApiClient::generateKey();
            $client->secret_hash ??= ApiClient::hashSecret('test-secret');
        });
    }

    /**
     * @param  list<string>  $scopes
     */
    public function scopes(array $scopes): static
    {
        return $this->state(fn (): array => ['scopes' => $scopes]);
    }
}
