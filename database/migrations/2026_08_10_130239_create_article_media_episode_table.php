<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Articles reference existing media episodes rather than carrying their own
 * video links.
 *
 * An episode is already a first-class record with a program, a season and
 * episode number, translations and a YouTube `video_url`. Storing loose URLs on
 * the article duplicated all of that and let the same lecture exist twice under
 * two different titles, with no way to find every article that cites it.
 *
 * `position` keeps the editor's ordering: the reader plays the queue in the
 * order the article intends, not in episode-number order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_media_episode', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('article_id')
                ->constrained('articles')
                ->cascadeOnDelete();

            $table->foreignId('media_episode_id')
                ->constrained('media_episodes')
                ->cascadeOnDelete();

            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            // One article cites an episode once; the pair is the natural key.
            $table->unique(['article_id', 'media_episode_id']);

            // Reading an article's queue is the hot path.
            $table->index(['article_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_media_episode');
    }
};
