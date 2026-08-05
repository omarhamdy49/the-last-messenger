<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Admin User Seeder
 *
 * Creates the initial admin accounts. Credentials are NEVER hardcoded:
 * emails/passwords come from env, and when no password env is set a strong
 * random one is generated and printed ONCE. Existing users are left untouched
 * (the seeder never resets a password), so it is safe to re-run after an admin
 * has changed their own password.
 */
class AdminUserSeeder extends Seeder
{
    /** @var array<int, array{0:string,1:string}> email => generated password, shown once */
    protected array $generatedCredentials = [];

    public function run(): void
    {
        $this->command->info('🔧 Creating Admin Users...');
        $this->command->newLine();

        $this->ensureRolesExist();

        $this->createAdminUser(
            email: (string) env('SUPER_ADMIN_EMAIL', 'supm@dwit.com'),
            name: 'Super Admin',
            role: 'super_admin',
            passwordEnv: 'ADMIN_PASSWORD',
        );

        $this->createAdminUser(
            email: (string) env('ADMIN_EMAIL', 'su@dwit.com'),
            name: 'Admin User',
            role: 'admin',
            passwordEnv: 'ADMIN_PASSWORD',
        );

        $this->command->newLine();
        $this->command->info('✅ Admin users ready.');
        $this->displayGeneratedCredentials();
    }

    /**
     * Ensure required roles exist (created by RolePermissionSeeder).
     */
    protected function ensureRolesExist(): void
    {
        foreach (['super_admin', 'admin', 'editor'] as $roleName) {
            if (! Role::where('name', $roleName)->exists()) {
                $this->command->warn("⚠️  Role '{$roleName}' not found. Run RolePermissionSeeder first.");
                $this->command->info('   php artisan db:seed --class=RolePermissionSeeder');
                exit(1);
            }
        }
    }

    /**
     * Create the admin account if it does not exist. Never resets the password
     * of an existing user. Password comes from $passwordEnv, else is generated.
     */
    protected function createAdminUser(string $email, string $name, string $role, string $passwordEnv): void
    {
        $existing = User::where('email', $email)->first();

        if ($existing !== null) {
            if (! $existing->hasRole($role)) {
                $existing->assignRole($role);
            }
            $this->command->info("ℹ️  {$role} already exists: {$email} (password left unchanged)");

            return;
        }

        $password = (string) env($passwordEnv, '');
        $wasGenerated = $password === '';
        if ($wasGenerated) {
            $password = Str::password(20);
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'language' => 'en',
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        $this->command->info("✓ Created {$role}: {$email}");
        if ($wasGenerated) {
            $this->generatedCredentials[] = [$email, $password];
        }
    }

    /**
     * Print any auto-generated passwords once so the operator can save them.
     */
    protected function displayGeneratedCredentials(): void
    {
        if ($this->generatedCredentials === []) {
            $this->command->newLine();
            $this->command->info('No new passwords generated (set via env or accounts already existed).');

            return;
        }

        $this->command->newLine();
        $this->command->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->warn('🔐 SAVE THESE NOW — generated passwords are shown only once:');
        foreach ($this->generatedCredentials as [$email, $password]) {
            $this->command->line("   {$email}  →  {$password}");
        }
        $this->command->warn('   Change them after first login.');
        $this->command->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
