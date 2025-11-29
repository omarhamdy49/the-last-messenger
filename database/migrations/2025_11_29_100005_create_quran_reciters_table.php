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
            $table->json('name'); // Arabic name
            $table->json('description')->nullable(); // Arabic description
            $table->string('style')->nullable(); // Recitation style (murattal, mujawwad, etc.)
            $table->string('country')->nullable(); // Country of origin
            $table->string('profile_image')->nullable(); // Profile image path
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);

            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('is_featured');
            $table->index('display_order');
            $table->index('country');
            $table->index('style');
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
