# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**The Last Messenger Backend** is the Laravel-based API and admin panel for The Last Messenger platform - a comprehensive Islamic digital encyclopedia focused on Prophet Muhammad ﷺ. This backend provides RESTful APIs, admin CMS, and content management for Seerah (biography), Hadith, Quranic content, genealogy, media programs, and interactive maps.

**Current Status:** Active development - Admin panel implemented, Articles/Blog module complete, Hadith/Quran/Seerah/Sahaba/Genealogy modules implemented with DDD architecture.

## Project Structure

```
the-last-messenger-be/
├── app/
│   ├── Domain/                         # Domain-Driven Design modules
│   │   ├── Blog/                       # Blog/Articles domain
│   │   │   ├── Actions/
│   │   │   ├── DTOs/
│   │   │   ├── Enums/
│   │   │   ├── Exporters/
│   │   │   ├── Models/
│   │   │   ├── Repositories/
│   │   │   └── Services/
│   │   ├── Content/                    # Site content (home, menus, pages, FAQs)
│   │   │   ├── Models/
│   │   │   ├── Repositories/
│   │   │   └── Services/
│   │   ├── Hadith/                     # Hadith domain (models + Sunnah.com import)
│   │   │   ├── Enums/                  # Grade
│   │   │   ├── Import/Sunnah/          # API client, mappers, import services
│   │   │   ├── Models/                 # Hadith, HadithBook, HadithChapter,
│   │   │   │                           #   HadithCollection, HadithNarrator
│   │   │   └── Repositories/
│   │   ├── Media/                      # Media programs, episodes, live channels
│   │   │   ├── Models/
│   │   │   ├── Repositories/
│   │   │   └── Services/
│   │   ├── Quran/                      # Quran domain (CQRS pattern)
│   │   │   ├── Application/
│   │   │   │   ├── Commands/
│   │   │   │   ├── DTOs/
│   │   │   │   ├── Handlers/
│   │   │   │   └── Queries/
│   │   │   ├── Enums/
│   │   │   ├── Events/
│   │   │   ├── Exceptions/
│   │   │   ├── Exporters/
│   │   │   ├── Models/
│   │   │   ├── Repositories/
│   │   │   └── Services/
│   │   ├── Seerah/                     # Seerah/Timeline domain
│   │   │   ├── Enums/
│   │   │   ├── Exporters/
│   │   │   ├── Models/
│   │   │   ├── Repositories/
│   │   │   └── Services/
│   │   ├── People/                     # Companions + genealogy (Muslim model)
│   │   │   ├── Models/                 # Muslim, MuslimRelationship, IslamicRole
│   │   │   ├── Repositories/
│   │   │   └── Services/               # GenealogyTreeService, MuslimDirectory…
│   │   ├── Topics/                     # Cross-module topic grouping (morph map)
│   │   │   ├── Enums/
│   │   │   ├── Models/
│   │   │   └── Repositories/
│   │   └── Shared/                     # Cross-cutting concerns
│   │       ├── Application/
│   │       │   ├── Commands/
│   │       │   ├── DTOs/
│   │       │   └── Queries/
│   │       ├── Builders/
│   │       ├── Contracts/
│   │       ├── DTOs/
│   │       ├── Enums/
│   │       ├── Events/
│   │       │   └── Traits/
│   │       ├── Exceptions/
│   │       ├── Exporters/
│   │       ├── Importers/
│   │       ├── Models/
│   │       ├── Repositories/
│   │       ├── Services/
│   │       ├── Traits/
│   │       └── ValueObjects/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/                  # 33+ admin panel controllers
│   │   │   │   ├── ActivityLogController.php
│   │   │   │   ├── AdminController.php
│   │   │   │   ├── AnalyticsController.php
│   │   │   │   ├── ArticleController.php
│   │   │   │   ├── AuditController.php
│   │   │   │   ├── BulkOperationController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── CollectionController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── DashboardWebController.php
│   │   │   │   ├── ExportController.php
│   │   │   │   ├── GenealogyController.php
│   │   │   │   ├── GradingLevelController.php
│   │   │   │   ├── ImportController.php
│   │   │   │   ├── LanguageSettingsController.php
│   │   │   │   ├── MediaController.php
│   │   │   │   ├── MediaSettingsController.php
│   │   │   │   ├── NarratorController.php
│   │   │   │   ├── QuranController.php
│   │   │   │   ├── ReciterController.php
│   │   │   │   ├── RoleController.php
│   │   │   │   ├── SahabiController.php
│   │   │   │   ├── SchedulerController.php
│   │   │   │   ├── ScholarController.php
│   │   │   │   ├── SeerahEventController.php
│   │   │   │   ├── SeriesController.php
│   │   │   │   ├── SurahController.php
│   │   │   │   ├── TafseerController.php
│   │   │   │   ├── TagController.php
│   │   │   │   ├── ThemeController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── VerseController.php
│   │   │   │   └── VersionController.php
│   │   │   └── Api/                    # Public API controllers
│   │   ├── Middleware/
│   │   ├── Requests/                   # Form validation requests
│   │   └── Resources/                  # API resource transformers (26 files)
│   │       ├── ArticleResource.php
│   │       ├── AuditLogResource.php
│   │       ├── BulkOperationResource.php
│   │       ├── CategoryResource.php
│   │       ├── GenealogyResource.php
│   │       ├── HadithBookResource.php
│   │       ├── HadithCollectionResource.php
│   │       ├── HadithNarratorResource.php
│   │       ├── HadithResource.php
│   │       ├── HadithThemeResource.php
│   │       ├── MediaResource.php
│   │       ├── SahabiResource.php
│   │       ├── SeerahEventResource.php
│   │       ├── SeriesResource.php
│   │       ├── SurahResource.php
│   │       ├── TafseerResource.php
│   │       ├── TagResource.php
│   │       ├── UserResource.php
│   │       ├── VerseResource.php
│   │       └── ... (7 more)
│   ├── Jobs/                           # Queue jobs
│   │   ├── CleanupFailedImports.php
│   │   ├── ProcessBulkOperation.php
│   │   ├── ProcessImport.php
│   │   ├── SyncBookJob.php
│   │   └── SyncCollectionJob.php
│   ├── Console/Commands/               # Artisan commands
│   │   ├── SyncHadithCommand.php
│   │   ├── SyncHadithFromJsonCommand.php
│   │   └── SyncQuranCommand.php
│   ├── Repositories/                   # Legacy repositories (app-level)
│   │   ├── ArticleRepository.php
│   │   └── MediaRepository.php
│   ├── Traits/
│   │   └── HasMedia.php                # Media handling trait
│   ├── Utils/
│   │   └── DateUtil.php                # Date conversion utilities
│   ├── Exceptions/                     # Custom exceptions
│   └── Helpers/
│       └── helpers.php                 # Global helper functions
├── database/
│   ├── migrations/                     # Database schema migrations
│   ├── seeders/                        # Database seeders
│   └── factories/                      # Model factories
├── resources/
│   ├── views/
│   │   ├── admin/                      # Admin panel Blade templates
│   │   │   ├── layouts/                # Base layouts
│   │   │   │   └── app.blade.php
│   │   │   ├── articles/               # Article CRUD views
│   │   │   ├── media/                  # Media management views
│   │   │   ├── categories/             # Category management
│   │   │   ├── hadith/                 # Hadith management views
│   │   │   ├── quran/                  # Quran management views
│   │   │   ├── seerah/                 # Seerah event views
│   │   │   ├── sahaba/                 # Companion profile views
│   │   │   ├── genealogy/              # Genealogy tree views
│   │   │   └── tags/                   # Tag management
│   │   └── components/                 # Reusable Blade components
│   ├── lang/                           # Multi-language translations
│   │   ├── ar/                         # Arabic (primary)
│   │   ├── en/                         # English
│   │   ├── tr/                         # Turkish
│   │   ├── fr/                         # French
│   │   └── ur/                         # Urdu
│   ├── js/
│   │   └── admin.js                    # Admin panel JavaScript
│   └── css/
│       └── admin.css                   # Admin panel styles
├── routes/
│   ├── web.php                         # Admin panel routes
│   ├── api.php                         # Public API routes
│   └── channels.php                    # Broadcasting channels
├── config/
│   ├── translatable.php                # Spatie translatable config
│   ├── permission.php                  # Spatie permission config
│   └── media-library.php               # Spatie media library config
├── public/
│   ├── storage/                        # Symlinked storage (media files)
│   └── build/                          # Compiled assets (Vite)
└── tests/
    ├── Feature/                        # Feature tests
    └── Unit/                           # Unit tests
```

