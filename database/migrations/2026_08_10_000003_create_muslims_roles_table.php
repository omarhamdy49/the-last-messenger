<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §3 — which capacities a person held.
 *
 * Composite primary key, so a person holds a given role exactly once. The
 * featured flag and sort order live here rather than on `muslims` because they
 * are per-role: someone can be a featured companion and an unremarkable
 * narrator at the same time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muslims_roles', function (Blueprint $table): void {
            $table->foreignId('muslim_id')->constrained('muslims')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('islamic_roles')->restrictOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->primary(['muslim_id', 'role_id']);

            // The role-directory query: filter by role, optionally featured,
            // ordered — leading column matches the WHERE clause and the
            // trailing muslim_id keeps it covering.
            $table->index(['role_id', 'is_featured', 'sort_order', 'muslim_id'], 'muslims_roles_directory_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muslims_roles');
    }
};
