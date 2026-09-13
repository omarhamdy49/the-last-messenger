<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
            QuranReciterSeeder::class,
        ]);

        // Public-site content (settings, menus, home cards/chips, pages, FAQs)
        $this->call([
            SiteSettingsSeeder::class,
            MenuSeeder::class,
            HomeSectionsSeeder::class,
            HomeModuleCardsSeeder::class,
            QuickFilterChipsSeeder::class,
            StaticPagesSeeder::class,
            FaqSeeder::class,
            MediaCategorySeeder::class,
        ]);

        // People: the Islamic roles taxonomy, then the Prophet ﷺ and his paternal
        // lineage — which is what the family tree roots itself at.
        $this->call([
            IslamicRoleSeeder::class,
            ProphetLineageSeeder::class,
            ProphetFamilySeeder::class,
        ]);

        // Topics auto-curate from EXISTING Quran/Hadith/Article content, so only
        // run once that content has been imported. Otherwise it would create empty
        // topics. Always runnable standalone via: php artisan db:seed --class=TopicSeeder
        if (DB::table('quran_verse_translations')->exists()
            && DB::table('hadith_texts')->exists()) {
            $this->call(TopicSeeder::class);
        }

        $this->command->newLine();
        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->newLine();
    }
}
