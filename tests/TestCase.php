<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

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
