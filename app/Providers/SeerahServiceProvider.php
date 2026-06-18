<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SeerahServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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
