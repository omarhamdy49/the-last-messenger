<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Blog\Services\ArticleBulkHandler;
use App\Domain\Media\Services\MediaProgramBulkHandler;
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
use App\Domain\Shared\Repositories\LocationRepository;
use App\Domain\Shared\Repositories\LocationRepositoryInterface;
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
use App\Domain\Shared\Services\LocationUsageRegistry;
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

        // Which tables point at `locations`. Registered here rather than as
        // relations on the Location model, because Shared is a leaf and may not
        // import Domain\Seerah, Domain\People or Domain\Quran (constitution IV).
        // Only the table and column names cross the boundary, never a class.
        $this->app->singleton(
            LocationUsageRegistry::class,
            fn (): LocationUsageRegistry => new LocationUsageRegistry([
                ['table' => 'seerah_events', 'column' => 'location_id', 'label' => 'seerah_events'],
                ['table' => 'muslims', 'column' => 'birth_place_id', 'label' => 'muslims'],
                ['table' => 'quran_chapters', 'column' => 'revelation_location_id', 'label' => 'quran_chapters'],
            ])
        );

        $this->app->bind(
            LocationRepositoryInterface::class,
            LocationRepository::class
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
                        new SeerahEventBulkHandler,
                        new MediaProgramBulkHandler,
                    ],
                );
            }
        );
    }
}
