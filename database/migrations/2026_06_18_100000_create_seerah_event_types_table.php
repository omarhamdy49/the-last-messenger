<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-driven taxonomy for Seerah event types (replaces the static EventType enum).
 * Editors manage the multilingual name, lucide icon, colour token, public-timeline
 * gradient, active flag and display order from the admin portal.
 *
 * Translations live in a dedicated per-locale table (HasLocalizedContent pattern),
 * consistent with every other Domain model in this codebase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seerah_event_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('icon', 50)->nullable();     // lucide icon name
            $table->string('color', 30)->nullable();    // UI colour token
            $table->string('gradient', 60)->nullable(); // tailwind gradient for public timeline
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('display_order');
        });

        Schema::create('seerah_event_type_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seerah_event_type_id')
                ->constrained('seerah_event_types')
                ->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name', 255)->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            // Explicit short name — the default would exceed MySQL's 64-char limit.
            $table->unique(['seerah_event_type_id', 'locale'], 'set_translations_type_locale_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seerah_event_type_translations');
        Schema::dropIfExists('seerah_event_types');
    }
};