## Technology Stack

### Backend Framework
- **PHP**: 8.5 (`composer.json` requires `^8.5`; `config.platform.php` pinned to 8.5.4)
- **Laravel**: 13.0 (`composer.json` requires `^13.0`)
- **Database**: MySQL 8.0+
- **Queue**: Laravel Queue (Database driver)
- **Cache**: File-based cache (can upgrade to Redis)

### Key Packages
- **spatie/laravel-translatable** (^6.11): Multi-language content management
- **spatie/laravel-permission** (^6.21): Role-based access control (RBAC)
- **spatie/laravel-medialibrary** (^11.17): Advanced media handling with conversions
- **intervention/image** (^3.11): Image manipulation and optimization
- **maatwebsite/excel** (^3.1): Excel import/export functionality
- **laravel/sanctum** (^4.2): API authentication (JWT)

### Frontend (Admin Panel)
- **Tailwind CSS**: Utility-first styling
- **Alpine.js**: Lightweight JavaScript interactivity
- **Vite**: Modern build tool for assets
- **Lucide Icons**: SVG icon library

## Design System (Admin Panel)

### Color Scheme (Blue-Green Gradient)
```javascript
// Primary Colors
brand: #0056b3       // Primary blue
secondary: #218838   // Green
accent: #64CCC5      // Teal/Cyan
neutral: #333-#F9FAFB // Text/backgrounds

// Gradient
primary: linear-gradient(135deg, #0056b3 0%, #218838 100%)
```

