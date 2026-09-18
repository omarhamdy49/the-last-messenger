<?php

declare(strict_types=1);

namespace Tests;

use App\Models\ApiClient;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Every /api/v1 route (except health) requires an API client (006). Requests to
     * /api/* made by a test automatically carry the credentials of a full-scope test
     * client, created on first use, unless the test already sent X-Api-Key or called
     * withoutApiClient() to exercise the unauthenticated path.
     */
    protected bool $sendApiClient = true;

    /** @var array<string, string>|null */
    private ?array $apiClientHeaders = null;

    public const TEST_API_SECRET = 'test-secret';

    protected function withoutApiClient(): static
    {
        $this->sendApiClient = false;

        return $this;
    }

    /**
     * Headers of the default test API client (created on first call).
     *
     * @param  list<string>  $scopes
     * @return array<string, string>
     */
    protected function apiClientHeaders(array $scopes = ['*']): array
    {
        if ($scopes !== ['*']) {
            $client = ApiClient::factory()->withSecret(self::TEST_API_SECRET)->scopes($scopes)->create(['rate_limit_per_minute' => 10_000]);

            return ['X-Api-Key' => $client->key, 'X-Api-Secret' => self::TEST_API_SECRET];
        }

        if ($this->apiClientHeaders === null) {
            $client = ApiClient::factory()->withSecret(self::TEST_API_SECRET)->create(['rate_limit_per_minute' => 10_000]);
            $this->apiClientHeaders = ['X-Api-Key' => $client->key, 'X-Api-Secret' => self::TEST_API_SECRET];
        }

        return $this->apiClientHeaders;
    }

    /**
     * @param  string  $method
     * @param  string  $uri
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $cookies
     * @param  array<string, mixed>  $files
     * @param  array<string, mixed>  $server
     * @param  string|null  $content
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        // parse_url() returns false for a path segment containing a colon
        // (`/api/v1/quran/verses/by_key/2:255`), so strip the query by hand.
        $path = ltrim(strtok((string) $uri, '?') ?: '', '/');

        if ($this->sendApiClient && str_starts_with($path, 'api/') && ! isset($server['HTTP_X_API_KEY'])) {
            $server = array_merge($this->transformHeadersToServerVars($this->apiClientHeaders()), $server);
        }

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    /**
     * Never let a Blade view's @vite directive depend on a frontend build.
     *
     * Vite resolves in two steps: if public/hot exists it points at the dev server,
     * otherwise it reads public/build/manifest.json and THROWS when that is absent.
     * A developer machine almost always has one or the other, so the whole suite goes
     * green locally; CI checks out a clean tree with neither and 95 tests die on
     * "Vite manifest not found" — a failure about asset builds, not about behaviour.
     *
     * Eighteen test classes had each discovered this and called withoutVite() by hand.
     * Doing it once here covers the rest and removes the local/CI divergence for good.
     * Those individual calls are harmless (it is idempotent) and are left in place.
     *
     * If a test ever needs the real thing, it can opt back in per-test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Authenticate as the given user.
     *
     * Admin portal routes use the dedicated `admin` session guard, so mirror the
     * acting user onto it whenever the default/web guard is used. This lets admin
     * feature tests keep calling actingAs() without a guard argument; authorization
     * is still enforced by the role middleware, and the guard separation itself is
     * covered explicitly by AdminAuthSeparationTest.
     *
     * @param  string|null  $guard
     */
    public function be(Authenticatable $user, $guard = null): static
    {
        parent::be($user, $guard);

        if ($guard === null || $guard === 'web') {
            $this->app['auth']->guard('admin')->setUser($user);
        }

        return $this;
    }

    /**
     * Create a test user
     */
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * Create an authenticated user
     */
    protected function createAuthenticatedUser(array $attributes = []): User
    {
        $user = $this->createUser($attributes);
        $this->actingAs($user);

        return $user;
    }

    /**
     * Seed roles and permissions for admin feature tests.
     */
    protected function seedRolesAndPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Create a user with an admin-capable role.
     */
    protected function createAdminUser(array $attributes = [], string $role = 'admin'): User
    {
        $this->seedRolesAndPermissions();

        $user = $this->createUser($attributes);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Create a writer user for article ownership tests.
     */
    protected function createWriterUser(array $attributes = []): User
    {
        $this->seedRolesAndPermissions();

        $user = $this->createUser($attributes);
        $user->assignRole('writer');

        return $user;
    }
}
