<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class GenealogyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Domain\Genealogy\Repositories\PersonRepositoryInterface::class,
            \App\Domain\Genealogy\Repositories\PersonRepository::class
        );
    }
}
