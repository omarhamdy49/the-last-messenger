<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Hadith\Repositories\CollectionRepository;
use App\Domain\Hadith\Repositories\CollectionRepositoryInterface;
use App\Domain\Hadith\Repositories\HadithRepository;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Hadith\Repositories\ScholarRepository;
use App\Domain\Hadith\Repositories\ScholarRepositoryInterface;
use App\Domain\Hadith\Services\HadithVisibility;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Three repository pairs, down from seven: books and chapters are never read
 * independently of their collection, so CollectionRepository owns all three.
 */
class HadithServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HadithVisibility::class);
        $this->app->tag(HadithVisibility::class, PublicVisibilityProviderInterface::class);

        $this->app->bind(CollectionRepositoryInterface::class, CollectionRepository::class);
        $this->app->bind(HadithRepositoryInterface::class, HadithRepository::class);
        $this->app->bind(ScholarRepositoryInterface::class, ScholarRepository::class);
    }
}
