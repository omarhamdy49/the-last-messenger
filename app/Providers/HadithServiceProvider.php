<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Hadith\Repositories\EloquentHadithImportRepository;
use App\Domain\Hadith\Repositories\HadithImportRepositoryInterface;
use Illuminate\Support\ServiceProvider;

final class HadithServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(HadithImportRepositoryInterface::class, EloquentHadithImportRepository::class);
    }
}
