<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The shared canonical place vocabulary.
 *
 * WHY IT LIVES IN Shared: three domains consume it — Seerah, People and Quran —
 * and constitution IV forbids a domain module from importing a sibling. A
 * `Domain\Locations` would force all three through a lookup contract and cost
 * eager loading on the Seerah list queries. See specs/003-locations-module/research.md R-01.
 *
 * INDEXES DELIBERATELY OMITTED: no `(is_active, type)` composite. Constitution II
 * forbids shipping an index before EXPLAIN shows it used, and nothing in this
 * feature filters on both columns. The unique slug and the parent FK index are
 * the only ones with a proven reader.
 *
 * COLUMN DELIBERATELY OMITTED: `zoom`. The existing map embed in
 * SeerahController::mapEventDetail() computes a bounding box (lat ± 0.04), not a
 * zoom level, so a zoom column would ship with no reader — constitution IX.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();

            // varchar, not a MySQL ENUM, so adding a kind is a code change not a
            // migration. Backed by App\Domain\Shared\Enums\LocationType.
            $table->string('type', 20);

            $table->foreignId('parent_id')->nullable()
                ->constrained('locations')->nullOnDelete();

            // Derived from the parent chain on save, never set by hand.
            $table->unsignedTinyInteger('depth')->default(0);

            // Precision matches seerah_events.latitude/longitude exactly, so a
            // coordinate can move between the two without rounding.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Alternate spellings. A JSON column rather than a table because
            // matching runs in PHP over a cached ~55-row vocabulary — see research.md R-04.
            $table->json('aliases')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
