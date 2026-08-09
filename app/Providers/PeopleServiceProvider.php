<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\People\Repositories\EloquentMuslimRelationshipRepository;
use App\Domain\People\Repositories\EloquentMuslimRepository;
use App\Domain\People\Repositories\MuslimRelationshipRepositoryInterface;
use App\Domain\People\Repositories\MuslimRepositoryInterface;
use App\Domain\People\Services\MuslimPersonLookup;
use App\Domain\People\Services\MuslimPublicVisibility;
use App\Domain\Shared\Contracts\PersonLookup;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use App\Domain\Shared\Support\ArabicNormalizer;
use Illuminate\Support\ServiceProvider;

class PeopleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton so the normalizer used to WRITE search_name_* is literally
        // the same instance used to fold an incoming query. If the two ever
        // fold differently, diacritic-insensitive search fails in one
        // direction only, silently.
        $this->app->singleton(ArabicNormalizer::class);

        $this->app->bind(MuslimRepositoryInterface::class, EloquentMuslimRepository::class);
        $this->app->bind(MuslimRelationshipRepositoryInterface::class, EloquentMuslimRelationshipRepository::class);

        // How Hadith (and any other module) reaches a person without importing
        // this one — constitution IV.
        $this->app->bind(PersonLookup::class, MuslimPersonLookup::class);

        // Without this, unpublished people leak into cross-module search.
        $this->app->singleton(MuslimPublicVisibility::class);
        $this->app->tag(MuslimPublicVisibility::class, PublicVisibilityProviderInterface::class);
    }
}
