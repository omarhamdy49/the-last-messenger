<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Seerah module: milestones (الحِقَب), event types, events, and their
 * translations — created in foreign-key order, in their final shape.
 *
 * `seerah_events.location_id` is added by 2026_08_18_000003, because `locations`
 * does not exist yet at this point. The gallery, media and cross-module link
 * tables follow in 2026_08_18_000004, once every table they reference exists.
 *
 * Status and era are plain strings: their allowed values are owned by the
 * SeerahEventStatus and EventEra enums, not by the database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seerah_milestones', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->integer('order')->default(0);
            $table->json('metadata')->nullable(); // {icon, color, gradient}
            $table->smallInteger('hijri_from')->nullable(); // negative = before the Hijra
            $table->smallInteger('hijri_to')->nullable();
            $table->smallInteger('gregorian_from')->nullable();
            $table->smallInteger('gregorian_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'order'], 'sm_active_order_idx');
        });

        Schema::create('seerah_milestone_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sm_id')->constrained('seerah_milestones')->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('title', 500)->nullable();
            $table->string('subtitle', 500)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['sm_id', 'locale'], 'smt_sm_locale_uq');
        });

        Schema::create('seerah_event_types', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->json('metadata')->nullable(); // {icon, color, gradient}
            $table->json('locale');               // {ar, en, fr, tr, ur} => display name
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('seerah_events', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('event_type')->constrained('seerah_event_types')->restrictOnDelete();
            $table->foreignId('sm_id')->nullable()->constrained('seerah_milestones')->nullOnDelete();
            $table->integer('order')->default(0);
            $table->string('thumb_url')->nullable();
            $table->string('cover_url')->nullable();
            $table->smallInteger('hijri_year')->nullable();
            $table->unsignedTinyInteger('hijri_month')->nullable();
            $table->unsignedTinyInteger('hijri_day')->nullable();
            $table->date('gregorian_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('era', 20)->nullable();
            $table->json('statistics')->nullable();
            $table->timestamps();

            $table->index(['sm_id', 'status', 'order'], 'se_sm_status_order_idx');
            $table->index(['status', 'era'], 'se_status_era_idx');
        });

        Schema::create('seerah_event_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('seerah_events')->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('title', 500)->nullable();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->text('dispute_note')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->json('metadata')->nullable();       // [{key, value}]
            $table->json('sources')->nullable();        // [{title, author, reference}]
            $table->json('seo_metadata')->nullable();   // {title, description} — same shape as article_locale
            $table->json('external_links')->nullable(); // [{title, url}]
            $table->timestamps();

            $table->unique(['event_id', 'locale'], 'set_event_locale_uq');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE seerah_event_translations ADD FULLTEXT fulltext_title_description (title, description)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seerah_event_translations');
        Schema::dropIfExists('seerah_events');
        Schema::dropIfExists('seerah_event_types');
        Schema::dropIfExists('seerah_milestone_translations');
        Schema::dropIfExists('seerah_milestones');
    }
};
