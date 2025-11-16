<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hadith_narrators', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 255)->comment('Narrator name in Arabic');
            $table->string('full_name_ar', 255)->nullable()->comment('Full name in Arabic');
            $table->string('kunya', 255)->nullable()->comment('Kunya (e.g., Abu...)');
            $table->string('title', 255)->nullable()->comment('Title/honorific');
            $table->integer('death_year_hijri')->nullable()->comment('Death year (Hijri calendar)');
            $table->text('notes')->nullable()->comment('Biographical notes');
        });

        // Set charset and collation
        DB::statement('ALTER TABLE hadith_narrators ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');

        // Add FULLTEXT index
        DB::statement('ALTER TABLE hadith_narrators ADD FULLTEXT INDEX ft_hadith_narrators (name_ar, full_name_ar, notes)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_narrators');
    }
};
