<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Sahaba\Services\SahabaPublicVisibility;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use Illuminate\Support\ServiceProvider;

class SahabaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SahabaPublicVisibility::class);
        $this->app->tag(SahabaPublicVisibility::class, PublicVisibilityProviderInterface::class);

        $this->app->bind(
            \App\Domain\Sahaba\Repositories\SahabiRepositoryInterface::class,
            \App\Domain\Sahaba\Repositories\SahabiRepository::class
        );
    }
}
