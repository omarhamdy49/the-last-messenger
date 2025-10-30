# Dynamic Language Management System - Implementation Summary

## Overview
A complete database-driven language management system has been implemented for The Last Messenger application. This system allows dynamic configuration of supported languages without code changes.

## System Components

### 1. Database Migration
**File**: `database/migrations/2025_10_29_200000_create_languages_table.php`

**Schema**:
```sql
CREATE TABLE languages (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(5) UNIQUE NOT NULL,              -- Language code (ar, en, fr, tr, ur)
    name JSON NOT NULL,                           -- Multilingual name
    native_name VARCHAR(100) NOT NULL,            -- Native script name
    direction ENUM('ltr', 'rtl') DEFAULT 'ltr',  -- Text direction
    is_active BOOLEAN DEFAULT TRUE,               -- Active status
    is_default BOOLEAN DEFAULT FALSE,             -- Default language flag
    sort_order SMALLINT UNSIGNED DEFAULT 0,       -- Display order
    flag_icon VARCHAR(10) NULL,                   -- Flag emoji
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_active (is_active),
    INDEX idx_default (is_default),
    INDEX idx_sort (sort_order),
    INDEX idx_active_sort (is_active, sort_order)
);
```

### 2. Language Model
**File**: `app/Domain/Shared/Models/Language.php`

**Key Features**:
- Eloquent model with Spatie Translatable trait
- Smart caching (5-minute TTL)
- Automatic cache invalidation on save/delete
- Ensures only one default language exists

**Public Methods**:
```php
// Static methods
Language::getAllActive()              // Returns Collection of active languages
Language::getDefault()                // Returns default Language model
Language::getActiveCodes()            // Returns array of active language codes
Language::isValidCode(string $code)   // Check if language code is valid

// Scopes
Language::active()                    // Query scope for active languages
Language::default()                   // Query scope for default language
Language::ordered()                   // Query scope for sort order

// Instance method
Language::clearCache()                // Clear all language caches
```

**Example Usage**:
```php
// Get all active languages
$languages = Language::getAllActive();
// Result: Collection of 5 Language models

// Get default language
$defaultLang = Language::getDefault();
// Result: Language model with code 'ar'

// Get active codes
$codes = Language::getActiveCodes();
// Result: ['ar', 'en', 'fr', 'tr', 'ur']

// Validate language code
if (Language::isValidCode('ar')) {
    // Arabic is valid and active
}
```

### 3. Language Seeder
**File**: `database/seeders/LanguageSeeder.php`

**Seeded Languages**:
1. **Arabic (ar)** - RTL, Default
   - Flag: 🇸🇦
   - Name: العربية
   - Sort order: 1

2. **English (en)** - LTR
   - Flag: 🇬🇧
   - Name: English
   - Sort order: 2

3. **French (fr)** - LTR
   - Flag: 🇫🇷
   - Name: Français
   - Sort order: 3

4. **Turkish (tr)** - LTR
   - Flag: 🇹🇷
   - Name: Türkçe
   - Sort order: 4

5. **Urdu (ur)** - RTL
   - Flag: 🇵🇰
   - Name: اردو
   - Sort order: 5

**Usage**:
```bash
# Seed languages
php artisan db:seed --class=LanguageSeeder

# Or include in DatabaseSeeder
php artisan db:seed
```

### 4. Translatable Configuration
**File**: `config/translatable.php`

**Configuration Options**:
```php
return [
    // Dynamic locales loading (loaded from database by ServiceProvider)
    'locales' => ['ar', 'en', 'fr', 'tr', 'ur'],

    // Arabic as fallback (default language)
    'fallback_locale' => 'ar',

    // Enable fallback for missing translations
    'use_fallback' => true,

    // Fallback to any available locale if preferred locale missing
    'fallback_any_locale' => true,

    // Locale separator for accessing translations
    'locale_separator' => ':',

    // Use property fallback for better performance
    'use_property_fallback' => true,

    // Always load translations in array responses
    'to_array_always_loads_translations' => true,

    // Performance: Cache translated attributes
    'cache_translations' => env('CACHE_TRANSLATIONS', true),

    // Lazy load translations (better for JSON storage)
    'eager_load_translations' => false,
];
```

### 5. Translation Service Provider
**File**: `app/Providers/TranslationServiceProvider.php`

