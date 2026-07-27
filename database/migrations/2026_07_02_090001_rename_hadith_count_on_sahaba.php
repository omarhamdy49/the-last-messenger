<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `hadith_count` sums to 4,150 across the table but is a raw scraper bio-text
 * claim (islamic_history.hadith_count from muslimscholars.info) — it has no
 * relationship to this database's actual hadith graph, where only 23 Sahaba
 * are linked as narrators via `hadith_narrators.sahaba_id`. Rename so the
 * column's provenance is explicit; the real, live count is derived at query
 * time (see Sahabi::scopeWithNarratorLinkCount) rather than denormalized,
 * since only 23 rows currently qualify.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sahaba', function (Blueprint $table): void {
            $table->renameColumn('hadith_count', 'reported_hadith_count');
        });
    }

    public function down(): void
    {
        Schema::table('sahaba', function (Blueprint $table): void {
            $table->renameColumn('reported_hadith_count', 'hadith_count');
        });
    }
};
