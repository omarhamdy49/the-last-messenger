<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Role and Permission Seeder
 * Creates roles and permissions for the application
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Article permissions
            'view articles',
            'create articles',
            'edit articles',
            'delete articles',
            'publish articles',

            // Hadith permissions
            'view hadith',
            'create hadith',
            'edit hadith',
            'delete hadith',

            // Media permissions
            'view programs',
            'create programs',
            'edit programs',
            'delete programs',

            // Timeline permissions
            'view timeline',
            'create events',
            'edit events',
            'delete events',

            // Genealogy permissions
            'view genealogy',
            'edit genealogy',

            // Quran permissions
            'view quran',
            'edit quran',

            // User management
            'view users',
            'edit users',
            'delete users',

            // System permissions
            'manage settings',
            'view analytics',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - has all permissions
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - can manage content and users
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'view articles', 'create articles', 'edit articles', 'delete articles', 'publish articles',
            'view hadith', 'create hadith', 'edit hadith', 'delete hadith',
            'view programs', 'create programs', 'edit programs', 'delete programs',
            'view timeline', 'create events', 'edit events', 'delete events',
            'view genealogy', 'edit genealogy',
            'view quran', 'edit quran',
            'view users', 'edit users',
            'view analytics',
        ]);

        // Editor - can create and edit content
        $editor = Role::create(['name' => 'editor']);
        $editor->givePermissionTo([
            'view articles', 'create articles', 'edit articles',
            'view hadith', 'create hadith', 'edit hadith',
            'view programs', 'create programs', 'edit programs',
            'view timeline', 'create events', 'edit events',
            'view genealogy',
            'view quran',
        ]);

        // Researcher - can view and contribute
        $researcher = Role::create(['name' => 'researcher']);
        $researcher->givePermissionTo([
            'view articles', 'create articles',
            'view hadith',
            'view programs',
            'view timeline',
            'view genealogy',
            'view quran',
        ]);

        // User - basic permissions
        $user = Role::create(['name' => 'user']);
        $user->givePermissionTo([
            'view articles',
            'view hadith',
            'view programs',
            'view timeline',
            'view genealogy',
            'view quran',
        ]);

        // Guest role (for reference, but guests won't have a role assigned)
        Role::create(['name' => 'guest']);

        $this->command->info('Roles and permissions created successfully!');
    }
}
