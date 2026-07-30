<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalised form of sunnah.com's `HadithTable.xrefs varchar(1000)` comma list.
     */
    public function up(): void
    {
        Schema::create('hadith_cross_refs', function (Blueprint $table): void {
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->foreignId('related_hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->enum('relation', ['parallel', 'duplicate', 'see_also'])->default('parallel');

            $table->primary(['hadith_id', 'related_hadith_id']);
            $table->index(['related_hadith_id', 'hadith_id'], 'idx_hadith_cross_refs_reverse');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_cross_refs');
    }
};
