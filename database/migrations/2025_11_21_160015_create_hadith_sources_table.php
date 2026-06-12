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
        Schema::create('hadith_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->onDelete('cascade')->comment('Reference to hadith');
            $table->string('source_type', 50)->comment('Source type (book, website, manuscript)');
            $table->string('title', 255)->comment('Source title');
            $table->string('author', 255)->nullable()->comment('Source author');
            $table->string('reference', 255)->nullable()->comment('Specific reference (page, volume, etc.)');
            $table->string('url', 500)->nullable()->comment('URL if available');
            $table->timestamps();

            // Indexes
            $table->index('hadith_id');
            $table->index('source_type');
        });

        if (config('database.default') === 'mysql') {
            // Set charset and collation
            DB::statement('ALTER TABLE hadith_sources ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_sources');
    }
};
