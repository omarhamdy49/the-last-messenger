<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Folds four sparse concerns into one table: significance (11.7% fill), applications (5.6%),
     * commentary (5.5%) and fiqh rulings. Four separate tables plus four model/repository/DTO sets
     * is not justified for under 25% combined fill.
     */
    public function up(): void
    {
        Schema::create('hadith_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();

            $table->enum('type', ['significance', 'application', 'commentary', 'fiqh_ruling']);
            $table->enum('locale', ['ar', 'en', 'fr', 'tr', 'ur']);

            $table->foreignId('scholar_id')->nullable()->constrained('hadith_scholars')->restrictOnDelete();

            $table->string('title')->nullable();
            $table->longText('body');
            $table->unsignedInteger('sort_order')->default(0);

            $table->string('source_reference')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->timestamps();

            $table->index(['hadith_id', 'type', 'locale', 'sort_order'], 'idx_hadith_notes_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_notes');
    }
};
