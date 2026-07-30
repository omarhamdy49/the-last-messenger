<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_themes', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('hadith_hadith_themes', function (Blueprint $table): void {
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->foreignId('theme_id')->constrained('hadith_themes')->restrictOnDelete();

            $table->primary(['hadith_id', 'theme_id']);
            $table->index(['theme_id', 'hadith_id'], 'idx_hadith_themes_reverse');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_hadith_themes');
        Schema::dropIfExists('hadith_themes');
    }
};
