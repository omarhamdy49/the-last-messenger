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
        Schema::create('content_schedules', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship to schedulable content
            $table->string('schedulable_type'); // Model class name
            $table->unsignedBigInteger('schedulable_id'); // Model ID
            $table->index(['schedulable_type', 'schedulable_id'], 'schedulable_index');

            // Schedule details
            $table->timestamp('scheduled_at'); // When to execute
            $table->string('action')->default('publish'); // publish, unpublish, update
            $table->string('status')->default('pending'); // pending, processing, published, failed, cancelled
            $table->string('timezone')->default('UTC');

            // Recurring schedule settings (nullable - for one-time schedules)
            $table->string('recurrence_type')->nullable(); // daily, weekly, monthly, yearly
            $table->integer('recurrence_interval')->nullable()->default(1); // Every X days/weeks/etc
            $table->timestamp('recurrence_ends_at')->nullable(); // When recurring stops
            $table->text('recurrence_days')->nullable(); // JSON array of days for weekly (e.g., [1,3,5])

            // Update data (for action = 'update')
            $table->json('scheduled_data')->nullable(); // Data to update when scheduled

            // Execution tracking
            $table->timestamp('executed_at')->nullable(); // When actually executed
            $table->text('error_message')->nullable(); // Error details if failed
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();

            // Metadata
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable(); // Admin notes about this schedule

            // Indexes for performance
            $table->index('scheduled_at');
            $table->index('status');
            $table->index('action');
            $table->index('created_by');

            $table->timestamps();
            $table->softDeletes(); // Allow soft delete for history
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_schedules');
    }
};
