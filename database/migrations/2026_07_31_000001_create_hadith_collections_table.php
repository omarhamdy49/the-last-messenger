<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §1.
 *
 * No `total_hadiths` counter: 17 rows, so `withCount` is cheaper than a column
 * that drifts the moment admin CRUD exists. No composite index either — at 17
 * rows a full scan beats every index (Constitution II: no speculative indexes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_collections', function (Blueprint $table): void {
            $table->id();
            // Also the URL and API path segment, and the sunnah.com path segment.
            $table->string('code', 50)->unique();
            $table->json('name');
            $table->json('author')->nullable();
            $table->json('short_intro')->nullable();
            $table->json('about')->nullable();
            $table->boolean('has_books')->default(true);
            $table->boolean('has_chapters')->default(true);
            $table->boolean('has_volumes')->default(false);
            // e.g. 'Sahih al-Bukhari {hadithNumber}'
            $table->string('reference_template', 255)->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_collections');
    }
};
