<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_collections', function (Blueprint $table): void {
            $table->id();

            // Mirrors sunnah.com's `collectionName`: the URL and API path segment.
            $table->string('code', 50)->unique();

            $table->json('name');
            $table->json('author')->nullable();
            $table->json('short_intro')->nullable();
            $table->json('about')->nullable();

            // Capability flags. sunnah.com stores these as varchar(3) holding 'yes'/'no'.
            $table->boolean('has_books')->default(true);
            $table->boolean('has_chapters')->default(true);
            $table->boolean('has_volumes')->default(false);

            // e.g. "Sahih al-Bukhari {hadithNumber}" — substituted by ReferenceBuilder.
            $table->string('reference_template')->nullable();

            $table->unsignedInteger('total_hadiths')->default(0);
            $table->boolean('is_published')->default(false);
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'featured', 'display_order'], 'idx_hadith_collections_public');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_collections');
    }
};