### Typography
- **Primary Font**: Cairo (Arabic-optimized, same as frontend)
- **Weights**: 300, 400, 600, 700, 900
- **Direction**: Full RTL/LTR support for all languages

### Component Patterns
- Cards with hover effects
- Glassmorphism headers
- Gradient buttons and badges
- DataTables with server-side processing
- Language tabs for translatable content
- Media picker with drag-and-drop upload

## Core Modules

### 1. Articles Module (Seerah/Blog)
**Status:** ✅ Complete

**Features:**
- Multi-language content (Arabic, English, Turkish, French, Urdu)
- Rich text editor (TinyMCE/similar)
- Featured image + gallery support
- SEO metadata per language
- Category and tag assignment
- Draft/Published status
- Hijri and Gregorian date support

**Key Files:**
- `app/Http/Controllers/Admin/ArticleController.php`
- `app/Models/Article.php` (uses Spatie Translatable)
- `app/Repositories/ArticleRepository.php`
- `resources/views/admin/articles/`
- `database/migrations/*_create_articles_table.php`

**API Endpoints:**
- ~~`GET /api/articles`~~ — **DISABLED** (commented out in `routes/api.php`; no API controller yet)
- ~~`GET /api/articles/{slug}`~~ — **DISABLED**
- Admin CRUD at `/su/portal/v1/admin/articles`

### 2. Media Management Module
**Status:** ✅ Complete

**Features:**
- Advanced media library (Spatie Media Library)
- Image upload with automatic optimization
- Thumbnail generation (multiple sizes)
- Drag-and-drop interface
- Image cropping and editing
- Media picker component for article galleries
- Storage in `storage/app/public/media`

**Key Files:**
- `app/Http/Controllers/Admin/MediaController.php`
- `app/Models/Media.php`
- `app/Traits/HasMedia.php`
- `resources/views/admin/media/`

### 3. Categories & Tags Module
**Status:** ✅ Complete

**Features:**
- Hierarchical categories (Hadith, Seerah, Quran, etc.)
- Multi-language category names
- Tag management with autocomplete
- Category icons/colors
- Content filtering by category/tag

**Key Files:**
- `app/Models/Category.php`
- `app/Models/Tag.php`
- `resources/views/admin/categories/`
- `resources/views/admin/tags/`

### 4. User Management & Authentication
**Status:** ✅ Complete

**Features:**
- Role-based access control (Admin, Editor, Viewer)
- Permission system (Spatie Permission)
- Laravel Sanctum for API authentication
- Multi-language login/register pages
- RTL-aware authentication views

**Key Files:**
- `app/Models/User.php`
- `config/permission.php`
- Authentication views (RTL-enabled)

### 5. Hadith Module
**Status:** 🟡 Rebuilt — data layer + import pipeline only, no admin/public UI yet

> The module was rebuilt. There is **no** CQRS `Application/` layer, **no** Hadith
> admin controllers, **no** `HadithResource`, and **no** `SyncBookJob` /
> `SyncCollectionJob` / `SyncHadithCommand`. Earlier revisions of this file
> described that (now deleted) implementation — do not code against it.

**Features:**
- 5 models: `Hadith`, `HadithBook`, `HadithChapter`, `HadithCollection`, `HadithNarrator`
- `Enums/Grade` — grades are `sahih|hasan|daif|mawdu`, or NULL when the source is
  ambiguous (`SunnahGradeMapper` refuses to guess and keeps `raw_grade` as evidence)
- Sunnah.com import pipeline in `app/Domain/Hadith/Import/Sunnah/`: rate-limited API
  client, mappers, count-parity gates, SHA-256 snapshot drift detection, chunked
  upserts keyed on `(collection_id, reference_key)` — safely re-runnable
- Cross-module people references go through the Shared `PersonLookup` contract
  (never a direct relation to the People domain); enforced by `deptrac.yaml`

**Key Files:**
- `app/Domain/Hadith/` — Enums, Import/Sunnah, Models, Repositories
- `app/Console/Commands/ImportSunnahHadithCommand.php`
- `app/Console/Commands/AuditNarratorDataCoverageCommand.php`
- Tests: `tests/Feature/Hadith/`

**Tables:** `hadiths`, `hadith_books`, `hadith_chapters`, `hadith_collections`,
`hadith_narrators`. (Pre-rebuild names `hadith_hadiths` / `hadith_scholars` /
`hadith_texts` no longer exist — code still referencing them will throw.)

**Visibility:** always read through `Hadith::scopePubliclyVisible()`. A streaming
re-import hides its collection (`is_published = false`) while the hadith rows stay
published, so a bare `where('is_published', true)` will serve hidden content.

### 6. Quran Module
**Status:** ✅ Implemented (Domain-Driven Design with CQRS)

**Features:**
- CQRS layer in `app/Domain/Quran/Application/` (Commands, Queries, Handlers, DTOs).
  Note it is consumed only by `Site\QuranController` / `Site\HomeController`; the
  admin controllers still go through `QuranService` / `VerseService` directly, so
  several reads exist in two places. Prefer the query stack for new read paths.
