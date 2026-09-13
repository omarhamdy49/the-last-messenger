<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Blog\Services\ArticleExportAdapter;
use App\Domain\Blog\Services\BlogDashboardStatsProvider;
use App\Domain\Hadith\Services\HadithDashboardStatsProvider;
use App\Domain\Media\Services\MediaDashboardStatsProvider;
use App\Domain\Media\Services\MediaProgramExportAdapter;
use App\Domain\People\Services\PeopleDashboardStatsProvider;
use App\Domain\Quran\Services\QuranDashboardStatsProvider;
use App\Domain\Quran\Services\QuranReciterExportAdapter;
use App\Domain\Quran\Services\QuranSurahExportAdapter;
use App\Domain\Quran\Services\QuranTafseerExportAdapter;
use App\Domain\Quran\Services\QuranVerseExportAdapter;
use App\Domain\Seerah\Services\SeerahDashboardStatsProvider;
use App\Domain\Seerah\Services\SeerahEventExportAdapter;
use App\Domain\Shared\Models\Language;
use App\Domain\Shared\Services\DashboardAnalyticsService;
use App\Domain\Shared\Services\DashboardService;
use App\Domain\Shared\Services\ExportGateway;
use App\Observers\LanguageObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $statsProviders = [
            new BlogDashboardStatsProvider,
            new HadithDashboardStatsProvider,
            new PeopleDashboardStatsProvider,
            new QuranDashboardStatsProvider,
            new SeerahDashboardStatsProvider,
            new MediaDashboardStatsProvider,
        ];

        $this->app->singleton(DashboardService::class, fn (): DashboardService => new DashboardService(statsProviders: $statsProviders)
        );

        $this->app->singleton(DashboardAnalyticsService::class, fn (): DashboardAnalyticsService => new DashboardAnalyticsService(statsProviders: $statsProviders)
        );

        $this->app->singleton(ExportGateway::class, function ($app): ExportGateway {
            return new ExportGateway(modules: [
                $app->make(ArticleExportAdapter::class),
                $app->make(QuranSurahExportAdapter::class),
                $app->make(QuranVerseExportAdapter::class),
                $app->make(QuranReciterExportAdapter::class),
                $app->make(QuranTafseerExportAdapter::class),
                $app->make(SeerahEventExportAdapter::class),
                $app->make(MediaProgramExportAdapter::class),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure named rate limiters for API routes
        $this->configureRateLimiters();

        // Set default locale to Arabic
        app()->setLocale(config('app.locale', 'ar'));

        // Configure Carbon to use Arabic locale
        Carbon::setLocale(config('app.locale', 'ar'));

        // Register observers
        Language::observe(LanguageObserver::class);

        // Register custom Blade directives
        $this->registerBladeDirectives();
    }

    /**
     * Define named rate limiters used across API routes.
     *
     * - auth:   10 requests/minute per IP — protects login & register from brute-force.
     * - search: 30 requests/minute per IP — prevents search abuse.
     * - api:    60 requests/minute per IP — default for general API endpoints.
     */
    protected function configureRateLimiters(): void
    {
        RateLimiter::for('auth', static function (Request $request): array {
            $email = Str::lower(trim((string) $request->input('email', 'unknown')));

            return [
                Limit::perMinute(10)->by('auth-ip:'.$request->ip()),
                Limit::perMinute(5)->by('auth-credential:'.$email.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('search', static function (Request $request): Limit {
            return Limit::perMinute(30)->by('search:'.($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('api', static function (Request $request): Limit {
            return Limit::perMinute(60)->by('api:'.($request->user()?->id ?? $request->ip()));
        });

        // ai_inquiry: 5 requests/minute per authenticated user — burst protection
        // layered under the monthly "Ask Quran" AI quota (enforced in the handler).
        RateLimiter::for('ai_inquiry', static function (Request $request): Limit {
            return Limit::perMinute(5)->by((string) (optional($request->user())->id ?? $request->ip()));
        });
    }

    /**
     * Register custom Blade directives for translations
     */
    protected function registerBladeDirectives(): void
    {
        // @trans directive for cleaner translation syntax
        // Usage: @trans($model, 'field_name')
        Blade::directive('trans', function ($expression) {
            return "<?php echo e(app('translator')->get($expression)); ?>";
        });

        // @transField directive for model translatable fields
        // Usage: @transField($article->title)
        Blade::directive('transField', function ($expression) {
            return "<?php echo e($expression); ?>";
        });

        // @isRtl directive to check if current locale is RTL
        // Usage: @isRtl returns true/false
        Blade::directive('isRtl', function () {
            return "<?php echo app('translator')->getLocale() === 'ar' || app('translator')->getLocale() === 'ur'; ?>";
        });

        // @direction directive to get current text direction
        // Usage: @direction returns 'rtl' or 'ltr'
        Blade::directive('direction', function () {
            return "<?php echo in_array(app('translator')->getLocale(), ['ar', 'ur']) ? 'rtl' : 'ltr'; ?>";
        });
    }
}
