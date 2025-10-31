# SEO Service - Integration Checklist

**Version:** 1.0.0
**Last Updated:** 2025-11-01
**Component:** Article Module (Task A7)

## Quick Integration Steps

This checklist guides you through integrating the SeoService into your Laravel application.

---

## Prerequisites

- ✅ Laravel 11.x installed
- ✅ Article model with translatable fields (Spatie Laravel Translatable)
- ✅ User, Category, Series, Tag models
- ✅ Storage configured (for featured images)
- ✅ Routes defined for articles

---

## Step 1: Install Dependencies

### Required Packages

The SeoService requires **Spatie Laravel Translatable** for multi-language support.

```bash
composer require spatie/laravel-translatable
```

### Configuration

Ensure `config/translatable.php` is published:

```bash
php artisan vendor:publish --provider="Spatie\Translatable\TranslatableServiceProvider"
```

---

## Step 2: Verify Article Model

Ensure the Article model has the following:

### Translatable Fields

```php
public $translatable = [
    'title',
    'excerpt',
    'content',
    'seo_title',
    'seo_description',
];
```

### Required Methods

```php
public function getSeoTitle(string $locale = 'ar'): string
{
    $seoTitle = $this->getTranslation('seo_title', $locale, false);
    return $seoTitle ?: $this->getTranslation('title', $locale);
}

public function getSeoDescription(string $locale = 'ar'): string
{
    $seoDescription = $this->getTranslation('seo_description', $locale, false);
    return $seoDescription ?: $this->getExcerptOrContent($locale, 160);
}

public function getOgImage(): ?string
{
    return $this->og_image ?: $this->featured_image;
}
```

---

## Step 3: Service Registration (Optional)

If you want to bind the service to the container:

**File:** `app/Providers/AppServiceProvider.php`

```php
use App\Services\SeoService;

public function register(): void
{
    $this->app->singleton(SeoService::class);
}
```

---

## Step 4: Controller Integration

### Example: ArticleController

```php
<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\SeoService;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function __construct(
        private SeoService $seoService
    ) {}

    /**
     * Display article with SEO metadata
     */
    public function show(string $slug)
    {
        $article = Article::published()
            ->with(['author', 'category', 'tags'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Increment view count
        $article->incrementViewCount();

        // Generate SEO metadata
        $locale = app()->getLocale();
        $seo = $this->seoService->generateSeoData($article, $locale);

        return view('articles.show', compact('article', 'seo'));
    }
}
```

---

## Step 5: Blade Template Integration

### Method 1: Using Blade Component (Recommended)

**File:** `resources/views/articles/show.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
<head>
    {{-- SEO Meta Tags Component --}}
    <x-seo-meta-tags :metadata="$seo" />

    {{-- Additional head elements --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    {{-- Article content --}}
    <article>
        <h1>{{ $article->getTranslation('title', app()->getLocale()) }}</h1>
        <div>{!! $article->getTranslation('content', app()->getLocale()) !!}</div>
    </article>
</body>
</html>
```

### Method 2: Manual Rendering

```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    {{-- Render all meta tags --}}
    {!! $seo->renderMetaTags() !!}

    {{-- Render JSON-LD --}}
    {!! $seo->renderJsonLd() !!}
</head>
<body>
    {{-- Content --}}
</body>
</html>
```

---

## Step 6: Admin Dashboard Integration

### Validation Widget

Display SEO score and suggestions in the article edit form:

**File:** `resources/views/admin/articles/edit.blade.php`

```blade
@php
    $validation = app(App\Services\SeoService::class)->validateSeoData($article, 'ar');
@endphp

<div class="seo-validation-widget">
    <h3>SEO Score: {{ $validation->seoScore }}/100 (Grade: {{ $validation->getGrade() }})</h3>

    @if($validation->hasErrors())
        <div class="alert alert-danger">
            <h4>Errors:</h4>
            <ul>
                @foreach($validation->errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($validation->hasWarnings())
        <div class="alert alert-warning">
            <h4>Warnings:</h4>
            <ul>
                @foreach($validation->warnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($validation->hasSuccesses())
        <div class="alert alert-success">
            <h4>Good Practices:</h4>
            <ul>
                @foreach($validation->successes as $success)
                    <li>{{ $success }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
```

