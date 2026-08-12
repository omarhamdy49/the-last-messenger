<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Progress ledger for Artisan commands launched from the admin settings panel.
 *
 * A queued run is advanced by RunArtisanCommandJob while the settings modal
 * polls this row; an inline run writes the same row so history and output read
 * identically for both.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('command_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('command', 150)->index();
            $table->json('input')->nullable();
            $table->string('status', 20)->default('queued')->index();
            $table->boolean('is_queued')->default(false);
            $table->unsignedInteger('progress_current')->default(0);
            $table->unsignedInteger('progress_total')->default(0);
            $table->string('message', 500)->nullable();
            $table->longText('output')->nullable();
            $table->smallInteger('exit_code')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('command_runs');
    }
};
