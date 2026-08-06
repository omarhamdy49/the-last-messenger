<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\People\Models\IslamicRole;
use Illuminate\Database\Seeder;

/**
 * The five capacities that replace `person_type`, `is_sahabi`, `is_companion`,
 * and the scholar/narrator/grader/compiler distinctions.
 *
 * `updateOrCreate` on `slug` so re-seeding is idempotent — the rebuild's whole
 * claim is that running it twice changes nothing.
 */
final class IslamicRoleSeeder extends Seeder
{
    private const ROLES = [
        ['slug' => 'sahabi', 'name_ar' => 'صحابي', 'name_en' => 'Companion'],
        ['slug' => 'narrator', 'name_ar' => 'راوي', 'name_en' => 'Narrator'],
        ['slug' => 'scholar', 'name_ar' => 'عالم', 'name_en' => 'Scholar'],
        ['slug' => 'grader', 'name_ar' => 'محقق', 'name_en' => 'Grader'],
        ['slug' => 'compiler', 'name_ar' => 'مصنف', 'name_en' => 'Compiler'],
    ];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            IslamicRole::updateOrCreate(
                ['slug' => $role['slug']],
                ['name_ar' => $role['name_ar'], 'name_en' => $role['name_en']],
            );
        }
    }
}
