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
        Schema::create('hadith_hadiths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('hadith_books')->onDelete('cascade')->comment('Reference to book');
            $table->foreignId('companion_id')->nullable()->constrained('hadith_companions')->onDelete('set null')->comment('Reference to companion (narrator)');
            $table->integer('hadith_number')->nullable()->comment('Hadith number in the book');
            $table->string('grading_status', 50)->nullable()->comment('Overall grading status');
            $table->string('grading_agreement', 255)->nullable()->comment('Grading agreement between scholars');
            $table->text('significance_ar')->nullable()->comment('Significance/importance in Arabic');
            $table->text('significance_en')->nullable()->comment('Significance/importance in English');
            $table->text('historical_context_ar')->nullable()->comment('Historical context in Arabic');
            $table->text('historical_context_en')->nullable()->comment('Historical context in English');
            $table->string('verification_status', 50)->nullable()->comment('Verification status');
            $table->text('uncertainty_notes')->nullable()->comment('Notes on any uncertainties');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            // Indexes
            $table->index('book_id');
            $table->index('companion_id');
            $table->index('hadith_number');
            $table->index('grading_status');
            $table->index('verification_status');
        });

        if (config('database.default') === 'mysql') {
            // Set charset and collation
            DB::statement('ALTER TABLE hadith_hadiths ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_hadiths');
    }
};
