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
        Schema::create('hadiths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('hadith_books')->onDelete('cascade')->comment('Reference to book/chapter');
            $table->integer('hadith_number')->nullable()->comment('Hadith number within the book');
            $table->string('grading_status', 50)->nullable()->comment('Overall grading status');
            $table->string('grading_agreement', 255)->nullable()->comment('Consensus on grading');
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('book_id', 'idx_hadiths_book');
            $table->index('hadith_number', 'idx_hadith_number');
        });

        // Set charset and collation
        DB::statement('ALTER TABLE hadiths ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadiths');
    }
};
