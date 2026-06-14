<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Progress ledger for queued playlist imports. The web layer polls a row
 * here while SyncYouTubePlaylistJob advances it in the background.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('youtube_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained('media_programs')->cascadeOnDelete();
            $table->string('playlist_url', 1000);
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->string('message', 500)->nullable();
            $table->text('error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('youtube_sync_runs');
    }
};
