<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_recording_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recording_id')->constrained('quran_reciters')->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('kind', 10);
            $table->string('source_key', 100);
            $table->string('external_reciter_id', 100)->nullable()->index();
            $table->string('external_recording_id', 100)->nullable();
            $table->string('external_timing_id', 100)->nullable();
            $table->string('base_url', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->unique(['recording_id', 'provider', 'kind', 'source_key'], 'quran_source_identity_unique');
            $table->index(['recording_id', 'kind', 'is_active', 'priority'], 'quran_source_selection_index');
        });
        Schema::table('quran_recitations', function (Blueprint $table): void {
            $table->foreignId('source_id')->nullable()->constrained('quran_recording_sources')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quran_recitations', fn (Blueprint $table) => $table->dropConstrainedForeignId('source_id'));
        Schema::dropIfExists('quran_recording_sources');
    }
};
