<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename plural table names to singular (correct Eloquent naming convention)
        if (Schema::hasTable('categories_translations')) {
            Schema::rename('categories_translations', 'category_translations');
        }

        if (Schema::hasTable('tags_translations')) {
            Schema::rename('tags_translations', 'tag_translations');
        }
    }

    public function down(): void
    {
        // Reverse the renames
        if (Schema::hasTable('category_translations')) {
            Schema::rename('category_translations', 'categories_translations');
        }

        if (Schema::hasTable('tag_translations')) {
            Schema::rename('tag_translations', 'tags_translations');
        }
    }
};
