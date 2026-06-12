<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE hadith_hadiths MODIFY grading_status VARCHAR(512) NULL');
        DB::statement('ALTER TABLE hadith_grading_levels MODIFY grade_ar VARCHAR(512) NOT NULL');
        DB::statement('ALTER TABLE hadith_grading_levels MODIFY grade_en VARCHAR(512) NOT NULL');
    }

    public function down(): void
    {
        if (config('database.default') !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE hadith_hadiths MODIFY grading_status VARCHAR(50) NULL');
        DB::statement('ALTER TABLE hadith_grading_levels MODIFY grade_ar VARCHAR(50) NOT NULL');
        DB::statement('ALTER TABLE hadith_grading_levels MODIFY grade_en VARCHAR(50) NOT NULL');
    }
};
