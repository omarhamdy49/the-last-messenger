<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rolled-up view count over the trailing 7 days (direct program views plus
 * episode views), recomputed by media:compute-trending every 30 minutes.
 * Drives the /media Trending row ordering; the manual is_trending flag stays
 * as the cold-start fallback.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_programs', function (Blueprint $table): void {
            $table->unsignedInteger('trending_score')->default(0)->index()->after('is_trending');
        });
    }

    public function down(): void
    {
        Schema::table('media_programs', function (Blueprint $table): void {
            $table->dropIndex(['trending_score']);
            $table->dropColumn('trending_score');
        });
    }
};
