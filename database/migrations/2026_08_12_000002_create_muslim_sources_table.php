<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muslim_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('muslim_id')->constrained('muslims')->cascadeOnDelete();
            $table->string('source', 100);
            $table->string('external_id', 100);
            $table->string('source_url', 500);
            $table->char('dataset_hash', 64);
            $table->char('source_snapshot_hash', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // This is the importer's idempotency boundary: an upstream row
            // can map to only one local person, even when the job is rerun.
            $table->unique(['source', 'external_id']);
            $table->index(['muslim_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muslim_sources');
    }
};
