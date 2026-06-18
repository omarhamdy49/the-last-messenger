<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seerah milestones (الحِقَب) — the high-level chronological eras of the Prophetic
 * biography. Each milestone groups multiple Seerah events and carries a Hijri +
 * Gregorian date range plus a narrative "story" title for the public hero-journey
 * timeline. Translations live in a dedicated per-locale table (HasLocalizedContent).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seerah_milestones', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->integer('order_index')->default(0);

            // Visual identity for the public timeline
            $table->string('icon', 50)->nullable();
            $table->string('color', 30)->nullable();
            $table->string('gradient', 60)->nullable();

            // Hijri range — human label (e.g. "53 ق.هـ") + numeric year for sorting (negative = ق.هـ)
            $table->string('hijri_label_from', 100)->nullable();
            $table->string('hijri_label_to', 100)->nullable();
            $table->integer('hijri_year_from')->nullable();
            $table->integer('hijri_year_to')->nullable();

            // Gregorian range (numeric years)
            $table->integer('gregorian_year_from')->nullable();
            $table->integer('gregorian_year_to')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('order_index');
            $table->index('is_active');
            $table->index('hijri_year_from');
        });

        Schema::create('seerah_milestone_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seerah_milestone_id')
                ->constrained('seerah_milestones')
                ->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('title', 500)->nullable();      // العنوان القصصي
            $table->string('subtitle', 500)->nullable();   // tagline
            $table->text('description')->nullable();        // intro / overview
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            // Explicit short name — the default would exceed MySQL's 64-char limit.
            $table->unique(['seerah_milestone_id', 'locale'], 'sm_translations_milestone_locale_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seerah_milestone_translations');
        Schema::dropIfExists('seerah_milestones');
    }
};
