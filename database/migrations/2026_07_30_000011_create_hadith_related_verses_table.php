<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kept separate from hadith_notes: this is a typed FK relation to another module, not prose.
     * As with hadith_scholars.sahaba_id, the FK is a database concern — PHP in Domain/Hadith must
     * not import Domain/Quran. Resolve verses through a Shared contract.
     */
    public function up(): void
    {
        Schema::create('hadith_related_verses', function (Blueprint $table): void {
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->foreignId('quran_verse_id')->constrained('quran_verses')->restrictOnDelete();
            $table->json('relevance')->nullable();

            $table->primary(['hadith_id', 'quran_verse_id']);
            $table->index(['quran_verse_id', 'hadith_id'], 'idx_hadith_verses_reverse');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_related_verses');
    }
};
