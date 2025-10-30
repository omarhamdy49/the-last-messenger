<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Shared\Models\Language;
use App\Observers\LanguageObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        // Register observers
        Language::observe(LanguageObserver::class);

        // Register custom Blade directives
        $this->registerBladeDirectives();
    }

    /**
     * Register custom Blade directives for translations
     *
     * @return void
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
