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
        Schema::create('quran_recitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reciter_id');
            $table->unsignedBigInteger('surah_id');
            $table->string('audio_url', 500);
            $table->string('format', 10); // 'mp3', 'aac', 'opus'
            $table->string('quality', 20)->nullable(); // 'high', 'medium', 'low'
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('file_size_kb')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('reciter_id')
                ->references('id')
                ->on('quran_reciters')
                ->cascadeOnDelete();

            $table->foreign('surah_id')
                ->references('id')
                ->on('quran_surahs');

            // One recording per surah per format per reciter
            $table->unique(['reciter_id', 'surah_id', 'format']);

            // Indexes for frequent lookups
            $table->index('reciter_id');
            $table->index('surah_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_recitations');
    }
};
