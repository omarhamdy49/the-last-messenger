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
        Schema::create('hadith_commentaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->onDelete('cascade')->comment('Reference to hadith');
            $table->string('language_code', 10)->comment('Language code (ar, en, etc.)');
            $table->string('author', 255)->nullable()->comment('Author/scholar name');
            $table->string('source', 255)->nullable()->comment('Source reference');
            $table->longText('commentary_text')->nullable()->comment('Commentary text');
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('hadith_id');
            $table->index('language_code');
            $table->index('author');
        });

        if (config('database.default') === 'mysql') {
            // Set charset and collation
            DB::statement('ALTER TABLE hadith_commentaries ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
        }

        // Add FULLTEXT index
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE hadith_commentaries ADD FULLTEXT INDEX ft_hadith_commentaries_text (commentary_text)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_commentaries');
    }
};
