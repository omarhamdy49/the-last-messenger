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
        Schema::create('bulk_operations', function (Blueprint $table) {
            $table->id();
            $table->string('operation_type', 50); // update, delete, publish, etc.
            $table->string('module', 50); // articles, hadith, media, etc.
            $table->string('status', 20)->default('pending'); // pending, processing, completed, failed, cancelled
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->json('parameters')->nullable(); // Operation parameters (fields to update, category_id, etc.)
            $table->json('item_ids')->nullable(); // IDs of items to process
            $table->json('result_data')->nullable(); // Results of operation
            $table->json('error_log')->nullable(); // Error details
            $table->boolean('dry_run')->default(false); // Preview mode
            $table->boolean('notify_on_completion')->default(true);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('operation_type');
            $table->index('module');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_operations');
    }
};
