<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topic_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')
                ->constrained('topics')
                ->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('title', 500)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['topic_id', 'locale']);
        });

        // FULLTEXT for title + description (MySQL-only; guarded for the test driver).
        if (config('database.default') === 'mysql') {
            DB::statement(
                'ALTER TABLE topic_translations ADD FULLTEXT fulltext_topic_title_desc (title, description)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_translations');
    }
};
