<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class HadithServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Domain\Hadith\Repositories\HadithRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\HadithRepository::class
        );
        $this->app->bind(
            \App\Domain\Hadith\Repositories\CollectionRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\CollectionRepository::class
        );
        $this->app->bind(
            \App\Domain\Hadith\Repositories\BookRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\BookRepository::class
        );
        $this->app->bind(
            \App\Domain\Hadith\Repositories\ChapterRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\ChapterRepository::class
        );
        $this->app->bind(
            \App\Domain\Hadith\Repositories\NarratorRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\NarratorRepository::class
        );
        $this->app->bind(
            \App\Domain\Hadith\Repositories\ScholarRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\ScholarRepository::class
        );
        $this->app->bind(
            \App\Domain\Hadith\Repositories\ThemeRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\ThemeRepository::class
        );
        $this->app->bind(
            \App\Domain\Hadith\Repositories\GradingLevelRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\GradingLevelRepository::class
        );
        $this->app->bind(
            \App\Domain\Shared\Events\EventDispatcherInterface::class,
            \App\Domain\Shared\Events\LaravelEventDispatcher::class
        );
    }
}
