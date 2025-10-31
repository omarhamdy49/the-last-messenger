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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100); // create, update, delete, login, logout, etc.
            $table->morphs('subject'); // subject_type, subject_id (the audited model)
            $table->string('description', 500)->nullable();
            $table->json('old_values')->nullable(); // Previous state
            $table->json('new_values')->nullable(); // New state
            $table->json('metadata')->nullable(); // Additional context
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('method', 10)->nullable(); // HTTP method: GET, POST, PUT, DELETE
            $table->timestamp('created_at')->useCurrent();

            // Indexes (morphs already creates index for subject_type + subject_id)
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
