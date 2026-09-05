# Translation Patterns Documentation

**Created:** 2026-09-05  
**Status:** Active Standard  
**Last Updated:** 2026-09-05

## Overview

This document describes the three translation patterns currently in use across the codebase and establishes the standardized approach for handling multi-language content. The Last Messenger backend supports translations in 5 languages (Arabic, English, Turkish, French, Urdu) using translation tables rather than JSON columns.

## Table of Contents

1. [Translation Architecture](#translation-architecture)
2. [The Three Patterns](#the-three-patterns)
3. [Standard Pattern (Pattern A)](#standard-pattern-pattern-a)
4. [Migration Guides](#migration-guides)
5. [Critical Warnings](#critical-warnings)
6. [Examples](#examples)
7. [Testing Guidelines](#testing-guidelines)

---

## Translation Architecture

### Translation Tables (NOT JSON Columns)

**CRITICAL:** Translations live in sibling `*_translations` tables (`category_translations`, `tag_translations`, `series_translations`, `seerah_event_translations`, etc.), **NOT** in JSON columns on the main table.

The old Spatie JSON columns (`categories.name`, `tags.name`, `series.title/description`) were **DROPPED** by the 2026-03-10 migrations.

**Exception:** Articles are mid-migration and still carry both JSON columns and `article_locale` table. However, the authoritative read path is the `article_locale` table.

### HasLocalizedContent Trait

Models using translation tables implement the `HasLocalizedContent` trait (`app/Domain/Shared/Traits/HasLocalizedContent.php`), which provides:

- **Read methods:** `getTranslation($field, $locale)`, `getTranslations($field)`, `translationFor($locale)`
- **Write method:** `syncTranslations($data)`
- **Relationship:** `translations()` HasMany relationship
- **Backward compatibility:** Drop-in replacement for Spatie's `HasTranslations` trait

Models must implement three abstract methods:
```php
abstract public function getTranslationModelClass(): string;
abstract public function getTranslatableFields(): array;
public function getTranslationForeignKey(): string; // optional override
```

---

## The Three Patterns

### Pattern A: syncTranslations() — **STANDARD** ✅

**Status:** Current standard — correct and recommended  
**Usage:** 10+ controllers/services (Category, Tag, Series, SeerahEvent, MediaProgram, etc.)  
**Location:** Service layer

**Description:**
Services call `syncTranslations()` directly on models with `HasLocalizedContent` trait. This is the canonical approach for writing translations.

**Example:**
```php
// Service layer (e.g., CategoryService::createCategory)
$category = $this->repository->create([
    'slug' => $data->slug,
    'type' => $data->type,
    'parent_id' => $data->parentId,
]);

$category->syncTranslations([
    'name' => ['ar' => 'السيرة النبوية', 'en' => 'Prophetic Biography'],
    'description' => ['ar' => 'وصف', 'en' => 'Description'],
]);
```

**Why this is standard:**
- ✅ Direct, explicit, easy to understand
- ✅ Works seamlessly with `HasLocalizedContent`
- ✅ Handles empty/missing locales gracefully
- ✅ Transactional safety when wrapped in DB::transaction
- ✅ Automatically sets `is_ready` flag when non-empty content exists

---

### Pattern B: translationsFrom() Helper — **REDUNDANT** ⚠️

**Status:** Functional but redundant — should be simplified to Pattern A  
**Usage:** 2 controllers (StaticPageController, HomeModuleCardController)  
**Location:** Controller layer

**Description:**
Controllers extract translations via a private `translationsFrom()` helper method, then pass to service which calls `syncTranslations()`. This adds an unnecessary indirection layer.

**Example:**
```php
// Controller (e.g., StaticPageController)
public function store(StoreStaticPageRequest $request): RedirectResponse
{
    $validated = $request->validated();
    
    $this->service->create(
        $this->attributesFrom($validated, $request),
        $this->translationsFrom($validated),  // ← Extra helper
    );
}

private function translationsFrom(array $validated): array
{
    return [
        'title' => $validated['title'],
        'body' => $validated['body'] ?? [],
        'meta_description' => $validated['meta_description'] ?? [],
    ];
}

// Service (e.g., StaticPageService::create)
public function create(array $attributes, array $translations): StaticPage
{
    $page = $this->repository->create($attributes);
    $page->syncTranslations($translations);  // ← Eventually calls Pattern A
    return $page;
}
```

**Why this should be migrated:**
- ⚠️ Adds unnecessary controller layer logic
- ⚠️ Separates translation data from other data artificially
- ⚠️ Creates confusion about where translation logic lives
- ✅ Still safe (eventually calls `syncTranslations()`)
- ✅ Works correctly with translation tables

---

### Pattern C: getTranslation/setTranslation — **DANGEROUS** ❌

**Status:** DANGEROUS for writes; safe for reads only when used with `HasLocalizedContent`  
**Usage:** Legacy code, some Site controllers, Blade views  
**Location:** Controllers, views

**Description:**
Direct calls to `getTranslation()`, `setTranslation()`, `getTranslations()` — the old Spatie JSON-column methods. These are **ACTIVELY DANGEROUS** for models whose JSON columns were dropped.

**Example (DANGEROUS — DO NOT USE):**
```php
// WRONG — DO NOT DO THIS
$category->setTranslation('name', 'ar', 'السيرة النبوية');
$category->setTranslation('name', 'en', 'Prophetic Biography');
$category->save();
```

**Why this is dangerous:**
- ❌ For models with dropped JSON columns (Category, Tag, Series, etc.), this will:
  - Silently return `null` if `HasTranslations` trait is still on the model
  - Throw an exception if the trait was removed
  - **NEVER** write to the translation table
- ❌ For Articles (which still have JSON columns), this reads from the WRONG source (JSON column instead of `article_locale` table)
- ❌ Causes stale data when translations updated via repository but read via `getTranslation()`
- ❌ No safety for missing locales
- ❌ Requires explicit `save()` call (easy to forget)

**When Pattern C IS safe (READ ONLY):**
```php
// ✅ SAFE — Read-only via HasLocalizedContent
$title = $category->getTranslation('name', app()->getLocale());
$allNames = $category->getTranslations('name'); // Returns ['ar' => '...', 'en' => '...']
```

The `HasLocalizedContent` trait provides backward-compatible `getTranslation()` and `getTranslations()` methods that read from translation tables correctly. Blade views using these methods continue to work.

---

## Standard Pattern (Pattern A)

### Implementation Checklist

When implementing translations in a new module or updating an existing one:

#### 1. Model Setup

```php
use App\Domain\Shared\Traits\HasLocalizedContent;

class YourModel extends Model
{
    use HasLocalizedContent;
    
    protected $fillable = ['slug', 'is_active', /* non-translatable fields */];
    
    public function getTranslationModelClass(): string
    {
        return YourModelTranslation::class;
    }
    
    public function getTranslatableFields(): array
    {
        return ['title', 'description', 'content'];
    }
    
    // Optional: only if FK doesn't follow convention
    public function getTranslationForeignKey(): string
    {
        return 'your_model_id';
    }
}
```

#### 2. Translation Model

```php
use Illuminate\Database\Eloquent\Model;

class YourModelTranslation extends Model
{
    protected $fillable = [
        'your_model_id',
        'locale',
        'title',
        'description',
        'content',
        'is_ready',
    ];
    
    protected $casts = [
        'is_ready' => 'boolean',
    ];
    
    public $timestamps = false;
}
```

#### 3. Migration

```php
Schema::create('your_models', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();
    $table->boolean('is_active')->default(true);
    // ... other non-translatable columns
    $table->timestamps();
});

Schema::create('your_model_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('your_model_id')
        ->constrained('your_models')
        ->cascadeOnDelete();
    $table->char('locale', 2);
    $table->string('title');
    $table->text('description')->nullable();
    $table->longText('content')->nullable();
    $table->boolean('is_ready')->default(false);
    
    $table->unique(['your_model_id', 'locale']);
    $table->index(['locale', 'is_ready']);
    
    // FULLTEXT search (optional)
    $table->fullText(['title', 'description', 'content']);
});
```

#### 4. Service Layer (CREATE)

```php
public function create(CreateYourModelData $data): YourModel
{
    return DB::transaction(function () use ($data) {
        // Create main record with non-translatable fields
        /** @var YourModel $model */
        $model = $this->repository->create([
            'slug' => $data->slug,
            'is_active' => $data->isActive,
        ]);
        
        // Sync translations
        $model->syncTranslations([
            'title' => $data->title,
            'description' => $data->description,
            'content' => $data->content,
        ]);
        
        return $model;
    });
}
```

#### 5. Service Layer (UPDATE)

```php
public function update(int $id, UpdateYourModelData $data): YourModel
{
    return DB::transaction(function () use ($id, $data) {
        // Update main record
        $this->repository->update($id, [
            'slug' => $data->slug,
            'is_active' => $data->isActive,
        ]);
        
        /** @var YourModel $model */
        $model = $this->repository->find($id);
        
        // Sync translations
        $model->syncTranslations([
            'title' => $data->title,
            'description' => $data->description,
            'content' => $data->content,
        ]);
        
        return $model;
    });
}
```

#### 6. Controller Layer

```php
public function store(StoreYourModelRequest $request): RedirectResponse
{
    $validated = $request->validated();
    
    try {
        $model = $this->service->create(
            CreateYourModelData::fromArray($validated)
        );
        
        return redirect()
            ->route('admin.your-models.index')
            ->with('success', __('admin.created_successfully'));
    } catch (\Throwable $exception) {
        \Log::error('your_model.create_failed', [
            'user_id' => auth()->id(),
            'error' => $exception->getMessage(),
        ]);
        
        return back()
            ->withInput()
            ->with('error', __('admin.operation_failed'));
    }
}
```

#### 7. Blade View (Language Tabs)

```blade
<div class="language-tabs" x-data="{ activeTab: 'ar' }">
    {{-- Language tabs --}}
    <div class="flex gap-2 mb-4">
        @foreach(['ar', 'en', 'tr', 'fr', 'ur'] as $lang)
            <button 
                type="button"
                @click="activeTab = '{{ $lang }}'" 
                :class="activeTab === '{{ $lang }}' ? 'active' : ''"
                class="px-4 py-2 rounded-md transition-colors">
                {{ __("languages.$lang") }}
            </button>
        @endforeach
    </div>
    
    {{-- Title input for each language --}}
    @foreach(['ar', 'en', 'tr', 'fr', 'ur'] as $lang)
        <div x-show="activeTab === '{{ $lang }}'">
            <label class="block text-sm font-medium mb-2">
                {{ __('admin.title') }} ({{ strtoupper($lang) }})
            </label>
            <input 
                type="text" 
                name="title[{{ $lang }}]" 
                value="{{ old("title.$lang", $model->getTranslation('title', $lang)) }}"
                class="w-full rounded-md border-gray-300">
        </div>
    @endforeach
</div>
```

---

## Migration Guides

### Migrating from Pattern B to Pattern A

**Before (Pattern B):**
```php
// Controller
public function store(StoreRequest $request): RedirectResponse
{
    $this->service->create(
        $this->attributesFrom($request->validated(), $request),
        $this->translationsFrom($request->validated()),
    );
}

private function translationsFrom(array $validated): array
{
    return [
        'title' => $validated['title'],
        'description' => $validated['description'] ?? [],
    ];
}

// Service
public function create(array $attributes, array $translations): Model
{
    $model = $this->repository->create($attributes);
    $model->syncTranslations($translations);
    return $model;
}
```

**After (Pattern A):**
```php
// Controller
public function store(StoreRequest $request): RedirectResponse
{
    $this->service->create(
        CreateModelData::fromArray($request->validated())
    );
}

// Service
public function create(CreateModelData $data): Model
{
    return DB::transaction(function () use ($data) {
        $model = $this->repository->create([
            'slug' => $data->slug,
            'is_active' => $data->isActive,
        ]);
        
        $model->syncTranslations([
            'title' => $data->title,
            'description' => $data->description,
        ]);
        
        return $model;
    });
}
```

**Benefits:**
- ✅ Removes unnecessary controller helper
- ✅ Centralizes translation logic in service
- ✅ Uses typed DTOs for better IDE support
- ✅ Explicit DB transaction wrapping

---

### Migrating from Pattern C to Pattern A

**Before (Pattern C — DANGEROUS):**
```php
// Service or Controller
$category = new Category(['slug' => $data['slug']]);
$category->setTranslation('name', 'ar', $data['name']['ar']);
$category->setTranslation('name', 'en', $data['name']['en']);
$category->save();
```

**After (Pattern A):**
```php
// Service
$category = $this->repository->create(['slug' => $data['slug']]);
$category->syncTranslations([
    'name' => [
        'ar' => $data['name']['ar'],
        'en' => $data['name']['en'],
    ],
]);
```

**Read-only usage (Pattern C) — KEEP AS IS:**
```php
// Views, Controllers (read-only)
$title = $category->getTranslation('name', app()->getLocale()); // ✅ SAFE
$allNames = $category->getTranslations('name'); // ✅ SAFE
```

**The `HasLocalizedContent` trait provides backward-compatible read methods that work correctly with translation tables.**

---

## Critical Warnings

### ⚠️ DO NOT Do These Things

1. **NEVER use setTranslation() for writes**
   ```php
   // ❌ WRONG
   $model->setTranslation('name', 'ar', 'السيرة');
   $model->save();
   ```

2. **NEVER select JSON columns that don't exist**
   ```php
   // ❌ WRONG — 'name' column doesn't exist on categories table
   Category::select('id', 'name')->get();
   
   // ✅ CORRECT — eager-load translations
   Category::with('translations:id,category_id,locale,name')->get();
   ```

3. **NEVER JSON_EXTRACT dropped columns**
   ```php
   // ❌ WRONG
   ->whereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%$term%"])
   
   // ✅ CORRECT — search via relation
   ->whereHas('translations', fn($q) => 
       $q->where('name', 'LIKE', "%$term%")
   )
   ```

4. **NEVER assume JSON columns still exist**
   ```php
   // ❌ WRONG
   'name' => ['ar' => 'required', 'en' => 'required'] // Validation for JSON column
   
   // ✅ CORRECT
   'name.ar' => 'required|string',
   'name.en' => 'required|string',
   ```

5. **NEVER add HasTranslations trait to models with translation tables**
   ```php
   // ❌ WRONG
   use Spatie\Translatable\HasTranslations;
   
   // ✅ CORRECT
   use App\Domain\Shared\Traits\HasLocalizedContent;
   ```

### ✅ DO These Things

1. **ALWAYS use syncTranslations() for writes**
2. **ALWAYS eager-load translations** when displaying lists
3. **ALWAYS search via whereHas('translations')** for translated fields
4. **ALWAYS wrap create/update in DB::transaction()** when both main record and translations are modified
5. **ALWAYS use HasLocalizedContent trait** for models with translation tables

---

## Examples

### Complete Service Example (SeerahEventService)

```php
use Illuminate\Support\Facades\DB;

public function createEvent(CreateSeerahEventData $data): SeerahEvent
{
    $event = DB::transaction(function () use ($data) {
        // 1. Create main record
        /** @var SeerahEvent $event */
        $event = $this->repository->create([
            'slug' => $data->slug,
            'hijri_date' => $data->hijriDate,
            'gregorian_date' => $data->gregorianDate,
            'importance' => $data->importance ?? EventImportance::MEDIUM->value,
            'status' => $data->status ?? SeerahEventStatus::PUBLISHED->value,
        ]);
        
        // 2. Sync translations
        $event->syncTranslations([
            'title' => $data->title,
            'description' => $data->description,
            'content' => $data->content,
        ]);
        
        // 3. Sync child relations if needed
        $this->syncTimelineItems($event, $data->timelineItems);
        
        return $event;
    });
    
    // 4. Clear cache after transaction commits
    $this->repository->clearCache();
    
    return $event;
}

private function syncTimelineItems(SeerahEvent $event, array $rows): void
{
    $event->timelineItems()->delete();
    
    foreach (array_values($rows) as $index => $row) {
        $item = SeerahEventTimelineItem::create([
            'seerah_event_id' => $event->id,
            'display_order' => $index + 1,
        ]);
        
        // Child models also use syncTranslations
        $item->syncTranslations([
            'title' => (array) ($row['title'] ?? []),
            'description' => (array) ($row['description'] ?? []),
        ]);
    }
}
```

### Complete Controller Example (CategoryController)

```php
public function store(StoreCategoryRequest $request): RedirectResponse
{
    $validated = $request->validated();
    
    try {
        $category = $this->service->createCategory(
            CreateCategoryData::fromArray($validated)
        );
        
        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('admin.category_created'));
    } catch (\Throwable $exception) {
        \Log::error('admin.category.create_failed', [
            'user_id' => auth()->id(),
            'error' => $exception->getMessage(),
        ]);
        
        return back()
            ->withInput()
            ->with('error', __('admin.operation_failed'));
    }
}
```

### Eager Loading Translations

```php
// ✅ CORRECT — List view with translations
$categories = Category::query()
    ->with('translations:id,category_id,locale,name')
    ->orderBy('display_order')
    ->paginate(15);

// ✅ CORRECT — Single record with all translation data
$category = Category::query()
    ->with('translations')
    ->findOrFail($id);

// In Blade
@foreach($categories as $category)
    <td>{{ $category->getTranslation('name', app()->getLocale()) }}</td>
@endforeach
```

### Searching Translated Content

```php
// ✅ CORRECT — Repository search method
public function search(string $term, int $perPage = 15): LengthAwarePaginator
{
    return $this->model()
        ->with('translations')
        ->whereHas('translations', function ($query) use ($term) {
            $query->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('description', 'LIKE', "%{$term}%");
        })
        ->orderBy('display_order')
        ->paginate($perPage);
}
```

---

## Testing Guidelines

### Unit Tests for Services

```php
public function test_creates_category_with_translations(): void
{
    $data = CreateCategoryData::fromArray([
        'slug' => 'test-category',
        'type' => CategoryType::ARTICLE->value,
        'name' => [
            'ar' => 'تصنيف تجريبي',
            'en' => 'Test Category',
        ],
        'description' => [
            'ar' => 'وصف تجريبي',
            'en' => 'Test Description',
        ],
    ]);
    
    $category = $this->service->createCategory($data);
    
    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'slug' => 'test-category',
    ]);
    
    $this->assertDatabaseHas('category_translations', [
        'category_id' => $category->id,
        'locale' => 'ar',
        'name' => 'تصنيف تجريبي',
    ]);
    
    $this->assertDatabaseHas('category_translations', [
        'category_id' => $category->id,
        'locale' => 'en',
        'name' => 'Test Category',
    ]);
}

public function test_reads_translations_correctly(): void
{
    $category = Category::factory()->create();
    $category->syncTranslations([
        'name' => [
            'ar' => 'السيرة النبوية',
            'en' => 'Prophetic Biography',
        ],
    ]);
    
    $this->assertEquals('السيرة النبوية', $category->getTranslation('name', 'ar'));
    $this->assertEquals('Prophetic Biography', $category->getTranslation('name', 'en'));
    
    $allNames = $category->getTranslations('name');
    $this->assertArrayHasKey('ar', $allNames);
    $this->assertArrayHasKey('en', $allNames);
}
```

### Feature Tests for Controllers

```php
public function test_admin_can_create_category_with_translations(): void
{
    $this->actingAs($this->admin);
    
    $response = $this->post(route('admin.categories.store'), [
        'slug' => 'new-category',
        'type' => CategoryType::ARTICLE->value,
        'name' => [
            'ar' => 'تصنيف جديد',
            'en' => 'New Category',
        ],
        'description' => [
            'ar' => 'وصف جديد',
            'en' => 'New Description',
        ],
    ]);
    
    $response->assertRedirect(route('admin.categories.index'));
    $response->assertSessionHas('success');
    
    $category = Category::where('slug', 'new-category')->first();
    $this->assertNotNull($category);
    
    $this->assertEquals('تصنيف جديد', $category->getTranslation('name', 'ar'));
    $this->assertEquals('New Category', $category->getTranslation('name', 'en'));
}
```

---

## Summary

### Quick Reference

| Pattern | Status | Use For | Avoid For |
|---------|--------|---------|-----------|
| **A: syncTranslations()** | ✅ Standard | All writes | Nothing |
| **B: translationsFrom()** | ⚠️ Redundant | Nothing (migrate to A) | New code |
| **C: getTranslation/setTranslation** | ❌ Dangerous | Reads only | Writes |

### Decision Tree

```
Need to write translations?
├─ Yes → Use Pattern A (syncTranslations)
└─ No (read-only)
   ├─ In view/display → Pattern C read methods OK (getTranslation)
   └─ In service/logic → Use Pattern A for consistency
```

### Key Principles

1. **One source of truth:** Translation tables, not JSON columns
2. **One write method:** `syncTranslations()` only
3. **One trait:** `HasLocalizedContent` for all translated models
4. **Transactional safety:** Wrap in `DB::transaction()` when creating/updating both main record and translations
5. **Eager loading:** Always `with('translations')` in list queries
6. **Search via relations:** Use `whereHas('translations')` for translated field searches

---

**For questions or clarifications, refer to:**
- `app/Domain/Shared/Traits/HasLocalizedContent.php` — Trait implementation
- `app/Domain/Blog/Services/CategoryService.php` — Example service
- `app/Domain/Seerah/Services/SeerahEventService.php` — Complex example with nested translations
- `CLAUDE.md` — Project-wide conventions
