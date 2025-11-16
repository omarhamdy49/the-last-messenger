<?php

declare(strict_types=1);

namespace Database\Seeders\Hadith;

use Illuminate\Database\Seeder;

class HadithMasterSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Starting Hadith Module Seeding...');
        $this->command->newLine();

        $this->call(CollectionSeeder::class);

        $this->command->newLine();
        $this->command->info('✅ Hadith Module seeding completed successfully!');
    }
}
