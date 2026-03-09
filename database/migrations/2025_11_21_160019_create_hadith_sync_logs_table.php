<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hadith_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('collection_name', 255)->comment('Collection name being synced');
            $table->string('type', 50)->default('json_import')->comment('Sync type (json_import, api_sync, etc.)');
            $table->string('status', 50)->default('pending')->comment('Sync status (pending, in_progress, completed, failed)');
            $table->json('metadata')->nullable()->comment('Additional metadata (file path, options, etc.)');
            $table->json('summary')->nullable()->comment('Import summary (counts, errors, etc.)');
            $table->text('error_message')->nullable()->comment('Error message if failed');
            $table->timestamp('started_at')->nullable()->comment('Sync start time');
            $table->timestamp('completed_at')->nullable()->comment('Sync completion time');
            $table->timestamps();

            // Indexes
            $table->index('collection_name');
            $table->index('type');
            $table->index('status');
            $table->index('started_at');
        });

        if (config('database.default') === 'mysql') {
            // Set charset and collation
            DB::statement('ALTER TABLE hadith_sync_logs ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_sync_logs');
    }
};
