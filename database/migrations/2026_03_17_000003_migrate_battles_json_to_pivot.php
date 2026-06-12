<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migrate battle data from the JSON battles column on sahaba
 * into the new sahabi_battles pivot table.
 *
 * This migration is intentionally data-only (no schema changes).
 * The JSON column is dropped in a separate migration (000004) so
 * this migration can be rolled back independently if needed.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sahabas = DB::table('sahaba')
            ->whereNotNull('battles')
            ->select(['id', 'battles'])
            ->get();

        foreach ($sahabas as $sahabi) {
            $battles = json_decode($sahabi->battles, true);

            if (! is_array($battles)) {
                continue;
            }

            foreach ($battles as $battleName) {
                if (empty($battleName)) {
                    continue;
                }

                DB::table('sahabi_battles')->insertOrIgnore([
                    'sahabi_id' => $sahabi->id,
                    'battle_name' => $battleName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * Restores the JSON battles column from pivot rows so that
     * rolling back migration 000004 (which re-adds the column)
     * followed by rolling back this migration is safe.
     */
    public function down(): void
    {
        $battles = DB::table('sahabi_battles')
            ->select(['sahabi_id', 'battle_name'])
            ->get()
            ->groupBy('sahabi_id');

        foreach ($battles as $sahabiId => $rows) {
            DB::table('sahaba')
                ->where('id', $sahabiId)
                ->update([
                    'battles' => json_encode(
                        $rows->pluck('battle_name')->values()->all()
                    ),
                ]);
        }
    }
};
