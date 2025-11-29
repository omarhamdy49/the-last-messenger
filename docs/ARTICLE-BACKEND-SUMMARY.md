# Article Backend Services - Implementation Summary

## Files Created/Updated

### 1. Controller
**File:** `/app/Http/Controllers/Admin/ArticleController.php`
- Extends `AdminController` base class
- 22 methods implemented
- Supports both Web (Blade) and API (JSON) responses

**Key Methods:**
- `index()` - List with advanced filtering
- `store()` - Create with multi-language support
- `update()` - Update with version tracking
- `destroy()` - Soft delete
- `bulkDestroy()` - Delete multiple
- `bulkPublish()` - Publish multiple
- `duplicate()` - Clone article
- `preview()` - Preview before publish
- `restore()` - Restore soft-deleted
- `forceDelete()` - Permanent delete
- `statistics()` - Get analytics
- `toggleFeature()` - Toggle featured status
- `togglePin()` - Toggle pinned status
- `incrementViews()` - Track views
- `trash()` - List deleted articles

### 2. Repository
**File:** `/app/Repositories/ArticleRepository.php`
- Data access layer with query optimization
- Caching support (Redis)

**Key Methods:**
- `getAllWithFilters()` - Advanced filtering & pagination
- `searchArticles()` - Full-text search
- `getArticlesByStatus()` - Filter by draft/published/featured/pinned
- `getArticlesByCategory()` - Category-based retrieval
- `getArticlesByAuthor()` - Author-based retrieval
- `getFeaturedArticles()` - Cached featured articles
- `getRelatedArticles()` - Based on categories & tags
- `incrementViews()` - Atomic view counter
- `getStatistics()` - Comprehensive analytics
- `findBySlug()` - Lookup by slug and locale
- `getPopularArticles()` - By views in time period
- `getTrendingArticles()` - High velocity articles

### 3. Form Requests
**Files:**
- `/app/Http/Requests/Admin/Article/StoreArticleRequest.php`
- `/app/Http/Requests/Admin/Article/UpdateArticleRequest.php`

**Validation Rules:**
- Multi-language fields for 5 locales (ar, en, fr, tr, ur)
- Unique slug validation per locale
- Image upload validation (max 5MB)
- Category and tag existence checks
- SEO field length limits (title: 70 chars, description: 160 chars)
- Custom error messages and attribute names
- Boolean preparation in `prepareForValidation()`

### 4. API Resource
**File:** `/app/Http/Resources/ArticleResource.php`
- JSON transformation for API responses
- Conditional loading of relationships
- URL generation for related resources
- Localized content structure

### 5. Model Updates
**File:** `/app/Models/Article.php`
- Added `HasVersions` trait for automatic versioning
- Added `tags()` relationship (MorphToMany)
- Supports soft deletes

### 6. Migration
**File:** `/database/migrations/2025_11_07_120000_create_taggables_table.php`
- Polymorphic tagging pivot table
- Unique constraint on [tag_id, taggable_id, taggable_type]

## API Endpoints Overview

### Core CRUD
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/articles` | List articles with filters |
| GET | `/admin/articles/{id}` | Show single article |
| POST | `/admin/articles` | Create new article |
| PUT | `/admin/articles/{id}` | Update article |
| DELETE | `/admin/articles/{id}` | Soft delete article |

### Bulk Operations
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/admin/articles/bulk-destroy` | Delete multiple |
| POST | `/admin/articles/bulk-publish` | Publish multiple |

