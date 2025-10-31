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
        Schema::create('content_versions', function (Blueprint $table) {
            $table->id();
            $table->morphs('versionable'); // versionable_type, versionable_id
            $table->integer('version_number')->default(1);
            $table->enum('change_type', ['created', 'updated', 'published', 'unpublished', 'deleted', 'restored'])->default('updated');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_summary', 500)->nullable();
            $table->json('diff')->nullable(); // JSON diff of changes
            $table->json('snapshot')->nullable(); // Full snapshot of content at this version
            $table->json('metadata')->nullable(); // Additional metadata (tags, categories, etc.)
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Indexes (morphs already creates index for versionable_type + versionable_id)
            $table->index('version_number');
            $table->index('change_type');
            $table->index('changed_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_versions');
    }
};
