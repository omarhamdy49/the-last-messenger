<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Seerah\Services\SeerahPublicVisibility;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use Illuminate\Support\ServiceProvider;

class SeerahServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SeerahPublicVisibility::class);
        $this->app->tag(SeerahPublicVisibility::class, PublicVisibilityProviderInterface::class);

        $this->app->bind(
            \App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface::class,
            \App\Domain\Seerah\Repositories\SeerahEventRepository::class
        );

        $this->app->bind(
            \App\Domain\Seerah\Repositories\SeerahEventTypeRepositoryInterface::class,
            \App\Domain\Seerah\Repositories\SeerahEventTypeRepository::class
        );

        $this->app->bind(
            \App\Domain\Seerah\Repositories\SeerahMilestoneRepositoryInterface::class,
            \App\Domain\Seerah\Repositories\SeerahMilestoneRepository::class
        );
    }
}
