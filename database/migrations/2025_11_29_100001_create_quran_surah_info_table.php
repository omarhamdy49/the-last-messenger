<?php

declare(strict_types=1);

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
        Schema::create('quran_surah_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surah_id')->constrained('quran_surahs')->cascadeOnDelete();
            $table->string('lang', 5); // ar, en, fr, tr, ur
            $table->mediumText('desc')->nullable(); // Description
            $table->longText('content')->nullable(); // Full content/explanation
            $table->string('source')->nullable(); // Source reference
            $table->boolean('active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('surah_id');
            $table->index('lang');
            $table->index('active');
            $table->unique(['surah_id', 'lang']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_surah_info');
    }
};
