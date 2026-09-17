<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A Seerah event's gallery and attached media — the same shape as
 * `articles_gallery` and `articles_media`. Dated here because both reference
 * `media`, which is created on 2025_11_02.
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
    }

    public function down(): void
    {
        Schema::dropIfExists('seerah_events_media');
        Schema::dropIfExists('seerah_events_gallery');
    }
};
