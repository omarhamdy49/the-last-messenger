<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ApiClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API key + secret, scopes, per-client rate limits and CORS for /api/v1 (006, US6b).
 */
final class ApiClientAuthTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE = '/api/v1/languages/current';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutApiClient();
        Sanctum::actingAs(User::factory()->create(), ['api:access']);
    }

    public function test_missing_credentials_are_rejected(): void
    {
        $this->getJson(self::ROUTE)
            ->assertUnauthorized()
            ->assertJson(['success' => false, 'type' => 'invalid_api_credentials']);
    }

    public function test_a_wrong_secret_is_rejected_and_never_logged(): void
    {
        $logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e) use (&$logged): void {
            $logged[] = $e->message.' '.json_encode($e->context);
        });
        $client = ApiClient::factory()->withSecret('right-secret')->create();

        $this->getJson(self::ROUTE, ['X-Api-Key' => $client->key, 'X-Api-Secret' => 'wrong-secret-4f1a'])->assertUnauthorized();

        $this->assertSame([], array_filter($logged, static fn (string $line): bool => str_contains($line, 'wrong-secret-4f1a')));
    }

    public function test_unknown_key_is_rejected(): void
    {
        $this->getJson(self::ROUTE, ['X-Api-Key' => 'nope', 'X-Api-Secret' => 'x'])->assertUnauthorized();
    }

    public function test_revoked_and_expired_clients_are_rejected(): void
    {
        $revoked = ApiClient::factory()->withSecret('s')->create(['is_active' => false]);
        $expired = ApiClient::factory()->withSecret('s')->create(['expires_at' => now()->subDay()]);

        $this->getJson(self::ROUTE, ['X-Api-Key' => $revoked->key, 'X-Api-Secret' => 's'])->assertUnauthorized();
        $this->getJson(self::ROUTE, ['X-Api-Key' => $expired->key, 'X-Api-Secret' => 's'])->assertUnauthorized();
    }

    public function test_a_valid_client_passes_and_last_used_is_recorded(): void
    {
        $client = ApiClient::factory()->withSecret('s')->create();

        $this->getJson(self::ROUTE, ['X-Api-Key' => $client->key, 'X-Api-Secret' => 's'])->assertOk();

        $this->assertNotNull($client->fresh()->last_used_at);
    }

    public function test_revoking_takes_effect_immediately_despite_the_cache(): void
    {
        $client = ApiClient::factory()->withSecret('s')->create();
        $headers = ['X-Api-Key' => $client->key, 'X-Api-Secret' => 's'];
        $this->getJson(self::ROUTE, $headers)->assertOk();

        $client->update(['is_active' => false]);

        $this->getJson(self::ROUTE, $headers)->assertUnauthorized();
    }

    public function test_an_origin_outside_the_client_allow_list_is_forbidden(): void
    {
        $client = ApiClient::factory()->withSecret('s')->create(['allowed_origins' => ['https://app.example.com']]);
        $headers = ['X-Api-Key' => $client->key, 'X-Api-Secret' => 's'];

        $this->getJson(self::ROUTE, $headers + ['Origin' => 'https://evil.example'])
            ->assertForbidden()->assertJson(['type' => 'origin_not_allowed']);
        $this->getJson(self::ROUTE, $headers + ['Origin' => 'https://app.example.com'])->assertOk();
        $this->getJson(self::ROUTE, $headers)->assertOk(); // server-to-server, no Origin
    }

    public function test_quran_routes_require_the_quran_scope(): void
    {
        $mediaOnly = ApiClient::factory()->withSecret('s')->scopes(['media'])->create();
        $quran = ApiClient::factory()->withSecret('s')->scopes(['quran'])->create();

        $quranRoute = collect(Route::getRoutes()->getRoutes())
            ->first(fn (LaravelRoute $r): bool => str_starts_with((string) $r->getName(), 'v1.quran.') && in_array('GET', $r->methods(), true) && ! str_contains($r->uri(), '{'));
        $this->assertNotNull($quranRoute);

        $this->getJson('/'.$quranRoute->uri(), ['X-Api-Key' => $mediaOnly->key, 'X-Api-Secret' => 's'])
            ->assertForbidden()->assertJson(['type' => 'insufficient_scope']);
        $this->assertNotSame(403, $this->getJson('/'.$quranRoute->uri(), ['X-Api-Key' => $quran->key, 'X-Api-Secret' => 's'])->status());
    }

    public function test_the_per_client_limit_returns_429_with_headers(): void
    {
        $client = ApiClient::factory()->withSecret('s')->create(['rate_limit_per_minute' => 2]);
        $headers = ['X-Api-Key' => $client->key, 'X-Api-Secret' => 's'];

        $this->getJson(self::ROUTE, $headers)->assertOk()->assertHeader('X-RateLimit-Limit');
        $this->getJson(self::ROUTE, $headers)->assertOk();
        $this->getJson(self::ROUTE, $headers)->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_repeated_bad_credentials_are_throttled_per_ip(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->getJson(self::ROUTE, ['X-Api-Key' => 'nope', 'X-Api-Secret' => 'x'])->assertUnauthorized();
        }

        $this->getJson(self::ROUTE, ['X-Api-Key' => 'nope', 'X-Api-Secret' => 'x'])->assertStatus(429);
    }

    public function test_health_needs_no_credentials(): void
    {
        $this->getJson('/api/v1/health')->assertOk();
    }

    public function test_every_api_route_except_health_requires_a_client(): void
    {
        $unprotected = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $r): bool => str_starts_with($r->uri(), 'api/v1'))
            ->reject(fn (LaravelRoute $r): bool => in_array('api.client', $r->gatherMiddleware(), true))
            ->map(fn (LaravelRoute $r): ?string => $r->getName())
            ->values()
            ->all();

        $this->assertSame(['v1.health'], $unprotected);
    }

    public function test_cors_preflight_allows_configured_origins_and_the_api_key_headers(): void
    {
        config(['cors.allowed_origins' => ['https://app.example.com']]);

        $this->call('OPTIONS', self::ROUTE, [], [], [], [
            'HTTP_ORIGIN' => 'https://app.example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'x-api-key,x-api-secret',
        ])->assertHeader('Access-Control-Allow-Origin', 'https://app.example.com');

        $blocked = $this->call('OPTIONS', self::ROUTE, [], [], [], [
            'HTTP_ORIGIN' => 'https://evil.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);
        // The CORS library answers a single configured origin statically; what matters is
        // that the disallowed origin is never echoed back, so the browser blocks it.
        $this->assertNotSame('https://evil.example', $blocked->headers->get('Access-Control-Allow-Origin'));
    }
}
