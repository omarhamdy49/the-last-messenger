<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Blog\Services\ArticleBulkHandler;
use App\Domain\Media\Services\MediaProgramBulkHandler;
use App\Domain\Quran\Services\VerseBulkHandler;
use App\Domain\Seerah\Services\SeerahEventBulkHandler;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use App\Domain\Shared\Repositories\AdminLogRepository;
use App\Domain\Shared\Repositories\AdminLogRepositoryInterface;
use App\Domain\Shared\Repositories\AuditLogRepository;
use App\Domain\Shared\Repositories\AuditLogRepositoryInterface;
use App\Domain\Shared\Repositories\BulkOperationRepository;
use App\Domain\Shared\Repositories\BulkOperationRepositoryInterface;
use App\Domain\Shared\Repositories\ContentVersionRepository;
use App\Domain\Shared\Repositories\ContentVersionRepositoryInterface;
use App\Domain\Shared\Repositories\ImportRepository;
use App\Domain\Shared\Repositories\ImportRepositoryInterface;
use App\Domain\Shared\Repositories\SavedFilterRepository;
use App\Domain\Shared\Repositories\SavedFilterRepositoryInterface;
use App\Domain\Shared\Repositories\SchedulerRepository;
use App\Domain\Shared\Repositories\SchedulerRepositoryInterface;
use App\Domain\Shared\Repositories\SearchHistoryRepository;
use App\Domain\Shared\Repositories\SearchHistoryRepositoryInterface;
use App\Domain\Shared\Repositories\UserEngagementMetricRepository;
use App\Domain\Shared\Repositories\UserEngagementMetricRepositoryInterface;
use App\Domain\Shared\Repositories\UserInteractionRepository;
use App\Domain\Shared\Repositories\UserInteractionRepositoryInterface;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Domain\Shared\Services\BulkOperationService;
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
            AdminLogRepositoryInterface::class,
            AdminLogRepository::class
        );
        $this->app->bind(
            ImportRepositoryInterface::class,
            ImportRepository::class
        );
        $this->app->bind(
            UserInteractionRepositoryInterface::class,
            UserInteractionRepository::class
        );
        $this->app->bind(
            UserEngagementMetricRepositoryInterface::class,
            UserEngagementMetricRepository::class
        );
        $this->app->bind(
            BulkOperationRepositoryInterface::class,
            BulkOperationRepository::class
        );
        $this->app->bind(
            SchedulerRepositoryInterface::class,
            SchedulerRepository::class
        );
        $this->app->bind(
            SearchHistoryRepositoryInterface::class,
            SearchHistoryRepository::class
        );
        $this->app->bind(
            SavedFilterRepositoryInterface::class,
            SavedFilterRepository::class
        );
        $this->app->bind(
            ContentVersionRepositoryInterface::class,
            ContentVersionRepository::class
        );
        $this->app->bind(
            AuditLogRepositoryInterface::class,
            AuditLogRepository::class
        );

        $this->app->singleton(
            BulkOperationService::class,
            function ($app) {
                return new BulkOperationService(
                    repository: $app->make(BulkOperationRepositoryInterface::class),
                    adminLogger: $app->make(AdminLoggerService::class),
                    handlers: [
                        new ArticleBulkHandler,
                        new VerseBulkHandler,
                        new SeerahEventBulkHandler,
                        new MediaProgramBulkHandler,
                    ],
                );
            }
        );
    }
}
