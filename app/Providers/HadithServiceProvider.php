<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Observers\HadithCacheObserver;
use App\Domain\Hadith\Repositories\EloquentHadithImportRepository;
use App\Domain\Hadith\Repositories\HadithImportRepositoryInterface;
use Illuminate\Support\ServiceProvider;

final class HadithServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ([HadithCollection::class, HadithBook::class, HadithChapter::class, Hadith::class] as $model) {
            $model::observe(HadithCacheObserver::class);
        }
    }

    public function register(): void
    {
        $this->app->bind(HadithImportRepositoryInterface::class, EloquentHadithImportRepository::class);
    }
}
