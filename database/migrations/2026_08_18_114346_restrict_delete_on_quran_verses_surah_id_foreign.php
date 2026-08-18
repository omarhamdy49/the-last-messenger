<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-04: quran_verses.surah_id was ON DELETE CASCADE, so deleting a surah
 * silently wiped every verse under it (and cascaded further into
 * translations/tafsirs/words). Swap it to RESTRICT so a surah with verses
 * cannot be hard-deleted at all; the admin surah-delete path is expected to
 * surface the resulting constraint violation as a clean error instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_verses', function (Blueprint $table) {
            $table->dropForeign('quran_verses_surah_id_foreign');
        });

        Schema::table('quran_verses', function (Blueprint $table) {
            $table->foreign('surah_id')
                ->references('id')->on('quran_surahs')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quran_verses', function (Blueprint $table) {
            $table->dropForeign('quran_verses_surah_id_foreign');
        });

        Schema::table('quran_verses', function (Blueprint $table) {
            $table->foreign('surah_id')
                ->references('id')->on('quran_surahs')
                ->cascadeOnDelete();
        });
    }
};
