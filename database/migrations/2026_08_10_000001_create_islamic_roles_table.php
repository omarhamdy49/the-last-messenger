<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §1 — the capacities a historical figure held.
 *
 * `person_type`, `is_sahabi`, and `is_companion` all collapse into rows here,
 * assigned through `muslims_roles`. A companion who narrated hadith and graded
 * them is ONE `muslims` row with three role assignments, not three records.
 *
 * Named `islamic_roles` per the owner's schema. Note it reads like a Spatie
 * Permission table but is not one: it carries no permissions and gates nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('islamic_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name_ar', 150);
            $table->string('name_en', 150);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('islamic_roles');
    }
};
