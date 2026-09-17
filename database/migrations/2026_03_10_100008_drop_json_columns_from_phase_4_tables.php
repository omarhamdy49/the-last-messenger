<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop from people table
        if (Schema::hasTable('people')) {
            $columns = Schema::getColumnListing('people');
            $columnsToRemove = array_intersect(['name', 'title', 'biography', 'birth_place', 'death_place', 'burial_place', 'occupation', 'notes'], $columns);
            if (! empty($columnsToRemove)) {
                Schema::table('people', function (Blueprint $table) use ($columnsToRemove) {
                    $table->dropColumn($columnsToRemove);
                });
            }
        }

        // Drop from quran_reciters table
        if (Schema::hasTable('quran_reciters')) {
            $columns = Schema::getColumnListing('quran_reciters');
            $columnsToRemove = array_intersect(['name', 'description'], $columns);
            if (! empty($columnsToRemove)) {
                Schema::table('quran_reciters', function (Blueprint $table) use ($columnsToRemove) {
                    $table->dropColumn($columnsToRemove);
                });
            }
        }

        // Drop from languages table
        if (Schema::hasTable('languages')) {
            $columns = Schema::getColumnListing('languages');
            $columnsToRemove = array_intersect(['name'], $columns);
            if (! empty($columnsToRemove)) {
                Schema::table('languages', function (Blueprint $table) use ($columnsToRemove) {
                    $table->dropColumn($columnsToRemove);
                });
            }
        }
    }

    public function down(): void
    {
        // Restore JSON columns for people table
        if (Schema::hasTable('people')) {
            $columns = Schema::getColumnListing('people');
            Schema::table('people', function (Blueprint $table) use ($columns) {
                if (! in_array('name', $columns)) {
                    $table->json('name')->nullable();
                }
                if (! in_array('title', $columns)) {
                    $table->json('title')->nullable();
                }
                if (! in_array('biography', $columns)) {
                    $table->json('biography')->nullable();
                }
                if (! in_array('birth_place', $columns)) {
                    $table->json('birth_place')->nullable();
                }
                if (! in_array('death_place', $columns)) {
                    $table->json('death_place')->nullable();
                }
                if (! in_array('burial_place', $columns)) {
                    $table->json('burial_place')->nullable();
                }
                if (! in_array('occupation', $columns)) {
                    $table->json('occupation')->nullable();
                }
                if (! in_array('notes', $columns)) {
                    $table->json('notes')->nullable();
                }
            });
        }

        // Restore JSON columns for quran_reciters table
        if (Schema::hasTable('quran_reciters')) {
            $columns = Schema::getColumnListing('quran_reciters');
            Schema::table('quran_reciters', function (Blueprint $table) use ($columns) {
                if (! in_array('name', $columns)) {
                    $table->json('name')->nullable();
                }
                if (! in_array('description', $columns)) {
                    $table->json('description')->nullable();
                }
            });
        }

        // Restore JSON columns for languages table
        if (Schema::hasTable('languages')) {
            $columns = Schema::getColumnListing('languages');
            Schema::table('languages', function (Blueprint $table) use ($columns) {
                if (! in_array('name', $columns)) {
                    $table->json('name')->nullable();
                }
            });
        }
    }
};
