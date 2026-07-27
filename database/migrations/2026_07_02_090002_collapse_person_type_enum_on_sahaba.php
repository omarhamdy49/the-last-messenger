<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `person_type` baked gender into the enum (companion_male/companion_female)
 * even though `gender` and `is_sahabi` already exist as their own columns —
 * a leftover from the dropped `people` table that was carried over unchanged.
 * Collapse to a short, gender-neutral enum: prophet|sahabi|family|tribe|historical.
 */
return new class extends Migration
{
    private const OLD_TO_NEW = [
        'companion_male' => 'sahabi',
        'companion_female' => 'sahabi',
        'tribe_leader' => 'tribe',
        'historical_figure' => 'historical',
    ];

    public function up(): void
    {
        foreach (self::OLD_TO_NEW as $old => $new) {
            DB::table('sahaba')->where('person_type', $old)->update(['person_type' => $new]);
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sahaba MODIFY person_type ENUM('prophet','sahabi','family','tribe','historical') NOT NULL DEFAULT 'sahabi'");
        } else {
            Schema::table('sahaba', function (Blueprint $table): void {
                $table->string('person_type', 20)->default('sahabi')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sahaba MODIFY person_type VARCHAR(50) NOT NULL DEFAULT 'companion_male'");
        }

        DB::table('sahaba')->where('person_type', 'tribe')->update(['person_type' => 'tribe_leader']);
        DB::table('sahaba')->where('person_type', 'historical')->update(['person_type' => 'historical_figure']);
        DB::table('sahaba')->where('person_type', 'sahabi')->where('gender', 'female')->update(['person_type' => 'companion_female']);
        DB::table('sahaba')->where('person_type', 'sahabi')->where('gender', 'male')->update(['person_type' => 'companion_male']);
    }
};
