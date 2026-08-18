<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-locale names for a location.
 *
 * Mirrors `category_translations` exactly so App\Domain\Shared\Traits\HasLocalizedContent
 * works without special casing. NOT a Spatie JSON column: the 2026-03-10 migrations
 * dropped those from categories, tags, series and the Seerah tables, and every
 * comparable model in the repo now uses this shape. See the Complexity Tracking
 * entry in specs/003-locations-module/plan.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['location_id', 'locale']);
            $table->index(['locale', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_translations');
    }
};
