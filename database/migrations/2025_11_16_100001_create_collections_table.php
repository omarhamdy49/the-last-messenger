<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->text('description')->nullable()->comment('Collection description');
            $table->string('compiler', 255)->nullable()->comment('Compiler/Author name');
            $table->integer('death_year_hijri')->nullable()->comment('Compiler death year (Hijri calendar)');
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('name_en', 'idx_hadith_collections_name_en');
        });

        // Set charset and collation
        DB::statement('ALTER TABLE hadith_collections ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_collections');
    }
};
