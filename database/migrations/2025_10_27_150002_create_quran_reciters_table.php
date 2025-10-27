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
        Schema::create('quran_reciters', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name'); // {"ar": "...", "en": "..."}
            $table->json('description')->nullable();
            $table->string('style')->nullable(); // Hafs, Warsh, etc.
            $table->string('country')->nullable();
            $table->string('profile_image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('is_featured');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_reciters');
    }
};