- Surahs, Verses, Tafseer, Translations, Reciters, Recitations, Revelation contexts
- `Enums/RevelationPlace` is the single source of truth for `makkah|madinah`; use
  `RevelationPlace::fromExternal()` when importing (datasets say Meccan/Medina/…)
- Verse identity is `verse_key` in `"surah:verse"` form (e.g. `"2:255"`)
- **Reciter playlists** (visitor-built, shareable): `quran_playlists` / `quran_playlist_items`,
  `Models/{Playlist,PlaylistItem}`, `PlaylistRepository`, `PlaylistService::assign()`
  (balanced-random surah → recording; `Recitation::playable()` is the "has audio" rule).
  Site routes `public.quran.playlists.*` (`/quran/playlists`, slug = 12-char random),
  admin `admin.playlists.*`. `Playlist::scopeVisibleTo()` is the only visibility rule —
  private playlists 404. Reader accepts `?reciter=<recording id>` (player `configure({force})`).
  Reciter people are bookmarkable via `FavorableType::RECITER`. Spec: `specs/004-quran-playlists/`.

**Key Files:**
- `app/Domain/Quran/` — full domain structure
- `app/Http/Controllers/Admin/Quran/{SurahController,VerseController,TafseerController,ReciterController,RecitationController,RevelationContextController,AiInquiryController}.php`
- `app/Http/Resources/SurahResource.php`, `VerseResource.php`, `TafseerResource.php`

> Verse CRUD lives at `admin.verses.*`. The old `admin.quran.*` verse routes and
> their controller were **removed**: the controller was wired to the Surah
> repository, so "delete verse" deleted the Surah with that id and cascaded to all
> of its verses.

### 7. Seerah Module
**Status:** ✅ Implemented

**Features:**
- Seerah events with timeline support
- Exporters for data output
- Enums for event categorization

**Key Files:**
- `app/Domain/Seerah/` — domain structure
- `app/Http/Controllers/Admin/SeerahEventController.php`
- `app/Http/Resources/SeerahEventResource.php`, `SeerahParticipantResource.php`

### 8. People Module (Companions + Genealogy)
**Status:** ✅ Implemented

> Replaces the former `Sahaba` and `Genealogy` domains, which no longer exist —
> companions and genealogy are one bounded context around the `Muslim` model.
> There is no `Person` model and no `SahabiResource`.

**Features:**
- `Muslim`, `MuslimRelationship`, `IslamicRole`; profiles are translatable
- Family tree read via **recursive CTEs** (one query per direction plus one link
  query, nested in memory) with an explicit depth cap that reports `depth_capped`
  — deliberately not per-node traversal
- Write-time cycle detection and pair-order normalisation so the relationship
  unique index is actually enforceable
- Exposed to other domains only through the Shared `PersonLookup` contract
- Idempotent import via `ImportMuslimScholarsSahabaCommand` (chunked transactions,
  slug-collision pre-check, provenance hashes, dry-run support)

**Key Files:**
- `app/Domain/People/` — Models, Repositories, Services (`GenealogyTreeService`,
  `MuslimDirectoryService`, `MuslimRelationshipService`)
- `app/Http/Controllers/Admin/SahabiController.php`, `GenealogyController.php`
- `app/Http/Resources/MuslimResource.php`, `IslamicRoleResource.php`
### 9. Media Module
**Status:** ✅ Implemented

**Features:**
- `MediaProgram`, `MediaEpisode`, `LiveChannel`, watch progress, YouTube playlist sync
- Two distinct media systems, both intentional: **Spatie Media Library** for model
  attachments (posters, thumbnails) and `App\Services\MediaService` for the
  encrypted media library (libsodium secretstream + generated variants)
- Public visibility filtering is centralised — use the repository/service methods
  (`publicEpisodes()` scope etc.), never a hand-rolled query in a controller

**Key Files:**
- `app/Domain/Media/` — Models, Repositories, Services
- `app/Http/Controllers/Admin/Media/`, `app/Http/Controllers/Api/MediaProgramController.php`
- `app/Jobs/SyncYouTubePlaylistJob.php` (idempotent: episodes matched by YouTube video id)

### 10. Content & Topics Modules
**Status:** ✅ Implemented
- `Content` — home sections, menus, static pages, FAQs, site settings (all translatable)
- `Topics` — groups content across modules via the morph map + `PublicVisibilityRegistry`,
  without importing any other domain

### 11. Planned / Partial Modules
- ❌ Search Module (Elasticsearch integration) — current search is MySQL FULLTEXT/LIKE
- ❌ Notification System (Laravel Echo + Pusher)

## Architecture Patterns

### Domain-Driven Design Architecture

The backend uses a DDD-inspired layered architecture with bounded contexts per module.

