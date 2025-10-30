# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**The Last Messenger** is a comprehensive Islamic digital encyclopedia backend built with Laravel 12.x, focusing on Prophet Muhammad ﷺ biography (Seerah), Hadith database, Quran module, media programs, timeline events, and genealogy. This is the **Laravel backend** for the project, with a separate UI project containing HTML prototypes.

**Current Status**: ~70% complete (7 of 10 major modules implemented)

## Architecture

This project follows **Domain-Driven Design (DDD)** with strict architectural patterns:

### Directory Structure
```
app/
├── Domain/                    # Business logic organized by domain
│   ├── Blog/                 # Articles, categories, series (✅ Complete)
│   ├── Hadith/               # Hadith database with narrators (✅ Complete)
│   ├── Media/                # Video programs Netflix-style (✅ Complete)
│   ├── Seerah/               # Timeline events biography (✅ Complete)
│   ├── Sahaba/               # Companions profiles (✅ Complete)
│   ├── Quran/                # Quran verses & surahs (✅ Complete)
│   ├── Genealogy/            # Family tree (✅ Complete)
│   └── Shared/               # Base classes, contracts, traits
├── Http/                      # API layer
│   ├── Controllers/Api/      # Thin controllers
│   ├── Resources/            # JSON transformers
│   └── Requests/             # Form validation
├── Support/                   # Utilities
│   └── Traits/ApiResponse.php
└── Providers/
    └── RepositoryServiceProvider.php  # Dependency injection bindings
```

### Key Architectural Patterns

**1. Repository Pattern (Strict)**
- ALL data access goes through repositories
- NEVER use Eloquent models directly in controllers
- Repositories handle caching, eager loading, query optimization

```php
// ❌ WRONG: Direct model usage
$articles = Article::where('status', 'published')->get();

// ✅ CORRECT: Through repository
$articles = $this->articleRepository->getPublished();
```

**2. Service Layer**
- Business logic resides in service classes
- Controllers should be thin (validation, calling service, returning response)
- Services coordinate multiple repositories and complex operations

```php
// ❌ WRONG: Logic in controller
public function store(Request $request) {
    $article = Article::create($request->all());
    $article->tags()->attach($request->tags);
    Cache::forget('articles');
    return response()->json($article);
}

// ✅ CORRECT: Logic in service
public function store(StoreArticleRequest $request) {
    $article = $this->articleService->create($request->validated());
    return $this->createdResponse($article);
}
```

**3. Dependency Injection**
- Repository interfaces bound in `RepositoryServiceProvider`
- Always inject interfaces, not concrete classes
- Constructor injection for dependencies

### Each Domain Module Contains
```
Domain/ModuleName/
├── Models/                    # Eloquent models
├── Repositories/
│   ├── Interface.php         # Repository contract
│   └── Implementation.php    # Concrete repository
├── Services/                  # Business logic
├── Enums/                     # Value objects
└── DTOs/                      # Data transfer objects (if needed)
```

## Common Development Commands

### Setup & Installation
```bash
# Complete project setup (one-time)
composer setup

# Install dependencies only
composer install

# Generate app key (if missing)
php artisan key:generate
```

### Database Operations
```bash
# Fresh migration (drops all tables)
php artisan migrate:fresh

# Fresh migration with role/permission seeding
php artisan migrate:fresh --seed

# Check migration status
php artisan migrate:status

# Quick reset using helper script (interactive)
./reset-database.sh

# Rollback last batch
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset
```

### Development Server
```bash
# Basic server (runs on http://127.0.0.1:8000)
php artisan serve

# Full development environment (requires concurrently)
composer dev
# Runs: server + queue worker + logs (pail) + vite in parallel
# Colors: blue (server) | purple (queue) | red (logs) | yellow (vite)
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage report
php artisan test --coverage

# Run single test file
php artisan test tests/Feature/Api/ArticleControllerTest.php

# Run single test method
php artisan test --filter test_can_create_article

# Run tests with custom PHP config
php artisan test --configuration=phpunit.xml --parallel

# Watch tests (re-run on file changes)
php artisan test --watch
```

### Code Quality
```bash
# Format code (Laravel Pint - PSR-12)
./vendor/bin/pint

# Format specific file
./vendor/bin/pint app/Domain/Blog/Models/Article.php

# Dry run (check without fixing)
./vendor/bin/pint --test
```

