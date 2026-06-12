<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SahabaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Domain\Sahaba\Repositories\SahabiRepositoryInterface::class,
            \App\Domain\Sahaba\Repositories\SahabiRepository::class
        );
    }
}