**Module organization** (`app/Domain/{ModuleName}/`):
- Each domain module is self-contained with its own Models, Repositories, Services, Enums, and DTOs.
- **Quran** uses a **CQRS pattern**: writes go through Commands dispatched to Handlers; reads go through typed Query objects with dedicated Handlers. No other module does — Hadith's CQRS layer was deleted in the rebuild.
- **Repository interfaces** define the contract in `app/Domain/{Module}/Repositories/`. Implementations are registered in the service container.
- The `Shared` module (`app/Domain/Shared/`) provides cross-cutting concerns (base contracts, shared DTOs, common enums, generic exporters/importers). Domain modules must never import from each other — only from `Shared`. This holds in practice: there are zero cross-domain imports, and `deptrac.yaml` enforces it for Hadith.
- Cross-module references use a Shared **contract**, not another module's Eloquent model — e.g. `PersonLookup` for people, and the morph map + `PublicVisibilityRegistry` for Topics.

**Repository locations (dual, deliberate):**
- `app/Repositories/` — `ArticleRepository`, `MediaRepository`, `UserRepository`, `FavoriteRepository`. These are **live and authoritative** for their entities, not deprecated shims: `ArticleRepository` is the article repo used by admin, site and API.
- `app/Domain/*/Repositories/` — domain repositories for everything else (Categories, Tags, Series, Media programs, Quran, Seerah, People, Content, Topics).
- Each entity has exactly one repository; there is no duplication between the two locations. The split is a placement inconsistency to migrate eventually, not a source of drift.

### Repository Pattern
All data access goes through repositories for better testability and separation of concerns.

```php
// Example: ArticleRepository
class ArticleRepository {
    public function findBySlug(string $slug, string $locale = 'ar'): ?Article;
    public function paginate(int $perPage = 15);
    public function create(array $data): Article;
    public function update(Article $article, array $data): Article;
}
```

### Translatable Models (translation tables — NOT Spatie JSON columns)

**This is the single most important convention to get right.** Translations live in
sibling `*_translations` tables (`category_translations`, `tag_translations`,
`series_translations`, `seerah_event_translations`, `media_program_translations`,
`reciter_translations`, …). The old Spatie JSON columns — `categories.name`,
`tags.name`, `series.title/description`, and the Seerah event/participant text
columns — were **dropped** by the 2026-03-10 migrations.

**Articles are the exception — they are mid-migration and carry both.** The
`articles` table still has real JSON columns (`title`, `excerpt`, `content`,
`seo_title`, `seo_description`) and `App\Models\Article` still uses Spatie's
`HasTranslations` over them; `ArticleFactory` depends on that trait, since those
columns are not in `casts()`. Meanwhile the authoritative read path is the
`article_locale` table (`title`, `slug`, `excerpt`, `content`, `seo_metadata`,
`is_ready`), which is what `ArticleRepository` eager-loads and searches. Read
articles through `locales`; do **not** strip the trait until the JSON columns are
actually dropped and the factory is migrated.

#### Standard Translation Pattern

**For detailed documentation, see `docs/TRANSLATION_PATTERNS.md`.**

The codebase uses the **`HasLocalizedContent`** trait (`app/Domain/Shared/Traits/HasLocalizedContent.php`)
for all models with translation tables. This trait provides:

- **Read methods (safe):** `getTranslation($field, $locale)`, `getTranslations($field)`, `translationFor($locale)`
- **Write method (standard):** `syncTranslations($data)` — **always use this for writes**
- **Relationship:** `translations()` HasMany relationship

**Critical rules:**

- **ALWAYS use `syncTranslations()` for writes** — the only correct way to save translations
- **NEVER use `setTranslation()` with `save()`** — this was the old Spatie pattern and is dangerous
- **Read-only use of `getTranslation()` is safe** — Blade views and display logic can use this
- **Never** `select('name')` or `with('category:id,name')` on these tables, and never
  `JSON_EXTRACT(name, '$.ar')`. Both throw — the column does not exist.
- Search via the relation: `whereHas('translations', fn ($q) => $q->where('name', 'LIKE', "%{$term}%"))`.
- Eager-load translations explicitly and constrain columns:
  `with('translations:id,category_id,locale,name')`.

```php
// ✅ CORRECT — Read (locale-aware, falls back to Arabic)
$category->getTranslation('name', app()->getLocale())
    ?? $category->getTranslation('name', 'ar');

// ✅ CORRECT — Write using syncTranslations (STANDARD PATTERN)
$category->syncTranslations([
    'name' => [
        'ar' => 'السيرة النبوية',
        'en' => 'The Prophetic Biography',
    ],
    'description' => [
        'ar' => 'وصف',
        'en' => 'Description',
    ],
]);

// ❌ WRONG — NEVER use setTranslation for writes
// $category->setTranslation('name', 'ar', 'السيرة النبوية');
// $category->save();
```

**Service Layer Pattern:**
```php
public function createCategory(CreateCategoryData $data): Category
{
    return DB::transaction(function () use ($data) {
        // 1. Create main record
        $category = $this->repository->create([
            'slug' => $data->slug,
            'type' => $data->type,
        ]);
        
        // 2. Sync translations (STANDARD PATTERN)
        $category->syncTranslations([
            'name' => $data->name,
            'description' => $data->description,
        ]);
        
        return $category;
    });
}
```

Spatie `HasTranslations` remains installed but must **not** be added to these models;
declaring `$translatable` for a column the table no longer has fatals on first write.

