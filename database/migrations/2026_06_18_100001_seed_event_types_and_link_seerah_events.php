<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the 11 canonical Seerah event types (snapshot of the former EventType enum),
 * add the seerah_event_type_id FK to seerah_events, backfill it from the legacy
 * event_type slug, then drop the legacy enum column.
 *
 * The data is inlined (not read from the enum) so this migration is a stable
 * historical snapshot independent of future enum/code changes.
 */
return new class extends Migration
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

    public function up(): void
    {
        // 1. Seed the types + their translations (idempotent on slug / locale).
        $order = 0;
        foreach (self::TYPES as $slug => [$ar, $en, $icon, $color, $gradient]) {
            $order++;
            DB::table('seerah_event_types')->insertOrIgnore([
                'slug' => $slug,
                'icon' => $icon,
                'color' => $color,
                'gradient' => $gradient,
                'is_active' => true,
                'display_order' => $order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $typeId = DB::table('seerah_event_types')->where('slug', $slug)->value('id');
            foreach (['ar' => $ar, 'en' => $en] as $locale => $name) {
                DB::table('seerah_event_type_translations')->insertOrIgnore([
                    'seerah_event_type_id' => $typeId,
                    'locale' => $locale,
                    'name' => $name,
                    'is_ready' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2. Add the FK column (nullable; constrained() auto-indexes it).
        Schema::table('seerah_events', function (Blueprint $table) {
            $table->foreignId('seerah_event_type_id')
                ->nullable()
                ->after('gregorian_year')
                ->constrained('seerah_event_types')
                ->nullOnDelete();
        });

        // 3. Backfill from the legacy event_type slug (portable: per-slug updates).
        $idBySlug = DB::table('seerah_event_types')->pluck('id', 'slug');
        foreach ($idBySlug as $slug => $id) {
            DB::table('seerah_events')->where('event_type', $slug)->update(['seerah_event_type_id' => $id]);
        }

        // 4. Drop the legacy event_type column and its indexes.
        Schema::table('seerah_events', function (Blueprint $table) {
            $table->dropIndex(['event_type']); // seerah_events_event_type_index
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('DROP INDEX idx_seerah_events_type ON seerah_events');
        } else {
            DB::statement('DROP INDEX IF EXISTS idx_seerah_events_type');
        }

        Schema::table('seerah_events', function (Blueprint $table) {
            $table->dropColumn('event_type');
        });
    }

    public function down(): void
    {
        // 1. Re-add the legacy event_type column.
        if (DB::getDriverName() === 'mysql') {
            $values = "'".implode("','", array_keys(self::TYPES))."'";
            DB::statement(
                "ALTER TABLE seerah_events ADD COLUMN event_type ENUM({$values}) NOT NULL DEFAULT 'other' AFTER gregorian_year"
            );
        } else {
            Schema::table('seerah_events', function (Blueprint $table) {
                $table->string('event_type', 30)->default('other')->after('gregorian_year');
            });
        }

        // 2. Backfill event_type from the type relation before it disappears.
        $slugById = DB::table('seerah_event_types')->pluck('slug', 'id');
        foreach ($slugById as $id => $slug) {
            DB::table('seerah_events')->where('seerah_event_type_id', $id)->update(['event_type' => $slug]);
        }

        // 3. Restore indexes.
        Schema::table('seerah_events', function (Blueprint $table) {
            $table->index('event_type');
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('CREATE INDEX idx_seerah_events_type ON seerah_events(event_type)');
        }

        // 4. Drop the FK column.
        Schema::table('seerah_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seerah_event_type_id');
        });

        // 5. Remove the seeded types.
        DB::table('seerah_event_types')->whereIn('slug', array_keys(self::TYPES))->delete();
    }
};
