# Language Management System - Quick Reference

## Common Operations

### Get Available Languages
```php
// Get all active languages
$languages = Language::getAllActive();

// Get language codes only
$codes = Language::getActiveCodes();
// Result: ['ar', 'en', 'fr', 'tr', 'ur']

// Get default language
$default = Language::getDefault();
// Result: Language model (code: 'ar')
```

### Validate Language Code
```php
if (Language::isValidCode($code)) {
    // Language is valid and active
}
```

### Using Translations in Models
```php
// Define translatable attributes
class Article extends Model
{
    use HasTranslations;

    public $translatable = ['title', 'content'];
    protected $casts = ['title' => 'array', 'content' => 'array'];
}

// Set translations
$article->setTranslations('title', [
    'ar' => 'عنوان المقال',
    'en' => 'Article Title',
    'fr' => 'Titre de l\'article',
    'tr' => 'Makale Başlığı',
    'ur' => 'مضمون کا عنوان',
]);

// Get translation
echo $article->title; // Current locale
echo $article->getTranslation('title', 'ar'); // Specific locale
```

### Set Application Locale
```php
// Set for current request
app()->setLocale('en');

// Get current locale
$locale = app()->getLocale();

// Check if locale is supported
if (in_array($locale, Language::getActiveCodes())) {
    // Supported
}
```

### Language Switcher in Blade
```blade
<select name="language" onchange="changeLanguage(this.value)">
    @foreach(\App\Domain\Shared\Models\Language::getAllActive() as $lang)
        <option value="{{ $lang->code }}"
                @selected(app()->getLocale() === $lang->code)>
            {{ $lang->flag_icon }} {{ $lang->native_name }}
        </option>
    @endforeach
</select>
```

### API Response with Translations
```php
// Return current locale translation
return response()->json([
    'title' => $article->title, // Auto-translated to current locale
]);

// Return all translations
return response()->json([
    'title' => $article->getTranslations('title'),
    // Result: {"ar": "...", "en": "...", "fr": "...", "tr": "...", "ur": "..."}
]);
```

## Database Operations

### Add New Language
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

### Update Language
```php
$language = Language::where('code', 'en')->first();
$language->update([
    'is_active' => false, // Disable
    // or
    'is_default' => true, // Set as default
]);
```

### Reorder Languages
```php
Language::where('code', 'en')->update(['sort_order' => 1]);
Language::where('code', 'ar')->update(['sort_order' => 2]);
```

## Cache Management

### Clear Language Cache
```php
Language::clearCache();
```

### Cache is Automatically Cleared When:
- Creating a language
- Updating a language
- Deleting a language

### Cache Keys (TTL: 5 minutes)
- `languages:active` - Active languages collection
- `languages:default` - Default language model
- `languages:codes` - Array of active language codes
- `system:locales` - System locales config
- `system:default_locale` - System default locale

## Artisan Commands

```bash
# Seed languages
php artisan db:seed --class=LanguageSeeder

# Test language operations
php artisan tinker
>>> Language::getAllActive()
>>> Language::getDefault()
>>> Language::getActiveCodes()
>>> Language::isValidCode('ar')

# Clear all caches
php artisan cache:clear
```

## Configuration

### Translatable Config (`config/translatable.php`)
```php
'locales' => ['ar', 'en', 'fr', 'tr', 'ur'],    // Loaded from DB
'fallback_locale' => 'ar',                       // Default: Arabic
'use_fallback' => true,                          // Enable fallback
'fallback_any_locale' => true,                   // Fallback to any if missing
```

## Query Scopes

```php
// Active languages only
Language::active()->get();

// Default language
Language::default()->first();

// Ordered by sort_order
Language::ordered()->get();

// Combined scopes
Language::active()->ordered()->get();
```

## Current System Configuration

- **Total Languages**: 5
- **Default Language**: Arabic (ar)
- **RTL Languages**: Arabic (ar), Urdu (ur)
- **LTR Languages**: English (en), French (fr), Turkish (tr)
- **All Active**: ar, en, fr, tr, ur
- **Cache TTL**: 5 minutes
- **Storage Format**: JSON in database columns

## Supported Languages

| Code | Name (Native) | Name (English) | Direction | Default | Flag |
|------|---------------|----------------|-----------|---------|------|
| ar   | العربية       | Arabic         | RTL       | ✓       | 🇸🇦   |
| en   | English       | English        | LTR       |         | 🇬🇧   |
| fr   | Français      | French         | LTR       |         | 🇫🇷   |
| tr   | Türkçe        | Turkish        | LTR       |         | 🇹🇷   |
| ur   | اردو          | Urdu           | RTL       |         | 🇵🇰   |

## Middleware Example (Optional)

```php
// app/Http/Middleware/SetLocaleFromRequest.php
public function handle($request, Closure $next)
{
    $locale = $request->header('Accept-Language')
           ?? $request->input('lang')
           ?? session('locale')
           ?? Language::getDefault()->code;

    if (Language::isValidCode($locale)) {
        app()->setLocale($locale);
    }

    return $next($request);
}
```

## API Endpoints Example (Future)

```php
// Get all languages
GET /api/languages
Response: [
    {
        "code": "ar",
        "name": {"ar": "العربية", "en": "Arabic", ...},
        "native_name": "العربية",
        "direction": "rtl",
        "is_default": true,
        "flag_icon": "🇸🇦"
    },
    ...
]

// Set user language preference
POST /api/user/language
Body: {"locale": "en"}
Response: {"message": "Language preference updated"}
```

## Troubleshooting

### Cache Not Clearing
```php
// Manual cache clear
Language::clearCache();
Cache::flush();
php artisan cache:clear
```

### Languages Not Loading
```php
// Check if table exists
Schema::hasTable('languages')

// Check data
Language::count()

// Reseed
php artisan db:seed --class=LanguageSeeder
```

### Translations Not Working
```php
// Check model uses trait
use Spatie\Translatable\HasTranslations;

// Check attribute is marked translatable
public $translatable = ['title', 'content'];

// Check casting
protected $casts = ['title' => 'array'];
```

## Documentation Files

- **Complete Guide**: `LANGUAGE-MANAGEMENT-SYSTEM.md`
- **Quick Reference**: This file
- **Migration**: `database/migrations/2025_10_29_200000_create_languages_table.php`
- **Model**: `app/Domain/Shared/Models/Language.php`
- **Seeder**: `database/seeders/LanguageSeeder.php`
