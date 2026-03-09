<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hadith_sources')) {
            $columns = Schema::getColumnListing('hadith_sources');
            $columnsToRemove = array_intersect(['name', 'description'], $columns);
            if (!empty($columnsToRemove)) {
                Schema::table('hadith_sources', function (Blueprint $table) use ($columnsToRemove) {
                    $table->dropColumn($columnsToRemove);
                });
            }
        }

        if (Schema::hasTable('hadith_themes')) {
            $columns = Schema::getColumnListing('hadith_themes');
            $columnsToRemove = array_intersect(['name', 'description'], $columns);
            if (!empty($columnsToRemove)) {
                Schema::table('hadith_themes', function (Blueprint $table) use ($columnsToRemove) {
                    $table->dropColumn($columnsToRemove);
                });
            }
        }

        if (Schema::hasTable('hadith_chapters')) {
            $columns = Schema::getColumnListing('hadith_chapters');
            $columnsToRemove = array_intersect(['name', 'description'], $columns);
            if (!empty($columnsToRemove)) {
                Schema::table('hadith_chapters', function (Blueprint $table) use ($columnsToRemove) {
                    $table->dropColumn($columnsToRemove);
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hadith_sources')) {
            Schema::table('hadith_sources', function (Blueprint $table) {
                $table->json('name')->nullable();
                $table->json('description')->nullable();
            });
        }

        if (Schema::hasTable('hadith_themes')) {
            Schema::table('hadith_themes', function (Blueprint $table) {
                $table->json('name')->nullable();
                $table->json('description')->nullable();
            });
        }

        if (Schema::hasTable('hadith_chapters')) {
            Schema::table('hadith_chapters', function (Blueprint $table) {
                $table->json('name')->nullable();
                $table->json('description')->nullable();
            });
        }
    }
};