### Suggestions Panel

```blade
@php
    $suggestions = app(App\Services\SeoService::class)->generateSuggestions($article, 'ar');
@endphp

@if(count($suggestions) > 0)
    <div class="seo-suggestions">
        <h3>SEO Improvement Suggestions</h3>
        <ul>
            @foreach($suggestions as $suggestion)
                <li>{{ $suggestion }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

---

## Step 7: Caching Implementation

### Cache SEO Metadata

Add caching to improve performance:

```php
use Illuminate\Support\Facades\Cache;

public function show(string $slug)
{
    $article = Article::published()
        ->with(['author', 'category', 'tags'])
        ->where('slug', $slug)
        ->firstOrFail();

    $locale = app()->getLocale();
    $cacheKey = "article:{$article->id}:seo:{$locale}";

    $seo = Cache::remember($cacheKey, now()->addHour(), function () use ($article, $locale) {
        return app(SeoService::class)->generateSeoData($article, $locale);
    });

    return view('articles.show', compact('article', 'seo'));
}
```

### Cache Invalidation

Add observer to invalidate cache when article is updated:

**File:** `app/Observers/ArticleObserver.php`

```php
<?php

namespace App\Observers;

use App\Models\Article;
use Illuminate\Support\Facades\Cache;

class ArticleObserver
{
    /**
     * Handle the Article "updated" event.
     */
    public function updated(Article $article): void
    {
        // Clear SEO cache for all locales
        foreach (['ar', 'en', 'fr', 'tr', 'ur'] as $locale) {
            Cache::forget("article:{$article->id}:seo:{$locale}");
        }
    }

    /**
     * Handle the Article "deleted" event.
     */
    public function deleted(Article $article): void
    {
        // Clear SEO cache for all locales
        foreach (['ar', 'en', 'fr', 'tr', 'ur'] as $locale) {
            Cache::forget("article:{$article->id}:seo:{$locale}");
        }
    }
}
```

**Register Observer in** `AppServiceProvider`:

```php
use App\Models\Article;
use App\Observers\ArticleObserver;

public function boot(): void
{
    Article::observe(ArticleObserver::class);
}
```

---

## Step 8: Sitemap Generation

### Generate XML Sitemap for Articles

**File:** `app/Http/Controllers/SitemapController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\SeoService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(
        private SeoService $seoService
    ) {}

    public function articles(): Response
    {
        $articles = Article::published()
            ->orderBy('updated_at', 'desc')
            ->get();

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"/>');

        foreach ($articles as $article) {
            try {
                $entry = $this->seoService->generateSitemapEntry($article);

                $url = $xml->addChild('url');
                $url->addChild('loc', $entry['loc']);
                $url->addChild('lastmod', $entry['lastmod']);
                $url->addChild('changefreq', $entry['changefreq']);
                $url->addChild('priority', $entry['priority']);

                // Add images
                foreach ($entry['images'] as $image) {
                    if ($image) {
                        $imageNode = $url->addChild('image:image', null, 'http://www.google.com/schemas/sitemap-image/1.1');
                        $imageNode->addChild('image:loc', $image, 'http://www.google.com/schemas/sitemap-image/1.1');
                    }
                }
            } catch (\Exception $e) {
                // Skip articles that fail sitemap generation
                continue;
            }
        }

        return response($xml->asXML(), 200)
            ->header('Content-Type', 'application/xml');
    }
}
```

### Register Sitemap Route

**File:** `routes/web.php`

```php
use App\Http\Controllers\SitemapController;

Route::get('/sitemap-articles.xml', [SitemapController::class, 'articles'])
    ->name('sitemap.articles');
