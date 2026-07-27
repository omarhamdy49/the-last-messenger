<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop sahaba columns confirmed unpopulated across all 10,314 live rows and
 * never wired into any admin form or request:
 *
 * - profile_image / gallery: real images live in the Spatie MediaLibrary
 *   `profile_image`/`gallery` collections; these scalar/JSON columns were
 *   pre-MediaLibrary scaffolding SahabiResource was still (incorrectly)
 *   reading from.
 * - related_article_id: scaffolded on table creation, never validated,
 *   never settable from any admin form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sahaba', function (Blueprint $table): void {
            $table->dropForeign(['related_article_id']);
            $table->dropColumn(['profile_image', 'gallery', 'related_article_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sahaba', function (Blueprint $table): void {
            $table->string('profile_image')->nullable();
            $table->json('gallery')->nullable();
            $table->foreignId('related_article_id')->nullable()->constrained('articles')->nullOnDelete();
        });
    }
};
