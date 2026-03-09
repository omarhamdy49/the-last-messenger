<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop JSON columns from series
        Schema::table('series', function (Blueprint $table) {
            $table->dropColumn(['title', 'description']);
        });

        // Drop JSON columns from categories
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['name', 'description']);
        });

        // Drop JSON columns from tags
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn(['name', 'description']);
        });
    }

    public function down(): void
    {
        // Re-add JSON columns to series
        Schema::table('series', function (Blueprint $table) {
            $table->json('title');
            $table->json('description')->nullable();
        });

        // Re-add JSON columns to categories
        Schema::table('categories', function (Blueprint $table) {
            $table->json('name');
            $table->json('description')->nullable();
        });

        // Re-add JSON columns to tags
        Schema::table('tags', function (Blueprint $table) {
            $table->json('name');
            $table->json('description')->nullable();
        });
    }
};
