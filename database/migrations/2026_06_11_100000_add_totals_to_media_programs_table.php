<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_programs', function (Blueprint $table) {
            // Editorial totals (may differ from uploaded episodes while a season is in progress).
            // Named total_* to avoid clashing with the episodes_count withCount() alias.
            $table->unsignedSmallInteger('total_seasons')->default(1)->after('year');
            $table->unsignedSmallInteger('total_episodes')->default(0)->after('total_seasons');
        });
    }

    public function down(): void
    {
        Schema::table('media_programs', function (Blueprint $table) {
            $table->dropColumn(['total_seasons', 'total_episodes']);
        });
    }
};
