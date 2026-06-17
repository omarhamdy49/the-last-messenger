<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * quran_words holds the printed-mushaf word layout (which word sits on which
     * line/page, and in what order). The public reader uses it to reproduce
     * mushaf line breaks and page order with the site's own Uthmani font.
     * We store real Unicode text (text_uthmani), NOT glyph-coded font data.
     */
    public function up(): void
    {
        Schema::create('quran_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verse_id')->constrained('quran_verses')->cascadeOnDelete();
            $table->unsignedTinyInteger('surah_number');   // 1..114 (denormalised for the render query)
            $table->unsignedSmallInteger('verse_number');  // ayah number within the surah
            $table->unsignedSmallInteger('position');      // word order within the verse
            $table->string('location', 16)->unique();      // "surah:verse:position", e.g. "2:255:3"
            $table->text('text_uthmani');                  // real Unicode Uthmani text (rendered in our font)
            $table->string('char_type', 16)->default('word'); // word | end | pause | sajdah | rub-el-hizb
            $table->unsignedTinyInteger('line_number');    // mushaf line within the page (1..15)
            $table->unsignedSmallInteger('page_number');   // mushaf page (1..604)
            $table->timestamps();

            // Surah render path: a surah's words in printed-mushaf order. Verses share
            // a line, so order is page → line → verse → word position.
            $table->index(['surah_number', 'page_number', 'line_number', 'verse_number', 'position'], 'qw_render_idx');
            // Page render path (page/juz/hizb readers): a page's words in line order.
            $table->index(['page_number', 'line_number', 'verse_number', 'position'], 'qw_page_idx');
            $table->index('verse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_words');
    }
};
