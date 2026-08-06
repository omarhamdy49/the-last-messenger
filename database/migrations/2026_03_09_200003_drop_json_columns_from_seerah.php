<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop JSON translatable columns from seerah_events.
 *
 * The sahaba half of this migration was removed with the Sahaba module: the
 * table no longer exists, so dropping columns from it would fail on a fresh
 * build. Seerah survives and still needs its JSON columns removed in favour of
 * seerah_event_translations.
 *
 * These columns are now stored in seerah_event_translations and sahabi_translations.
 * Only run this after verifying data migration succeeded (migration 200002).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seerah_events', function (Blueprint $table) {
            $table->dropColumn(['title', 'description', 'content']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seerah_events', function (Blueprint $table) {
            $table->json('title')->nullable()->after('slug');
            $table->json('description')->nullable()->after('title');
            $table->json('content')->nullable()->after('description');
        });

    }
};
