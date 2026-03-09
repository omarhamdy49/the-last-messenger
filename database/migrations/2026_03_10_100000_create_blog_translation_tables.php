<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // series_translations table
        Schema::create('series_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained('series')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->longText('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();
            $table->unique(['series_id', 'locale']);
            $table->index('locale');
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['title', 'description']);
            }
        });

        // categories_translations table
        Schema::create('categories_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name');
            $table->longText('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();
            $table->unique(['category_id', 'locale']);
            $table->index('locale');
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['name', 'description']);
            }
        });

        // tags_translations table
        Schema::create('tags_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name');
            $table->longText('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();
            $table->unique(['tag_id', 'locale']);
            $table->index('locale');
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['name', 'description']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags_translations');
        Schema::dropIfExists('categories_translations');
        Schema::dropIfExists('series_translations');
    }
};
