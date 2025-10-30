<?php

/**
 * VALIDATION RULES & HELPER SERVICES IMPLEMENTATION SUMMARY
 * ==========================================================
 *
 * This document provides a comprehensive overview of the multi-language
 * validation and helper system implementation.
 *
 * CREATED FILES:
 * -------------
 *
 * 1. Custom Validation Rules (app/Rules/)
 *    - TranslatableRequired.php         ✅ Custom rule to validate required default language
 *    - TranslatableArray.php            ✅ Custom rule to validate translation array structure
 *
 * 2. Models (app/Domain/Shared/Models/)
 *    - Language.php                     ✅ Language model with caching (already existed, enhanced)
 *
 * 3. Services (app/Domain/Shared/Services/)
 *    - TranslationHelperService.php     ✅ Comprehensive translation utilities
 *    - LanguageService.php              ✅ Language management service
 *
 * 4. Traits (app/Domain/Shared/Traits/)
 *    - HasTranslationHelpers.php        ✅ Model trait for enhanced translation methods
 *
 * 5. Base Request Classes (app/Http/Requests/)
 *    - BaseTranslatableRequest.php      ✅ Base class for translatable validation
 *
 * 6. Updated Form Requests
 *    - StoreArticleRequest.php          ✅ Updated to use BaseTranslatableRequest
 *    - UpdateArticleRequest.php         ✅ Updated to use BaseTranslatableRequest
 *
 * 7. Middleware (app/Http/Middleware/)
 *    - SetLocale.php                    ✅ Already well-implemented with user preference
 *
 * 8. Database (database/migrations/ & database/seeders/)
 *    - 2025_10_29_000000_create_languages_table.php   ✅ Languages table migration
 *    - LanguageSeeder.php               ✅ Already existed with all 5 languages
 *
 * FEATURES IMPLEMENTED:
 * --------------------
 *
 * 1. CUSTOM VALIDATION RULES
 *
 *    TranslatableRequired:
 *    - Validates that default language (Arabic) is provided
 *    - Customizable default language and field name
 *    - Clean error messages
 *
 *    Example usage:
 *    ```php
 *    'title' => ['required', 'array', new TranslatableRequired('ar', 'title')],
 *    ```
 *
 *    TranslatableArray:
 *    - Validates translation array structure
 *    - Checks for invalid language codes
 *    - Optionally enforces all languages
 *
 *    Example usage:
 *    ```php
 *    'content' => ['array', new TranslatableArray(['ar', 'en'], false, 'content')],
 *    ```
 *
 * 2. LANGUAGE MODEL ENHANCEMENTS
 *
 *    Static methods added:
 *    - getDefaultCached()           // Get default language with caching
 *    - getActiveCached()            // Get active languages with caching
 *    - getAllCached()               // Get all languages with caching
 *    - getDefaultCode()             // Get default language code ('ar')
 *    - getActiveCodes()             // Get array of active codes
 *    - isValidCode($code)           // Check if code is valid
 *    - clearCache()                 // Clear language cache
 *
 *    Auto-caching:
 *    - 5-minute cache TTL
 *    - Auto-clear on save/delete
 *    - Ensures only one default language
 *
 * 3. TRANSLATION HELPER SERVICE
 *
 *    Core methods:
 *    - getActiveLanguages()                                    // Get active languages
 *    - getDefaultLanguage()                                    // Get default language
 *    - validateTranslation($data, $field, $requireDefault)     // Validate translations
 *    - fillMissingTranslations($data, $useDefaultValue)        // Auto-fill missing
 *    - sanitizeTranslations($data, $removeEmpty)               // Clean translations
 *    - getTranslation($translations, $locale, $fallback)       // Get with fallback
 *    - prepareForStorage($data, $translatableFields)           // Prepare for DB
 *    - createEmptyTranslationArray($defaultValue)              // Create empty array
 *    - isTranslationComplete($translations)                    // Check completeness
 *    - getMissingTranslations($translations)                   // Get missing codes
 *
 *    Example usage:
 *    ```php
 *    $helper = app(TranslationHelperService::class);
 *
 *    // Validate
 *    $result = $helper->validateTranslation($data, 'title', true);
 *    if (!$result['valid']) {
 *        // Handle errors: $result['errors']
 *    }
 *
 *    // Sanitize and fill
 *    $sanitized = $helper->sanitizeTranslations($data['title']);
 *    $filled = $helper->fillMissingTranslations($sanitized);
 *
 *    // Get translation with fallback
 *    $title = $helper->getTranslation($article->title, 'en', 'ar');
 *    ```
 *
 * 4. LANGUAGE SERVICE
 *
 *    Management methods:
 *    - getAllLanguages()                               // Get all languages
 *    - getActiveLanguages()                            // Get active languages
 *    - getDefaultLanguage()                            // Get default language
 *    - getLanguageByCode($code)                        // Get by code
 *    - isValidLanguageCode($code)                      // Validate code
 *    - getLanguageCodes($activeOnly = true)            // Get codes array
 *    - getRtlLanguages()                               // Get RTL languages (ar, ur)
 *    - getLtrLanguages()                               // Get LTR languages (en, fr, tr)
 *    - isRtl($code)                                    // Check if RTL
 *    - createOrUpdateLanguage($data)                   // CRUD operations
 *    - toggleLanguageStatus($code)                     // Toggle active status
 *    - setDefaultLanguage($code)                       // Set default
 *    - seedDefaultLanguages()                          // Seed 5 languages
 *
 *    Example usage:
 *    ```php
 *    $languageService = app(LanguageService::class);
 *
 *    // Check if RTL
 *    if ($languageService->isRtl('ar')) {
 *        // Set RTL layout
 *    }
 *
 *    // Get active codes
 *    $codes = $languageService->getLanguageCodes(); // ['ar', 'en', 'fr', 'tr', 'ur']
 *    ```
 *
 * 5. HAS TRANSLATION HELPERS TRAIT
 *
 *    Model enhancement methods:
 *    - getTranslationWithFallback($key, $locale, $useFallback)    // Enhanced getTranslation
 *    - trans($key)                                                // Get for current locale
 *    - getAllTranslations($key)                                   // Get all translations
 *    - hasTranslation($key, $locale)                              // Check if exists
 *    - hasCompleteTranslations($key)                              // Check all languages
 *    - getMissingTranslationCodes($key)                           // Get missing codes
 *    - setTranslationForLocale($key, $locale, $value)             // Set translation
 *    - fillMissingTranslations($key)                              // Fill with default
 *    - sanitizeTranslatableAttributes()                           // Clean before save
 *
 *    Usage in models:
 *    ```php
 *    use App\Domain\Shared\Traits\HasTranslationHelpers;
 *    use Spatie\Translatable\HasTranslations;
 *
 *    class Article extends Model
 *    {
 *        use HasTranslations, HasTranslationHelpers;
 *
 *        public $translatable = ['title', 'content'];
 *    }
 *
 *    // In controller/service
 *    $title = $article->trans('title');                    // Get for current locale
 *    $missing = $article->getMissingTranslationCodes('title'); // ['fr', 'tr']
 *    $article->fillMissingTranslations('title');           // Auto-fill
 *    ```
 *
 * 6. BASE TRANSLATABLE REQUEST
 *
 *    Abstract base class for form requests with translatable fields:
 *
 *    Abstract methods (must implement):
 *    - translatableFields()                    // Define translatable fields
 *    - requiredTranslatableFields()            // Define required fields (for create)
 *
 *    Provided methods:
 *    - translatableRules($field, $required, $extraRules)       // Generate rules
 *    - translatableMessages($field, $displayName)              // Generate messages
 *    - mergeTranslatableRules($customRules)                    // Merge all rules
 *
 *    Example usage:
 *    ```php
 *    class StoreArticleRequest extends BaseTranslatableRequest
 *    {
 *        protected function translatableFields(): array
 *        {
 *            return [
 *                'title' => ['string', 'max:500'],
 *                'content' => ['string'],
 *            ];
 *        }
 *
 *        protected function requiredTranslatableFields(): array
 *        {
 *            return ['title', 'content'];
 *        }
 *
 *        public function rules(): array
 *        {
 *            $translatableRules = $this->mergeTranslatableRules();
 *
 *            return array_merge($translatableRules, [
 *                'slug' => ['required', 'string', 'unique:articles'],
 *                'status' => ['required', 'string'],
 *            ]);
 *        }
 *    }
 *    ```
 *
 *    Auto-features:
 *    - Auto-generates validation for all active languages
 *    - Auto-sanitizes input (trims whitespace)
 *    - Validates default language is required
 *    - Validates no invalid language codes
 *
 * 7. SET LOCALE MIDDLEWARE (ALREADY IMPLEMENTED)
 *
 *    Priority order:
 *    1. Authenticated user preference (User->language)
 *    2. Session locale
 *    3. Accept-Language header
 *    4. Default locale ('ar')
 *
 *    Features:
 *    - Parses Accept-Language header correctly
 *    - Auto-stores user preference in session
 *    - Validates against active languages
 *    - Caches system language settings
 *
 * 8. DATABASE MIGRATIONS & SEEDERS
 *
 *    Languages table structure:
 *    - id (primary key)
 *    - code (unique, 5 chars)          // 'ar', 'en', 'fr', 'tr', 'ur'
 *    - name (JSON)                     // Multi-language name
 *    - native_name (string)            // Native name display
 *    - direction (enum)                // 'ltr' or 'rtl'
 *    - is_active (boolean)             // Active status
 *    - is_default (boolean)            // Default language (only one)
 *    - sort_order (integer)            // Display order
 *    - flag_icon (string)              // Flag emoji
 *    - timestamps
 *
 *    LanguageSeeder seeds 5 languages:
 *    1. Arabic (ar)    - RTL, Default
 *    2. English (en)   - LTR
 *    3. French (fr)    - LTR
 *    4. Turkish (tr)   - LTR
 *    5. Urdu (ur)      - RTL
 *
 * USAGE EXAMPLES:
 * --------------
 *
 * 1. IN CONTROLLERS (using services):
 *
 *    ```php
 *    use App\Domain\Shared\Services\TranslationHelperService;
 *
 *    public function store(StoreArticleRequest $request)
 *    {
 *        $helper = app(TranslationHelperService::class);
 *
 *        // Prepare data
 *        $data = $request->validated();
 *        $data = $helper->prepareForStorage($data, ['title', 'content', 'excerpt']);
 *
 *        // Create article
 *        $article = $this->articleService->create($data);
 *
 *        return $this->createdResponse($article);
 *    }
 *    ```
 *
 * 2. IN MODELS (using trait):
 *
 *    ```php
 *    use App\Domain\Shared\Traits\HasTranslationHelpers;
 *
 *    class Article extends Model
 *    {
 *        use HasTranslations, HasTranslationHelpers;
 *
 *        public $translatable = ['title', 'content', 'excerpt'];
 *
 *        protected static function boot()
 *        {
 *            parent::boot();
 *
 *            static::saving(function ($article) {
 *                $article->sanitizeTranslatableAttributes();
 *            });
 *        }
 *    }
 *
 *    // Usage
 *    $article->trans('title');                           // Get for current locale
 *    $article->hasCompleteTranslations('content');       // Check completeness
 *    $article->fillMissingTranslations('excerpt');       // Auto-fill
 *    ```
 *
 * 3. IN FORM REQUESTS (extending BaseTranslatableRequest):
 *
 *    ```php
 *    class StoreHadithRequest extends BaseTranslatableRequest
 *    {
 *        protected function translatableFields(): array
 *        {
 *            return [
 *                'arabic_text' => ['string'],
 *                'translation' => ['string'],
 *                'explanation' => ['string', 'max:1000'],
 *            ];
 *        }
 *
 *        protected function requiredTranslatableFields(): array
 *        {
 *            return ['arabic_text', 'translation'];
 *        }
 *
 *        public function rules(): array
 *        {
 *            return $this->mergeTranslatableRules([
 *                'book_id' => ['required', 'integer', 'exists:hadith_books,id'],
 *                'hadith_number' => ['required', 'integer'],
 *            ]);
 *        }
 *    }
 *    ```
 *
 * 4. IN VIEWS/RESOURCES (getting translations):
 *
 *    ```php
 *    // In API Resource
 *    public function toArray($request)
 *    {
 *        return [
 *            'id' => $this->id,
 *            'title' => $this->trans('title'),              // Current locale
 *            'all_titles' => $this->getAllTranslations('title'), // All languages
 *            'missing_translations' => $this->getMissingTranslationCodes('content'),
 *        ];
 *    }
 *    ```
 *
 * TESTING:
 * -------
 *
 * Run migrations and seed languages:
 * ```bash
 * php artisan migrate
 * php artisan db:seed --class=LanguageSeeder
 * ```
 *
 * Test validation:
 * ```bash
 * # POST /api/articles
 * {
 *   "title": {
 *     "ar": "المقالة الأولى",
 *     "en": "First Article",
 *     "fr": "Premier article"
 *   },
 *   "content": {
 *     "ar": "المحتوى العربي"
 *     // Other languages optional
 *   },
 *   "slug": "first-article"
 * }
 * ```
 *
 * Test locale detection:
 * ```bash
 * # With header
 * curl -H "Accept-Language: en" http://localhost/api/articles
 *
 * # With authenticated user (user->language = 'fr')
 * # Will use 'fr' locale
 * ```
 *
 * NEXT STEPS:
 * ----------
 *
 * 1. Update other Form Requests to extend BaseTranslatableRequest:
 *    - Hadith requests
 *    - Media Program requests
 *    - Seerah Event requests
 *    - Sahabi requests
 *    - Quran requests
 *
 * 2. Add HasTranslationHelpers trait to all translatable models:
 *    - Article, Category, Tag, Series
 *    - Hadith, HadithBook, HadithChapter
 *    - MediaProgram, MediaEpisode
 *    - SeerahEvent
 *    - Sahabi
 *    - Surah, Verse, Tafseer
 *
 * 3. Use TranslationHelperService in services for data preparation
 *
 * 4. Update API Resources to use trans() method for current locale
 *
 * 5. Create admin endpoints for language management:
 *    - GET /api/admin/languages
 *    - POST /api/admin/languages/{code}/toggle
 *    - POST /api/admin/languages/{code}/set-default
 *
 * FILES CREATED/UPDATED:
 * ---------------------
 * ✅ app/Rules/TranslatableRequired.php                              (NEW)
 * ✅ app/Rules/TranslatableArray.php                                 (NEW)
 * ✅ app/Domain/Shared/Models/Language.php                           (ENHANCED)
 * ✅ app/Domain/Shared/Services/TranslationHelperService.php         (NEW)
 * ✅ app/Domain/Shared/Services/LanguageService.php                  (NEW)
 * ✅ app/Domain/Shared/Traits/HasTranslationHelpers.php              (NEW)
 * ✅ app/Http/Requests/BaseTranslatableRequest.php                   (NEW)
 * ✅ app/Http/Requests/StoreArticleRequest.php                       (UPDATED)
 * ✅ app/Http/Requests/UpdateArticleRequest.php                      (UPDATED)
 * ✅ app/Http/Middleware/SetLocale.php                               (ALREADY GOOD)
 * ✅ database/migrations/2025_10_29_000000_create_languages_table.php (NEW)
 * ✅ database/seeders/LanguageSeeder.php                             (ALREADY EXISTS)
 *
 * ARCHITECTURE BENEFITS:
 * ---------------------
 *
 * 1. DRY Principle:
 *    - Reusable validation rules across all translatable requests
 *    - Common translation logic in service layer
 *    - Trait-based model enhancements
 *
 * 2. Separation of Concerns:
 *    - Validation rules isolated in dedicated classes
 *    - Business logic in services
 *    - Model helpers in traits
 *
 * 3. Performance:
 *    - Language data cached for 5 minutes
 *    - Auto-invalidation on changes
 *    - Minimal database queries
 *
 * 4. Maintainability:
 *    - Centralized translation logic
 *    - Easy to extend for new languages
 *    - Consistent validation across modules
 *
 * 5. Type Safety:
 *    - Strict types declared
 *    - PHPDoc annotations
 *    - IDE-friendly methods
 *
 * AUTHOR: Claude Code (Agent 6)
 * DATE: 2025-10-29
 */
