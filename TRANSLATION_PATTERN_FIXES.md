# Translation Pattern Fixes - Blog Module Controllers & Services

## Overview
Fixed critical issue where Series/Category/Tag updates weren't persisting because controllers and services were still using old JSON column patterns after the translation migration moved translatable fields to dedicated translation tables.

## Root Cause
The translation migration (Phase 2-4) moved translatable fields from JSON columns in parent tables to separate translation tables:
- `series.title`, `series.description` → `series_translations`
- `categories.name`, `categories.description` → `categories_translations`
- `tags.name`, `tags.description` → `tags_translations`

However, the controllers and services weren't updated to:
1. Separate translatable data from model data
2. Call `syncTranslations()` on the model after create/update
3. Query translation tables instead of non-existent JSON columns

## Fixed Issues

### SeriesController (`app/Http/Controllers/Admin/SeriesController.php`)
**Issue:** Series updates weren't saving translations to the database

**Fixes:**
- `index()` method: Changed search from `whereJsonContains()` to `whereHas('translations')`
- `store()` method: Now separates translatable data and calls `syncTranslations()` after create
- `update()` method: Now calls `syncTranslations()` for translatable fields (THIS WAS THE MAIN BUG)
- `show()` and `edit()` methods: Added eager loading of `translations` relationship

**Status:** ✅ FIXED

### CategoryService (`app/Domain/Blog/Services/CategoryService.php`)
**Issue:** CategoryService was not handling translations during create/update operations

**Fixes:**
- Overrode `create()` method to delegate to `createCategory()` which handles translations
- Overrode `update()` method to delegate to `updateCategory()` which handles translations
- Updated `createCategory()` to separate translatable data and call `syncTranslations()`
- Updated `updateCategory()` to sync translations after updating parent model

**Status:** ✅ FIXED

### CategoryRepository (`app/Domain/Blog/Repositories/CategoryRepository.php`)
**Issue:** Search queries were using JSON functions on non-existent JSON columns

**Fixes:**
- Changed `paginateWithFilters()` search from `JSON_UNQUOTE(JSON_EXTRACT(...))` to `whereHas('translations')`

**Status:** ✅ FIXED

### TagService (`app/Domain/Blog/Services/TagService.php`)
**Issue:** TagService was not handling translations during create/update operations

**Fixes:**
- Updated `create()` method to separate translatable data and call `syncTranslations()`
- Updated `update()` method to sync translations after updating parent model
- Added slug generation from translatable title field

**Status:** ✅ FIXED

### TagRepository (`app/Domain/Blog/Repositories/TagRepository.php`)
**Issue:** Multiple methods were using JSON queries on non-existent columns

**Fixes:**
- Changed `paginate()` search from JSON queries to `whereHas('translations')`
- Changed `findDuplicate()` to search in translation table
- Changed `searchForAutocomplete()` to search in translation table

**Status:** ✅ FIXED

## Modules Still Needing Review

### Reciter (Phase 4) ⚠️
**Status:** Needs fixing
- `ReciterController.index()` uses `whereJsonContains()` for search
- Need to check `ReciterService` for translation handling in create/update

### Person/Genealogy (Phase 4) ⚠️
**Status:** Unknown - no admin controller found

### SeerahParticipant (Phase 4) ⚠️
**Status:** Unknown - need to verify

### Language (Phase 4) ⚠️
**Status:** Unknown - `LanguageSettingsController` might handle this differently

### Hadith/HadithSource/HadithTheme/HadithChapter (Phase 3) ✅
**Status:** Does not use `HasLocalizedContent` trait - uses different pattern

## Testing

### Test Series Update
```php
// Test that Series update properly saves translations
$series = Series::find(1);
$series->update(['slug' => 'new-slug']);
$series->syncTranslations([
    'title' => [
        'ar' => 'عنوان جديد',
        'en' => 'New Title'
    ]
]);

// Verify both parent table and translation table updated
$series->fresh();
$series->load('translations');
```

### Test Category Create
```php
// Test that Category create properly saves translations
$category = Category::create([
    'slug' => 'test-category',
    'type' => 'blog',
    'status' => 'active'
]);

$category->syncTranslations([
    'name' => ['ar' => 'اسم الفئة', 'en' => 'Category Name'],
    'description' => ['ar' => '...', 'en' => '...']
]);
```

### Test Tag Search
```php
// Test that Tag search works via translation table
$tags = Tag::whereHas('translations', function($q) {
    $q->where('name', 'LIKE', '%search%');
})->get();
```

## Pattern Reference

### Working Pattern (SeerahEvent & Sahabi - Phase 1)
Both controllers use a helper method `extractTranslatableData()` that:
1. Extracts translatable fields from request data
2. Removes them from model data
3. Creates/updates model with non-translatable data
4. Calls `syncTranslations()` with extracted data

### Implemented Pattern (Series, Category, Tag)
Similar to above but done inline:
1. Iterate through validated data
2. Separate into `$translatableData` and `$nonTranslatableData` arrays
3. Create/update model with non-translatable data
4. Call `syncTranslations()` with translatable data

## Files Modified
1. `app/Http/Controllers/Admin/SeriesController.php`
2. `app/Domain/Blog/Services/CategoryService.php`
3. `app/Domain/Blog/Repositories/CategoryRepository.php`
4. `app/Domain/Blog/Services/TagService.php`
5. `app/Domain/Blog/Repositories/TagRepository.php`

## Commit
- Commit: `f775b02` - fix(blog): implement hasLocalizedContent translation pattern in controllers and services

## Next Steps
1. Fix `ReciterController` and `ReciterService`
2. Verify `SeerahParticipant`, `Person`, `Language` modules
3. Run comprehensive integration tests for all CRUD operations
4. Update API responses to ensure translations are properly serialized
5. Test search functionality across all affected modules
