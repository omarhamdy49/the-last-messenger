<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A reciter can be filed under another reciter as one of their recordings.
 *
 * Quran Foundation and mp3quran both list a reciter once per recording, so the same
 * person arrives as several `quran_resources` rows. `parent_id` groups them without
 * touching ids: playlists, favourites and reader links keep pointing at the recording
 * they always pointed at. One level only — a recording never has recordings of its own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_resources', function (Blueprint $table) {
            // nullOnDelete: removing the profile releases its recordings instead of deleting them.
            $table->foreignId('parent_id')->nullable()->after('kind')->constrained('quran_resources')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quran_resources', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
