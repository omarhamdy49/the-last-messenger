<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hadith_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('collection_name')->nullable()->comment('Collection being synced');
            $table->enum('status', ['pending', 'in_progress', 'success', 'failed', 'partial'])->default('pending');
            $table->enum('sync_type', ['full', 'incremental', 'collection', 'book', 'chapter'])->comment('Type of sync operation');
            $table->unsignedBigInteger('total_records')->default(0);
            $table->unsignedBigInteger('synced_records')->default(0);
            $table->unsignedBigInteger('failed_records')->default(0);
            $table->unsignedBigInteger('skipped_records')->default(0);
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('duration_seconds')->nullable()->comment('Total duration in seconds');
            $table->string('triggered_by')->nullable()->comment('Command or user who triggered sync');
            $table->timestamps();

            // Indexes
            $table->index('collection_name');
            $table->index('status');
            $table->index('sync_type');
            $table->index(['status', 'created_at']);
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_sync_logs');
    }
};
