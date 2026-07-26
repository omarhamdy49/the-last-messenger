<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class BackfillPublicUserRolesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_idempotent_and_preserves_existing_and_local_accounts(): void
    {
        $social = User::factory()->create([
            'provider' => 'google',
            'provider_id' => 'social-1',
        ]);
        $admin = User::factory()->create([
            'provider' => 'facebook',
            'provider_id' => 'admin-1',
        ]);
        $local = User::factory()->create();

        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $this->artisan('users:backfill-public-role')
            ->expectsOutput('Assigned the user role to 1 social account(s).')
            ->assertSuccessful();
        $this->artisan('users:backfill-public-role')
            ->expectsOutput('Assigned the user role to 0 social account(s).')
            ->assertSuccessful();

        $this->assertSame(['user'], $social->fresh()->roles->pluck('name')->all());
        $this->assertSame(['admin'], $admin->fresh()->roles->pluck('name')->all());
        $this->assertTrue($local->fresh()->roles->isEmpty());
    }
}
