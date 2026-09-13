<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_playlists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 80);
            // Random, never derived from the name: the slug is the share link.
            $table->char('slug', 12)->unique();
            $table->string('description', 300)->nullable();
            $table->enum('scope', ['full', 'custom'])->default('full');
            $table->boolean('is_public')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('plays_count')->default(0);
            $table->unsignedSmallInteger('items_count')->default(0);
            $table->timestamps();
            $table->index(['is_public', 'is_featured', 'plays_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_playlists');
    }
};
