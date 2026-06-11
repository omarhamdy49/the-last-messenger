<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Media\Enums\ProgramCategory;
use App\Domain\Media\Models\MediaCategory;
use Illuminate\Database\Seeder;

/**
 * One row per ProgramCategory enum value — idempotent (keyed on `key`).
 * All categories start featured so the public list shows every row until
 * an admin hides one.
 */
class MediaCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (ProgramCategory::cases() as $order => $category) {
            MediaCategory::updateOrCreate(
                ['key' => $category->value],
                ['display_order' => $order],
            );
        }

        $this->command?->info('Media categories seeded: '.MediaCategory::count());
    }
}
