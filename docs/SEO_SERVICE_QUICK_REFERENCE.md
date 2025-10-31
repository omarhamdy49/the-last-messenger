# SEO Service - Quick Reference Card

**Version:** 1.0.0 | **Last Updated:** 2025-11-01 | **Component:** Article Module (Task A7)

Quick reference for common SeoService operations.

---

## Service Initialization

```php
use App\Services\SeoService;

$seoService = app(SeoService::class);
```

---

## Core Methods

### Generate Complete SEO Metadata

```php
$seo = $seoService->generateSeoData($article, 'ar');
```

**Returns:** `SeoMetadata` object with all SEO data

---

### Validate SEO Data

```php
$validation = $seoService->validateSeoData($article, 'ar');
```

**Returns:** `SeoValidationResult` with score, errors, warnings

---

### Generate Suggestions

```php
$suggestions = $seoService->generateSuggestions($article, 'ar');
```

**Returns:** `array` of improvement suggestions

---

### Generate JSON-LD

```php
$jsonLd = $seoService->generateJsonLd($article);
```

**Returns:** JSON-LD `string`

---

### Generate OpenGraph Tags

```php
$ogTags = $seoService->generateOpenGraphTags($article, 'ar');
```

**Returns:** `array` of OpenGraph tags

---

### Generate Twitter Card Tags

```php
$twitterTags = $seoService->generateTwitterCardTags($article, 'ar');
```

**Returns:** `array` of Twitter Card tags

---

### Generate Sitemap Entry

```php
$entry = $seoService->generateSitemapEntry($article);
```

**Returns:** `array` with sitemap data

---

## SeoMetadata Object

### Properties

```php
$seo->title;           // string - Page title
$seo->description;     // string - Meta description
$seo->keywords;        // string - Keywords
$seo->canonicalUrl;    // string - Canonical URL
$seo->featuredImage;   // ?string - Featured image URL
$seo->openGraph;       // array - OpenGraph tags
$seo->twitterCard;     // array - Twitter Card tags
$seo->jsonLd;          // array - JSON-LD data
$seo->metaTags;        // array - Additional meta tags
$seo->locale;          // string - Current locale
```

### Methods

```php
$seo->renderMetaTags();           // Render HTML meta tags
$seo->renderJsonLd();             // Render JSON-LD script
$seo->render();                   // Render everything
$seo->toArray();                  // Convert to array
$seo->isComplete();               // Check if complete
$seo->getMissingFields();         // Get missing fields
$seo->getOpenGraphTag('title');   // Get specific OG tag
$seo->getTwitterCardTag('card');  // Get specific Twitter tag
```

---

## SeoValidationResult Object

### Properties

```php
$validation->isValid;      // bool - Overall validation
$validation->errors;       // array - Critical issues
$validation->warnings;     // array - Recommendations
$validation->successes;    // array - Good practices
$validation->seoScore;     // float - Score (0-100)
$validation->metrics;      // array - Detailed metrics
```

### Methods

```php
$validation->calculateScore();     // Calculate score
$validation->updateScore();        // Recalculate score
$validation->getGrade();           // Get grade (A-F)
$validation->getScoreColor();      // Get color (green/yellow/red)
$validation->getSummary();         // Get summary text
$validation->hasErrors();          // Check for errors
$validation->hasWarnings();        // Check for warnings
$validation->getIssueCount();      // Get total issues
$validation->toArray();            // Convert to array
```

---

## Controller Usage

```php
public function show(string $slug)
{
    $article = Article::published()
        ->with(['author', 'category', 'tags'])
        ->where('slug', $slug)
        ->firstOrFail();

    $seo = app(SeoService::class)->generateSeoData($article, app()->getLocale());

    return view('articles.show', compact('article', 'seo'));
}
```

---

## Blade Template Usage

### Using Component

```blade
<x-seo-meta-tags :metadata="$seo" />
```

### Manual Rendering

```blade
{!! $seo->renderMetaTags() !!}
{!! $seo->renderJsonLd() !!}
```

---

## Caching

```php
use Illuminate\Support\Facades\Cache;

$cacheKey = "article:{$article->id}:seo:{$locale}";

$seo = Cache::remember($cacheKey, 3600, function () use ($article, $locale) {
    return app(SeoService::class)->generateSeoData($article, $locale);
});
```

---

## Validation Scores

| Score | Grade | Color  | Status     |
|-------|-------|--------|------------|
| 90+   | A     | Green  | Excellent  |
| 80-89 | B     | Green  | Good       |
| 70-79 | C     | Yellow | Acceptable |
| 60-69 | D     | Orange | Needs Work |
| 0-59  | F     | Red    | Poor       |

---

## SEO Settings