**Features**:
- Automatically loads active languages from database on boot
- Caches language configuration (5 minutes)
- Sets application locale dynamically
- Handles migration scenarios gracefully
- Updates config at runtime

**How It Works**:
1. On application boot, queries database for active languages
2. Caches results for 5 minutes to avoid repeated queries
3. Updates `config('translatable.locales')` with active codes
4. Sets default locale from database (Arabic)
5. Registers available locales as singleton service

**Registration**:
Added to `bootstrap/providers.php`:
```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
    App\Providers\TranslationServiceProvider::class, // ✅ Added
];
```

## Usage Examples

### In Controllers
```php
use App\Domain\Shared\Models\Language;

class LanguageController extends Controller
{
    public function index()
    {
        // Get all active languages for language selector
        $languages = Language::getAllActive();

        return response()->json($languages);
    }

    public function setLocale(Request $request)
    {
        $code = $request->input('locale');

        if (Language::isValidCode($code)) {
            app()->setLocale($code);
            session(['locale' => $code]);

            return response()->json(['message' => 'Language changed']);
        }

        return response()->json(['error' => 'Invalid language'], 400);
    }
}
```

### In Models with Translations
```php
use Spatie\Translatable\HasTranslations;

class Article extends Model
{
    use HasTranslations;

    public $translatable = ['title', 'content'];

    protected $casts = [
        'title' => 'array',
        'content' => 'array',
    ];
}

// Usage
$article = new Article();
$article->setTranslations('title', [
    'ar' => 'عنوان المقال',
    'en' => 'Article Title',
    'fr' => 'Titre de l\'article',
    'tr' => 'Makale Başlığı',
    'ur' => 'مضمون کا عنوان',
]);

// Get translation for current locale
echo $article->title; // Returns translation for app()->getLocale()

// Get specific translation
echo $article->getTranslation('title', 'ar'); // Returns 'عنوان المقال'
```

### In Blade Views
```php
// Get available languages for dropdown
@php
    $languages = \App\Domain\Shared\Models\Language::getAllActive();
@endphp

<select name="language">
    @foreach($languages as $lang)
        <option value="{{ $lang->code }}"
                @if(app()->getLocale() === $lang->code) selected @endif>
            {{ $lang->flag_icon }} {{ $lang->native_name }}
        </option>
    @endforeach
</select>
```

### In API Resources
```php
class ArticleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title, // Returns translation for current locale
            'content' => $this->content,
            'translations' => [
                'title' => $this->getTranslations('title'), // All translations
                'content' => $this->getTranslations('content'),
            ],
        ];
    }
}
```

## Database Status

### Migration Status
```bash
✅ Migration executed successfully
   - Table: languages
   - Columns: 10
   - Indexes: 4
```

### Seeded Data
```bash
✅ 5 languages seeded successfully
   - Arabic (ar)    - RTL, Default ✓
   - English (en)   - LTR
   - French (fr)    - LTR
   - Turkish (tr)   - LTR
   - Urdu (ur)      - RTL
```

### Verification Results
```json
{
  "active_languages": ["ar", "en", "fr", "tr", "ur"],
  "default_language": "ar",
  "active_count": 5,
  "rtl_languages": ["ar", "ur"],
  "ltr_languages": ["en", "fr", "tr"]
}
```

## Performance Considerations

### Caching Strategy
1. **Language List Cache** (5 minutes):
   - Key: `languages:active`
   - Stores: All active Language models

2. **Default Language Cache** (5 minutes):
   - Key: `languages:default`
   - Stores: Default Language model

3. **Language Codes Cache** (5 minutes):
   - Key: `languages:codes`
   - Stores: Array of active language codes

4. **System Locales Cache** (5 minutes):
   - Key: `system:locales`
   - Stores: Active language codes for config

5. **Default Locale Cache** (5 minutes):
   - Key: `system:default_locale`
   - Stores: Default language code

### Cache Invalidation
Caches are automatically cleared when:
- A language is created
- A language is updated
- A language is deleted

**Manual Cache Clear**:
```php
Language::clearCache();
```

## Administration Features

### Adding New Languages
```php
Language::create([
    'code' => 'es',
    'name' => [
        'ar' => 'الإسبانية',
        'en' => 'Spanish',
        'fr' => 'Espagnol',
        'tr' => 'İspanyolca',
        'ur' => 'ہسپانوی',
        'es' => 'Español',
    ],
    'native_name' => 'Español',
    'direction' => 'ltr',
    'is_active' => true,
    'sort_order' => 6,
    'flag_icon' => '🇪🇸',
]);
```