### Cache Management
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache optimization (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Other Useful Commands
```bash
# List all routes
php artisan route:list

# List routes for specific API
php artisan route:list --path=api/articles

# Interactive shell (test code)
php artisan tinker

# Generate IDE helper files (if installed)
php artisan ide-helper:generate
php artisan ide-helper:models
```

## Multi-Language Implementation

All content uses JSON columns with 5 languages:
```json
{
  "ar": "Arabic text - العربية",
  "en": "English text",
  "fr": "Texte français",
  "tr": "Türkçe metin",
  "ur": "اردو متن"
}
```

When creating/updating translatable content, always provide all 5 languages or use the `spatie/laravel-translatable` package methods.

## Database Schema Overview

### Authentication & Authorization
- `users` - User accounts (includes `language` preference)
- `personal_access_tokens` - Sanctum API tokens
- `roles`, `permissions` - Spatie Permission (RBAC)
- `model_has_roles`, `model_has_permissions` - Role assignments

### Blog Module
- `articles` - Multi-language articles with generated columns for full-text search
- `categories` - Hierarchical categories (self-referencing)
- `tags`, `taggables` - Polymorphic tagging
- `series` - Article series grouping
- `favorites` - Polymorphic bookmarks
- `user_interactions` - View tracking, reading progress

### Hadith Module
- `hadith_books` - Sahih Bukhari, Muslim, etc.
- `hadith_chapters` - Chapter organization
- `hadith` - Hadith texts with Arabic + English (full-text indexed)
- `hadith_narrators` - Narrator biographies
- `hadith_narrator_chain` - Pivot table for narrator chains

### Media Module
- `media_categories` - Program categories
- `media_programs` - Video series/shows
- `media_episodes` - Individual episodes with video URLs

### Seerah Module
- `seerah_events` - Timeline events with dates (Hijri & Gregorian)
- `seerah_participants` - People involved in events
- `seerah_sources` - Historical sources

### Sahaba Module
- `sahabis` - Companion profiles with detailed info
- Includes: names, tribe, status, hadith_count, battles, family connections

### Quran Module
- `quran_surahs` - 114 chapters
- `quran_verses` - 6,236 verses with translations
- `quran_reciters` - Recitation audio references
- `quran_tafseer` - Verse interpretations

### Genealogy Module
- `people` - Family tree nodes
- `relationships` - Edges (father, mother, spouse, child)

### Important Migration Notes
- **JSON Full-Text Search**: Uses MySQL generated columns (not direct JSON indexing)
- **Indexes**: All foreign keys indexed, composite indexes for common queries
- **Soft Deletes**: Enabled on articles, hadith, media programs

## Admin Panel & Blade Templates

### Admin Portal Structure
The admin panel is a **separate web application** built with Laravel Blade templates and Alpine.js, distinct from the JSON API:

```
app/Http/Controllers/Admin/      # Admin controllers (web, not API)
├── DashboardController.php      # Statistics & overview dashboard
├── ArticleController.php        # Article CRUD management
├── HadithController.php         # Hadith database management
├── MediaProgramController.php   # Video programs & episodes
├── SeerahEventController.php    # Timeline events editor
├── SahabiController.php         # Companions profiles
├── QuranController.php          # Quran surahs & verses
├── GenealogyController.php      # Family tree management
├── UserController.php           # User account management
├── RoleController.php           # Role & permission management
├── LanguageSettingsController.php
├── ActivityLogController.php
└── MediaUploadController.php    # File uploads for media

resources/views/admin/
├── layouts/
│   ├── admin.blade.php          # Master layout with sidebar, dark mode
│   └── auth.blade.php           # Login layout
├── auth/
│   └── login.blade.php          # Admin login form
├── dashboard.blade.php          # Dashboard with stats cards
├── articles/
│   ├── index.blade.php          # List, search, filter, bulk delete
│   ├── create.blade.php         # Create new article
│   └── edit.blade.php           # Edit existing article
├── hadith/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── media/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── seerah/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── sahaba/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── genealogy/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── users/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
└── roles/
    └── index.blade.php
```

### Admin Routes
- **Path Prefix**: `/su/portal/v1/admin`
- **Authentication**: Session-based (web middleware)
- **Middleware**: `AdminAuthMiddleware`, rate limiting (60 req/min)
- **Example**: `GET /su/portal/v1/admin/login`, `POST /su/portal/v1/admin/articles`

### Admin Features
- **Dark Mode**: Toggle via localStorage, persists across sessions
- **Search**: Real-time search with 500ms debounce on index pages
- **Pagination**: Shows 15 items per page with next/previous navigation
- **Bulk Operations**: Select multiple items, delete in bulk
- **Stats Cards**: Show counts on all module index pages
- **Multi-language Forms**: All forms support 5 languages (ar, en, fr, tr, ur)
- **AJAX Integration**: Data loading without full page refresh
- **Toast Notifications**: User feedback on success/error actions

### Blade Template Patterns
All admin templates follow consistent patterns:

**Master Layout Integration:**
```blade
@extends('admin.layouts.admin')

@section('title', 'Page Title')

@section('content')
    <div class="container">
        <!-- Page content -->
    </div>
@endsection
```

**Form Structure (Create/Edit):**
```blade
<form action="{{ route('admin.articles.store') }}" method="POST">
    @csrf

    <!-- Multi-language input fields -->
    <div class="form-group">
        <label for="title_ar">{{ __('admin.title_arabic') }}</label>
        <input type="text" name="title[ar]" value="{{ old('title.ar') }}">
        @error('title.ar') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="title_en">{{ __('admin.title_english') }}</label>
        <input type="text" name="title[en]" value="{{ old('title.en') }}">
        @error('title.en') <span class="error">{{ $message }}</span> @enderror
    </div>

    <!-- Submit button -->
</form>
```

**Index Page Structure (Search, Filter, Bulk Delete):**
```blade
<div class="search-bar">
    <form method="GET" action="{{ route('admin.articles.index') }}">
        <input type="text" name="search" placeholder="Search...">
        <button type="submit">Search</button>
    </form>
</div>

<div class="actions-bar">
    <button id="deleteSelected">Delete Selected</button>
    <button><a href="{{ route('admin.articles.create') }}">Create New</a></button>
</div>

<table>
    <thead>
        <tr>
            <th><input type="checkbox" id="selectAll"></th>
            <th>Title</th>
            <th>Author</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($articles as $article)
            <tr>
                <td><input type="checkbox" class="article-select" value="{{ $article->id }}"></td>
                <td>{{ $article->title['en'] ?? $article->title['ar'] }}</td>
                <td>{{ $article->author->name }}</td>
                <td>{{ $article->status }}</td>
                <td>
                    <a href="{{ route('admin.articles.edit', $article) }}">Edit</a>
                    <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

{{ $articles->links() }}
```

## API Structure

### Endpoint Patterns
```
/api/auth/*           - Authentication (register, login, logout)
/api/articles/*       - Blog/Seerah articles (CRUD, search, favorites)
/api/categories/*     - Category listing
/api/hadith/*         - Hadith database (books, chapters, search)
/api/media/*          - Video programs & episodes
/api/seerah/*         - Timeline events (chronological biography)
/api/sahaba/*         - Companions profiles
/api/quran/*          - Quran surahs & verses
/api/health           - Health check
```

All routes defined in `routes/api.php`.

### Authentication
- Uses **Laravel Sanctum** (token-based)
- Protected routes use `auth:sanctum` middleware
- Tokens returned on login, sent via `Authorization: Bearer {token}` header

### API Response Format
All controllers use `ApiResponse` trait with consistent structure:
```json
{
  "success": true,
  "message": "Resource retrieved successfully",
  "data": { ... },
  "meta": {
    "current_page": 1,
    "total": 100
  }
}
```

## Caching Strategy

### Cache Configuration
- **Driver**: Redis (configured in `.env`)
- **TTL Values**:
  - Articles: 15 minutes
  - Categories: 30 minutes
  - Hadith: 30 minutes
  - Quran: Permanent (rarely changes)
  - Genealogy tree: 1 hour

### Cache Implementation
- Implemented in repository layer (not controllers)
- Cache keys are namespaced: `articles:all`, `hadith:book:1`
- Automatic invalidation on create/update/delete
- Use cache tags for bulk invalidation

### Cache Invalidation Pattern
```php
// In repository on update/delete
Cache::tags(['articles'])->flush();

// Or specific key
Cache::forget('articles:featured');
```

## Testing Patterns

### Test Organization
```
tests/
├── Feature/           # Integration tests (HTTP, database)
│   └── Api/          # API endpoint tests
└── Unit/             # Isolated unit tests
    └── Services/     # Service layer tests
```

### Testing Best Practices
- Use `RefreshDatabase` trait for database tests
- Create factories for all models
- Test validation rules in Feature tests
- Test business logic in Unit tests
- Mock external services

## Controller Patterns

### API Controllers (Thin)
API controllers should only handle HTTP concerns and delegate business logic to services:

```php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Blog\Services\ArticleService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    use ApiResponse;

    public function __construct(protected ArticleService $articleService)
    {
        // Protect routes except public endpoints
        $this->middleware('auth:sanctum')->except(['index', 'show', 'search']);
    }

    /**
     * Display articles
     *
     * GET /api/articles
     * Query params: per_page (default 15), category_id
     */
    public function index(Request $request): JsonResponse
    {
        $articles = $this->articleService->getPublishedArticles(
            perPage: (int) $request->input('per_page', 15)
        );

        return $this->paginatedResponse(
            $articles->setCollection(
                $articles->getCollection()->map(fn ($article) => new ArticleResource($article))
            )
        );
    }

    /**
     * Store new article
     *
     * POST /api/articles
     * Body: title (json), content (json), category_id, status
     */
    public function store(StoreArticleRequest $request): JsonResponse
    {
        $article = $this->articleService->create($request->validated());
        return $this->createdResponse(new ArticleResource($article));
    }
}
```

### Admin Controllers (Web)
Admin controllers handle view rendering and form processing (session-based):

```php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Blog\Services\ArticleService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(protected ArticleService $articleService) {}

    /**
     * Show article management index
     */
    public function index(): View
    {
        $articles = $this->articleService->paginate(perPage: 15);
        return view('admin.articles.index', compact('articles'));
    }

    /**
     * Show create article form
     */
    public function create(): View
    {
        return view('admin.articles.create');
    }

    /**
     * Store article via form submission
     */
    public function store(StoreArticleRequest $request)
    {
        $this->articleService->create($request->validated());
        return redirect()->route('admin.articles.index')->with('success', 'Article created');
    }
}
```

## Code Standards

### Strict Requirements
- **Strict Types**: Every PHP file starts with `declare(strict_types=1);`
- **Type Hints**: All parameters and return types must be typed
- **PHPDoc**: Required for all public methods with `@param`, `@return`, `@throws`
- **No N+1 Queries**: Always use eager loading in repositories
- **PSR-12**: Follow Laravel's coding style (enforced by Pint)

### Repository Requirements
```php
declare(strict_types=1);

namespace App\Domain\ModuleName\Repositories;

use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ExampleRepository extends BaseRepository implements ExampleRepositoryInterface
{
    // Constructor must set $model property
    public function __construct()
    {
        parent::__construct();
        $this->model = ModelName::class;
    }

    // Must use eager loading
    protected function getBaseQuery()
    {
        return $this->model::with(['relation1', 'relation2']);
    }

    // Cache frequently accessed data
    public function getAll(): Collection
    {
        return Cache::tags(['examples'])->remember(
            'examples:all',
            now()->addMinutes(15),
            fn() => $this->getBaseQuery()->get()
        );
    }
}
```

### Model Requirements
```php
declare(strict_types=1);

namespace App\Domain\ModuleName\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Example extends Model
{
    use SoftDeletes, HasTranslations;

    // Required properties
    protected $fillable = [];        // List all fillable attributes
    protected $casts = [];           // Type casting
    public $translatable = [];       // Translatable fields

    // Always define relationships with return types
    public function relation(): BelongsTo
    {
        return $this->belongsTo(RelatedModel::class);
    }
}
```

## Performance Optimization

### Query Optimization Checklist
- [ ] Use `select()` to fetch only needed columns
- [ ] Eager load all relationships with `with()`
- [ ] Add database indexes for foreign keys and search columns
- [ ] Use chunk() for large datasets
- [ ] Cache expensive queries

### Full-Text Search
- Uses MySQL generated columns (not direct JSON indexing)
- Arabic text requires proper collation (utf8mb4_unicode_ci)
- Search pattern: `MATCH(column) AGAINST(? IN NATURAL LANGUAGE MODE)`

## Creating a New Module

When adding a new domain module, follow this checklist:

### 1. Database Layer
```bash
# Create migration
php artisan make:migration create_module_table

# Add indexes, foreign keys, JSON columns
# Run migration
php artisan migrate
```

### 2. Domain Layer
```
app/Domain/ModuleName/
├── Models/
│   └── ModelName.php              # Eloquent model
├── Repositories/
│   ├── ModelRepositoryInterface.php
│   └── ModelRepository.php
├── Services/
│   └── ModelService.php
└── Enums/                         # If needed
```

### 3. HTTP Layer
```
app/Http/
├── Controllers/Api/
│   └── ModelController.php        # Thin controller
├── Resources/
│   └── ModelResource.php          # JSON transformer
└── Requests/
    ├── StoreModelRequest.php      # Validation
    └── UpdateModelRequest.php
```

### 4. Bind Repository in Provider
```php
// app/Providers/RepositoryServiceProvider.php
$this->app->bind(
    \App\Domain\ModuleName\Repositories\ModelRepositoryInterface::class,
    \App\Domain\ModuleName\Repositories\ModelRepository::class
);
```

### 5. Add Routes
```php
// routes/api.php
Route::prefix('module-name')->group(function () {
    Route::get('/', [ModelController::class, 'index']);
    // ... other routes
});
```

### 6. Create Tests
```bash
php artisan make:test ModuleTest --unit
php artisan make:test Api/ModuleApiTest
```

## Environment Configuration

### Required .env Variables
```env
# App
APP_NAME="The Last Messenger"
APP_ENV=local
APP_KEY=                  # Generate with: php artisan key:generate

# Database (MySQL recommended)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=last_messenger
DB_USERNAME=root
DB_PASSWORD=

# Redis (required for caching)
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Cache
CACHE_DRIVER=redis
CACHE_PREFIX=lm_

# Queue (optional, use 'sync' for development)
QUEUE_CONNECTION=sync
```

### Alternative: SQLite for Local Development
```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

## Security Considerations

- **API Rate Limiting**: Configured in `routes/api.php` middleware
- **CORS**: Configured in `config/cors.php`
- **Sanctum**: Token authentication with expiration
- **Permission System**: Spatie Permission with 6 roles (super_admin, admin, editor, researcher, user, guest)
- **Input Validation**: All requests validated via Form Request classes
- **SQL Injection**: Prevented via Eloquent query builder
- **XSS Protection**: Laravel's Blade escaping (not used in API, but for future)

## Permissions & Roles

### Available Roles (Seeded)
1. `super_admin` - Full system access
2. `admin` - Manage content and users
3. `editor` - Create/edit articles and content
4. `researcher` - Read-only access to research content
5. `user` - Authenticated basic user
6. `guest` - Public access (no auth required)

### Check Permissions in Code
```php
// In controller (middleware)
$this->middleware('permission:articles.create');

// In code
if ($user->hasPermissionTo('articles.delete')) {
    // ...
}

// Check role
if ($user->hasRole('admin')) {
    // ...
}
```

## Troubleshooting

### "Table already exists" Error
```bash
# Drop all tables and re-migrate
php artisan migrate:fresh

# Or use the interactive script
./reset-database.sh
```

### "JSON column supports indexing only via generated columns"
This was fixed in migration files. If you encounter it:
- Check migration uses generated columns for full-text search on JSON
- Example: `$table->string('title_ar_generated')->storedAs("JSON_UNQUOTE(JSON_EXTRACT(title, '$.ar'))")`

### "Class not found" After Creating New Class
```bash
# Rebuild autoload files
composer dump-autoload
```

### N+1 Query Problems
```bash
# Install Laravel Debugbar (dev only)
composer require barryvdh/laravel-debugbar --dev

# Check queries in debug panel
# Always use with() in repositories
```

## Related Documentation

- **README.md** - Project overview and installation
- **QUICK_START.md** - Quick start guide and common tasks
- **PROJECT_STATUS_70_PERCENT.md** - Current implementation status
- **MIGRATION_FIX.md** - JSON full-text search migration issue resolution
- **MIGRATION_INSTRUCTIONS.md** - Database migration guide
- **be-tasks.md** - Detailed implementation tasks (if exists)

## Important Notes

### Dual System Architecture
- **API Layer** (`/api/*`): JSON responses for mobile/external clients (Sanctum auth)
- **Admin Panel** (`/su/portal/v1/admin/*`): Blade templates for content management (session auth)
- **Two Different Authentication Systems**: API uses tokens, admin uses sessions
- Routes defined separately in `routes/api.php` and `routes/admin.php`

### Multi-Language JSON Access Patterns

**In Controllers/Models:**
```php
// Access single language (returns string)
$title = $article->title['ar'];  // Returns: "عنوان"
$title = $article->getTranslation('title', 'ar');  // Using Spatie method

// Access all languages (returns array)
$allTitles = $article->title;  // Returns: ["ar" => "عنوان", "en" => "Title", ...]

// Set translations
$article->title = [
    'ar' => 'عنوان جديد',
    'en' => 'New Title',
    'fr' => 'Nouveau Titre',
    'tr' => 'Yeni Başlık',
    'ur' => 'نیا عنوان'
];
$article->save();
```

**In Blade Templates (Admin Forms):**
```blade
<!-- Display with fallback to Arabic if English missing -->
{{ $article->title['en'] ?? $article->title['ar'] }}

<!-- Loop through languages for form inputs -->
@foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
    <div class="form-group">
        <label>{{ __("admin.label_$lang") }}</label>
        <input type="text"
               name="title[{{ $lang }}]"
               value="{{ old("title.$lang", $article->title[$lang] ?? '') }}">
        @error("title.$lang") <span class="error">{{ $message }}</span> @enderror
    </div>
@endforeach
```

**In API Resources:**
```php
// Always return all languages in API responses
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'title' => [
            'ar' => $this->title['ar'] ?? null,
            'en' => $this->title['en'] ?? null,
            'fr' => $this->title['fr'] ?? null,
            'tr' => $this->title['tr'] ?? null,
            'ur' => $this->title['ur'] ?? null,
        ],
        'content' => [
            'ar' => $this->content['ar'] ?? null,
            'en' => $this->content['en'] ?? null,
            // ... rest of languages
        ],
    ];
}
```

### Critical Multi-Language Rules
1. **Always provide all 5 languages** when creating/updating translatable content
2. **Default to Arabic** (`ar`) for displays when language not specified
3. **Validation must check all languages** in form requests:
   ```php
   protected function rules(): array
   {
       return [
           'title.ar' => 'required|string|max:255',
           'title.en' => 'required|string|max:255',
           'title.fr' => 'required|string|max:255',
           'title.tr' => 'required|string|max:255',
           'title.ur' => 'required|string|max:255',
       ];
   }
   ```
4. **JSON full-text search** uses generated columns for indexing
5. **RTL Support**: Always use logical properties (start/end) not left/right

### Never Commit
- `.env` file (contains API keys, database credentials)
- Database files (`.sqlite`, `.db`)
- `vendor/` directory
- `node_modules/` directory
- IDE config files (`.vscode/settings.json`, `.idea/`)

### Development Workflow Tips
1. Use factories for test data: `Article::factory()->create()`
2. Always use eager loading to prevent N+1 queries
3. Cache expensive queries, invalidate on create/update/delete
4. Use repositories to access data, never query models directly in controllers
5. Services should handle all business logic, controllers stay thin
6. Test feature endpoints, not just unit logic
7. Follow established patterns - consistency is critical in DDD architecture


### Feature Implementation Guidelines
- **CRITICAL:** Any Implementation Don't use dummy data or placeholders; always use real data structures.
- **CRITICAL:** Make MINIMAL CHANGES to existing patterns and structures.
- **CRITICAL:** Preserve existing naming conventions and file organization.
- Follow project's established architecture and component patterns.
- Use existing utility functions and avoid duplicating functionality.
- Ensure any new code adheres to the project's coding standards and styles.
- Ensure all new features are fully tested and works without errors.
- always create tasks.md file to track all tasks and always update it with new progress to keep remaining tasks visible.
- don't create summary document after each task
