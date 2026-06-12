<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seerah taxonomy + rich-content columns (WEB_APP_MODULES_ANALYSIS.md §8):
 * - era / phase taxonomy used by the public timeline UI filters
 * - is_pivotal flag ("حدث مفصلي" badge)
 * - statistics JSON (battle forces, martyrs, duration, ...)
 * - widen event_type so it covers the UI's category set (adds delegation, personal)
 */
return new class extends Migration
{
    private const EVENT_TYPES = [
        'birth',
        'death',
        'battle',
        'migration',
        'revelation',
        'treaty',
        'marriage',
        'miracle',
        'delegation',
        'personal',
        'other',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seerah_events', function (Blueprint $table) {
            $table->string('era', 20)->nullable()->after('importance');
            $table->string('phase', 20)->nullable()->after('era');
            $table->boolean('is_pivotal')->default(false)->after('is_featured');
            $table->json('statistics')->nullable()->after('gallery');

            $table->index('era');
            $table->index('phase');
            $table->index('is_pivotal');
        });

        // Widen the event_type enum to include the new UI categories.
        if (DB::getDriverName() === 'mysql') {
            $values = "'".implode("','", self::EVENT_TYPES)."'";
            DB::statement(
                "ALTER TABLE seerah_events MODIFY event_type ENUM({$values}) NOT NULL DEFAULT 'other'"
            );
        } else {
            // Non-MySQL drivers (tests): relax to a plain string column.
            Schema::table('seerah_events', function (Blueprint $table) {
                $table->string('event_type', 30)->default('other')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // New enum values cannot survive the narrowing below.
        DB::table('seerah_events')
            ->whereIn('event_type', ['delegation', 'personal'])
            ->update(['event_type' => 'other']);

        if (DB::getDriverName() === 'mysql') {
            $originalTypes = array_values(array_diff(self::EVENT_TYPES, ['delegation', 'personal']));
            $values = "'".implode("','", $originalTypes)."'";
            DB::statement(
                "ALTER TABLE seerah_events MODIFY event_type ENUM({$values}) NOT NULL DEFAULT 'other'"
            );
        }

        Schema::table('seerah_events', function (Blueprint $table) {
            $table->dropIndex(['era']);
            $table->dropIndex(['phase']);
            $table->dropIndex(['is_pivotal']);
            $table->dropColumn(['era', 'phase', 'is_pivotal', 'statistics']);
        });
    }
};
