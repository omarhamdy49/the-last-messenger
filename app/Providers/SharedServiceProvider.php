<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use App\Domain\Shared\Services\PublicVisibilityRegistry;
use Illuminate\Support\ServiceProvider;

class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            PublicVisibilityRegistry::class,
            fn ($app) => new PublicVisibilityRegistry(
                $app->tagged(PublicVisibilityProviderInterface::class)
            )
        );

        $this->app->bind(
            \App\Domain\Shared\Repositories\AdminLogRepositoryInterface::class,
            \App\Domain\Shared\Repositories\AdminLogRepository::class
        );
        $this->app->bind(
            \App\Domain\Shared\Repositories\ImportRepositoryInterface::class,
            \App\Domain\Shared\Repositories\ImportRepository::class
        );
        $this->app->bind(
            \App\Domain\Shared\Repositories\UserInteractionRepositoryInterface::class,
            \App\Domain\Shared\Repositories\UserInteractionRepository::class
        );
        $this->app->bind(
            \App\Domain\Shared\Repositories\UserEngagementMetricRepositoryInterface::class,
            \App\Domain\Shared\Repositories\UserEngagementMetricRepository::class
        );
        $this->app->bind(
            \App\Domain\Shared\Repositories\BulkOperationRepositoryInterface::class,
            \App\Domain\Shared\Repositories\BulkOperationRepository::class
        );
        $this->app->bind(
            \App\Domain\Shared\Repositories\SchedulerRepositoryInterface::class,
            \App\Domain\Shared\Repositories\SchedulerRepository::class
        );
        $this->app->bind(
            \App\Domain\Shared\Repositories\SearchHistoryRepositoryInterface::class,
            \App\Domain\Shared\Repositories\SearchHistoryRepository::class
        );
        $this->app->bind(
            \App\Domain\Shared\Repositories\SavedFilterRepositoryInterface::class,
            \App\Domain\Shared\Repositories\SavedFilterRepository::class
        );
        $this->app->bind(
            \App\Domain\Shared\Repositories\ContentVersionRepositoryInterface::class,
            \App\Domain\Shared\Repositories\ContentVersionRepository::class
        );
        $this->app->bind(
            \App\Domain\Shared\Repositories\AuditLogRepositoryInterface::class,
            \App\Domain\Shared\Repositories\AuditLogRepository::class
        );

        $this->app->singleton(
            \App\Domain\Shared\Services\BulkOperationService::class,
            function ($app) {
                return new \App\Domain\Shared\Services\BulkOperationService(
                    repository: $app->make(\App\Domain\Shared\Repositories\BulkOperationRepositoryInterface::class),
                    adminLogger: $app->make(\App\Domain\Shared\Services\AdminLoggerService::class),
                    handlers: [
                        new \App\Domain\Blog\Services\ArticleBulkHandler,
                        new \App\Domain\Hadith\Services\HadithBulkHandler,
                        new \App\Domain\Quran\Services\VerseBulkHandler,
                        new \App\Domain\Sahaba\Services\SahabiBulkHandler,
                        new \App\Domain\Seerah\Services\SeerahEventBulkHandler,
                        new \App\Domain\Genealogy\Services\GenealogyBulkHandler,
                        new \App\Domain\Media\Services\MediaProgramBulkHandler,
                    ],
                );
            }
        );
    }
}
