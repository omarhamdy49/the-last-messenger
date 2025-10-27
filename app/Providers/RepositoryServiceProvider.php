<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Blog\Repositories\ArticleRepository;
use App\Domain\Blog\Repositories\ArticleRepositoryInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 * Binds repository interfaces to their implementations
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Blog Module Repositories
        $this->app->bind(ArticleRepositoryInterface::class, ArticleRepository::class);
        
        // Hadith Module Repositories
        $this->app->bind(
            \App\Domain\Hadith\Repositories\HadithRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\HadithRepository::class
        );
        
        // Media Module Repositories
        $this->app->bind(
            \App\Domain\Media\Repositories\MediaProgramRepositoryInterface::class,
            \App\Domain\Media\Repositories\MediaProgramRepository::class
        );
        
        // Seerah Module Repositories
        $this->app->bind(
            \App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface::class,
            \App\Domain\Seerah\Repositories\SeerahEventRepository::class
        );
        
        // Sahaba Module Repositories
        $this->app->bind(
            \App\Domain\Sahaba\Repositories\SahabiRepositoryInterface::class,
            \App\Domain\Sahaba\Repositories\SahabiRepository::class
        );
        
        // Quran Module Repositories
        $this->app->bind(
            \App\Domain\Quran\Repositories\SurahRepositoryInterface::class,
            \App\Domain\Quran\Repositories\SurahRepository::class
        );
        $this->app->bind(
            \App\Domain\Quran\Repositories\VerseRepositoryInterface::class,
            \App\Domain\Quran\Repositories\VerseRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

