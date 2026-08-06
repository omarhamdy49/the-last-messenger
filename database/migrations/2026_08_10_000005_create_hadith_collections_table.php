<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §5.
 *
 * One public key only: `slug`. The old `code` column is gone (Decision F).
 * Slugs come from the map in config/islamic-data.php, NOT from directory names
 * — six of the seventeen differ, so deriving them would produce six wrong URLs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_collections', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 200)->unique();
            $table->string('thumb', 255)->nullable();

            // Load-bearing. Collections print the number MID-LINE
            // ("Book {bookNumber}, Hadith {hadithNumber}"), so the number is
            // substituted into a placeholder and never appended. Without this
            // there is no canonical citation line at all.
            $table->string('reference_template', 255)->nullable();

            $table->string('title_ar', 200);
            $table->string('title_en', 200);
            $table->mediumText('intro_ar')->nullable();
            $table->mediumText('intro_en')->nullable();

            // The compiler is a person like any other. Database foreign keys
            // may cross module boundaries; the PHP import may not.
            $table->foreignId('compiler_id')->nullable()
                ->constrained('muslims')->restrictOnDelete();

            // The total the SOURCE BOOK declares — a fact about the book, not a
            // count of our rows. Never recomputed. The number a reader can
            // actually open is computed separately as available_hadiths_count.
            $table->unsignedInteger('total_hadith')->nullable();

            $table->boolean('has_books');
            $table->boolean('has_chapters');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'sort_order', 'id']);
            $table->index(['is_published', 'is_featured', 'sort_order', 'id']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE hadith_collections ADD CONSTRAINT chk_collections_chapters_need_books '.
                'CHECK (has_chapters = 0 OR has_books = 1)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_collections');
    }
};
