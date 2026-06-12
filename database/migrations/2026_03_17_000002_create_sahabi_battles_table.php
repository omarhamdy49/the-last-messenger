<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the sahabi_battles pivot table.
 *
 * Replaces the JSON battles column on sahaba with a proper relational table,
 * enabling index-backed lookups instead of full-table JSON scans.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sahabi_battles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sahabi_id')->constrained('sahaba')->cascadeOnDelete();
            $table->string('battle_name', 100)->index();
            $table->timestamps();

            // Composite index: the primary access pattern is getByBattle(battle_name)
            // which filters on battle_name then joins to sahaba rows.
            // The leading column matches the WHERE clause; sahabi_id avoids a second
            // index look-up when the join column is resolved.
            $table->index(['battle_name', 'sahabi_id'], 'sahabi_battles_battle_sahabi_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sahabi_battles');
    }
};
