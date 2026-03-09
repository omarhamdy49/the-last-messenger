<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_source_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_source_id')->constrained('hadith_sources')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name')->nullable();
            $table->longText('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->unique(['hadith_source_id', 'locale']);
            $table->index('locale');
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['name', 'description']);
            }
        });

        Schema::create('hadith_theme_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_theme_id')->constrained('hadith_themes')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name')->nullable();
            $table->longText('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->unique(['hadith_theme_id', 'locale']);
            $table->index('locale');
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['name', 'description']);
            }
        });

        Schema::create('hadith_chapter_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_chapter_id')->constrained('hadith_chapters')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name')->nullable();
            $table->longText('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();
            $table->unique(['hadith_chapter_id', 'locale']);
            $table->index('locale');
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['name', 'description']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_chapter_translations');
        Schema::dropIfExists('hadith_theme_translations');
        Schema::dropIfExists('hadith_source_translations');
    }
};
