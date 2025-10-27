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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name'); // Multi-language support
            $table->string('type')->default('general'); // blog, hadith, media, etc.
            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('type');
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->morphs('taggable'); // Polymorphic relation (automatically creates index)
            $table->timestamps();

            // Unique constraint for tag-model combination
            $table->unique(['tag_id', 'taggable_id', 'taggable_type'], 'taggables_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
    }
};

