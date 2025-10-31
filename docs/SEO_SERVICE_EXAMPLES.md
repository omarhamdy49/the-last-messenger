# SEO Service - Usage Examples

**Version:** 1.0.0
**Last Updated:** 2025-11-01
**Component:** Article Module (Task A7)

This document provides comprehensive usage examples for the SeoService.

---

## Table of Contents

1. [Basic Usage](#basic-usage)
2. [Controller Examples](#controller-examples)
3. [Blade Template Examples](#blade-template-examples)
4. [Validation Examples](#validation-examples)
5. [Multi-Language Examples](#multi-language-examples)
6. [Caching Examples](#caching-examples)
7. [Admin Dashboard Examples](#admin-dashboard-examples)
8. [API Examples](#api-examples)
9. [Sitemap Examples](#sitemap-examples)
10. [Advanced Examples](#advanced-examples)

---

## Basic Usage

### Example 1: Generate Complete SEO Metadata

```php
use App\Models\Article;
use App\Services\SeoService;

// Get article
$article = Article::published()->where('slug', 'prophet-biography')->first();

// Generate SEO metadata
$seoService = app(SeoService::class);
$seo = $seoService->generateSeoData($article, 'ar');

// Access metadata
echo $seo->title;              // SEO title
echo $seo->description;        // SEO description
echo $seo->keywords;           // Keywords
echo $seo->canonicalUrl;       // Canonical URL
echo $seo->featuredImage;      // Featured image URL

// Access OpenGraph tags
print_r($seo->openGraph);

// Access Twitter Card tags
print_r($seo->twitterCard);

// Access JSON-LD data
print_r($seo->jsonLd);
```

---

### Example 2: Render SEO Tags in HTML

```php
use App\Models\Article;
use App\Services\SeoService;

$article = Article::find(1);
$seoService = app(SeoService::class);
$seo = $seoService->generateSeoData($article, 'en');

// Render all meta tags
echo $seo->renderMetaTags();

// Render JSON-LD structured data
echo $seo->renderJsonLd();

// Or render everything together
echo $seo->render();
```

**Output:**
```html
<meta name="description" content="Article description">
<meta name="keywords" content="keyword1, keyword2, keyword3">
<link rel="canonical" href="https://thelastmessenger.com/articles/slug">
<meta property="og:type" content="article">
<meta property="og:title" content="Article Title">
<!-- ... more tags ... -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "Article Title",
  ...
}
</script>
```

---

## Controller Examples

### Example 3: Article Show Controller

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
        // Load article with relationships
        $article = Article::published()
            ->with(['author', 'category', 'series', 'tags'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Increment view count
        $article->incrementViewCount();

        // Get current locale
        $locale = app()->getLocale();

        // Generate SEO metadata
        $seo = $this->seoService->generateSeoData($article, $locale);

        // Validate SEO (for admin preview)
        $validation = null;
        if (auth()->user()?->isAdmin()) {
            $validation = $this->seoService->validateSeoData($article, $locale);
        }

        return view('articles.show', compact('article', 'seo', 'validation'));
    }
}
```

---

### Example 4: Article Preview Controller (Draft Mode)

```php
public function preview(Article $article)
{
    // Allow admins to preview drafts
    $this->authorize('preview', $article);

    $locale = request()->get('locale', 'ar');

    // Generate SEO metadata for draft
    $seo = $this->seoService->generateSeoData($article, $locale);

    // Get SEO validation and suggestions
    $validation = $this->seoService->validateSeoData($article, $locale);
    $suggestions = $this->seoService->generateSuggestions($article, $locale);

    return view('articles.preview', compact('article', 'seo', 'validation', 'suggestions'));
}
```

---

## Blade Template Examples

### Example 5: Using Blade Component

```blade
{{-- resources/views/articles/show.blade.php --}}

<!DOCTYPE html>
<html lang="{{ $seo->locale }}" dir="{{ in_array($seo->locale, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
<head>
    {{-- Include SEO meta tags component --}}
    <x-seo-meta-tags :metadata="$seo" />

    {{-- Additional styles --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <article>
        <header>
            <h1>{{ $article->getTranslation('title', $seo->locale) }}</h1>

            @if($article->author)
                <p>By {{ $article->author->name }}</p>
            @endif

            @if($article->published_at)
                <time datetime="{{ $article->published_at->toIso8601String() }}">
                    {{ $article->published_at->format('F d, Y') }}
                </time>
            @endif
        </header>

        <div class="article-content">
            {!! $article->getTranslation('content', $seo->locale) !!}
        </div>
    </article>
</body>
</html>
```

---

### Example 6: Manual Meta Tag Rendering

```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $seo->title }}</title>

    {{-- Render all SEO meta tags --}}
    {!! $seo->renderMetaTags() !!}

    {{-- Render JSON-LD structured data --}}
    {!! $seo->renderJsonLd() !!}

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    @yield('content')
</body>
</html>
```

---

### Example 7: Conditional No-Index for Drafts

```blade
<x-seo-meta-tags
    :metadata="$seo"
    :title="$article->getTranslation('title', app()->getLocale())"
    :noIndex="!$article->isPublished()"
/>
```

---

## Validation Examples

### Example 8: Validate Article SEO

```php
use App\Models\Article;
use App\Services\SeoService;

$article = Article::find(1);
$seoService = app(SeoService::class);

// Validate SEO data
$validation = $seoService->validateSeoData($article, 'ar');

// Check validation status
if ($validation->isValid) {
    echo "✓ SEO validation passed!\n";
} else {
    echo "✗ SEO validation failed.\n";
}

// Display score and grade
echo "Score: {$validation->seoScore}/100\n";
echo "Grade: {$validation->getGrade()}\n";
echo "Color: {$validation->getScoreColor()}\n";

// Display errors
if ($validation->hasErrors()) {
    echo "\nErrors:\n";
    foreach ($validation->errors as $error) {
        echo "  - {$error}\n";
    }
}

// Display warnings
if ($validation->hasWarnings()) {
    echo "\nWarnings:\n";
    foreach ($validation->warnings as $warning) {
        echo "  - {$warning}\n";
    }
}

// Display successes
if ($validation->hasSuccesses()) {
    echo "\nGood Practices:\n";
    foreach ($validation->successes as $success) {
        echo "  ✓ {$success}\n";
    }
}

// Get metrics
echo "\nMetrics:\n";
echo "Title Length: {$validation->getMetric('title_length')} chars\n";
echo "Description Length: {$validation->getMetric('description_length')} chars\n";
echo "Word Count: {$validation->getMetric('word_count')} words\n";
```

**Output:**
```
✓ SEO validation passed!
Score: 85/100
Grade: B
Color: green

Good Practices:
  ✓ Title length is optimal
  ✓ Description length is optimal
  ✓ Keyword count is optimal
  ✓ Content length is sufficient
  ✓ Featured image is present

Warnings:
  - Add more internal links to improve SEO and user engagement

Metrics:
Title Length: 52 chars
Description Length: 148 chars
Word Count: 850 words
```

---

### Example 9: Real-Time Validation in Admin Form

```blade
{{-- resources/views/admin/articles/edit.blade.php --}}

<div class="form-group">
    <label>SEO Title (Arabic)</label>
    <input type="text" name="seo_title[ar]" class="form-control"
           value="{{ old('seo_title.ar', $article->getTranslation('seo_title', 'ar', false)) }}"
           maxlength="60">
    <small class="text-muted">
        <span id="seo-title-length">0</span>/60 characters (Optimal: 55)
    </small>
</div>

@php
    $validation = app(\App\Services\SeoService::class)->validateSeoData($article, 'ar');
@endphp

<div class="seo-score-widget mt-4 p-3 border rounded" style="background-color: {{ $validation->getScoreColor() === 'green' ? '#d4edda' : ($validation->getScoreColor() === 'yellow' ? '#fff3cd' : '#f8d7da') }}">
    <h4>SEO Score: {{ $validation->seoScore }}/100 (Grade: {{ $validation->getGrade() }})</h4>
    <p>{{ $validation->getSummary() }}</p>
</div>

<script>
// Real-time character count
document.querySelector('input[name="seo_title[ar]"]').addEventListener('input', function() {
    document.getElementById('seo-title-length').textContent = this.value.length;
});
</script>
```

---

## Multi-Language Examples

### Example 10: Generate SEO for All Languages

```php
use App\Models\Article;
use App\Services\SeoService;

$article = Article::find(1);
$seoService = app(SeoService::class);

$locales = ['ar', 'en', 'fr', 'tr', 'ur'];
$seoData = [];

foreach ($locales as $locale) {
    $seoData[$locale] = $seoService->generateSeoData($article, $locale);
}

// Access specific language
echo $seoData['ar']->title;  // Arabic title
echo $seoData['en']->title;  // English title
echo $seoData['fr']->title;  // French title
```

---

### Example 11: Language Switcher with SEO

```blade
{{-- resources/views/articles/show.blade.php --}}

<div class="language-switcher">
    @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français'] as $locale => $name)
        <a href="{{ route('articles.show', ['slug' => $article->slug, 'locale' => $locale]) }}"
           class="btn {{ app()->getLocale() === $locale ? 'btn-primary' : 'btn-secondary' }}"
           hreflang="{{ $locale }}">
            {{ $name }}
        </a>
    @endforeach
</div>

{{-- Add hreflang tags for SEO --}}
@section('head')
    @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $locale)
        <link rel="alternate" hreflang="{{ $locale }}"
              href="{{ route('articles.show', ['slug' => $article->slug, 'locale' => $locale]) }}">
    @endforeach
@endsection
```

---

## Caching Examples

### Example 12: Cache SEO Metadata

```php
use App\Models\Article;
use App\Services\SeoService;
use Illuminate\Support\Facades\Cache;

public function show(string $slug)
{
    $article = Article::published()
        ->with(['author', 'category', 'tags'])
        ->where('slug', $slug)
        ->firstOrFail();

    $locale = app()->getLocale();
    $cacheKey = "article:{$article->id}:seo:{$locale}";

    // Cache for 1 hour
    $seo = Cache::remember($cacheKey, 3600, function () use ($article, $locale) {
        return app(SeoService::class)->generateSeoData($article, $locale);
    });

    return view('articles.show', compact('article', 'seo'));
}
```

---

### Example 13: Cache Invalidation Observer

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
        $this->clearSeoCache($article);
    }

    /**
     * Handle the Article "deleted" event.
     */
    public function deleted(Article $article): void
    {
        $this->clearSeoCache($article);
    }

    /**
     * Clear SEO cache for all locales
     */
    private function clearSeoCache(Article $article): void
    {
        $locales = ['ar', 'en', 'fr', 'tr', 'ur'];

        foreach ($locales as $locale) {
            Cache::forget("article:{$article->id}:seo:{$locale}");
        }
    }
}
```

---

## Admin Dashboard Examples

### Example 14: SEO Suggestions Panel

```blade
{{-- resources/views/admin/articles/partials/seo-suggestions.blade.php --}}

@php
    $suggestions = app(\App\Services\SeoService::class)->generateSuggestions($article, 'ar');
@endphp

@if(count($suggestions) > 0)
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-lightbulb"></i> SEO Improvement Suggestions
        </div>
        <div class="card-body">
            <ul class="list-group list-group-flush">
                @foreach($suggestions as $suggestion)
                    <li class="list-group-item">
                        <i class="fas fa-arrow-right text-primary"></i> {{ $suggestion }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
```

---

### Example 15: SEO Metrics Dashboard

```blade
{{-- resources/views/admin/articles/seo-metrics.blade.php --}}

@php
    $validation = app(\App\Services\SeoService::class)->validateSeoData($article, 'ar');
@endphp

<div class="row">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h1 class="display-4 text-{{ $validation->getScoreColor() }}">
                    {{ $validation->seoScore }}
                </h1>
                <p class="text-muted">SEO Score</p>
                <span class="badge badge-{{ $validation->getScoreColor() }}">
                    Grade {{ $validation->getGrade() }}
                </span>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3>{{ $validation->getMetric('title_length', 0) }}</h3>
                <p class="text-muted">Title Length</p>
                <small>Optimal: 30-60 chars</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3>{{ $validation->getMetric('description_length', 0) }}</h3>
                <p class="text-muted">Description Length</p>
                <small>Optimal: 120-160 chars</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3>{{ $validation->getMetric('word_count', 0) }}</h3>
                <p class="text-muted">Word Count</p>
                <small>Minimum: 300 words</small>
            </div>
        </div>
    </div>
</div>
```

---

## API Examples

### Example 16: API Validation Endpoint

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

        // Validate SEO
        $validation = $this->seoService->validateSeoData($article, $locale);

        // Get suggestions
        $suggestions = $this->seoService->generateSuggestions($article, $locale);

        return response()->json([
            'success' => true,
            'data' => [
                'validation' => $validation->toArray(),
                'suggestions' => $suggestions,
            ],
        ]);
    }
}
```

**Request:**
```bash
GET /api/articles/1/seo/validate?locale=ar
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "validation": {
      "is_valid": true,
      "seo_score": 85,
      "grade": "B",
      "score_color": "green",
      "summary": "Good SEO, but there are some improvements possible.",
      "errors": [],
      "warnings": [
        "Add more internal links to improve SEO and user engagement"
      ],
      "successes": [
        "Title length is optimal",
        "Description length is optimal"
      ],
      "metrics": {
        "title_length": 52,
        "description_length": 148,
        "word_count": 850
      }
    },
    "suggestions": [
      "Add more internal links to improve SEO and user engagement",
      "Use more heading tags (H2, H3) to improve content structure"
    ]
  }
}
```

---

## Sitemap Examples

### Example 17: Generate Article Sitemap

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

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
        $xml .= 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach ($articles as $article) {
            try {
                $entry = $this->seoService->generateSitemapEntry($article);

                $xml .= "  <url>\n";
                $xml .= "    <loc>{$entry['loc']}</loc>\n";
                $xml .= "    <lastmod>{$entry['lastmod']}</lastmod>\n";
                $xml .= "    <changefreq>{$entry['changefreq']}</changefreq>\n";
                $xml .= "    <priority>{$entry['priority']}</priority>\n";

                foreach ($entry['images'] as $image) {
                    if ($image) {
                        $xml .= "    <image:image>\n";
                        $xml .= "      <image:loc>{$image}</image:loc>\n";
                        $xml .= "    </image:image>\n";
                    }
                }

                $xml .= "  </url>\n";
            } catch (\Exception $e) {
                // Skip articles with errors
                continue;
            }
        }

        $xml .= "</urlset>";

        return response($xml, 200)
            ->header('Content-Type', 'application/xml');
    }
}
```

---

## Advanced Examples

### Example 18: Custom JSON-LD Schema

```php
use App\Services\SeoService;

$seoService = app(SeoService::class);

// Generate FAQ Page schema
$article = Article::find(1);

$jsonLd = $seoService->generateJsonLd($article, 'FAQPage');

// Or manually construct custom schema
$customSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => [
        [
            '@type' => 'Question',
            'name' => 'What is Seerah?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'Seerah is the biography of Prophet Muhammad ﷺ.',
            ],
        ],
    ],
];

// Add to metadata
$seo = $seoService->generateSeoData($article);
$seo->jsonLd = array_merge($seo->jsonLd, $customSchema);
```

---

### Example 19: Batch Validation for Articles

```php
use App\Models\Article;
use App\Services\SeoService;

$seoService = app(SeoService::class);
$articles = Article::published()->get();

$report = [];

foreach ($articles as $article) {
    $validation = $seoService->validateSeoData($article, 'ar');

    if (!$validation->isValid || $validation->seoScore < 70) {
        $report[] = [
            'id' => $article->id,
            'title' => $article->getTranslation('title', 'ar'),
            'score' => $validation->seoScore,
            'grade' => $validation->getGrade(),
            'errors' => $validation->errors,
            'warnings' => $validation->warnings,
        ];
    }
}

// Output report
foreach ($report as $item) {
    echo "Article #{$item['id']}: {$item['title']}\n";
    echo "Score: {$item['score']}/100 (Grade: {$item['grade']})\n";
    echo "Issues: " . count($item['errors']) + count($item['warnings']) . "\n\n";
}
```

---

### Example 20: Export SEO Data to JSON

```php
use App\Models\Article;
use App\Services\SeoService;

$article = Article::find(1);
$seoService = app(SeoService::class);

$seo = $seoService->generateSeoData($article, 'ar');

// Export to JSON
$jsonData = json_encode($seo->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// Save to file
file_put_contents(storage_path('seo/article-' . $article->id . '.json'), $jsonData);

// Or return as API response
return response()->json($seo->toArray());
```

---

## Related Documentation

- [SEO Service Technical Guide](./SEO_SERVICE_TECHNICAL_GUIDE.md)
- [SEO Service Integration Guide](./SEO_SERVICE_INTEGRATION.md)
- [SEO Service Quick Reference](./SEO_SERVICE_QUICK_REFERENCE.md)

---

**End of Examples**
