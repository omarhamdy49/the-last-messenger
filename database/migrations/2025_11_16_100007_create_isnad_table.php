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
        Schema::create('hadith_isnad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadiths')->onDelete('cascade')->comment('Reference to hadith');
            $table->integer('order_number')->comment('Position in the chain of narration');
            $table->foreignId('narrator_id')->constrained('hadith_narrators')->onDelete('cascade')->comment('Reference to narrator');
            $table->string('verb', 50)->nullable()->comment('Narration verb (e.g., حدثنا، أخبرنا)');
            $table->string('narrated_from', 255)->nullable()->comment('Source of narration');

            // Indexes
            $table->index('hadith_id', 'idx_hadith_isnad_hadith');
            $table->index('narrator_id', 'idx_hadith_isnad_narrator');
            $table->index('order_number', 'idx_hadith_isnad_order');
        });

        // Set charset and collation
        DB::statement('ALTER TABLE hadith_isnad ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_isnad');
    }
};
