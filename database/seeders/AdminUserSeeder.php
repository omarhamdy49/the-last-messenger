<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Admin User Seeder
 * Creates default admin users for the system
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔧 Creating Admin Users...');
        $this->command->newLine();

        // Ensure roles exist
        $this->ensureRolesExist();

        // Create Super Admin
        $this->createSuperAdmin();

        // Create Regular Admin
        $this->createAdmin();

        $this->command->newLine();
        $this->command->info('✅ Admin users created successfully!');
        $this->command->newLine();

        // Display credentials
        $this->displayCredentials();
    }

    /**
     * Ensure required roles exist
     */
    protected function ensureRolesExist(): void
    {
        $requiredRoles = ['super_admin', 'admin', 'editor'];

        foreach ($requiredRoles as $roleName) {
            if (! Role::where('name', $roleName)->exists()) {
                $this->command->warn("⚠️  Role '{$roleName}' not found. Please run RolePermissionSeeder first.");
                $this->command->info('   Run: php artisan db:seed --class=RolePermissionSeeder');
                exit(1);
            }
        }
    }

    /**
     * Create Super Admin User
     */
    protected function createSuperAdmin(): void
    {
        $email = 'supm@dwit.com';

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => Hash::make(']$g!(9LwvY2+B=#w'),
                'language' => 'en',
                'email_verified_at' => now(),
            ]
        );

        // Assign super_admin role
        if (! $user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
            $this->command->info("✓ Created Super Admin: {$email}");
        } else {
            $this->command->info("ℹ️  Super Admin already exists: {$email}");
        }
    }

    /**
     * Create Admin User
     */
    protected function createAdmin(): void
    {
        $email = 'su@dwit.com';

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin User',
                'password' => Hash::make('p+^;uN>28<pzbdzV'),
                'language' => 'en',
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role
        if (! $user->hasRole('admin')) {
            $user->assignRole('admin');
            $this->command->info("✓ Created Admin: {$email}");
        } else {
            $this->command->info("ℹ️  Admin already exists: {$email}");
        }
    }

    /**
     * Display user credentials
     */
    protected function displayCredentials(): void
    {
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('📝 Admin User Credentials:');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->newLine();

        $this->command->info('👑 Super Admin (Full Access):');
        $this->command->line('   Email:    superadmin@thelastmessenger.com');
        $this->command->line('   Password: SuperAdmin123!');
        $this->command->newLine();

        $this->command->info('👤 Admin (Content Management):');
        $this->command->line('   Email:    admin@thelastmessenger.com');
        $this->command->line('   Password: Admin123!');
        $this->command->newLine();

        $this->command->info('✏️  Editor (Edit Content):');
        $this->command->line('   Email:    editor@thelastmessenger.com');
        $this->command->line('   Password: Editor123!');
        $this->command->newLine();

        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('🔐 Login Endpoint:');
        $this->command->line('   POST http://127.0.0.1:8000/api/auth/login');
        $this->command->newLine();
        $this->command->info('📊 Admin Dashboard:');
        $this->command->line('   GET http://127.0.0.1:8000/su/portal/v1/admin/dashboard');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->newLine();

        $this->command->warn('⚠️  IMPORTANT: Change these passwords in production!');
        $this->command->newLine();
    }
}
