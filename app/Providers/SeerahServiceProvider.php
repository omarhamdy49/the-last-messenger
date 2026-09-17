<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Seerah\Repositories\SeerahEventRepository;
use App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface;
use App\Domain\Seerah\Repositories\SeerahEventTypeRepository;
use App\Domain\Seerah\Repositories\SeerahEventTypeRepositoryInterface;
use App\Domain\Seerah\Repositories\SeerahMilestoneRepository;
use App\Domain\Seerah\Repositories\SeerahMilestoneRepositoryInterface;
use App\Domain\Seerah\Services\SeerahPublicVisibility;
use App\Domain\Shared\Contracts\MediaLibraryLookup;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use App\Services\MediaLibraryLookupService;
use Illuminate\Support\ServiceProvider;

class SeerahServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MediaLibraryLookup::class, MediaLibraryLookupService::class);

        $this->app->singleton(SeerahPublicVisibility::class);
        $this->app->tag(SeerahPublicVisibility::class, PublicVisibilityProviderInterface::class);

        $this->app->bind(
            SeerahEventRepositoryInterface::class,
            SeerahEventRepository::class
        );

        $this->app->bind(
            SeerahEventTypeRepositoryInterface::class,
            SeerahEventTypeRepository::class
        );

        $this->app->bind(
            SeerahMilestoneRepositoryInterface::class,
            SeerahMilestoneRepository::class
        );
    }
}
