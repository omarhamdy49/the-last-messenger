<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-locale episode transcript for the YouTube-style transcript section
 * on the public episode page. Plain text; lines may start with a
 * "mm:ss" / "hh:mm:ss" timestamp that the frontend renders as a chip.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_episode_translations', function (Blueprint $table) {
            $table->longText('transcript')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('media_episode_translations', function (Blueprint $table) {
            $table->dropColumn('transcript');
        });
    }
};