### Disabling Languages
```php
$language = Language::where('code', 'fr')->first();
$language->update(['is_active' => false]);
// Cache automatically cleared, language removed from active list
```

### Changing Default Language
```php
$language = Language::where('code', 'en')->first();
$language->update(['is_default' => true]);
// Automatically removes default flag from other languages
// Cache cleared, config updated
```

## Integration with Existing System

### Models Already Using Translations
All existing models with JSON multilingual fields continue to work:
- `Article` (Blog module)
- `Category` (Blog module)
- `Hadith` (Hadith module)
- `HadithBook` (Hadith module)
- `Surah` (Quran module)
- `Verse` (Quran module)
- `SeerahEvent` (Seerah module)
- `Sahabi` (Sahaba module)
- `MediaProgram` (Media module)

### No Breaking Changes
- Existing JSON structure remains unchanged
- All 5 languages still supported
- Default behavior preserved
- Backward compatible with current code

## Testing

### Unit Tests
Create tests for Language model:
```php
// tests/Unit/LanguageTest.php
public function test_get_all_active_languages()
{
    $languages = Language::getAllActive();
    $this->assertCount(5, $languages);
}

public function test_get_default_language()
{
    $default = Language::getDefault();
    $this->assertEquals('ar', $default->code);
    $this->assertTrue($default->is_default);
}

public function test_language_code_validation()
{
    $this->assertTrue(Language::isValidCode('ar'));
    $this->assertTrue(Language::isValidCode('en'));
    $this->assertFalse(Language::isValidCode('xx'));
}
```

### Feature Tests
```php
// tests/Feature/LanguageSwitchTest.php
public function test_can_switch_language()
{
    $response = $this->postJson('/api/language/set', [
        'locale' => 'en'
    ]);

    $response->assertStatus(200);
    $this->assertEquals('en', app()->getLocale());
}
```

## Commands

### Useful Artisan Commands
```bash
# Seed languages
php artisan db:seed --class=LanguageSeeder

# Clear language cache
php artisan tinker
>>> Language::clearCache();

# Get active languages
php artisan tinker
>>> Language::getAllActive();

# Get default language
php artisan tinker
>>> Language::getDefault();

# Check all language data
php artisan tinker
>>> Language::all();
```

## Future Enhancements

### Potential Features
1. **Language API Endpoints**:
   - `GET /api/languages` - List all active languages
   - `POST /api/user/language` - Set user preferred language
   - `GET /api/languages/{code}` - Get language details

2. **Admin Panel Integration**:
   - CRUD interface for language management
   - Enable/disable languages without code changes
   - Change default language via UI
   - Reorder languages in selector

3. **User Preferences**:
   - Store user's preferred language in `users` table
   - Auto-detect browser language
   - Remember language choice across sessions

4. **Translation Management**:
   - Missing translation detection
   - Translation coverage report
   - Bulk translation interface

5. **Advanced Features**:
   - Regional variants (e.g., ar_SA, ar_EG)
   - Automatic translation via API (Google Translate)
   - Translation memory for reuse

## Files Created

1. ✅ `database/migrations/2025_10_29_200000_create_languages_table.php` - Database schema
2. ✅ `app/Domain/Shared/Models/Language.php` - Eloquent model with methods
3. ✅ `database/seeders/LanguageSeeder.php` - Initial data seeder
4. ✅ `config/translatable.php` - Spatie translatable configuration
5. ✅ `app/Providers/TranslationServiceProvider.php` - Dynamic locale loading
6. ✅ `bootstrap/providers.php` - Updated to include TranslationServiceProvider

## Summary

The dynamic language management system is now fully operational:

- ✅ Database table created with proper schema
- ✅ Language model with caching and helper methods
- ✅ 5 languages seeded (Arabic as default)
- ✅ Configuration system for dynamic locale management
- ✅ Service provider for automatic loading on boot
- ✅ Backward compatible with existing translatable models
- ✅ Caching strategy for optimal performance
- ✅ Ready for admin panel integration

The system allows complete flexibility in managing supported languages without requiring code changes. Languages can be added, disabled, or reordered directly in the database, and changes take effect automatically after cache expiration (5 minutes) or immediate cache clearing.