### Media Handling (Spatie Media Library)
Advanced media management with automatic conversions.

```php
// Add media to model
$article->addMedia($request->file('image'))
    ->toMediaCollection('featured_image');

// Get media URL with conversion
$article->getFirstMediaUrl('featured_image', 'thumbnail');
```

### Date Handling (Hijri + Gregorian)
Custom date utility for Islamic calendar support.

```php
use App\Utils\DateUtil;

// Convert and format dates
DateUtil::convertToHijri('2024-11-11');
DateUtil::formatDate($date, 'ar'); // Arabic date format
```

## Important Conventions

### RTL/LTR Support
**CRITICAL:** All admin views support both RTL (Arabic/Urdu) and LTR (English/Turkish/French).

```blade
{{-- Layout direction based on locale --}}
<html dir="{{ app()->getLocale() === 'ar' || app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">

{{-- Use logical properties in Tailwind --}}
<div class="ms-4 me-2"> {{-- margin-start, margin-end --}}
```

### Multi-Language Content
**ALL content models use translation tables via the `HasLocalizedContent` trait.**

See `docs/TRANSLATION_PATTERNS.md` for complete documentation.

```php
// ✅ CORRECT — Save translations using syncTranslations (STANDARD PATTERN)
$category->syncTranslations([
    'name' => [
        'ar' => $request->input('name.ar'),
        'en' => $request->input('name.en'),
    ],
]);

// ✅ SAFE — Retrieve in specific language (read-only)
$title = $category->getTranslation('name', 'ar');
$allTitles = $category->getTranslations('name'); // ['ar' => '...', 'en' => '...']

// ❌ WRONG — NEVER use setTranslation with save() for writes
// $category->setTranslation('name', 'ar', 'السيرة');
// $category->save(); // This does NOT write to translation tables!
```

### Naming Conventions
- **Controllers:** Singular (ArticleController, not ArticlesController)
- **Models:** Singular (Article, not Articles)
- **Tables:** Plural (articles, not article)
- **Routes:** Plural (`/admin/articles`)
- **Views:** Plural directory (`resources/views/admin/articles/`)

### Database Conventions
- Use migrations for all schema changes
- Foreign keys: `{model}_id` (e.g., `article_id`, `user_id`)
- Timestamps: Always include `created_at` and `updated_at`
- Soft deletes: Use `deleted_at` for recoverable data
- JSON columns: Use `JSONB` for translatable content and metadata

## Development Workflow

### Setup & Installation
```bash
# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan db:seed

# Link storage for media files
php artisan storage:link

# Build assets
npm run build
```

### Development Server
```bash
# Option 1: Single command (recommended)
composer dev
# Runs: Laravel server + Queue worker + Logs + Vite dev server

# Option 2: Manual
php artisan serve          # Laravel server on :8000
npm run dev                # Vite dev server
php artisan queue:listen   # Queue worker (for media processing)
php artisan pail           # Real-time logs
```

### Testing
```bash
# Run all tests
composer test

# Run specific test
php artisan test --filter=ArticleTest

# Code style (Laravel Pint)
./vendor/bin/pint
```

### Database Operations
```bash
# Create migration
php artisan make:migration create_hadith_table

# Create model with migration and factory
php artisan make:model Hadith -mf

# Rollback and re-migrate
php artisan migrate:fresh --seed

# Create seeder
php artisan make:seeder HadithSeeder
```

## API Development

### RESTful API Structure
All versioned APIs follow REST conventions and return JSON. The API is private
by default; only registration, login, and health are anonymous.

**Base URL:** `/api/v1/`

**Authentication:** Laravel Sanctum bearer tokens with the `api:access` ability.
Send `Authorization: Bearer <token>` on every route except
`POST /auth/register`, `POST /auth/login`, and `GET /health`.

**Response Format:**
```json
{
  "success": true,
  "data": {},
  "message": "Success message",
  "meta": {
    "current_page": 1,
    "total": 100
  }
}
```

**Error Format:**
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["Validation error"]
  }
}
```

### Example API Endpoints
```php
// Articles API
Route::middleware(['auth:sanctum', 'abilities:api:access', 'throttle:api'])
    ->prefix('articles')->group(function () {
    Route::get('/', [ArticleController::class, 'index']);        // List
    Route::get('/{slug}', [ArticleController::class, 'show']);   // Single
});
```

The public website uses separate session/CSRF-protected `web` routes; do not
make a bearer-token API anonymous to support a browser page.

## Admin Panel Development

### Creating a New Admin Module
1. **Create Controller:**
```bash
php artisan make:controller Admin/HadithController --resource
```

2. **Create Model with Migration:**
```bash
php artisan make:model Hadith -m
```

3. **Add Routes:**
```php
// routes/web.php
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('hadith', Admin\HadithController::class);
});
```

4. **Create Views:**
```bash
mkdir resources/views/admin/hadith
touch resources/views/admin/hadith/index.blade.php
touch resources/views/admin/hadith/create.blade.php
```

5. **Add to Navigation:**
```blade
{{-- resources/views/admin/layouts/sidebar.blade.php --}}
<a href="{{ route('admin.hadith.index') }}">Hadith</a>
```

### Form Builder Pattern
Use the standardized form builder for consistent UI.

```blade
<div class="language-tabs" x-data="{ activeTab: 'ar' }">
    {{-- Language tabs --}}
    <div class="flex gap-2 mb-4">
        <button @click="activeTab = 'ar'" :class="activeTab === 'ar' ? 'active' : ''">
            العربية
        </button>
        <button @click="activeTab = 'en'" :class="activeTab === 'en' ? 'active' : ''">
            English
        </button>
    </div>

    {{-- Arabic content --}}
    <div x-show="activeTab === 'ar'">
        <input type="text" name="title[ar]" placeholder="العنوان">
    </div>

    {{-- English content --}}
    <div x-show="activeTab === 'en'">
        <input type="text" name="title[en]" placeholder="Title">
    </div>
