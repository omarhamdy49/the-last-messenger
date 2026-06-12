<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the battles JSON column from the sahaba table.
 *
 * Run this only after verifying that migration 000003
 * (migrate_battles_json_to_pivot) completed successfully and
 * that the sahabi_battles row count matches expectations.
 *
 * Rolling back this migration re-adds the column (populated by
 * rolling back migration 000003 afterwards).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('sahaba', 'battles')) {
            Schema::table('sahaba', function (Blueprint $table) {
                $table->dropColumn('battles');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Re-adds the column as nullable JSON so that rolling back
     * migration 000003 can restore the data into it.
     */
    public function down(): void
    {
        Schema::table('sahaba', function (Blueprint $table) {
            $table->json('battles')->nullable()->after('hadith_count');
        });
    }
};
