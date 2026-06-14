<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remember the source YouTube playlist on a program so the import modal can
 * prefill it and future re-syncs are one click. Nullable — most programs are
 * authored manually.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_programs', function (Blueprint $table): void {
            $table->string('youtube_playlist_id')->nullable()->after('slug')->index();
        });
    }

    public function down(): void
    {
        Schema::table('media_programs', function (Blueprint $table): void {
            $table->dropColumn('youtube_playlist_id');
        });
    }
};