</div>
```

### DataTable Pattern
Use server-side DataTables for large datasets.

```blade
<table id="articles-table" class="datatable">
    <thead>
        <tr>
            <th>{{ __('Title') }}</th>
            <th>{{ __('Category') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Actions') }}</th>
        </tr>
    </thead>
</table>

@push('scripts')
<script>
$(document).ready(function() {
    $('#articles-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.articles.data") }}',
        columns: [
            { data: 'title', name: 'title' },
            { data: 'category', name: 'category.name' },
            { data: 'status', name: 'status' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });
});
</script>
@endpush
```

## Configuration Files

### Key Config Files
- `config/app.php` - App settings, timezone, locale
- `config/translatable.php` - Spatie translatable settings
- `config/permission.php` - Role & permission settings
- `config/media-library.php` - Media conversions & settings
- `config/database.php` - Database connections
- `config/filesystems.php` - Storage configuration

### Environment Variables (.env)
```env
APP_NAME="The Last Messenger"
APP_URL=http://localhost:8000
APP_LOCALE=ar
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=the_last_messenger
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public
MEDIA_DISK=public
```

## Testing Strategy

### Feature Tests
Test full user workflows (admin actions, API endpoints).

```php
// tests/Feature/ArticleTest.php
public function test_admin_can_create_article()
{
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('admin.articles.store'), [
        'title' => ['ar' => 'عنوان', 'en' => 'Title'],
        'content' => ['ar' => 'محتوى', 'en' => 'Content'],
    ]);

    $response->assertStatus(302);
    $this->assertDatabaseHas('articles', ['slug' => 'title']);
}
```

### Unit Tests
Test individual methods and business logic.

```php
// tests/Unit/DateUtilTest.php
public function test_converts_gregorian_to_hijri()
{
    $hijriDate = DateUtil::convertToHijri('2024-11-11');
    $this->assertNotNull($hijriDate);
}
```

## Known Issues & Limitations

### Current Limitations
1. **No Elasticsearch**: Search uses basic MySQL LIKE queries (needs upgrade)
2. **No Redis**: Cache and queue use database/file drivers (needs upgrade)
3. **No Real-time Notifications**: WebSocket support not implemented yet
4. **Media CDN**: Media served from local storage (needs S3 + CDN)
5. **API Versioning**: Only v1 exists, no versioning strategy yet

### Security Todos
- [ ] Implement rate limiting for API endpoints
- [ ] Add CORS configuration for frontend integration
- [ ] Set up SSL/TLS for production
- [ ] Implement API key management for third-party access
- [ ] Add security headers (CSP, HSTS, etc.)

## Priority Tasks

### Phase 1 - Backend Core (Current)
- ✅ Admin panel design system
- ✅ Multi-language support (translation tables — see the Translatable Models section)
- ✅ Articles/Blog module (admin CRUD + public API)
- ✅ Media management (Spatie Media Library + encrypted MediaService library)
- ✅ Categories & tags
- ✅ User authentication & roles (admin guard separate from the public `web` guard)
- 🟡 Hadith module (models + Sunnah.com import pipeline; no admin or public UI yet)
- ✅ Quran module (CQRS read stack, full admin CRUD)
- ✅ Seerah module (events, timeline, admin CRUD)
- ✅ People module (companions + genealogy, admin CRUD)
- ✅ Media programs module (programs, episodes, live channels, YouTube sync)
- ✅ Content & Topics modules
- ❌ API documentation (Swagger/OpenAPI)

### Phase 2 - Advanced Features
- ❌ Elasticsearch integration
- ❌ Redis caching & queue
- ❌ Real-time notifications (Laravel Echo + Pusher)
- ❌ GraphQL API layer
- ❌ CDN integration (S3 + CloudFront)
- ❌ Media streaming optimization
- ❌ Full-text search across all modules

### Phase 3 - Scalability & Performance
- ❌ Microservices architecture preparation
- ❌ Docker + Kubernetes deployment
- ❌ Load testing & optimization
- ❌ Database read replicas
- ❌ Horizontal scaling strategy
- ❌ API rate limiting & throttling

## Quick Reference

### Common Laravel Commands
```bash
# Artisan
php artisan route:list              # List all routes
php artisan tinker                  # Laravel REPL
php artisan make:controller Name    # Create controller
php artisan make:model Name -m      # Create model with migration
php artisan migrate:status          # Check migration status

# Composer
composer dump-autoload              # Regenerate autoload files
composer require package/name       # Install package

# Cache
php artisan config:cache            # Cache configuration
php artisan route:cache             # Cache routes
php artisan view:cache              # Cache views
php artisan optimize                # Optimize everything
php artisan optimize:clear          # Clear all caches
```

### Git Workflow
```bash
# Check status
git status

# Commit changes
git add .
git commit -m "feat: add Hadith module"

# Push to remote
git push origin main
```

### Debugging
```bash
# Logs
tail -f storage/logs/laravel.log

# Or use Laravel Pail (real-time)
php artisan pail

# Database queries
DB::enableQueryLog();
// ... your code
dd(DB::getQueryLog());

# Debug with Ray
ray($variable);
```

## Documentation References

### Internal Documentation (docs/)
- `TRANSLATION_PATTERNS.md` — **Translation handling guide (REQUIRED READING)**
- `ADMIN-DESIGN-SYSTEM.md` — Complete admin UI design system
- `ARTICLE-API-DOCUMENTATION.md` — Articles API documentation
- `MEDIA-PICKER-ARCHITECTURE.md` — Media picker architecture guide
- `FORM-BUILDER-GUIDE.md` — Form builder patterns
- `DATATABLE-API-DOCUMENTATION.md` — DataTable implementation
- `ARABIC_RTL_CONFIGURATION_SUMMARY.md` — RTL support guide

### External Documentation
- Laravel 13 Docs: https://laravel.com/docs/13.x
- Spatie Translatable: https://spatie.be/docs/laravel-translatable
- Spatie Media Library: https://spatie.be/docs/laravel-medialibrary
- Spatie Permission: https://spatie.be/docs/laravel-permission
- Intervention Image: https://image.intervention.io/v3

## Additional Context

- **Project Language:** Primarily Arabic (RTL) with full multi-language support
- **Target Audience:** Global Muslim community + content administrators
- **Admin Users:** Content editors, translators, media managers
- **API Consumers:** Frontend (the-last-messenger-ui), mobile apps, third-party integrations
- **Performance Goal:** <200ms API response time, <2s page load
- **Scalability Target:** Support 10M+ users, 1M+ concurrent connections
- **Security:** OWASP Top 10 compliance, regular security audits

## Gotchas worth knowing before you change anything

- **Translations are in tables, not JSON columns.** See the Translatable Models
  section and `docs/TRANSLATION_PATTERNS.md` for the complete guide. **ALWAYS use
  `syncTranslations()` for writes; NEVER use `setTranslation()` with `save()`.**
  Most historical bugs in this repo trace back to code that was never migrated
  off the dropped JSON columns.
- **Verify a class exists before trusting an import.** Several files have imported
  classes that do not exist (`App\Domain\Blog\Models\Article` — the real one is
  `App\Models\Article`). PHP does not error on an unused `use`, and `Foo::class`
  resolves to a string without autoloading, so these hide until the line runs.
- **Do not swallow `Throwable` into a zeroed/empty fallback without `report()`.**
  A silent catch is how a renamed table made the entire admin dashboard read 0.
- **`Cache::flush()` is never the right invalidation.** It wipes other modules'
  caches and the rate limiter. Use the versioned-key pattern (`BaseRepository::clearCache`,
  `VersionedCacheKey`) or a targeted forget, and if you override `clearCache()`
  in a repository, call `parent::clearCache()`.
- **Route order matters.** Register static segments (`/trash`, `/history`, `/search`)
  *before* `Route::resource` or `/{param}`, or they resolve as the parameter.
- **Method spoofing does not work from a JSON body.** `_method: 'PUT'` in JSON is
  ignored (Symfony reads the form bag), so the request stays POST and 405s. Send a
  real `method: 'PUT'`.
- **Admin controllers returning `JsonResponse` to a plain HTML form** render raw JSON
  in the browser. Content-negotiate with `$request->expectsJson()`.
- **Verify against the real schema** (`SHOW COLUMNS`) rather than a model's docblock;
  several docblocks describe columns that were dropped.

---

**Last Updated:** August 2026
**Laravel Version:** 13.0
**PHP Version:** 8.5
- localize all view make it in arabic by default

## Active Technologies
- PHP 8.5 (platform pinned 8.5.4) + Laravel 13, spatie/laravel-permission, maatwebsite/excel, Blade + Alpine 3 + Tailwind. **No new package.** (005-seerah-schema-refactor)
- MySQL 8 (InnoDB, utf8mb4); public disk for thumb/cover/gallery files (005-seerah-schema-refactor)

- PHP 8.5, Laravel 13, MySQL 8 (InnoDB, utf8mb4). No new package added by in-flight features.

## Recent Changes

- `003-locations-module`: shared `locations` + `location_translations` tables in
  `app/Domain/Shared/`, with additive nullable FKs on `seerah_events`, `muslims` and
  `quran_surahs`. No existing place column is dropped. See
  `specs/003-locations-module/plan.md`.
