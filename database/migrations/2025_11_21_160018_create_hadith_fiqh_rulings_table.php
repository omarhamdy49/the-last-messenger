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
        Schema::create('hadith_fiqh_rulings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->onDelete('cascade')->comment('Reference to hadith');
            $table->text('principle')->comment('Fiqh principle/ruling text');
            $table->string('language_code', 10)->comment('Language code (ar, en, etc.)');
            $table->integer('display_order')->default(0)->comment('Display order');
            $table->timestamps();

            // Indexes
            $table->index('hadith_id');
            $table->index('language_code');
            $table->index('display_order');
        });

        if (config('database.default') === 'mysql') {
            // Set charset and collation
            DB::statement('ALTER TABLE hadith_fiqh_rulings ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
        }

        // Add FULLTEXT index
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE hadith_fiqh_rulings ADD FULLTEXT INDEX ft_hadith_fiqh_rulings_principle (principle)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_fiqh_rulings');
    }
};
