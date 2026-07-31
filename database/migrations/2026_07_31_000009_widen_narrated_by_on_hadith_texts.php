<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `narrated_by` was declared varchar(500) in data-model.md §5 on the assumption
 * that a narrator preamble is a short attribution line. The real corpus
 * disagrees: Sahih al-Bukhari 3906 carries a 2,806-character preamble, because
 * sunnah.com's `.hadith_narrated` sometimes contains the whole opening
 * narration rather than just the narrator's name.
 *
 * Measured across all 97 Bukhari books before changing anything: `narrated_by`
 * is the ONLY string column the real data overflows. hadith_number peaks at 9
 * of 50, chapter_number at 3 of 20, book_number at 2 of 20.
 *
 * A separate migration rather than an edit to the create migration: the schema
 * has already been applied to both databases, and editing an applied migration
 * leaves environments silently divergent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hadith_texts', function (Blueprint $table): void {
            $table->text('narrated_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Truncating rows would destroy scripture, so the rollback restores the
        // narrow type only when nothing would be lost.
        Schema::table('hadith_texts', function (Blueprint $table): void {
            $table->string('narrated_by', 500)->nullable()->change();
        });
    }
};
