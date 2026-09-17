<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The Seerah event-type lookup.
 *
 * Idempotent: keyed on slug, so re-running updates names and look-and-feel in
 * place rather than duplicating rows. Formerly seeded inside a migration.
 */
class SeerahEventTypeSeeder extends Seeder
{
    /**
     * slug => [ar, en, icon, color, gradient]
     */
    private const TYPES = [
        'birth' => ['ولادة', 'Birth', 'baby', 'green', 'from-green-400 to-emerald-500'],
        'death' => ['وفاة', 'Death', 'cross', 'gray', 'from-gray-500 to-slate-600'],
        'battle' => ['غزوة/معركة', 'Battle', 'swords', 'red', 'from-red-500 to-orange-500'],
        'migration' => ['هجرة', 'Migration', 'move', 'blue', 'from-blue-500 to-cyan-500'],
        'revelation' => ['نزول وحي', 'Revelation', 'book-open', 'purple', 'from-yellow-400 to-amber-500'],
        'treaty' => ['معاهدة', 'Treaty', 'file-text', 'yellow', 'from-emerald-500 to-teal-500'],
        'marriage' => ['زواج', 'Marriage', 'heart', 'pink', 'from-pink-400 to-rose-500'],
        'miracle' => ['معجزة', 'Miracle', 'sparkles', 'cyan', 'from-indigo-500 to-purple-600'],
        'delegation' => ['وفد/بعثة', 'Delegation', 'users', 'teal', 'from-teal-400 to-cyan-500'],
        'personal' => ['حدث شخصي', 'Personal', 'user', 'orange', 'from-amber-400 to-orange-500'],
        'other' => ['حدث آخر', 'Other Event', 'circle', 'slate', 'from-slate-400 to-slate-500'],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $slug => [$ar, $en, $icon, $color, $gradient]) {
            DB::table('seerah_event_types')->updateOrInsert(
                ['slug' => $slug],
                [
                    'metadata' => json_encode(compact('icon', 'color', 'gradient')),
                    'locale' => json_encode(['ar' => $ar, 'en' => $en], JSON_UNESCAPED_UNICODE),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        $this->command?->info('Seerah event types: '.count(self::TYPES));
    }
}
