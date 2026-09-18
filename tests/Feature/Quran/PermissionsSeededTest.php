<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class PermissionsSeededTest extends TestCase
{
    use RefreshDatabase;

    public function test_quran_sync_and_api_client_permissions_are_seeded(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertTrue(Permission::where('name', 'sync quran')->exists());
        $this->assertTrue(Permission::where('name', 'manage api clients')->exists());
        $this->assertTrue(Role::findByName('admin', 'web')->hasPermissionTo('sync quran'));
        $this->assertTrue(Role::findByName('admin', 'web')->hasPermissionTo('manage api clients'));
    }

    public function test_editor_does_not_gain_quran_write_access(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = Role::findByName('editor', 'web');

        $this->assertFalse($editor->hasPermissionTo('sync quran'));
        $this->assertFalse($editor->hasPermissionTo('manage api clients'));
    }
}
