<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Main Database Seeder
 * Seeds all necessary data for the application
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting Database Seeding...');
        $this->command->newLine();

        // Seed roles and permissions first
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->newLine();
    }
}
