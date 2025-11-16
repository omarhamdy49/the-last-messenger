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
        Schema::create('hadith_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadiths')->onDelete('cascade')->comment('Reference to hadith');
            $table->string('source_book', 255)->nullable()->comment('Source book name');
            $table->string('reference_code', 50)->nullable()->comment('Reference code/number');
            $table->string('arabic_ref', 50)->nullable()->comment('Arabic reference');
        });

        // Set charset and collation
        DB::statement('ALTER TABLE hadith_sources ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');

        // Add FULLTEXT index
        DB::statement('ALTER TABLE hadith_sources ADD FULLTEXT INDEX ft_hadith_sources (source_book)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_sources');
    }
};