```php
// Title constraints
'title_min_length' => 30
'title_max_length' => 60
'title_optimal_length' => 55

// Description constraints
'description_min_length' => 120
'description_max_length' => 160
'description_optimal_length' => 155

// Keywords
'keywords_min_count' => 3
'keywords_max_count' => 5

// Content
'min_content_length' => 300 (words)
'min_images' => 1

// OpenGraph Image
'og_image_width' => 1200
'og_image_height' => 630
```

---

## Supported Locales

```php
['ar', 'en', 'fr', 'tr', 'ur']
```

### Locale Codes

| Locale | Code   | Language           |
|--------|--------|--------------------|
| ar     | ar_SA  | Arabic (Saudi)     |
| en     | en_US  | English (US)       |
| fr     | fr_FR  | French (France)    |
| tr     | tr_TR  | Turkish (Turkey)   |
| ur     | ur_PK  | Urdu (Pakistan)    |

---

## Common Patterns

### Pattern 1: Generate + Render

```php
$seo = app(SeoService::class)->generateSeoData($article, 'ar');
echo $seo->render();
```

### Pattern 2: Validate + Suggest

```php
$validation = $seoService->validateSeoData($article, 'ar');
$suggestions = $seoService->generateSuggestions($article, 'ar');
```

### Pattern 3: Cache + Invalidate

```php
// Cache
$seo = Cache::remember("article:{$id}:seo:{$locale}", 3600, fn() => $seoService->generateSeoData($article, $locale));

// Invalidate
Cache::forget("article:{$id}:seo:{$locale}");
```

---

## Validation Checks

✅ **Title Length:** 30-60 characters
✅ **Description Length:** 120-160 characters
✅ **Keywords Count:** 3-5 keywords
✅ **Content Length:** 300+ words
✅ **Featured Image:** Must be present
✅ **Slug:** Must exist
✅ **Internal Links:** 3+ recommended
✅ **Headings:** H2/H3 structure

---

## Exception Handling

```php
use App\Exceptions\SeoException;
use App\Exceptions\SeoValidationException;
use App\Exceptions\SeoGenerationException;

try {
    $seo = $seoService->generateSeoData($article, 'ar');
} catch (SeoValidationException $e) {
    // Validation failed
} catch (SeoGenerationException $e) {
    // Generation failed
} catch (SeoException $e) {
    // General SEO error
}
```

---

## API Endpoints (Example)

```
GET /api/articles/{id}/seo/validate?locale=ar
```

**Response:**
```json
{
  "validation": {
    "is_valid": true,
    "seo_score": 85,
    "grade": "B",
    "errors": [],
    "warnings": ["..."],
    "successes": ["..."]
  },
  "suggestions": ["..."]
}
```

---

## Testing Commands

```bash
# Tinker
php artisan tinker

# Test generation
$article = Article::first();
$seo = app(SeoService::class)->generateSeoData($article, 'ar');
dump($seo->toArray());

# Test validation
$validation = app(SeoService::class)->validateSeoData($article, 'ar');
dump($validation->toArray());
```

---

## OpenGraph Debuggers

- **Facebook:** https://developers.facebook.com/tools/debug/
- **Twitter:** https://cards-dev.twitter.com/validator
- **LinkedIn:** https://www.linkedin.com/post-inspector/

---

## File Locations

```
app/Services/
├── SeoService.php
├── Dtos/
│   ├── SeoMetadata.php
│   └── SeoValidationResult.php

app/Exceptions/
├── SeoException.php
├── SeoValidationException.php
└── SeoGenerationException.php

resources/views/components/
└── seo-meta-tags.blade.php

docs/
├── SEO_SERVICE_TECHNICAL_GUIDE.md
├── SEO_SERVICE_INTEGRATION.md
├── SEO_SERVICE_EXAMPLES.md
└── SEO_SERVICE_QUICK_REFERENCE.md
```

---

## Related Models

- **Article:** Main content model
- **User:** Author/creator
- **Category:** Content categorization
- **Series:** Content grouping
- **Tag:** Content tagging

---

## Performance Tips

1. **Cache metadata** for 1 hour
2. **Eager load** relationships
3. **Use indexes** on slug, status, published_at
4. **Invalidate cache** on article update
5. **Batch process** for sitemaps

---

## Security Notes

- All HTML is escaped automatically
- XSS protection enabled
- Input validation on locales
- URL validation on image paths

---

## Quick Troubleshooting

| Issue | Solution |
|-------|----------|
| Missing featured image | Link storage: `php artisan storage:link` |
| Invalid locale | Use: ar, en, fr, tr, ur only |
| Class not found | Run: `composer dump-autoload` |
| Null translations | Check Spatie Translatable config |
| Cache not clearing | Check observer registration |

---

## Need More Help?

- [Technical Guide](./SEO_SERVICE_TECHNICAL_GUIDE.md) - Complete documentation
- [Integration Guide](./SEO_SERVICE_INTEGRATION.md) - Step-by-step setup
- [Usage Examples](./SEO_SERVICE_EXAMPLES.md) - 20+ code examples

---

**Quick Reference Complete!** 🚀
