<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_channel_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_channel_id')
                ->constrained('live_channels')
                ->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('title', 500)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['live_channel_id', 'locale']);
        });

        // FULLTEXT for title + description (MySQL-only)
        if (config('database.default') === 'mysql') {
            DB::statement(
                'ALTER TABLE live_channel_translations ADD FULLTEXT fulltext_live_channel_title_desc (title, description)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('live_channel_translations');
    }
};
