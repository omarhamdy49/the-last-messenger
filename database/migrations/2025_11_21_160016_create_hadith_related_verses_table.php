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
        Schema::create('hadith_related_verses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->onDelete('cascade')->comment('Reference to hadith');
            $table->integer('surah')->comment('Surah number (1-114)');
            $table->string('surah_name_ar', 255)->nullable()->comment('Surah name in Arabic');
            $table->string('surah_name_en', 255)->nullable()->comment('Surah name in English');
            $table->integer('ayah')->comment('Ayah (verse) number');
            $table->text('text_ar')->nullable()->comment('Verse text in Arabic');
            $table->text('text_en')->nullable()->comment('Verse text in English');
            $table->text('relevance')->nullable()->comment('Explanation of relevance');
            $table->timestamps();

            // Indexes
            $table->index('hadith_id');
            $table->index(['surah', 'ayah']);
        });

        if (config('database.default') === 'mysql') {
            // Set charset and collation
            DB::statement('ALTER TABLE hadith_related_verses ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_related_verses');
    }
};
