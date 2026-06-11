<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_program_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_program_id')
                ->constrained('media_programs')
                ->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('title', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('host', 255)->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['media_program_id', 'locale']);
        });

        // FULLTEXT for title + description (MySQL-only)
        if (config('database.default') === 'mysql') {
            DB::statement(
                'ALTER TABLE media_program_translations ADD FULLTEXT fulltext_program_title_desc (title, description)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_program_translations');
    }
};
