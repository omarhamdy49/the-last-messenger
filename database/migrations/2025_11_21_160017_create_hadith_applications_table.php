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
        Schema::create('hadith_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->onDelete('cascade')->comment('Reference to hadith');
            $table->string('category_ar', 255)->nullable()->comment('Application category in Arabic');
            $table->string('category_en', 255)->nullable()->comment('Application category in English');
            $table->text('description_ar')->nullable()->comment('Description in Arabic');
            $table->text('description_en')->nullable()->comment('Description in English');
            $table->integer('display_order')->default(0)->comment('Display order');
            $table->timestamps();

            // Indexes
            $table->index('hadith_id');
            $table->index('category_en');
            $table->index('display_order');
        });

        // Set charset and collation
        DB::statement('ALTER TABLE hadith_applications ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_applications');
    }
};
