<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records which mp3quran.net recitation ("read") each of our reciters is, so the
 * mapping survives between runs of quran:sync-mp3quran and Phase 2 can ask for
 * per-ayah timing without re-matching names.
 *
 * `mp3quran_read_id` is null for a reciter whose recitation mp3quran hosts but
 * has no timing data for — the folder is still usable for whole-surah audio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_reciters', function (Blueprint $table) {
            $table->unsignedInteger('mp3quran_read_id')->nullable()->after('everyayah_folder');
            $table->string('mp3quran_folder')->nullable()->after('mp3quran_read_id');

            // Phase 2 looks a reciter up by read id when timing data comes back.
            $table->index('mp3quran_read_id', 'quran_reciters_mp3quran_read_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('quran_reciters', function (Blueprint $table) {
            $table->dropIndex('quran_reciters_mp3quran_read_id_index');
            $table->dropColumn(['mp3quran_read_id', 'mp3quran_folder']);
        });
    }
};
