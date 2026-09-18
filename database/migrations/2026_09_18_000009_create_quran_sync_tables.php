<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 006-quran-qf-refactor — QF Content Sync bookkeeping.
 *
 * quran_sync_states: one row per canonical resources filter; QF requires the same
 * filter for a given sync token, so the token is stored against the filter string.
 * quran_sync_runs: an auditable ledger of every bootstrap/incremental/snapshot/fetch
 * run with reconciled counts and itemised errors.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_sync_states', function (Blueprint $table): void {
            $table->id();
            // Both grow with the number of enabled resources; uniqueness is on
            // filter_hash, so neither needs a length (see 2026_09_18_000012).
            $table->text('resources_filter');
            $table->char('filter_hash', 64)->unique();
            $table->text('sync_token')->nullable();
            $table->unsignedBigInteger('last_sequence')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->string('status', 12)->default('idle');
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('quran_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('mode', 12);
            $table->text('resources_filter')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('status', 12)->default('running');
            $table->unsignedInteger('mutations_applied')->default(0);
            $table->unsignedInteger('snapshots_fetched')->default(0);
            $table->unsignedInteger('rows_upserted')->default(0);
            $table->unsignedInteger('rows_deleted')->default(0);
            $table->unsignedInteger('rows_skipped_locked')->default(0);
            $table->json('errors')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_sync_runs');
        Schema::dropIfExists('quran_sync_states');
    }
};