```

---

## Step 9: Validation API Endpoint (Optional)

Create an API endpoint for real-time SEO validation:

**File:** `app/Http/Controllers/Api/SeoValidationController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\SeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoValidationController extends Controller
{
    public function __construct(
        private SeoService $seoService
    ) {}

    /**
     * Validate SEO data for article
     */
    public function validate(Request $request, Article $article): JsonResponse
    {
        $locale = $request->get('locale', 'ar');

        $validation = $this->seoService->validateSeoData($article, $locale);
        $suggestions = $this->seoService->generateSuggestions($article, $locale);

        return response()->json([
            'validation' => $validation->toArray(),
            'suggestions' => $suggestions,
        ]);
    }
}
```

### Register API Route

**File:** `routes/api.php`

```php
use App\Http\Controllers\Api\SeoValidationController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/articles/{article}/seo/validate', [SeoValidationController::class, 'validate'])
        ->name('api.articles.seo.validate');
});
```

---

## Step 10: Testing

### Test SEO Generation

```php
use App\Models\Article;
use App\Services\SeoService;

// In Tinker or test
$article = Article::published()->first();
$seoService = app(SeoService::class);

// Test metadata generation
$seo = $seoService->generateSeoData($article, 'ar');
dump($seo->toArray());

// Test validation
$validation = $seoService->validateSeoData($article, 'ar');
dump($validation->toArray());

// Test suggestions
$suggestions = $seoService->generateSuggestions($article, 'ar');
dump($suggestions);
```

### Test with OpenGraph Debugger

1. **Facebook Debugger:** https://developers.facebook.com/tools/debug/
2. **Twitter Card Validator:** https://cards-dev.twitter.com/validator
3. **LinkedIn Post Inspector:** https://www.linkedin.com/post-inspector/

---

## Step 11: Configuration (Optional)

### Custom Twitter Handles

**File:** `config/services.php`

```php
return [
    // ...existing services

    'twitter' => [
        'site' => '@thelastmessenger',
        'creator' => '@yourusername',
    ],
];
```

### Custom Organization Settings

If you need to override organization settings, create a config file:

**File:** `config/seo.php`

```php
<?php

return [
    'organization' => [
        'name' => env('SEO_ORG_NAME', 'The Last Messenger'),
        'url' => env('SEO_ORG_URL', 'https://thelastmessenger.com'),
        'logo' => env('SEO_ORG_LOGO', 'https://thelastmessenger.com/logo.png'),
    ],

    'defaults' => [
        'title_suffix' => ' - The Last Messenger',
        'default_image' => asset('images/default-og-image.jpg'),
    ],
];
```

---

## Troubleshooting

### Issue: "Class SeoService not found"

**Solution:** Run `composer dump-autoload`

```bash
composer dump-autoload
```

---

### Issue: Missing featured image URL

**Solution:** Ensure storage is linked:

```bash
php artisan storage:link
```

---

### Issue: Translatable fields returning null

**Solution:** Check that Spatie Translatable is properly configured:

```php
// In Article model
public $translatable = [
    'title',
    'excerpt',
    'content',
    'seo_title',
    'seo_description',
];
```

---

### Issue: Invalid locale error

**Solution:** Use supported locales only: `ar`, `en`, `fr`, `tr`, `ur`

---

## Checklist Summary

- [ ] Install Spatie Laravel Translatable
- [ ] Verify Article model has translatable fields
- [ ] Integrate SeoService in controller
- [ ] Add SEO component to Blade templates
- [ ] Implement caching strategy
- [ ] Add SEO validation to admin dashboard
- [ ] Create sitemap route
- [ ] Test with OpenGraph debuggers
- [ ] Configure Twitter handles (optional)
- [ ] Deploy and monitor

---

## Next Steps

1. Review [SEO Service Usage Examples](./SEO_SERVICE_EXAMPLES.md)
2. Check [SEO Service Quick Reference](./SEO_SERVICE_QUICK_REFERENCE.md)
3. Read [SEO Service Technical Guide](./SEO_SERVICE_TECHNICAL_GUIDE.md)

---

**Integration Complete!** 🎉

Your application now has comprehensive SEO support with multi-language content, validation, and structured data.
