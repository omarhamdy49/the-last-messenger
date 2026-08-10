<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The admin confirms a per-video mapping (episode number, title, include)
 * in the import modal before the run starts. The queued job needs that
 * mapping, so it is recorded on the run rather than re-derived.
 *
 * NULL keeps the previous behaviour: import every entry in playlist order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('youtube_sync_runs', function (Blueprint $table): void {
            $table->json('items')->nullable()->after('playlist_url');
        });
    }

    public function down(): void
    {
        Schema::table('youtube_sync_runs', function (Blueprint $table): void {
            $table->dropColumn('items');
        });
    }
};
