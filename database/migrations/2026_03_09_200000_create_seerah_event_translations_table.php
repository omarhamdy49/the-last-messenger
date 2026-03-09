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
        Schema::create('seerah_event_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seerah_event_id')
                ->constrained('seerah_events')
                ->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('title', 500)->nullable();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['seerah_event_id', 'locale']);
        });

        // Add FULLTEXT index (MySQL-specific)
        if (config('database.default') === 'mysql') {
            \Illuminate\Support\Facades\DB::statement(
                'ALTER TABLE seerah_event_translations ADD FULLTEXT fulltext_title_description (title, description)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seerah_event_translations');
    }
};
