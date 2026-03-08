<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        $this->applyTestDatabaseEnvironment();

        parent::setUp();

        // Set up testing environment
        $this->setUpTestDatabase();
    }

    /**
     * Set up test database
     */
    protected function setUpTestDatabase(): void
    {
        if (getenv('TEST_DB_CONNECTION') === 'mysql') {
            config([
                'database.default' => 'mysql',
                'database.connections.mysql.host' => getenv('DB_HOST') ?: '127.0.0.1',
                'database.connections.mysql.port' => getenv('DB_PORT') ?: '3306',
                'database.connections.mysql.database' => getenv('DB_DATABASE') ?: 'the_last_messenger_test',
                'database.connections.mysql.username' => getenv('DB_USERNAME') ?: 'root',
                'database.connections.mysql.password' => getenv('DB_PASSWORD') ?: '',
            ]);

            return;
        }

        // Use SQLite in memory when no test database override is provided.
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
    }

    /**
     * Override PHPUnit's default SQLite env vars before the application boots
     * when a dedicated MySQL test database is requested.
     */
    protected function applyTestDatabaseEnvironment(): void
    {
        if (getenv('TEST_DB_CONNECTION') !== 'mysql') {
            return;
        }

        $overrides = [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => getenv('TEST_DB_HOST') ?: getenv('DB_HOST') ?: '127.0.0.1',
            'DB_PORT' => getenv('TEST_DB_PORT') ?: getenv('DB_PORT') ?: '3306',
            'DB_DATABASE' => getenv('TEST_DB_DATABASE') ?: 'the_last_messenger_test',
            'DB_USERNAME' => getenv('TEST_DB_USERNAME') ?: getenv('DB_USERNAME') ?: 'root',
            'DB_PASSWORD' => getenv('TEST_DB_PASSWORD') ?: getenv('DB_PASSWORD') ?: '',
        ];

        foreach ($overrides as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
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
