<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Blog\Repositories\CategoryRepository;
use App\Domain\Blog\Repositories\CategoryRepositoryInterface;
use App\Domain\Blog\Repositories\SeriesRepository;
use App\Domain\Blog\Repositories\SeriesRepositoryInterface;
use App\Domain\Blog\Repositories\TagRepository;
use App\Domain\Blog\Repositories\TagRepositoryInterface;
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
            CategoryRepositoryInterface::class,
            CategoryRepository::class
        );
        $this->app->bind(
            SeriesRepositoryInterface::class,
            SeriesRepository::class
        );
        $this->app->bind(
            TagRepositoryInterface::class,
            TagRepository::class
        );
    }
}
