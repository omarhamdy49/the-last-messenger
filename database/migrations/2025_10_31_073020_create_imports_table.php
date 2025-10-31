<?php

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
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('module', 50)->index(); // articles, hadith, media_programs, etc.
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type', 20); // csv, xlsx, xls
            $table->integer('file_size')->unsigned(); // in bytes
            $table->integer('total_rows')->unsigned()->default(0);
            $table->integer('success_count')->unsigned()->default(0);
            $table->integer('error_count')->unsigned()->default(0);
            $table->integer('skipped_count')->unsigned()->default(0);
            $table->enum('status', ['pending', 'parsing', 'validating', 'processing', 'completed', 'failed', 'cancelled'])->default('pending')->index();
            $table->json('result_data')->nullable(); // summary stats, processing time, etc.
            $table->json('options')->nullable(); // has_header, skip_duplicates, update_existing, dry_run
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('processing_time_seconds')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['module', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
