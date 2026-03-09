<?php

declare(strict_types=1);

namespace App\Providers;

// use App\Domain\Blog\Repositories\ArticleRepository; // Temporarily disabled
// use App\Domain\Blog\Repositories\ArticleRepositoryInterface; // Temporarily disabled
use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 * Binds repository interfaces to their implementations
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Blog Module Repositories
        // $this->app->bind(ArticleRepositoryInterface::class, ArticleRepository::class); // Temporarily disabled
        $this->app->bind(
            \App\Domain\Blog\Repositories\CategoryRepositoryInterface::class,
            \App\Domain\Blog\Repositories\CategoryRepository::class
        );
        $this->app->bind(
            \App\Domain\Blog\Repositories\SeriesRepositoryInterface::class,
            \App\Domain\Blog\Repositories\SeriesRepository::class
        );
        $this->app->bind(
            \App\Domain\Blog\Repositories\TagRepositoryInterface::class,
            \App\Domain\Blog\Repositories\TagRepository::class
        );

        // Hadith Module Repositories
        $this->app->bind(
            \App\Domain\Hadith\Repositories\HadithRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\HadithRepository::class
        );
        $this->app->bind(
            \App\Domain\Hadith\Repositories\CollectionRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\CollectionRepository::class
        );
        $this->app->bind(
            \App\Domain\Hadith\Repositories\BookRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\BookRepository::class
        );
        $this->app->bind(
            \App\Domain\Hadith\Repositories\ChapterRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\ChapterRepository::class
        );
        $this->app->bind(
            \App\Domain\Hadith\Repositories\NarratorRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\NarratorRepository::class
        );
        $this->app->bind(
            \App\Domain\Hadith\Repositories\ScholarRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\ScholarRepository::class
        );
        $this->app->bind(
            \App\Domain\Hadith\Repositories\ThemeRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\ThemeRepository::class
        );
        $this->app->bind(
            \App\Domain\Hadith\Repositories\GradingLevelRepositoryInterface::class,
            \App\Domain\Hadith\Repositories\GradingLevelRepository::class
        );

        // Domain Events Infrastructure
        $this->app->bind(
            \App\Domain\Shared\Events\EventDispatcherInterface::class,
            \App\Domain\Shared\Events\LaravelEventDispatcher::class
        );

        // DISABLED: Media Module Repositories
        // $this->app->bind(
        //     \App\Domain\Media\Repositories\MediaProgramRepositoryInterface::class,
        //     \App\Domain\Media\Repositories\MediaProgramRepository::class
        // );
        // $this->app->bind(
        //     \App\Domain\Media\Repositories\MediaCategoryRepositoryInterface::class,
        //     \App\Domain\Media\Repositories\MediaCategoryRepository::class
        // );

        // Seerah Module Repositories
        $this->app->bind(
            \App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface::class,
            \App\Domain\Seerah\Repositories\SeerahEventRepository::class
        );

        // Sahaba Module Repositories
        $this->app->bind(
            \App\Domain\Sahaba\Repositories\SahabiRepositoryInterface::class,
            \App\Domain\Sahaba\Repositories\SahabiRepository::class
        );

        // Quran Module Repositories
        $this->app->bind(
            \App\Domain\Quran\Repositories\SurahRepositoryInterface::class,
            \App\Domain\Quran\Repositories\SurahRepository::class
        );
        $this->app->bind(
            \App\Domain\Quran\Repositories\VerseRepositoryInterface::class,
            \App\Domain\Quran\Repositories\VerseRepository::class
        );
        $this->app->bind(
            \App\Domain\Quran\Repositories\ReciterRepositoryInterface::class,
            \App\Domain\Quran\Repositories\ReciterRepository::class
        );
        $this->app->bind(
            \App\Domain\Quran\Repositories\TafseerRepositoryInterface::class,
            \App\Domain\Quran\Repositories\TafseerRepository::class
        );

        // Genealogy Module Repositories
        $this->app->bind(
            \App\Domain\Genealogy\Repositories\PersonRepositoryInterface::class,
            \App\Domain\Genealogy\Repositories\PersonRepository::class
        );

        // Shared/Admin Module Repositories
        $this->app->bind(
            \App\Domain\Shared\Repositories\AdminLogRepositoryInterface::class,
            \App\Domain\Shared\Repositories\AdminLogRepository::class
        );
        $this->app->bind(
            \App\Domain\Shared\Repositories\ImportRepositoryInterface::class,
            \App\Domain\Shared\Repositories\ImportRepository::class
        );

        // Engagement Tracking Repositories
        $this->app->bind(
            \App\Domain\Shared\Repositories\UserInteractionRepositoryInterface::class,
            \App\Domain\Shared\Repositories\UserInteractionRepository::class
        );
        $this->app->bind(
            \App\Domain\Shared\Repositories\UserEngagementMetricRepositoryInterface::class,
            \App\Domain\Shared\Repositories\UserEngagementMetricRepository::class
        );

        // Bulk Operations Repository
        $this->app->bind(
            \App\Domain\Shared\Repositories\BulkOperationRepositoryInterface::class,
            \App\Domain\Shared\Repositories\BulkOperationRepository::class
        );

        // Bulk Operation Service — wired with all domain handlers
        $this->app->singleton(
            \App\Domain\Shared\Services\BulkOperationService::class,
            function ($app) {
                return new \App\Domain\Shared\Services\BulkOperationService(
                    repository: $app->make(\App\Domain\Shared\Repositories\BulkOperationRepositoryInterface::class),
                    adminLogger: $app->make(\App\Domain\Shared\Services\AdminLoggerService::class),
                    handlers: [
                        new \App\Domain\Blog\Services\ArticleBulkHandler(),
                        new \App\Domain\Hadith\Services\HadithBulkHandler(),
                        new \App\Domain\Quran\Services\VerseBulkHandler(),
                        new \App\Domain\Sahaba\Services\SahabiBulkHandler(),
                        new \App\Domain\Seerah\Services\SeerahEventBulkHandler(),
                        new \App\Domain\Genealogy\Services\GenealogyBulkHandler(),
                    ],
                );
            }
        );

        // Content Scheduler Repository
        $this->app->bind(
            \App\Domain\Shared\Repositories\SchedulerRepositoryInterface::class,
            \App\Domain\Shared\Repositories\SchedulerRepository::class
        );

        // Search & Filter Repositories
        $this->app->bind(
            \App\Domain\Shared\Repositories\SearchHistoryRepositoryInterface::class,
            \App\Domain\Shared\Repositories\SearchHistoryRepository::class
        );
        $this->app->bind(
            \App\Domain\Shared\Repositories\SavedFilterRepositoryInterface::class,
            \App\Domain\Shared\Repositories\SavedFilterRepository::class
        );

        // Version Control & Audit Repositories
        $this->app->bind(
            \App\Domain\Shared\Repositories\ContentVersionRepositoryInterface::class,
            \App\Domain\Shared\Repositories\ContentVersionRepository::class
        );
        $this->app->bind(
            \App\Domain\Shared\Repositories\AuditLogRepositoryInterface::class,
            \App\Domain\Shared\Repositories\AuditLogRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
