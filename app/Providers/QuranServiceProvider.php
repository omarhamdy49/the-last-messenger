<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class QuranServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Domain\Quran\Repositories\SurahRepositoryInterface::class,
            \App\Domain\Quran\Repositories\SurahRepository::class
        );
        $this->app->bind(
            \App\Domain\Quran\Repositories\VerseRepositoryInterface::class,
            \App\Domain\Quran\Repositories\VerseRepository::class
        );
        $this->app->bind(
            \App\Domain\Quran\Repositories\ReciterRepositoryInterface::class,
            \App\Domain\Quran\Repositories\ReciterRepository::class
        );
        $this->app->bind(
            \App\Domain\Quran\Repositories\TafseerRepositoryInterface::class,
            \App\Domain\Quran\Repositories\TafseerRepository::class
        );
        $this->app->bind(
            \App\Domain\Quran\Repositories\RevelationContextRepositoryInterface::class,
            \App\Domain\Quran\Repositories\RevelationContextRepository::class
        );
    }
}
