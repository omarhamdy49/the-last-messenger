<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Blog\Services\ArticlePublicVisibility;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use App\Models\Article;
use Illuminate\Support\ServiceProvider;

class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ArticlePublicVisibility::class,
            fn () => new ArticlePublicVisibility(Article::class),
        );
        $this->app->tag(ArticlePublicVisibility::class, PublicVisibilityProviderInterface::class);

        $this->app->bind(
            \App\Domain\Blog\Repositories\CategoryRepositoryInterface::class,
            \App\Domain\Blog\Repositories\CategoryRepository::class
        );
        $this->app->bind(
            \App\Domain\Blog\Repositories\SeriesRepositoryInterface::class,
            \App\Domain\Blog\Repositories\SeriesRepository::class
        );
        $this->app->bind(
            \App\Domain\Blog\Repositories\TagRepositoryInterface::class,
            \App\Domain\Blog\Repositories\TagRepository::class
        );
    }
}
