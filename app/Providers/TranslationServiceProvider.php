<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Shared\Models\Language;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
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
     *
     * @return void
     */
    protected function loadLanguagesFromDatabase(): void
    {
        try {
            // Check if languages table exists
            if (! Schema::hasTable('languages')) {
                return;
            }

            // Cache language codes for 5 minutes
            $locales = Cache::remember(
                'system:locales',
                now()->addMinutes(5),
                function () {
                    return Language::active()
                        ->ordered()
                        ->pluck('code')
                        ->toArray();
                }
            );

            // Get default language
            $defaultLocale = Cache::remember(
                'system:default_locale',
                now()->addMinutes(5),
                function () {
                    $defaultLang = Language::default()->first();

                    return $defaultLang ? $defaultLang->code : 'ar';
                }
            );

            // Set locales in translatable config
            if (! empty($locales)) {
                Config::set('translatable.locales', $locales);
                Config::set('translatable.fallback_locale', $defaultLocale);
                Config::set('app.fallback_locale', $defaultLocale);
            }

            // Set available locales for the application
            $this->app->singleton('available_locales', function () use ($locales) {
                return $locales;
            });

            // Set default locale if not already set
            if (! $this->app->getLocale() || $this->app->getLocale() === 'en') {
                $this->app->setLocale($defaultLocale);
            }
        } catch (\Exception $e) {
            // Fallback to default config if database is not available
            report($e);
        }
    }

    /**
     * Determine if languages should be loaded.
     * Skip during migrations to avoid circular dependency.
     *
     * @return bool
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