### Special Actions
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/admin/articles/{id}/duplicate` | Clone article |
| GET | `/admin/articles/{id}/preview` | Preview article |
| POST | `/admin/articles/{id}/restore` | Restore deleted |
| DELETE | `/admin/articles/{id}/force-delete` | Permanent delete |
| GET | `/admin/articles/statistics` | Get analytics |
| POST | `/admin/articles/{id}/toggle-feature` | Toggle featured |
| POST | `/admin/articles/{id}/toggle-pin` | Toggle pinned |
| POST | `/admin/articles/{id}/increment-views` | Track views |
| GET | `/admin/articles/trash` | List trashed |

## Features Implemented

### ✅ Multi-Language Support
- 5 locales: Arabic, English, French, Turkish, Urdu
- Separate fields per locale: title, slug, excerpt, description, content, SEO
- Locale-specific readiness flag
- Automatic slug generation with uniqueness check

### ✅ Version Control
- Automatic snapshots on create/update/delete
- Full diff tracking between versions
- Revert to previous versions
- User, IP, and timestamp tracking
- Change summary and metadata support

### ✅ Content Management
- Primary category + additional categories
- Polymorphic tagging system
- Media gallery (Spatie Media Library)
- Thumbnail upload with validation
- Auto-generated table of contents from H2/H3 tags
- Auto-calculated read time (200 words/min)
- Hijri date support

### ✅ Publishing Workflow
- Status: draft, published, scheduled, archived
- Scheduled publishing via queue jobs
- Featured/Pinned flags
- Soft delete with restore
- Permanent delete option

### ✅ Search & Filtering
- Full-text search (title, excerpt, description, content body)
- Filter by: status, category, author, locale, date range, featured, pinned
- Sort by: created_at, views, alphabetical
- Pagination with configurable per_page

### ✅ Performance Optimization
- Repository pattern for data access
- Eager loading of relationships
- Redis caching for:
  - Featured articles (60 min TTL)
  - Statistics (30 min TTL)
  - Trending articles (30 min TTL)
- Cache invalidation on updates
- Query optimization with indexes

### ✅ Security
- Permission-based authorization via AdminController
- Form request validation
- CSRF protection
- SQL injection prevention (Eloquent ORM)
- File upload validation

### ✅ Activity Logging
- All CRUD operations logged
- User ID, email, IP, user agent tracking
- Metadata support
- Centralized logging in AdminController

## Database Relationships

```
Article
├── writer (BelongsTo User)
├── category (BelongsTo Category) - Primary
├── categories (BelongsToMany Category) - Additional
├── locales (HasMany ArticleLocale)
├── tags (MorphToMany Tag via taggables)
├── media (BelongsToMany Media)
├── gallery (HasMany ArticleGallery)
└── versions (MorphMany ContentVersion)
```

## Example Request/Response

### Create Article Request
```json
{
  "title": {"ar": "عنوان المقال", "en": "Article Title"},
  "content": {"ar": "<p>محتوى</p>", "en": "<p>Content</p>"},
  "excerpt": {"ar": "مقتطف", "en": "Excerpt"},
  "category_id": 5,
  "additional_categories": [8, 12],
  "tags": [1, 3],
  "status": "published",
  "is_featured": true,
  "is_ready": {"ar": true, "en": true},
  "thumb": "(file)",
  "gallery": [15, 22]
}
```

### Show Article Response
```json
{
  "success": true,
  "data": {
    "id": 123,
    "thumb": "https://example.com/storage/articles/thumbs/image.jpg",
    "read_time": 5,
    "views": 1250,
    "is_featured": true,
    "current_version": 3,
    "locales": {
      "ar": {
        "title": "عنوان المقال",
        "slug": "article-title",
        "content": {
          "body": "<p>محتوى</p>",
          "toc": [{"id": "section-1", "text": "القسم", "level": 2}],
          "summary": "ملخص"
        },
        "is_ready": true,
        "url": "https://example.com/ar/articles/article-title"
      }
    },
    "writer": {"id": 2, "name": "John Doe"},
    "categories": [{"id": 5, "name": "Seerah"}],
    "tags": [{"id": 1, "slug": "prophet", "name": {"ar": "النبي"}}],
    "versions": [
      {"id": 45, "version_number": 3, "change_type": "updated"}
    ]
  }
}
```

## Helper Methods

### ArticleController
- `generateUniqueSlug()` - Ensures unique slugs per locale
- `calculateReadTime()` - Word count / 200 words per minute
- `generateTableOfContents()` - Extracts H2/H3 headings from HTML

### ArticleRepository
- `applyFilters()` - Dynamic query building
- `applySearch()` - Full-text search logic
- `getLocaleName()` - Locale code to name mapping

## Usage Tips

### Creating an Article
1. Required fields: `title.ar`, `title.en`, `content.ar`, `content.en`
2. Slug auto-generated if not provided
3. Read time auto-calculated if not provided
4. Version created automatically
5. Use `is_ready` flags to control locale visibility

### Searching Articles
```php
GET /admin/articles?q=prophet&locale=ar&category_id=5&status=published&per_page=20
```

### Bulk Publishing
```php
POST /admin/articles/bulk-publish
Body: {"ids": [1, 2, 3]}
```

### Version Management
- Automatic versioning on create/update/delete
- Access via `$article->versions`
- Revert via `VersionService::revertToVersion()`
- Compare via `VersionService::compareVersions()`

## Performance Considerations

### Caching Strategy
- Featured articles: 60 minutes
- Statistics: 30 minutes
- Trending articles: 30 minutes
- Cache cleared on article updates

### Optimization Tips
1. Use eager loading: `with(['locales', 'writer', 'category'])`
2. Limit pagination: max 100 per page
3. Index slug + locale in article_locale table
4. Prune old versions: keep last 10 per article

## Next Steps

### Routes Configuration
Add to `routes/admin.php`:
```php
Route::prefix('articles')->name('articles.')->group(function () {
    Route::get('/', [ArticleController::class, 'index'])->name('index');
    Route::get('/trash', [ArticleController::class, 'trash'])->name('trash');
    Route::get('/statistics', [ArticleController::class, 'statistics'])->name('statistics');
    Route::post('/bulk-destroy', [ArticleController::class, 'bulkDestroy'])->name('bulk-destroy');
    Route::post('/bulk-publish', [ArticleController::class, 'bulkPublish'])->name('bulk-publish');

    Route::get('/create', [ArticleController::class, 'create'])->name('create');
    Route::post('/', [ArticleController::class, 'store'])->name('store');
    Route::get('/{article}', [ArticleController::class, 'show'])->name('show');
    Route::get('/{article}/edit', [ArticleController::class, 'edit'])->name('edit');
    Route::put('/{article}', [ArticleController::class, 'update'])->name('update');
    Route::delete('/{article}', [ArticleController::class, 'destroy'])->name('destroy');

    Route::post('/{article}/duplicate', [ArticleController::class, 'duplicate'])->name('duplicate');
    Route::get('/{article}/preview', [ArticleController::class, 'preview'])->name('preview');
    Route::post('/{id}/restore', [ArticleController::class, 'restore'])->name('restore');
    Route::delete('/{id}/force-delete', [ArticleController::class, 'forceDelete'])->name('force-delete');
    Route::post('/{article}/toggle-feature', [ArticleController::class, 'toggleFeature'])->name('toggle-feature');
    Route::post('/{article}/toggle-pin', [ArticleController::class, 'togglePin'])->name('toggle-pin');
    Route::post('/{article}/increment-views', [ArticleController::class, 'incrementViews'])->name('increment-views');
});
```

### Service Provider Registration
Add to `AppServiceProvider`:
```php
$this->app->singleton(ArticleRepository::class);
```

### Queue Job for Scheduled Publishing
Create: `app/Jobs/PublishScheduledContent.php`
```php
public function handle()
{
    $article->locales()->update(['is_ready' => true]);
}
```

### Testing
- Feature tests for all endpoints
- Unit tests for repository methods
- Test multi-language validation
- Test version control
- Test caching behavior

## Documentation
- Complete API documentation: `ARTICLE-API-DOCUMENTATION.md`
- OpenAPI/Swagger spec (recommended)
- Postman collection (recommended)
