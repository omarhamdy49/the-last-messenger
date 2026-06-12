<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create per-companion personal timeline tables.
 *
 * The companion biography page shows a personal timeline of expandable
 * entries with hijri dates and color-coded dots. Structural fields live on
 * sahabi_timeline_items; localized title/description live in a per-locale
 * translation table, following the sahabi_translations pattern.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sahabi_timeline_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sahabi_id')
                ->constrained('sahaba')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('hijri_date', 100)->nullable(); // Display date, e.g. "شوال 3 هـ"
            $table->smallInteger('hijri_year')->nullable(); // Signed: negative = years before Hijra
            $table->string('color', 30)->nullable(); // Color token for the timeline dot
            $table->timestamps();

            $table->unique(['sahabi_id', 'position']);
        });

        Schema::create('sahabi_timeline_item_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sahabi_timeline_item_id')
                ->constrained('sahabi_timeline_items', indexName: 'stit_item_fk')
                ->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['sahabi_timeline_item_id', 'locale'], 'stit_item_locale_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sahabi_timeline_item_translations');
        Schema::dropIfExists('sahabi_timeline_items');
    }
};
