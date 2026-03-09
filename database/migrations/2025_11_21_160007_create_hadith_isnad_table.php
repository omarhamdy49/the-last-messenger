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
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->onDelete('cascade')->comment('Reference to hadith');
            $table->foreignId('narrator_id')->constrained('hadith_narrators')->onDelete('cascade')->comment('Reference to narrator');
            $table->integer('position_in_chain')->comment('Position in the chain (1 = closest to Prophet)');
            $table->string('transmission_method', 100)->nullable()->comment('Method of transmission (e.g., حدثنا)');
            $table->timestamps();

            // Indexes
            $table->index('hadith_id');
            $table->index('narrator_id');
            $table->index('position_in_chain');
            $table->unique(['hadith_id', 'narrator_id', 'position_in_chain']);
        });

        if (config('database.default') === 'mysql') {
            // Set charset and collation
            DB::statement('ALTER TABLE hadith_isnad ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_isnad');
    }
};
