# Validation Helpers & Translation Services - Usage Guide

This guide provides practical examples for using the multi-language validation and helper system.

## Table of Contents

1. [Quick Start](#quick-start)
2. [Custom Validation Rules](#custom-validation-rules)
3. [Form Requests](#form-requests)
4. [Translation Helper Service](#translation-helper-service)
5. [Language Service](#language-service)
6. [Model Trait](#model-trait)
7. [API Examples](#api-examples)

## Quick Start

### 1. Run Migration and Seeder

```bash
# Run migration (if not already done)
php artisan migrate

# Seed languages
php artisan db:seed --class=LanguageSeeder
```

### 2. Verify Languages

```bash
php artisan tinker
>>> use App\Domain\Shared\Models\Language;
>>> Language::getActiveCodes();
=> ["ar", "en", "fr", "tr", "ur"]
```

## Custom Validation Rules

### TranslatableRequired

Validates that the default language (Arabic) is provided.

```php
use App\Rules\TranslatableRequired;

// In Form Request
public function rules(): array
{
    return [
        'title' => ['required', 'array', new TranslatableRequired('ar', 'title')],
        'title.ar' => ['required', 'string', 'max:255'],
        'title.en' => ['nullable', 'string', 'max:255'],
        // ...
    ];
}
```

### TranslatableArray

Validates translation array structure and language codes.

```php
use App\Rules\TranslatableArray;

public function rules(): array
{
    return [
        'content' => [
            'array',
            new TranslatableArray(['ar', 'en', 'fr', 'tr', 'ur'], false, 'content')
        ],
    ];
}
```

## Form Requests

### Extending BaseTranslatableRequest

The easiest way to handle translatable fields is to extend `BaseTranslatableRequest`.

#### Example: Store Request

```php
<?php

namespace App\Http\Requests;

use App\Domain\Blog\Enums\ArticleStatus;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends BaseTranslatableRequest
{
    /**
     * Define translatable fields with their extra validation rules
     */
    protected function translatableFields(): array
    {
        return [
            'title' => ['string', 'max:500'],    // Field => extra rules
            'excerpt' => ['string'],
            'content' => ['string'],
        ];
    }

    /**
     * Define which translatable fields are required (default language must be provided)
     */
    protected function requiredTranslatableFields(): array
    {
        return ['title', 'content']; // excerpt is optional
    }

    /**
     * Merge with non-translatable fields
     */
    public function rules(): array
    {
        $translatableRules = $this->mergeTranslatableRules();

        return array_merge($translatableRules, [
            'slug' => ['required', 'string', 'max:255', 'unique:articles,slug'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['required', 'string', Rule::in(ArticleStatus::values())],
        ]);
    }
}
```

This automatically generates rules like:
```php
[
    'title' => ['required', 'array', new TranslatableRequired('ar', 'title')],
    'title.ar' => ['required', 'string', 'max:500'],
    'title.en' => ['nullable', 'string', 'max:500'],
    'title.fr' => ['nullable', 'string', 'max:500'],
    'title.tr' => ['nullable', 'string', 'max:500'],
    'title.ur' => ['nullable', 'string', 'max:500'],
    'content' => ['required', 'array', new TranslatableRequired('ar', 'content')],
    'content.ar' => ['required', 'string'],
    'content.en' => ['nullable', 'string'],
    // ... and so on
]
```

#### Example: Update Request

```php
class UpdateArticleRequest extends BaseTranslatableRequest
{
    protected function translatableFields(): array
    {
        return [
            'title' => ['string', 'max:500'],
            'content' => ['string'],
        ];
    }

    // For updates, all fields are optional
    protected function requiredTranslatableFields(): array
    {
        return [];
    }

    public function rules(): array
    {
        $articleId = $this->route('article');

        // Only validate translatable fields that are present
        $translatableRules = [];
        foreach ($this->translatableFields() as $field => $extraRules) {
            if ($this->has($field)) {
                $translatableRules = array_merge(
                    $translatableRules,
                    $this->translatableRules($field, false, $extraRules)
                );
            }
        }

        return array_merge($translatableRules, [
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('articles', 'slug')->ignore($articleId)],
            'status' => ['sometimes', 'string', Rule::in(ArticleStatus::values())],
        ]);
    }
}
```

## Translation Helper Service

The `TranslationHelperService` provides utilities for working with multi-language data.

### Basic Usage

```php
use App\Domain\Shared\Services\TranslationHelperService;

$helper = app(TranslationHelperService::class);
```

### Get Active Languages

```php
$activeLanguages = $helper->getActiveLanguages();
// Returns Collection of Language models

$activeCodes = $helper->getActiveLanguageCodes();
// Returns: ['ar', 'en', 'fr', 'tr', 'ur']

$defaultCode = $helper->getDefaultLanguageCode();
// Returns: 'ar'
```

### Validate Translations

```php
$data = [
    'title' => [
        'ar' => 'مقالة جديدة',
        'en' => 'New Article',
    ]
];

$result = $helper->validateTranslation($data, 'title', true);

if ($result['valid']) {
    // Validation passed
} else {
    // Handle errors
    foreach ($result['errors'] as $error) {
        echo $error;
    }
}
```

### Fill Missing Translations

```php
$translations = [
    'ar' => 'مرحبا',
    'en' => 'Hello',
    // fr, tr, ur are missing
];

// Fill with empty string
$filled = $helper->fillMissingTranslations($translations, false);
// Returns: ['ar' => 'مرحبا', 'en' => 'Hello', 'fr' => '', 'tr' => '', 'ur' => '']

// Fill with default language value
$filled = $helper->fillMissingTranslations($translations, true);
// Returns: ['ar' => 'مرحبا', 'en' => 'Hello', 'fr' => 'مرحبا', 'tr' => 'مرحبا', 'ur' => 'مرحبا']
```

### Sanitize Translations

```php
$translations = [
    'ar' => '  مرحبا  ',
    'en' => '  Hello  ',
    'fr' => '',
];

// Remove whitespace
$sanitized = $helper->sanitizeTranslations($translations, false);
// Returns: ['ar' => 'مرحبا', 'en' => 'Hello', 'fr' => '']

// Remove whitespace AND empty values
$sanitized = $helper->sanitizeTranslations($translations, true);
// Returns: ['ar' => 'مرحبا', 'en' => 'Hello']
```

### Get Translation with Fallback

```php
$translations = [
    'ar' => 'مرحبا',
    'en' => 'Hello',
    'fr' => 'Bonjour',
];

// Get French translation
$text = $helper->getTranslation($translations, 'fr');
// Returns: 'Bonjour'

// Get Turkish (missing) - falls back to Arabic
$text = $helper->getTranslation($translations, 'tr', 'ar');
// Returns: 'مرحبا'

// Get for current app locale
$text = $helper->getTranslation($translations);
// Returns translation for app()->getLocale()
```

### Prepare for Storage

```php
$data = $request->validated();

// Prepare translatable fields
$data = $helper->prepareForStorage($data, ['title', 'content', 'excerpt']);

// This will:
// 1. Sanitize translations (trim whitespace)
// 2. Fill missing translations with empty strings
// 3. Ensure consistent structure

$article = Article::create($data);
```

### Check Translation Completeness

```php
$translations = ['ar' => 'مرحبا', 'en' => 'Hello'];

$isComplete = $helper->isTranslationComplete($translations);
// Returns: false (missing fr, tr, ur)

$missing = $helper->getMissingTranslations($translations);
// Returns: ['fr', 'tr', 'ur']
```

## Language Service

The `LanguageService` handles language management operations.

### Basic Usage

```php
use App\Domain\Shared\Services\LanguageService;

$languageService = app(LanguageService::class);
```

### Get Languages

```php
// All languages (including inactive)
$allLanguages = $languageService->getAllLanguages();

// Active languages only
$activeLanguages = $languageService->getActiveLanguages();

// Default language
$defaultLanguage = $languageService->getDefaultLanguage();
// Returns Language model

// Get specific language
$arabic = $languageService->getLanguageByCode('ar');
```

### Check Language Properties

```php
// Check if code is valid
if ($languageService->isValidLanguageCode('ar')) {
    // Valid language
}

// Check if RTL
if ($languageService->isRtl('ar')) {
    // Apply RTL layout
}

// Get RTL languages
$rtlLanguages = $languageService->getRtlLanguages();
// Returns: Collection with 'ar' and 'ur'

// Get LTR languages
$ltrLanguages = $languageService->getLtrLanguages();
// Returns: Collection with 'en', 'fr', 'tr'
```

### Language Management (Admin)

```php
// Toggle language status
$languageService->toggleLanguageStatus('fr');

// Set default language
$languageService->setDefaultLanguage('en');

// Create/update language
$language = $languageService->createOrUpdateLanguage([
    'code' => 'es',
    'name' => [
        'ar' => 'الإسبانية',
        'en' => 'Spanish',
        'fr' => 'Espagnol',
        'tr' => 'İspanyolca',
        'ur' => 'ہسپانوی',
    ],
    'native_name' => 'Español',
    'direction' => 'ltr',
    'is_active' => true,
    'is_default' => false,
    'sort_order' => 6,
]);
```

## Model Trait

The `HasTranslationHelpers` trait provides enhanced translation methods for models.

### Add to Models

```php
use App\Domain\Shared\Traits\HasTranslationHelpers;
use Spatie\Translatable\HasTranslations;

class Article extends Model
{
    use HasTranslations, HasTranslationHelpers;

    public $translatable = ['title', 'content', 'excerpt'];
}
```

### Usage in Controllers/Services

```php
// Get translation for current locale
$title = $article->trans('title');
// If app locale is 'en', returns English title with fallback to Arabic

// Get all translations
$allTitles = $article->getAllTranslations('title');
// Returns: ['ar' => '...', 'en' => '...', 'fr' => '...', 'tr' => '...', 'ur' => '...']

// Check if translation exists
if ($article->hasTranslation('content', 'fr')) {
    // French translation exists
}

// Check if all translations are complete
if ($article->hasCompleteTranslations('title')) {
    // All 5 languages have values
}

// Get missing translation codes
$missing = $article->getMissingTranslationCodes('content');
// Returns: ['fr', 'tr'] (if French and Turkish are missing)

// Set specific translation
$article->setTranslationForLocale('title', 'fr', 'Titre français');
$article->save();

// Auto-fill missing translations with default language
$article->fillMissingTranslations('excerpt');
$article->save();
```

### Auto-Sanitize on Save

```php
class Article extends Model
{
    use HasTranslations, HasTranslationHelpers;

    public $translatable = ['title', 'content'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($article) {
            $article->sanitizeTranslatableAttributes();
        });
    }
}
```

## API Examples

### Create Article with Translations

```bash
POST /api/articles
Content-Type: application/json

{
  "title": {
    "ar": "مقالة جديدة عن السيرة النبوية",
    "en": "New Article about Prophet's Biography",
    "fr": "Nouvel article sur la biographie du Prophète",
    "tr": "Peygamberin Biyografisi Hakkında Yeni Makale",
    "ur": "نبوی سوانح حیات کے بارے میں نیا مضمون"
  },
  "content": {
    "ar": "المحتوى العربي...",
    "en": "English content...",
    "fr": "Contenu français...",
    "tr": "Türkçe içerik...",
    "ur": "اردو مواد..."
  },
  "excerpt": {
    "ar": "ملخص المقالة",
    "en": "Article summary"
  },
  "slug": "prophet-biography-article",
  "status": "published",
  "category_id": 1
}
```

### Get Article in Specific Language

```bash
GET /api/articles/1
Accept-Language: fr

# Response will use French translations with Arabic fallback
{
  "id": 1,
  "title": "Nouvel article sur la biographie du Prophète",
  "content": "Contenu français...",
  ...
}
```

### Update Specific Language

```bash
PATCH /api/articles/1
Content-Type: application/json

{
  "title": {
    "fr": "Titre français mis à jour"
  }
}

# Only French title will be updated, other languages remain unchanged
```

### Validation Error Example

```bash
POST /api/articles
Content-Type: application/json

{
  "title": {
    "en": "Title without Arabic"
  }
}

# Response: 422 Unprocessable Entity
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "title.ar": ["The title in ar is required."]
  }
}
```

## Controller Example

```php
use App\Domain\Shared\Services\TranslationHelperService;

class ArticleController extends Controller
{
    public function __construct(
        private ArticleService $articleService,
        private TranslationHelperService $translationHelper
    ) {}

    public function store(StoreArticleRequest $request)
    {
        // Data is already validated by StoreArticleRequest
        $data = $request->validated();

        // Optional: Prepare translations (sanitize, fill missing)
        $data = $this->translationHelper->prepareForStorage(
            $data,
            ['title', 'content', 'excerpt']
        );

        // Create article
        $article = $this->articleService->create($data);

        return $this->createdResponse($article);
    }

    public function show(int $id)
    {
        $article = $this->articleService->findOrFail($id);

        // Get title in current locale
        $localizedTitle = $article->trans('title');

        // Check translation completeness
        $missingTranslations = $article->getMissingTranslationCodes('content');

        return $this->successResponse([
            'article' => new ArticleResource($article),
            'localized_title' => $localizedTitle,
            'missing_translations' => $missingTranslations,
        ]);
    }
}
```

## Resource Example

```php
class ArticleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,

            // Current locale translation
            'title' => $this->trans('title'),
            'content' => $this->trans('content'),
            'excerpt' => $this->trans('excerpt'),

            // All translations (for admin panel)
            'all_translations' => $this->when($request->user()?->isAdmin(), [
                'title' => $this->getAllTranslations('title'),
                'content' => $this->getAllTranslations('content'),
                'excerpt' => $this->getAllTranslations('excerpt'),
            ]),

            // Translation status
            'translation_complete' => $this->hasCompleteTranslations('content'),
            'missing_languages' => $this->getMissingTranslationCodes('content'),

            'status' => $this->status,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
        ];
    }
}
```

## Testing

### Feature Test Example

```php
public function test_article_requires_arabic_translation()
{
    $response = $this->postJson('/api/articles', [
        'title' => [
            'en' => 'English Title',
            // Missing Arabic
        ],
        'content' => [
            'ar' => 'المحتوى',
        ],
        'slug' => 'test-article',
        'status' => 'draft',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['title.ar']);
}

public function test_can_create_article_with_all_languages()
{
    $response = $this->postJson('/api/articles', [
        'title' => [
            'ar' => 'عنوان عربي',
            'en' => 'English Title',
            'fr' => 'Titre français',
            'tr' => 'Türkçe başlık',
            'ur' => 'اردو عنوان',
        ],
        'content' => [
            'ar' => 'محتوى عربي',
            'en' => 'English content',
            // Other languages optional
        ],
        'slug' => 'test-article',
        'status' => 'published',
    ]);

    $response->assertStatus(201)
             ->assertJsonPath('data.title', 'عنوان عربي'); // Default locale
}
```

## Best Practices

1. **Always validate Arabic (default language)** as required
2. **Use BaseTranslatableRequest** for consistent validation
3. **Sanitize translations** before storage using `prepareForStorage()`
4. **Cache language data** - it's already cached automatically
5. **Use trait methods** like `trans()` instead of manual locale handling
6. **Provide meaningful field names** to custom rules for better error messages
7. **Test with multiple languages** to ensure fallback logic works
8. **Document which fields are translatable** in your API documentation

## Troubleshooting

### "Language code is invalid"
- Ensure you've run `LanguageSeeder`
- Check that the language is active: `Language::where('code', 'XX')->first()->is_active`

### "Default language not found"
- Run: `php artisan db:seed --class=LanguageSeeder`
- Verify: `Language::getDefault()` returns a result

### Validation passes but data not saved
- Check that model has `HasTranslations` trait
- Ensure field is in `$translatable` array
- Verify field type is JSON in migration

### Cache not clearing
- Manually clear: `Language::clearCache()`
- Check Redis connection in `.env`

## Additional Resources

- See `VALIDATION-HELPERS-IMPLEMENTATION-SUMMARY.php` for complete technical documentation
- Check `app/Domain/Shared/Services/TranslationHelperService.php` for all available methods
- Review `app/Http/Requests/BaseTranslatableRequest.php` for validation internals
