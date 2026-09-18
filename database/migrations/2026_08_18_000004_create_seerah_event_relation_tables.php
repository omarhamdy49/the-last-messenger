<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a Seerah event is attached to: its gallery and media (the same shape as
 * `articles_gallery` / `articles_media`) and its links to content in other
 * modules — verses, hadiths, companions, articles, media episodes, tags and
 * categories.
 *
 * Links are one table keyed by LinkedContentType rather than a pivot per target:
 * the event only ever holds ids, resolved through the Shared
 * ContentReferenceLookup, so Seerah never imports another module. The cost is
 * no database FK to the target — a deleted target leaves a row the lookup
 * silently skips.
 *
 * Dated after every table these reference (media, locations).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seerah_events_gallery', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('seerah_events')->cascadeOnDelete();
            $table->string('image'); // public-disk copy
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('caption')->nullable(); // {ar, en, fr, tr, ur}
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'order'], 'seg_event_order_idx');
        });

        Schema::create('seerah_events_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('seerah_events')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'media_id'], 'sem_event_media_uq');
        });

        Schema::create('seerah_event_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('seerah_events')->cascadeOnDelete();
            $table->string('type', 20); // LinkedContentType
            $table->unsignedBigInteger('target_id');
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['event_id', 'type', 'target_id'], 'sel_event_type_target_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seerah_event_links');
        Schema::dropIfExists('seerah_events_media');
        Schema::dropIfExists('seerah_events_gallery');
    }
};
