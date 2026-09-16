<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Shared\Services\LanguageService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Only load languages if the languages table exists (for migrations)
        if (! $this->app->runningInConsole() || $this->shouldLoadLanguages()) {
            $this->loadLanguagesFromDatabase();
        }
    }

    /**
     * Load available languages from database and set them in config.
     */
    protected function loadLanguagesFromDatabase(): void
    {
        try {
            // Check if languages table exists
            if (! Schema::hasTable('languages')) {
                return;
            }

            $service = app(LanguageService::class);
            $service->applyRuntimeConfiguration();
            $this->app->setLocale($service->getDefaultLanguage()?->code ?? 'ar');
        } catch (\Exception $e) {
            // Fallback to default config if database is not available
            report($e);
        }
    }

    /**
     * Determine if languages should be loaded.
     * Skip during migrations to avoid circular dependency.
     */
    protected function shouldLoadLanguages(): bool
    {
        // Don't load during migrate:fresh or migrate:rollback
        if ($this->app->runningInConsole()) {
            $command = $_SERVER['argv'][1] ?? '';

            // Skip for migration commands that drop tables
            if (in_array($command, ['migrate:fresh', 'migrate:rollback', 'migrate:reset'], true)) {
                return false;
            }

            // Allow for regular migrate and db:seed
            if (in_array($command, ['migrate', 'db:seed'], true)) {
                return Schema::hasTable('languages');
            }
        }

        return true;
    }
}
