<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_playlist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('playlist_id')->constrained('quran_playlists')->cascadeOnDelete();
            $table->foreignId('surah_id')->constrained('quran_surahs')->cascadeOnDelete();
            // The recording. NULL after a recording is deleted: the row stays and
            // renders as "needs a reciter" instead of silently vanishing.
            $table->foreignId('reciter_id')->nullable()->constrained('quran_reciters')->nullOnDelete();
            // Play order is surah order, so one row per surah is the whole model.
            $table->unique(['playlist_id', 'surah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_playlist_items');
    }
};
