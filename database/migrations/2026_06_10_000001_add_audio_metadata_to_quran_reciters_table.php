<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds audio-resolution and presentation metadata to reciters:
     * - riwayah: the narration of the Quranic text recited (e.g. "Hafs 'an 'Asim")
     * - everyayah_folder: folder slug on everyayah.com used to resolve per-verse
     *   audio URLs ({folder}/{SSS}{AAA}.mp3). Nullable — reciters without
     *   per-verse audio simply resolve to null.
     * - followers_count: displayed on public reciter cards (recordings count is
     *   derived from quran_recitations via withCount, not stored).
     */
    public function up(): void
    {
        Schema::table('quran_reciters', function (Blueprint $table) {
            $table->string('riwayah', 100)->nullable()->after('style');
            $table->string('everyayah_folder', 100)->nullable()->after('riwayah');
            $table->unsignedInteger('followers_count')->default(0)->after('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_reciters', function (Blueprint $table) {
            $table->dropColumn(['riwayah', 'everyayah_folder', 'followers_count']);
        });
    }
};
