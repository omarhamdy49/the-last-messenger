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
        Schema::create('hadith_commentaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadiths')->onDelete('cascade')->comment('Reference to hadith');
            $table->string('language_code', 10)->comment('Language code (e.g., ar, en, ur, fr, tr)');
            $table->string('author', 255)->nullable()->comment('Commentary author');
            $table->string('source', 255)->nullable()->comment('Source book (e.g., Fath al-Bari, Sharh al-Nawawi)');
            $table->longText('commentary_text')->nullable()->comment('Commentary/explanation text');
            $table->timestamp('created_at')->useCurrent();
        });

        // Set charset and collation
        DB::statement('ALTER TABLE hadith_commentaries ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');

        // Add FULLTEXT index
        DB::statement('ALTER TABLE hadith_commentaries ADD FULLTEXT INDEX ft_commentary_text (commentary_text)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_commentaries');
    }
};
