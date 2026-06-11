<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-category visibility flags for the public programs list.
 *
 * `key` mirrors the ProgramCategory enum values; admins toggle `is_featured`
 * to control which category rows (and filter pills) load on /media.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_categories', function (Blueprint $table) {
            $table->id();
            $table->string('key', 30)->unique();
            $table->boolean('is_featured')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['is_featured', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_categories');
    }
};
