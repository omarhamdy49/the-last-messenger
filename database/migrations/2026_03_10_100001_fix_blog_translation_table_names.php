<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Handle category_translations table
        if (!Schema::hasTable('category_translations') && !Schema::hasTable('categories_translations')) {
            // Create with correct singular name
            Schema::create('category_translations', function (Blueprint $table) {
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
        } else if (Schema::hasTable('categories_translations') && !Schema::hasTable('category_translations')) {
            // Rename from plural to singular
            Schema::rename('categories_translations', 'category_translations');
        }

        // Handle tag_translations table
        if (!Schema::hasTable('tag_translations') && !Schema::hasTable('tags_translations')) {
            // Create with correct singular name
            Schema::create('tag_translations', function (Blueprint $table) {
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
        } else if (Schema::hasTable('tags_translations') && !Schema::hasTable('tag_translations')) {
            // Rename from plural to singular
            Schema::rename('tags_translations', 'tag_translations');
        }
    }

    public function down(): void
    {
        // Only drop if both exist, to be safe
        if (Schema::hasTable('category_translations')) {
            Schema::dropIfExists('category_translations');
        }
        if (Schema::hasTable('tag_translations')) {
            Schema::dropIfExists('tag_translations');
        }
    }
};
