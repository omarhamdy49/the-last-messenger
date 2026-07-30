<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §8. Replaces the old `hadith_sync_logs`.
 *
 * `unmapped_grades` records every raw string the normaliser could not interpret
 * (FR-007) and is surfaced in the admin, so the mapping rules can be improved
 * without re-reading 51k records.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_import_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('collection_code', 50);
            $table->string('source_path', 500)->nullable();
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            // Per-table row counts, expected vs actual.
            $table->json('counts')->nullable();
            $table->json('unmapped_grades')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['collection_code', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_import_runs');
    }
};
