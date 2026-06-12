<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hadith_collections', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 255)->comment('Collection name in Arabic');
            $table->string('name_en', 255)->comment('Collection name in English');
            $table->string('slug_ar', 255)->unique()->comment('Arabic slug for URLs');
            $table->string('slug_en', 255)->unique()->comment('English slug for URLs');
            $table->text('description_ar')->nullable()->comment('Description in Arabic');
            $table->text('description_en')->nullable()->comment('Description in English');
            $table->string('abbreviation', 50)->nullable()->comment('Abbreviation (e.g., "Bukhari")');
            $table->integer('total_hadiths')->default(0)->comment('Total number of hadiths');
            $table->timestamps();

            // Indexes
            $table->index('name_ar');
            $table->index('name_en');
            $table->index('abbreviation');
        });

        // Set charset and collation (MySQL only)
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE hadith_collections ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');

            // Add FULLTEXT index
            DB::statement('ALTER TABLE hadith_collections ADD FULLTEXT INDEX ft_hadith_collections_names (name_ar, name_en)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_collections');
    }
};
