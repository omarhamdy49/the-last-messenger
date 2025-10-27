<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set up testing environment
        $this->setUpTestDatabase();
    }

    /**
     * Set up test database
     */
    protected function setUpTestDatabase(): void
    {
        // Use SQLite in memory for faster tests
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
    }

    /**
     * Create a test user
     */
    protected function createUser(array $attributes = []): \App\Models\User
    {
        return \App\Models\User::factory()->create($attributes);
    }

    /**
     * Create an authenticated user
     */
    protected function createAuthenticatedUser(): \App\Models\User
    {
        return $this->actingAs(\App\Models\User::factory()->create());
    }
}
