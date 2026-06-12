<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-locale value for translatable site settings.
 * Mirrors the seerah_event_translations pattern (FK + locale unique).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_setting_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_setting_id')
                ->constrained('site_settings')
                ->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->longText('value')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['site_setting_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_setting_translations');
    }
};
