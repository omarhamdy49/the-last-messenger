<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionGroups = [
            'articles' => [
                'view articles',
                'create articles',
                'edit articles',
                'delete articles',
                'publish articles',
            ],
            'hadith' => [
                'view hadith',
                'create hadith',
                'edit hadith',
                'delete hadith',
            ],
            'media' => [
                'view media',
                'create media',
                'edit media',
                'delete media',
                'view programs',
                'create programs',
                'edit programs',
                'delete programs',
            ],
            'timeline' => [
                'view timeline',
                'create events',
                'edit events',
                'delete events',
            ],
            'genealogy' => [
                'view genealogy',
                'edit genealogy',
            ],
            'quran' => [
                'view quran',
                'edit quran',
            ],
            'hadith_taxonomy' => [
                'view collections',
                'create collections',
                'edit collections',
                'delete collections',
                'view narrators',
                'create narrators',
                'edit narrators',
                'delete narrators',
                'view scholars',
                'create scholars',
                'edit scholars',
                'delete scholars',
                'view themes',
                'create themes',
                'edit themes',
                'delete themes',
                'view grading-levels',
            ],
            'tags' => [
                'tags.create',
                'tags.update',
            ],
            'users' => [
                'view users',
                'edit users',
                'delete users',
            ],
            'system' => [
                'manage settings',
                'view analytics',
                'manage_content',
            ],
        ];

        $permissions = collect($permissionGroups)
            ->flatten()
            ->unique()
            ->values()
            ->all();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [
            'super_admin' => $permissions,
            'admin' => $permissions,
            'editor' => [
                'view articles',
                'create articles',
                'edit articles',
                'publish articles',
                'view hadith',
                'create hadith',
                'edit hadith',
                'view media',
                'create media',
                'edit media',
                'view programs',
                'create programs',
                'edit programs',
                'view timeline',
                'create events',
                'edit events',
                'view genealogy',
                'view quran',
                'view collections',
                'create collections',
                'edit collections',
                'view narrators',
                'create narrators',
                'edit narrators',
                'view scholars',
                'create scholars',
                'edit scholars',
                'view themes',
                'create themes',
                'edit themes',
                'view grading-levels',
                'tags.create',
                'tags.update',
                'manage_content',
            ],
            'writer' => [
                'view articles',
                'create articles',
                'edit articles',
            ],
            'researcher' => [
                'view articles',
                'create articles',
                'view hadith',
                'view media',
                'view programs',
                'view timeline',
                'view genealogy',
                'view quran',
                'view collections',
                'view narrators',
                'view scholars',
                'view themes',
                'view grading-levels',
            ],
            'user' => [
                'view articles',
                'view hadith',
                'view media',
                'view programs',
                'view timeline',
                'view genealogy',
                'view quran',
            ],
            'guest' => [],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($this->command) {
            $this->command->info('Roles and permissions created successfully!');
        }
    }
}
