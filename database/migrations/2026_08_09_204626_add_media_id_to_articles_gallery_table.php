<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The article gallery is populated from the media library, but the row only
 * stored the derived public path. That made the edit round-trip lossless in one
 * direction only: the form re-posted ArticleGallery ids, which were then
 * validated against `media` — a table they have nothing to do with.
 *
 * Recording the originating media id keeps the picker authoritative and lets
 * create and update share one code path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles_gallery', function (Blueprint $table): void {
            $table->foreignId('media_id')
                ->nullable()
                ->after('article_id')
                ->constrained('media')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('articles_gallery', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('media_id');
        });
    }
};
