<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\People\Repositories\EloquentMuslimRepository;
use App\Domain\People\Repositories\MuslimRepositoryInterface;
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
    }
}
