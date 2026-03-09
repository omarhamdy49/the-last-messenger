<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop JSON translatable columns from seerah_events and sahaba tables.
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

        Schema::table('sahaba', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'full_name',
                'kunya',
                'laqab',
                'biography',
                'virtues',
                'famous_narrations',
            ]);
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

        Schema::table('sahaba', function (Blueprint $table) {
            $table->json('name')->nullable()->after('slug');
            $table->json('full_name')->nullable()->after('name');
            $table->json('kunya')->nullable()->after('full_name');
            $table->json('laqab')->nullable()->after('kunya');
            $table->json('biography')->nullable()->after('status');
            $table->json('virtues')->nullable()->after('biography');
            $table->json('famous_narrations')->nullable()->after('virtues');
        });
    }
};
