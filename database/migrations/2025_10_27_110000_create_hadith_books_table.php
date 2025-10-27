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
        Schema::create('hadith_books', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name'); // Multi-language: {"ar": "صحيح البخاري", "en": "Sahih al-Bukhari"}
            $table->json('description')->nullable();
            $table->string('author_name')->nullable();
            $table->integer('total_hadith_count')->default(0);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_books');
    }
};

