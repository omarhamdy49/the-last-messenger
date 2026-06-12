<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Quran\Models\Verse;
use App\Domain\Topics\Enums\TopicItemType;
use App\Domain\Topics\Repositories\TopicRepository;
use App\Domain\Topics\Repositories\TopicRepositoryInterface;
use App\Models\Article;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the cross-module Topics feature.
 *
 * Like FavoriteServiceProvider, this is a Bootstrap-layer composition point: it
 * is the single place that knows the concrete content model classes a topic can
 * aggregate, and it binds the short morph aliases ("verse", "hadith", "article")
 * stored in topic_items.itemable_type to those classes via the morph map. The
 * Topics domain code itself never imports these classes — keeping it deptrac-safe.
 */
final class TopicsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TopicRepositoryInterface::class, TopicRepository::class);
    }

    public function boot(): void
    {
        // Merge (not replace) so any existing morph map entries are preserved.
        Relation::morphMap([
            TopicItemType::VERSE->value => Verse::class,
            TopicItemType::HADITH->value => Hadith::class,
            TopicItemType::ARTICLE->value => Article::class,
        ]);
    }
}
