<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inner timeline of a Seerah event (the 4–6 expandable sub-points shown on the
 * event single pages). Content is localized through a dedicated translations
 * table, following the seerah_event_translations pattern.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seerah_event_timeline_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seerah_event_id')
                ->constrained('seerah_events')
                ->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['seerah_event_id', 'display_order']);
        });

        Schema::create('seerah_event_timeline_item_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seerah_event_timeline_item_id')
                ->constrained('seerah_event_timeline_items', indexName: 'seerah_tl_item_translations_item_id_fk')
                ->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('title', 500)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(
                ['seerah_event_timeline_item_id', 'locale'],
                'seerah_tl_item_translations_item_locale_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seerah_event_timeline_item_translations');
        Schema::dropIfExists('seerah_event_timeline_items');
    }
};
