<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_import_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('collection_code', 50);
            $table->string('source_path', 500)->nullable();
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');

            $table->json('counts')->nullable();

            // Every raw grade string the normaliser could not map, so the rule set can be improved
            // without re-reading 51k records.
            $table->json('unmapped_grades')->nullable();

            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['collection_code', 'status'], 'idx_hadith_import_runs_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_import_runs');
    }
};
