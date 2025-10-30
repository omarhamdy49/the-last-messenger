# Admin Dashboard Implementation Tasks

**Last Updated:** 2025-10-30
**Status:** Planning Complete - Ready for Implementation
**Total Tasks:** 29 | **Completed:** 0 | **In Progress:** 0 | **Pending:** 29

---

## Executive Summary

This document outlines all missing features and implementation tasks for the admin dashboard across 3 phases:

- **Phase 1 (Weeks 1-4):** Critical CRUD operations - 7 new controllers, 28 views
- **Phase 2 (Weeks 5-8):** Enhancements & Quran completion - 5 new controllers, 4 enhanced modules
- **Phase 3 (Weeks 9-16):** Advanced features - analytics, bulk ops, workflows

**Total Effort:** 640 hours (16 weeks) | **Team:** 2-3 developers

---

## PHASE 1: CRITICAL CRUD OPERATIONS (Weeks 1-4)

### Priority Level: 🔴 P0 - CRITICAL
These features MUST be implemented. They unblock core functionality.

---

## Task 1: Create CategoryController + Views

**Priority:** P0 (CRITICAL - Blocks article organization)
**Week:** 1-2
**Effort:** 40 hours (1 developer)
**Deliverables:** 1 controller + 4 views + 2 form requests

### Background
- Articles, Media Programs need to be organized by categories
- Categories support hierarchical structure (parent/child)
- Currently hardcoded in forms; need full management interface

### Requirements

#### 1.1 CategoryController
**File:** `app/Http/Controllers/Admin/CategoryController.php`

- [ ] Create controller extending Controller
- [ ] Constructor: Inject CategoryService
- [ ] Middleware: Apply admin auth + permission checks
- [ ] `index()` method:
  - [ ] Return paginated categories (50 per page)
  - [ ] Support search by name (multi-language)
  - [ ] Support filter by type (blog, media, timeline)
  - [ ] Support filter by parent_id (show children of specific parent)
  - [ ] Handle AJAX requests (return JSON) vs HTML page loads
  - [ ] Return stats: total categories, categories per type
- [ ] `create()` method:
  - [ ] Return create form view
- [ ] `store()` method:
  - [ ] Validate input (StoreCategoryRequest)
  - [ ] Create category via service
  - [ ] Handle multi-language (ar, en, fr, tr, ur)
  - [ ] Return success/error response
- [ ] `edit()` method:
  - [ ] Load category with relationships
  - [ ] Return edit form view
- [ ] `update()` method:
  - [ ] Validate input (UpdateCategoryRequest)
  - [ ] Update category via service
  - [ ] Handle parent change (prevent circular references)
  - [ ] Return success/error response
- [ ] `show()` method:
  - [ ] Load category with child categories and articles count
  - [ ] Return show view with category details
- [ ] `destroy()` method:
  - [ ] Delete category (soft delete?)
  - [ ] Handle cascade: articles using this category
  - [ ] Return success/error response
- [ ] `reorder()` method (AJAX):
  - [ ] Accept array of category IDs with new order
  - [ ] Update display_order for all categories
  - [ ] Return JSON response

**Validation Rules (StoreCategoryRequest):**
```php
[
    'name.ar' => 'required|string|max:255',
    'name.en' => 'required|string|max:255',
    'name.fr' => 'required|string|max:255',
    'name.tr' => 'required|string|max:255',
    'name.ur' => 'required|string|max:255',
    'description.ar' => 'nullable|string|max:1000',
    'description.en' => 'nullable|string|max:1000',
    'description.fr' => 'nullable|string|max:1000',
    'description.tr' => 'nullable|string|max:1000',
    'description.ur' => 'nullable|string|max:1000',
    'type' => 'required|in:blog,media,timeline',
    'parent_id' => 'nullable|exists:categories,id',
    'icon' => 'nullable|string|max:50',
    'color' => 'nullable|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
    'display_order' => 'nullable|integer|min:0',
]
```

#### 1.2 Form Requests
**Files:**
- `app/Http/Requests/Admin/StoreCategoryRequest.php`
- `app/Http/Requests/Admin/UpdateCategoryRequest.php`

- [ ] StoreCategoryRequest: Validation rules above
- [ ] UpdateCategoryRequest: Same rules
- [ ] Both: Authorize method (check admin permission)
- [ ] Both: Prepare data (format JSON fields)

#### 1.3 Views

**1.3.1 index.blade.php** - Category listing with tree view
- [ ] Extend admin layout
- [ ] Display categories as tree structure (indented list)
- [ ] Show columns:
  - [ ] Checkbox (select multiple)
  - [ ] Name (with language fallback: en or ar)
  - [ ] Type badge (blog, media, timeline)
  - [ ] Children count
  - [ ] Articles using this category count
  - [ ] Display order indicator
  - [ ] Actions (edit, delete)
- [ ] Search bar (by name)
- [ ] Filter dropdowns:
  - [ ] Filter by type (All, Blog, Media, Timeline)
  - [ ] Filter by parent (show root only, or specific parent)
- [ ] Bulk actions:
  - [ ] Select all checkbox
  - [ ] Delete selected button (with confirmation)
  - [ ] Change type for selected
- [ ] Create button at top right
- [ ] Pagination (show tree view if < 50, else paginate)
- [ ] Dark/light mode support
- [ ] AJAX data loading:
  - [ ] Load on page change
  - [ ] Load on search/filter change
  - [ ] Show loading skeleton
  - [ ] Handle errors with toast notifications

**1.3.2 create.blade.php** - Create category form
- [ ] Form fields (for each language):
  - [ ] Name inputs (ar, en, fr, tr, ur) - text inputs in 2 columns
  - [ ] Description inputs (ar, en, fr, tr, ur) - textarea in 2 columns
- [ ] Other fields:
  - [ ] Type dropdown (blog, media, timeline)
  - [ ] Parent category selector (dropdown with tree structure)
  - [ ] Icon picker (modal with icon library)
  - [ ] Color picker (color input with presets)
  - [ ] Display order input (number field)
- [ ] Validation messages
- [ ] Submit/Cancel buttons
- [ ] Dark/light mode support

**1.3.3 edit.blade.php** - Edit category form
- [ ] Same as create.blade.php but with pre-filled values
- [ ] Additional warning if changing parent (confirm)
- [ ] Warning if articles use this category (show count)
- [ ] Soft delete option (if applicable)

**1.3.4 show.blade.php** - Category details view
- [ ] Display category information (all languages)
- [ ] Show type, icon, color
- [ ] List child categories (table with links)
- [ ] List articles using this category (paginated)
- [ ] List media programs using this category (paginated)
- [ ] Edit button
- [ ] Delete button (with warning if has children/articles)
- [ ] Breadcrumb showing parent category path

#### 1.4 Routes
**File:** `routes/admin.php`
- [ ] Add routes:
  ```php
  Route::resource('categories', CategoryController::class);
  Route::post('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');
  ```

#### 1.5 Sidebar
**File:** `resources/views/admin/partials/sidebar.blade.php`
- [ ] Add sidebar link under "Blog" section:
  ```
  📁 Categories → /su/portal/v1/admin/categories
  ```

#### 1.6 Testing Checklist
- [ ] Create root category
- [ ] Create child category (with parent)
- [ ] Edit category name in all languages
- [ ] Change parent category (verify no circular refs)
- [ ] Search categories by name
- [ ] Filter by type
- [ ] Delete category (check cascade handling for articles)
- [ ] Test AJAX requests in browser console
- [ ] Test in dark/light mode
- [ ] Test mobile responsiveness

---

## Task 2: Create TagController + Views

**Priority:** P0 (CRITICAL - Blocks article tagging)
**Week:** 1-2
**Effort:** 35 hours (1 developer)
**Deliverables:** 1 controller + 4 views + 2 form requests

### Background
- Articles use tags for categorization/discovery
- Tags can be created on-the-fly in article forms, but need centralized management
- Need to show usage statistics and prevent duplicate tags
- Need merge functionality for duplicate tags

### Requirements

#### 2.1 TagController
**File:** `app/Http/Controllers/Admin/TagController.php`

- [ ] Create controller extending Controller
- [ ] Constructor: Inject TagService
- [ ] Middleware: Apply admin auth + permission checks
- [ ] `index()` method:
  - [ ] Return paginated tags (50 per page)
  - [ ] Support search by name (multi-language)
  - [ ] Support filter by type (article, hadith, program, etc.)
  - [ ] Include usage count per tag (articles using it)
  - [ ] Handle AJAX requests (return JSON) vs HTML page loads
  - [ ] Return stats: total tags, total uses, most used tags
- [ ] `create()` method:
  - [ ] Return create form view
- [ ] `store()` method:
  - [ ] Validate input (StoreTagRequest)
  - [ ] Check for duplicate (name, language) and warn if exists
  - [ ] Create tag via service
  - [ ] Handle multi-language (ar, en, fr, tr, ur)
  - [ ] Return success/error response
- [ ] `edit()` method:
  - [ ] Load tag with article count
  - [ ] Return edit form view
- [ ] `update()` method:
  - [ ] Validate input (UpdateTagRequest)
  - [ ] Update tag via service
  - [ ] Return success/error response
- [ ] `show()` method:
  - [ ] Load tag with all articles using it (paginated)
  - [ ] Show usage statistics
  - [ ] Return show view
- [ ] `destroy()` method:
  - [ ] Delete tag
  - [ ] Handle cascade: remove tag from articles
  - [ ] Return success/error response
- [ ] `merge()` method (AJAX):
  - [ ] Accept two tag IDs
  - [ ] Merge tag B into tag A (move all articles from B to A)
  - [ ] Delete tag B
  - [ ] Return JSON response with merged count
- [ ] `bulkDelete()` method (AJAX):
  - [ ] Accept array of tag IDs
  - [ ] Delete multiple tags
  - [ ] Return success/error with count deleted

**Validation Rules (StoreTagRequest):**
```php
[
    'name.ar' => 'required|string|max:100|unique:tags,name->ar',
    'name.en' => 'required|string|max:100|unique:tags,name->en',
    'name.fr' => 'nullable|string|max:100',
    'name.tr' => 'nullable|string|max:100',
    'name.ur' => 'nullable|string|max:100',
    'type' => 'required|in:article,hadith,program,event',
    'slug' => 'nullable|string|unique:tags,slug',
]
```

#### 2.2 Form Requests
**Files:**
- `app/Http/Requests/Admin/StoreTagRequest.php`
- `app/Http/Requests/Admin/UpdateTagRequest.php`

- [ ] Same pattern as Category
- [ ] Unique validation on name per language
- [ ] Slug auto-generation from English name

#### 2.3 Views

**2.3.1 index.blade.php** - Tag listing
- [ ] Display tags in data table:
  - [ ] Checkbox (select multiple)
  - [ ] Tag name (with language fallback)
  - [ ] Type badge (article, hadith, program, event)
  - [ ] Usage count (articles using this tag)
  - [ ] Created date
  - [ ] Actions (edit, delete)
- [ ] Search bar (by name)
- [ ] Filter dropdowns:
  - [ ] Filter by type
  - [ ] Sort by: name, usage count, created date
- [ ] Stats cards at top:
  - [ ] Total tags
  - [ ] Total uses
  - [ ] Most used tag
  - [ ] Recently added
- [ ] Bulk actions:
  - [ ] Delete selected
  - [ ] Merge selected (opens merge modal)
- [ ] Create button
- [ ] Pagination with AJAX loading
- [ ] Dark/light mode support

**2.3.2 create.blade.php** - Create tag form
- [ ] Form fields (for each language):
  - [ ] Name inputs (ar, en, fr, tr, ur)
- [ ] Other fields:
  - [ ] Type dropdown
  - [ ] Slug field (auto-fill from English name)
  - [ ] Duplicate warning (show if tag with similar name exists)
- [ ] Submit/Cancel buttons

**2.3.3 edit.blade.php** - Edit tag form
- [ ] Same as create.blade.php but with pre-filled values
- [ ] Show articles using this tag count
- [ ] Merge option (button to merge modal)

**2.3.4 show.blade.php** - Tag details view
- [ ] Display tag information
- [ ] Show type, slug
- [ ] Usage statistics:
  - [ ] Total articles using this tag
  - [ ] Chart showing usage trend (if available)
- [ ] List articles using this tag (paginated table):
  - [ ] Article title
  - [ ] Author
  - [ ] Status
  - [ ] Date
- [ ] Edit button
- [ ] Merge button (opens modal)
- [ ] Delete button

**2.3.5 merge-modal.blade.php** - Merge dialog (Alpine.js component)
- [ ] Modal dialog
- [ ] Select dropdown to choose tag to merge into
- [ ] Show preview: "Merge 'OldTag' into 'SelectedTag'"
- [ ] Confirm/Cancel buttons
- [ ] Success message after merge

#### 2.4 Routes
**File:** `routes/admin.php`
- [ ] Add routes:
  ```php
  Route::resource('tags', TagController::class);
  Route::post('tags/merge', [TagController::class, 'merge'])->name('tags.merge');
  Route::post('tags/bulk-delete', [TagController::class, 'bulkDelete'])->name('tags.bulk-delete');
  ```

#### 2.5 Sidebar
**File:** `resources/views/admin/partials/sidebar.blade.php`
- [ ] Add sidebar link under "Blog" section:
  ```
  🏷️ Tags → /su/portal/v1/admin/tags
  ```

#### 2.6 Testing Checklist
- [ ] Create tag with multi-language names
- [ ] Edit tag
- [ ] Search tags by name
- [ ] Filter by type
- [ ] View tag usage statistics
- [ ] Merge two tags (verify articles moved)
- [ ] Delete tag
- [ ] Bulk delete multiple tags
- [ ] Test duplicate warning
- [ ] Test in dark/light mode

---

## Task 3: Create SeriesController + Views

**Priority:** P0 (CRITICAL - Blocks article grouping)
**Week:** 1-2
**Effort:** 40 hours (1 developer)
**Deliverables:** 1 controller + 4 views + 2 form requests

### Background
- Articles can be grouped into series (e.g., "Prophet's Life in 30 Articles")
- Series have metadata: title, description, thumbnail, display order
- Articles can be reordered within a series
- Series can have many articles, articles can belong to one series

### Requirements

#### 3.1 SeriesController
**File:** `app/Http/Controllers/Admin/SeriesController.php`

- [ ] Create controller extending Controller
- [ ] Constructor: Inject SeriesService
- [ ] Middleware: Apply admin auth + permission checks
- [ ] `index()` method:
  - [ ] Return paginated series (50 per page)
  - [ ] Support search by title
  - [ ] Include article count per series
  - [ ] Handle AJAX requests (return JSON) vs HTML page loads
  - [ ] Return stats: total series, total articles in series
- [ ] `create()` method:
  - [ ] Return create form view
- [ ] `store()` method:
  - [ ] Validate input (StoreSeriesRequest)
  - [ ] Create series via service
  - [ ] Handle thumbnail upload
  - [ ] Handle multi-language (ar, en, fr, tr, ur)
  - [ ] Return success/error response
- [ ] `edit()` method:
  - [ ] Load series with articles
  - [ ] Return edit form view
- [ ] `update()` method:
  - [ ] Validate input (UpdateSeriesRequest)
  - [ ] Update series via service
  - [ ] Handle thumbnail upload (update or delete old)
  - [ ] Return success/error response
- [ ] `show()` method:
  - [ ] Load series with articles (ordered)
  - [ ] Show series statistics
  - [ ] Return show view
- [ ] `destroy()` method:
  - [ ] Delete series
  - [ ] Handle cascade: remove series_id from articles
  - [ ] Delete thumbnail file
  - [ ] Return success/error response
- [ ] `reorderArticles()` method (AJAX):
  - [ ] Accept array of article IDs with new order
  - [ ] Update article positions within series
  - [ ] Return JSON response
- [ ] `bulkDelete()` method (AJAX):
  - [ ] Accept array of series IDs
  - [ ] Delete multiple series
  - [ ] Delete thumbnail files
  - [ ] Return success/error with count deleted

**Validation Rules (StoreSeriesRequest):**
```php
[
    'title.ar' => 'required|string|max:255',
    'title.en' => 'required|string|max:255',
    'title.fr' => 'nullable|string|max:255',
    'title.tr' => 'nullable|string|max:255',
    'title.ur' => 'nullable|string|max:255',
    'description.ar' => 'nullable|string|max:1000',
    'description.en' => 'nullable|string|max:1000',
    'description.fr' => 'nullable|string|max:1000',
    'description.tr' => 'nullable|string|max:1000',
    'description.ur' => 'nullable|string|max:1000',
    'thumbnail' => 'nullable|image|max:2048',
    'display_order' => 'nullable|integer|min:0',
]
```

#### 3.2 Form Requests
**Files:**
- `app/Http/Requests/Admin/StoreSeriesRequest.php`
- `app/Http/Requests/Admin/UpdateSeriesRequest.php`

- [ ] Same pattern as Category/Tag

#### 3.3 Views

**3.3.1 index.blade.php** - Series listing
- [ ] Display series in data table:
  - [ ] Checkbox (select multiple)
  - [ ] Thumbnail image
  - [ ] Title (with language fallback)
  - [ ] Article count
  - [ ] Created date
  - [ ] Actions (edit, delete)
- [ ] Search bar (by title)
- [ ] Sort options: name, article count, created date
- [ ] Stats cards at top:
  - [ ] Total series
  - [ ] Total articles in series
  - [ ] Most articles in series
- [ ] Bulk actions:
  - [ ] Delete selected
- [ ] Create button
- [ ] Pagination with AJAX loading
- [ ] Dark/light mode support

**3.3.2 create.blade.php** - Create series form
- [ ] Form fields (for each language):
  - [ ] Title inputs (ar, en, fr, tr, ur)
  - [ ] Description textarea (ar, en, fr, tr, ur)
- [ ] Other fields:
  - [ ] Thumbnail upload (file input with preview)
  - [ ] Display order input
- [ ] Submit/Cancel buttons

**3.3.3 edit.blade.php** - Edit series form
- [ ] Same as create.blade.php but with pre-filled values
- [ ] Show article list with drag-drop reordering
- [ ] Thumbnail display with delete option
- [ ] Submit/Cancel buttons

**3.3.4 show.blade.php** - Series details view
- [ ] Display series information
- [ ] Show thumbnail
- [ ] List articles in order:
  - [ ] Drag-drop reordering (Alpine.js)
  - [ ] Article title, author, status
  - [ ] Order number
  - [ ] Remove from series button
- [ ] Edit button
- [ ] Delete button

#### 3.4 Routes
**File:** `routes/admin.php`
- [ ] Add routes:
  ```php
  Route::resource('series', SeriesController::class);
  Route::post('series/{series}/reorder-articles', [SeriesController::class, 'reorderArticles'])->name('series.reorder-articles');
  Route::post('series/bulk-delete', [SeriesController::class, 'bulkDelete'])->name('series.bulk-delete');
  ```

#### 3.5 Sidebar
**File:** `resources/views/admin/partials/sidebar.blade.php`
- [ ] Add sidebar link under "Blog" section:
  ```
  📚 Series → /su/portal/v1/admin/series
  ```

#### 3.6 Testing Checklist
- [ ] Create series with thumbnail
- [ ] Edit series
- [ ] Search series by title
- [ ] Add articles to series (via article edit)
- [ ] Reorder articles within series
- [ ] Remove article from series
- [ ] Delete series (verify articles are unassociated)
- [ ] Test thumbnail upload/delete
- [ ] Test in dark/light mode

---

## Task 4: Create MediaCategoryController + Views

**Priority:** P0 (CRITICAL - Blocks media organization)
**Week:** 2
**Effort:** 30 hours (1 developer)
**Deliverables:** 1 controller + 4 views + 2 form requests

### Background
- Media programs (videos) are organized by categories
- Categories can have display order
- Similar to blog categories but simpler (no hierarchies)

### Requirements

#### 4.1 MediaCategoryController
**File:** `app/Http/Controllers/Admin/MediaCategoryController.php`

- [ ] Create controller extending Controller
- [ ] Constructor: Inject MediaCategoryService
- [ ] Middleware: Apply admin auth + permission checks
- [ ] `index()` method:
  - [ ] Return paginated categories (50 per page)
  - [ ] Support search by name
  - [ ] Include program count per category
  - [ ] Handle AJAX requests (return JSON) vs HTML page loads
  - [ ] Return stats: total categories, total programs
- [ ] `create()` method:
  - [ ] Return create form view
- [ ] `store()` method:
  - [ ] Validate input (StoreMediaCategoryRequest)
  - [ ] Create category via service
  - [ ] Handle multi-language (ar, en, fr, tr, ur)
  - [ ] Return success/error response
- [ ] `edit()` method:
  - [ ] Load category with program count
  - [ ] Return edit form view
- [ ] `update()` method:
  - [ ] Validate input (UpdateMediaCategoryRequest)
  - [ ] Update category via service
  - [ ] Return success/error response
- [ ] `show()` method:
  - [ ] Load category with programs (paginated)
  - [ ] Return show view
- [ ] `destroy()` method:
  - [ ] Delete category
  - [ ] Handle cascade: remove category from programs
  - [ ] Return success/error response
- [ ] `reorder()` method (AJAX):
  - [ ] Accept array of category IDs with new order
  - [ ] Update display_order for all
  - [ ] Return JSON response

**Validation Rules (StoreMediaCategoryRequest):**
```php
[
    'name.ar' => 'required|string|max:255',
    'name.en' => 'required|string|max:255',
    'name.fr' => 'nullable|string|max:255',
    'name.tr' => 'nullable|string|max:255',
    'name.ur' => 'nullable|string|max:255',
    'description.ar' => 'nullable|string|max:1000',
    'description.en' => 'nullable|string|max:1000',
    'description.fr' => 'nullable|string|max:1000',
    'description.tr' => 'nullable|string|max:1000',
    'description.ur' => 'nullable|string|max:1000',
    'icon' => 'nullable|string|max:50',
    'display_order' => 'nullable|integer|min:0',
]
```

#### 4.2 Form Requests
**Files:**
- `app/Http/Requests/Admin/StoreMediaCategoryRequest.php`
- `app/Http/Requests/Admin/UpdateMediaCategoryRequest.php`

- [ ] Basic validation as above

#### 4.3 Views
- [ ] index.blade.php (similar to CategoryController)
- [ ] create.blade.php (name, description, icon picker, display order)
- [ ] edit.blade.php (same as create)
- [ ] show.blade.php (category details + programs list)

#### 4.4 Routes & Sidebar
- [ ] Add routes to routes/admin.php
- [ ] Add sidebar link under "Media" section

#### 4.5 Testing Checklist
- [ ] Create/edit/delete categories
- [ ] Search categories
- [ ] Reorder categories
- [ ] Assign programs to categories

---

## Task 5: Create HadithBookController + Views

**Priority:** P0 (CRITICAL - Blocks hadith organization)
**Week:** 3
**Effort:** 35 hours (1 developer)
**Deliverables:** 1 controller + 4 views + 2 form requests

### Background
- Hadith are organized by books (Sahih Bukhari, Sahih Muslim, Jami' al-Tirmidhi, Sunan Abu Dawud, Sunan Ibn Majah, Sunan al-Nasa'i)
- Books have chapters (each book has multiple chapters)
- Each chapter has hadith
- Need to manage books: name, description, author, number of hadith

### Requirements

#### 5.1 HadithBookController
**File:** `app/Http/Controllers/Admin/HadithBookController.php`

- [ ] Create controller extending Controller
- [ ] Constructor: Inject HadithBookService
- [ ] Middleware: Apply admin auth + permission checks
- [ ] `index()` method:
  - [ ] Return paginated hadith books (50 per page)
  - [ ] Support search by name/author
  - [ ] Include chapter count and hadith count per book
  - [ ] Handle AJAX requests (return JSON) vs HTML page loads
  - [ ] Return stats: total books, total chapters, total hadith
- [ ] `create()` method:
  - [ ] Return create form view
- [ ] `store()` method:
  - [ ] Validate input (StoreHadithBookRequest)
  - [ ] Create book via service
  - [ ] Handle multi-language (ar, en, fr, tr, ur)
  - [ ] Return success/error response
- [ ] `edit()` method:
  - [ ] Load book with chapter and hadith counts
  - [ ] Return edit form view
- [ ] `update()` method:
  - [ ] Validate input (UpdateHadithBookRequest)
  - [ ] Update book via service
  - [ ] Return success/error response
- [ ] `show()` method:
  - [ ] Load book with chapters (paginated)
  - [ ] Load hadith count per chapter
  - [ ] Return show view
- [ ] `destroy()` method:
  - [ ] Delete book (soft delete?)
  - [ ] Handle cascade: what happens to hadith/chapters?
  - [ ] Return success/error response
- [ ] `reorder()` method (AJAX):
  - [ ] Accept array of book IDs with new order
  - [ ] Update display_order for all
  - [ ] Return JSON response
- [ ] `bulkDelete()` method (AJAX):
  - [ ] Accept array of book IDs
  - [ ] Delete multiple books
  - [ ] Return success/error with count deleted

**Validation Rules (StoreHadithBookRequest):**
```php
[
    'name.ar' => 'required|string|max:255',
    'name.en' => 'required|string|max:255',
    'name.fr' => 'nullable|string|max:255',
    'name.tr' => 'nullable|string|max:255',
    'name.ur' => 'nullable|string|max:255',
    'author.ar' => 'required|string|max:255',
    'author.en' => 'required|string|max:255',
    'author.fr' => 'nullable|string|max:255',
    'author.tr' => 'nullable|string|max:255',
    'author.ur' => 'nullable|string|max:255',
    'description.ar' => 'nullable|string|max:1000',
    'description.en' => 'nullable|string|max:1000',
    'description.fr' => 'nullable|string|max:1000',
    'description.tr' => 'nullable|string|max:1000',
    'description.ur' => 'nullable|string|max:1000',
    'total_hadith' => 'nullable|integer|min:0',
    'display_order' => 'nullable|integer|min:0',
]
```

#### 5.2 Form Requests
**Files:**
- `app/Http/Requests/Admin/StoreHadithBookRequest.php`
- `app/Http/Requests/Admin/UpdateHadithBookRequest.php`

- [ ] Validation as above

#### 5.3 Views
- [ ] index.blade.php (books table: name, author, chapter count, hadith count)
- [ ] create.blade.php (name, author, description, total_hadith, display_order in all languages)
- [ ] edit.blade.php (same as create + edit button)
- [ ] show.blade.php (book details + chapters list + hadith count per chapter)

#### 5.4 Routes & Sidebar
- [ ] Add routes to routes/admin.php
- [ ] Add sidebar link under "Hadith" section

#### 5.5 Testing Checklist
- [ ] Create hadith books
- [ ] Edit books
- [ ] Search books
- [ ] Reorder books
- [ ] View chapters in book
- [ ] View hadith count per chapter

---

## Task 6: Create HadithChapterController + Views

**Priority:** P0 (CRITICAL - Blocks hadith organization)
**Week:** 3-4
**Effort:** 40 hours (1 developer)
**Deliverables:** 1 controller + 4 views + 2 form requests

### Background
- Each hadith book has chapters
- Chapters organize hadith by topic
- Each chapter has a number, name, and many hadith
- Need full CRUD for chapters

### Requirements

#### 6.1 HadithChapterController
**File:** `app/Http/Controllers/Admin/HadithChapterController.php`

- [ ] Create controller extending Controller
- [ ] Constructor: Inject HadithChapterService
- [ ] Middleware: Apply admin auth + permission checks
- [ ] `index()` method:
  - [ ] Return paginated chapters (50 per page)
  - [ ] Support search by name
  - [ ] Support filter by book
  - [ ] Include hadith count per chapter
  - [ ] Group chapters by book in display
  - [ ] Handle AJAX requests (return JSON) vs HTML page loads
  - [ ] Return stats: total chapters, total hadith in chapters
- [ ] `create()` method:
  - [ ] Return create form view
- [ ] `store()` method:
  - [ ] Validate input (StoreHadithChapterRequest)
  - [ ] Create chapter via service
  - [ ] Handle multi-language (ar, en, fr, tr, ur)
  - [ ] Return success/error response
- [ ] `edit()` method:
  - [ ] Load chapter with hadith count
  - [ ] Return edit form view
- [ ] `update()` method:
  - [ ] Validate input (UpdateHadithChapterRequest)
  - [ ] Update chapter via service
  - [ ] Warn if changing book (confirm)
  - [ ] Return success/error response
- [ ] `show()` method:
  - [ ] Load chapter with hadith (paginated)
  - [ ] Return show view
- [ ] `destroy()` method:
  - [ ] Delete chapter
  - [ ] Handle cascade: warn if chapter has hadith, option to reassign to different chapter
  - [ ] Return success/error response
- [ ] `reorder()` method (AJAX):
  - [ ] Accept array of chapter IDs with new order
  - [ ] Update chapter_order for all
  - [ ] Return JSON response
- [ ] `bulkDelete()` method (AJAX):
  - [ ] Accept array of chapter IDs
  - [ ] Delete multiple chapters
  - [ ] Return success/error with count deleted

**Validation Rules (StoreHadithChapterRequest):**
```php
[
    'hadith_book_id' => 'required|exists:hadith_books,id',
    'chapter_number' => 'required|integer|min:1',
    'name.ar' => 'required|string|max:255',
    'name.en' => 'required|string|max:255',
    'name.fr' => 'nullable|string|max:255',
    'name.tr' => 'nullable|string|max:255',
    'name.ur' => 'nullable|string|max:255',
    'description.ar' => 'nullable|string|max:1000',
    'description.en' => 'nullable|string|max:1000',
    'description.fr' => 'nullable|string|max:1000',
    'description.tr' => 'nullable|string|max:1000',
    'description.ur' => 'nullable|string|max:1000',
]
```

#### 6.2 Form Requests
**Files:**
- `app/Http/Requests/Admin/StoreHadithChapterRequest.php`
- `app/Http/Requests/Admin/UpdateHadithChapterRequest.php`

- [ ] Validation as above

#### 6.3 Views
- [ ] index.blade.php (chapters grouped by book: book name, chapter number, chapter name, hadith count)
- [ ] create.blade.php (book selector dropdown, chapter number, name, description in all languages)
- [ ] edit.blade.php (same as create + warning if changing book)
- [ ] show.blade.php (chapter details + list hadith in chapter)

#### 6.4 Routes & Sidebar
- [ ] Add routes to routes/admin.php
- [ ] Add sidebar link under "Hadith" section (or as submenu of Books)

#### 6.5 Testing Checklist
- [ ] Create chapters for different books
- [ ] Edit chapters
- [ ] Search chapters
- [ ] Filter by book
- [ ] Reorder chapters
- [ ] View hadith in chapter

---

## Task 7: Create MediaEpisodeController + Views

**Priority:** P0 (CRITICAL - Blocks Netflix-style feature)
**Week:** 4
**Effort:** 50 hours (1-2 developers)
**Deliverables:** 1 controller + 4 views + 2 form requests

### Background
- Media programs are Netflix-style series of episodes
- Each episode has: title, description, video URL (YouTube/Vimeo/direct), thumbnail, duration, release date, episode number
- Episodes are ordered within a program
- Need video URL validation and preview functionality
- Need drag-drop reordering

### Requirements

#### 7.1 MediaEpisodeController
**File:** `app/Http/Controllers/Admin/MediaEpisodeController.php`

- [ ] Create controller extending Controller
- [ ] Constructor: Inject MediaEpisodeService
- [ ] Middleware: Apply admin auth + permission checks
- [ ] `index()` method:
  - [ ] Return paginated episodes (50 per page)
  - [ ] Support search by title
  - [ ] Support filter by program
  - [ ] Support filter by published status
  - [ ] Group episodes by program
  - [ ] Handle AJAX requests (return JSON) vs HTML page loads
  - [ ] Return stats: total episodes, total duration, published count
- [ ] `create()` method:
  - [ ] Return create form view
- [ ] `store()` method:
  - [ ] Validate input (StoreMediaEpisodeRequest)
  - [ ] Validate video URL (YouTube/Vimeo/direct video)
  - [ ] Create episode via service
  - [ ] Handle thumbnail upload
  - [ ] Auto-detect duration from video URL if possible
  - [ ] Handle multi-language (ar, en, fr, tr, ur)
  - [ ] Return success/error response
- [ ] `edit()` method:
  - [ ] Load episode with video preview
  - [ ] Return edit form view
- [ ] `update()` method:
  - [ ] Validate input (UpdateMediaEpisodeRequest)
  - [ ] Update episode via service
  - [ ] Handle thumbnail upload (update or delete old)
  - [ ] Return success/error response
- [ ] `show()` method:
  - [ ] Load episode with video embed
  - [ ] Show episode statistics (views, likes)
  - [ ] Show next/previous episode in program
  - [ ] Return show view
- [ ] `destroy()` method:
  - [ ] Delete episode
  - [ ] Delete thumbnail file
  - [ ] Auto-update program's episode count
  - [ ] Return success/error response
- [ ] `reorderEpisodes()` method (AJAX):
  - [ ] Accept array of episode IDs with new order
  - [ ] Update episode_number for all
  - [ ] Return JSON response
- [ ] `bulkDelete()` method (AJAX):
  - [ ] Accept array of episode IDs
  - [ ] Delete multiple episodes
  - [ ] Delete thumbnail files
  - [ ] Return success/error with count deleted
- [ ] `bulkPublish()` method (AJAX):
  - [ ] Accept array of episode IDs
  - [ ] Set status = 'published' for all
  - [ ] Return JSON response
- [ ] `bulkUnpublish()` method (AJAX):
  - [ ] Accept array of episode IDs
  - [ ] Set status = 'draft' for all
  - [ ] Return JSON response

**Validation Rules (StoreMediaEpisodeRequest):**
```php
[
    'media_program_id' => 'required|exists:media_programs,id',
    'episode_number' => 'required|integer|min:1',
    'title.ar' => 'required|string|max:255',
    'title.en' => 'required|string|max:255',
    'title.fr' => 'nullable|string|max:255',
    'title.tr' => 'nullable|string|max:255',
    'title.ur' => 'nullable|string|max:255',
    'description.ar' => 'nullable|string|max:2000',
    'description.en' => 'nullable|string|max:2000',
    'description.fr' => 'nullable|string|max:2000',
    'description.tr' => 'nullable|string|max:2000',
    'description.ur' => 'nullable|string|max:2000',
    'video_url' => 'required|string|url', // Custom validation for YouTube/Vimeo/direct
    'video_type' => 'required|in:youtube,vimeo,direct',
    'thumbnail' => 'nullable|image|max:2048',
    'duration' => 'nullable|integer|min:1', // in seconds
    'released_at' => 'nullable|date',
    'status' => 'required|in:draft,published,scheduled',
]
```

#### 7.2 Form Requests
**Files:**
- `app/Http/Requests/Admin/StoreMediaEpisodeRequest.php`
- `app/Http/Requests/Admin/UpdateMediaEpisodeRequest.php`

- [ ] Validation as above
- [ ] Custom validation for video URLs:
  - [ ] YouTube: `https://www.youtube.com/watch?v=...` or `https://youtu.be/...`
  - [ ] Vimeo: `https://vimeo.com/...`
  - [ ] Direct: `https://...` must be video file

#### 7.3 Views

**7.3.1 index.blade.php** - Episodes listing
- [ ] Display episodes grouped by program:
  - [ ] Program name as section header
  - [ ] Checkbox (select multiple)
  - [ ] Episode number
  - [ ] Thumbnail image
  - [ ] Title (with language fallback)
  - [ ] Duration
  - [ ] Status badge (draft, published, scheduled)
  - [ ] Release date
  - [ ] Views count (if available)
  - [ ] Actions (edit, delete)
- [ ] Search bar (by title)
- [ ] Filter dropdowns:
  - [ ] Filter by program
  - [ ] Filter by status
  - [ ] Sort by: episode number, release date, created date
- [ ] Stats cards at top:
  - [ ] Total episodes
  - [ ] Published episodes
  - [ ] Total duration
  - [ ] Recently added
- [ ] Bulk actions:
  - [ ] Publish selected
  - [ ] Unpublish selected
  - [ ] Delete selected
- [ ] Create button
- [ ] Pagination with AJAX loading
- [ ] Dark/light mode support

**7.3.2 create.blade.php** - Create episode form
- [ ] Form fields (for each language):
  - [ ] Title inputs (ar, en, fr, tr, ur)
  - [ ] Description textarea (ar, en, fr, tr, ur)
- [ ] Other fields:
  - [ ] Program selector dropdown
  - [ ] Episode number input
  - [ ] Video URL input with type selector (YouTube, Vimeo, Direct)
  - [ ] Video preview (embed YouTube/Vimeo preview)
  - [ ] Thumbnail upload (file input with preview)
  - [ ] Duration input (auto-fill or manual)
  - [ ] Release date picker
  - [ ] Status dropdown (draft, published, scheduled)
- [ ] Submit/Cancel buttons
- [ ] Help text: "Supported: YouTube, Vimeo, direct video URLs"

**7.3.3 edit.blade.php** - Edit episode form
- [ ] Same as create.blade.php but with pre-filled values
- [ ] Video preview showing current video
- [ ] Thumbnail display with delete option
- [ ] Submit/Cancel buttons

**7.3.4 show.blade.php** - Episode details view
- [ ] Display episode information
- [ ] Embedded video player (YouTube/Vimeo embed or video player)
- [ ] Episode metadata:
  - [ ] Program name (link to program)
  - [ ] Episode number
  - [ ] Duration
  - [ ] Release date
  - [ ] Views count
- [ ] Related episodes:
  - [ ] Previous episode (link)
  - [ ] Next episode (link)
  - [ ] All episodes in program (dropdown)
- [ ] Edit button
- [ ] Delete button

#### 7.4 Routes & Sidebar
- [ ] Add routes to routes/admin.php
- [ ] Add sidebar link under "Media" section
- [ ] Add "Manage Episodes" button in media-programs/show.blade.php

#### 7.5 Testing Checklist
- [ ] Create episode with YouTube URL
- [ ] Create episode with Vimeo URL
- [ ] Create episode with direct video URL
- [ ] Test video preview in form
- [ ] Edit episode
- [ ] Upload/update thumbnail
- [ ] Search episodes by title
- [ ] Filter by program, status
- [ ] Reorder episodes within program (drag-drop)
- [ ] Publish/unpublish episodes
- [ ] Delete episode
- [ ] Test in dark/light mode
- [ ] Test video embed in show page

---

## PHASE 1 SUMMARY

| Task | Controller | Views | Requests | Priority | Effort | Status |
|------|-----------|-------|----------|----------|--------|--------|
| 1. Categories | CategoryController | 4 | 2 | P0 | 40h | ⏳ Pending |
| 2. Tags | TagController | 4 | 2 | P0 | 35h | ⏳ Pending |
| 3. Series | SeriesController | 4 | 2 | P0 | 40h | ⏳ Pending |
| 4. Media Categories | MediaCategoryController | 4 | 2 | P0 | 30h | ⏳ Pending |
| 5. Hadith Books | HadithBookController | 4 | 2 | P0 | 35h | ⏳ Pending |
| 6. Hadith Chapters | HadithChapterController | 4 | 2 | P0 | 40h | ⏳ Pending |
| 7. Media Episodes | MediaEpisodeController | 4 | 2 | P0 | 50h | ⏳ Pending |

**Total Phase 1:** 7 controllers, 28 views, 14 form requests, 270 hours (4 weeks)

---

## PHASE 2: ENHANCEMENTS (Weeks 5-8)

### Priority Level: 🟡 P1 - HIGH
These features should be implemented after Phase 1. They enhance existing functionality.

---

## Task 8: Split QuranController → SurahController + VerseController

**Priority:** P1 (HIGH - Complete Quran module)
**Week:** 5
**Effort:** 45 hours (1 developer)
**Status:** ⏳ Pending

### Background
- Current QuranController manages both Surahs and Verses
- Surahs (114 fixed chapters) should be managed separately
- Verses should be manageable per-surah
- Need to restructure workflow

### Requirements
- [ ] Create SurahController (manage 114 Surahs, editing only)
- [ ] Rename QuranController to VerseController
- [ ] SurahController views: index, edit, show (no create)
- [ ] VerseController views: index (grouped by surah), create, edit, show
- [ ] Update routes to reflect new structure
- [ ] Update sidebar navigation
- [ ] Full testing of Quran workflow

---

## Task 9: Create ReciterController + Views

**Priority:** P1 (HIGH - Quran reciters)
**Week:** 5-6
**Effort:** 35 hours (1 developer)
**Status:** ⏳ Pending

### Background
- Reciters (Qaris) are important for Quran recitation
- Each reciter has: name, biography, style, country, featured flag
- Need to manage reciters and their recitations

### Requirements
- [ ] Create ReciterController with full CRUD
- [ ] Create 4 views: index, create, edit, show
- [ ] Profile image upload
- [ ] Featured reciter management
- [ ] Filter by country, style
- [ ] Display order management

---

## Task 10: Create TafseerController + Views

**Priority:** P1 (HIGH - Quran interpretations)
**Week:** 6
**Effort:** 45 hours (1 developer)
**Status:** ⏳ Pending

### Background
- Tafseer (Quran interpretation) is crucial for understanding verses
- Multiple tafseer sources (Ibn Kathir, Jalalayn, etc.)
- Need rich text editor for Arabic and English
- Support footnotes and references

### Requirements
- [ ] Create TafseerController with full CRUD
- [ ] Create 4 views with rich text editor (CKEditor or Quill)
- [ ] Support for Arabic/English text
- [ ] Verse selector (surah:verse format)
- [ ] Tafseer name/source selector
- [ ] Footnotes management
- [ ] Duplicate detection

---

## Task 11: Create HadithNarratorController + Views

**Priority:** P1 (HIGH - Hadith narrator management)
**Week:** 6-7
**Effort:** 40 hours (1 developer)
**Status:** ⏳ Pending

### Background
- Narrators are key to hadith authentication
- Each narrator has: name, biography, reliability grade, era/period
- Need narrator chain builder in hadith forms

### Requirements
- [ ] Create HadithNarratorController with full CRUD
- [ ] Create 4 views with biography editor
- [ ] Reliability grade selector (Sahih, Hasan, Daif, etc.)
- [ ] Show hadith count per narrator
- [ ] Filter by grade, era, country
- [ ] Narrator chain builder (for use in hadith forms)

---

## Task 12: Enhance ArticleController (Add Selectors & Filters)

**Priority:** P1 (HIGH - Unblock article management)
**Week:** 7
**Effort:** 30 hours (1 developer)
**Status:** ⏳ Pending

### Requirements
- [ ] Add category selector to create/edit forms (dropdown or modal)
- [ ] Add tag selector with auto-complete (create-on-the-fly option)
- [ ] Add series selector dropdown
- [ ] Add advanced filters to index view:
  - [ ] Filter by category
  - [ ] Filter by author
  - [ ] Filter by date range (published_at)
  - [ ] Filter by series
  - [ ] Filter by status (draft, published, archived)
  - [ ] Filter by tag
- [ ] Add columns to index table:
  - [ ] Category name
  - [ ] Series name (if applicable)
  - [ ] Tag count
  - [ ] View count (if available)
- [ ] Update AJAX data loading to support all filters

---

## Task 13: Enhance HadithController (Add Selectors & Filters)

**Priority:** P1 (HIGH - Unblock hadith management)
**Week:** 7
**Effort:** 35 hours (1 developer)
**Status:** ⏳ Pending

### Requirements
- [ ] Add hadith book selector to create/edit forms
- [ ] Add hadith chapter selector (dependent on book selection)
- [ ] Add narrator chain builder (modal for selecting narrator chain)
- [ ] Add authenticity/grade selector (Sahih, Hasan, Daif, Weak, etc.)
- [ ] Add advanced filters to index view:
  - [ ] Filter by book
  - [ ] Filter by chapter (dependent on book)
  - [ ] Filter by grade
  - [ ] Filter by narrator (any in chain)
  - [ ] Filter by date range (collected_at)
- [ ] Add columns to index table:
  - [ ] Book name
  - [ ] Chapter number
  - [ ] Grade badge
  - [ ] Narrator count in chain
  - [ ] Reference count

---

## Task 14: Enhance MediaProgramController (Add Selectors & Filters)

**Priority:** P1 (HIGH - Unblock media management)
**Week:** 7-8
**Effort:** 30 hours (1 developer)
**Status:** ⏳ Pending

### Requirements
- [ ] Add media category selector to create/edit forms
- [ ] Add advanced filters to index view:
  - [ ] Filter by category
  - [ ] Filter by status (ongoing, completed, upcoming)
  - [ ] Filter by published status (published, draft)
  - [ ] Filter by view count range
  - [ ] Filter by episode count range
- [ ] Add columns to index table:
  - [ ] Category name
  - [ ] Episode count (auto-update)
  - [ ] View count (if available)
  - [ ] Status badge
- [ ] Auto-update episode count when episodes are added/deleted
- [ ] Add "Manage Episodes" button in index and show views

---

## Task 15: Enhance RoleController (Add Bulk Ops & Search)

**Priority:** P1 (HIGH - Complete user management)
**Week:** 8
**Effort:** 25 hours (1 developer)
**Status:** ⏳ Pending

### Requirements
- [ ] Add search functionality to index (filter by name)
- [ ] Add bulk delete functionality
- [ ] Add users count per role (show in index)
- [ ] Add permission matrix view (visual grid of permissions)
- [ ] Add role cloning (duplicate role with all permissions)
- [ ] Protect system roles (admin, super_admin) from deletion
- [ ] Add warning badges for system roles

---

## PHASE 2 SUMMARY

| Task | Feature | Priority | Effort | Status |
|------|---------|----------|--------|--------|
| 8. Quran Split | SurahController + VerseController | P1 | 45h | ⏳ Pending |
| 9. Reciters | ReciterController | P1 | 35h | ⏳ Pending |
| 10. Tafseer | TafseerController | P1 | 45h | ⏳ Pending |
| 11. Hadith Narrators | HadithNarratorController | P1 | 40h | ⏳ Pending |
| 12. Enhance Articles | Add selectors & filters | P1 | 30h | ⏳ Pending |
| 13. Enhance Hadith | Add selectors & filters | P1 | 35h | ⏳ Pending |
| 14. Enhance Media | Add selectors & filters | P1 | 30h | ⏳ Pending |
| 15. Enhance Roles | Add bulk ops & search | P1 | 25h | ⏳ Pending |

**Total Phase 2:** 5 controllers, 4 enhanced modules, 285 hours (4 weeks)

---

## PHASE 3: ADVANCED FEATURES (Weeks 9-16)

### Priority Level: 🟢 P2 - NICE TO HAVE
These features are nice to have but not critical. Implement after Phase 1 & 2.

---

## Task 16: Implement Bulk Export (CSV/Excel)

**Priority:** P2 (NICE - Data export)
**Week:** 9
**Effort:** 40 hours (1 developer)
**Status:** ⏳ Pending

### Background
- All modules should support bulk export for data management
- Export to CSV and Excel formats
- Include filters in export (same records shown in table)

### Requirements
- [ ] Create ExportService for each module
- [ ] Add "Export" button to all index pages
- [ ] Support CSV format
- [ ] Support Excel (XLSX) format
- [ ] Export filtered data (respect current filters)
- [ ] Include headers (translated to user's language)
- [ ] Handle JSON fields (flatten to individual columns)

---

## Task 17: Implement Bulk Import (CSV/Excel)

**Priority:** P2 (NICE - Data import)
**Week:** 9-10
**Effort:** 50 hours (1 developer)
**Status:** ⏳ Pending

### Background
- Import data from CSV/Excel files
- Validate all records before importing
- Show validation errors and preview before confirming import

### Requirements
- [ ] Create ImportService for each module
- [ ] Add "Import" button to all index pages
- [ ] Support CSV and Excel upload
- [ ] Validate all records
- [ ] Show preview of data to import
- [ ] Option to skip invalid records or stop import
- [ ] Show import results (successful, failed, skipped)
- [ ] Undo import functionality (optional)

---

## Task 18: Implement Content Analytics Dashboard

**Priority:** P2 (NICE - Analytics)
**Week:** 11
**Effort:** 50 hours (1 developer)
**Status:** ⏳ Pending

### Background
- Provide insights into content performance
- Track views, favorites, engagement metrics

### Requirements
- [ ] Most viewed articles/hadith/programs
- [ ] Most favorited content
- [ ] Search trends (popular search queries)
- [ ] Content growth over time (chart)
- [ ] Engagement metrics (views, favorites, shares)
- [ ] Export reports to PDF/Excel

---

## Task 19: Implement User Analytics Dashboard

**Priority:** P2 (NICE - Analytics)
**Week:** 11-12
**Effort:** 40 hours (1 developer)
**Status:** ⏳ Pending

### Background
- Monitor user engagement and growth

### Requirements
- [ ] Active users count (last 7 days, 30 days)
- [ ] New registrations trend (chart)
- [ ] User retention metrics
- [ ] Top contributors (editors, content creators)
- [ ] User engagement score per role

---

## Task 20: Implement Custom Report Builder

**Priority:** P2 (NICE - Reporting)
**Week:** 12
**Effort:** 45 hours (1 developer)
**Status:** ⏳ Pending

### Background
- Allow admins to create custom reports
- Export reports in multiple formats

### Requirements
- [ ] Visual report builder (select metrics, filters, date range)
- [ ] Save reports for future use
- [ ] Scheduled reports (daily, weekly, monthly email)
- [ ] Export to PDF, Excel, CSV
- [ ] Report templates (pre-built reports)

---

## Task 21: Implement Media Library Browser

**Priority:** P2 (NICE - Media management)
**Week:** 13
**Effort:** 40 hours (1 developer)
**Status:** ⏳ Pending

### Background
- Central media file picker used in all forms
- Visual library browser instead of file input

### Requirements
- [ ] Modal media picker (used in image/video fields)
- [ ] Thumbnail grid view of media
- [ ] Upload new files directly in modal
- [ ] Search media by name
- [ ] Filter by file type (images, videos, audio)
- [ ] Pagination in modal
- [ ] Drag-drop to select/upload

---

## Task 22: Implement Image Editor

**Priority:** P2 (NICE - Media editing)
**Week:** 13-14
**Effort:** 35 hours (1 developer)
**Status:** ⏳ Pending

### Background
- Edit images directly in admin (crop, resize, filters)
- Improve image optimization

### Requirements
- [ ] Crop tool (free, 16:9, 4:3, 1:1 aspect ratios)
- [ ] Resize tool (scale image)
- [ ] Rotate tool
- [ ] Basic filters (brightness, contrast, saturation)
- [ ] Apply to thumbnail generation

---

## Task 23: Implement CDN Integration (S3/CloudFront)

**Priority:** P2 (NICE - Performance)
**Week:** 14
**Effort:** 45 hours (1 developer)
**Status:** ⏳ Pending

### Background
- Offload media storage to S3
- Use CloudFront for distribution
- Improve performance and scalability

### Requirements
- [ ] S3 bucket integration (uploads)
- [ ] CloudFront distribution setup
- [ ] Configure .env for S3 credentials
- [ ] Update file upload handlers to use S3
- [ ] Update file deletion to remove from S3
- [ ] Generate CloudFront URLs for media

---

## Task 24: Implement Video Direct Upload

**Priority:** P2 (NICE - Media management)
**Week:** 14-15
**Effort:** 50 hours (1 developer)
**Status:** ⏳ Pending

### Background
- Upload video files directly instead of just URLs
- Support MP4, WebM, OGV formats
- Auto-generate thumbnails from video

### Requirements
- [ ] Video file upload field (accept .mp4, .webm, .ogv)
- [ ] Upload progress indicator
- [ ] Chunked upload for large files
- [ ] Store on S3 or local storage
- [ ] Auto-generate thumbnail from video
- [ ] Auto-detect video duration
- [ ] Return CDN URL after upload

---

## Task 25: Implement Content Moderation Queue

**Priority:** P2 (NICE - Workflow)
**Week:** 15
**Effort:** 45 hours (1 developer)
**Status:** ⏳ Pending

### Background
- Implement draft → review → publish workflow
- Assign reviewers to content
- Track moderation status

### Requirements
- [ ] Create moderation status field (draft, under_review, approved, rejected)
- [ ] Moderation queue view (all pending review)
- [ ] Reviewer assignment
- [ ] Review comments/feedback
- [ ] Approve/reject with reason
- [ ] Send notifications to content creator

---

## Task 26: Implement Email/In-app Notifications

**Priority:** P2 (NICE - Workflow)
**Week:** 15-16
**Effort:** 50 hours (1 developer)
**Status:** ⏳ Pending

### Background
- Notify users of important events
- Support email and in-app notifications

### Requirements
- [ ] Create notifications table
- [ ] Send email on content rejection
- [ ] Send email on content approval
- [ ] Send email on new comments
- [ ] In-app notification dropdown
- [ ] Mark notifications as read
- [ ] Notification preferences per user

---

## Task 27: Implement Content Scheduling

**Priority:** P2 (NICE - Workflow)
**Week:** 16
**Effort:** 40 hours (1 developer)
**Status:** ⏳ Pending

### Background
- Schedule content publishing
- Auto-publish at specified date/time
- Support unpublish at date/time

### Requirements
- [ ] Add scheduled_at field to articles/hadith/media
- [ ] Date/time picker in create/edit forms
- [ ] Show scheduled content in separate view
- [ ] Queue worker to auto-publish at scheduled time
- [ ] Show countdown to publish in admin
- [ ] Update status automatically

---

## Task 28: Implement Revision History & Rollback

**Priority:** P2 (NICE - Workflow)
**Week:** 16
**Effort:** 55 hours (1 developer)
**Status:** ⏳ Pending

### Background
- Track all changes to content
- Ability to rollback to previous versions
- View diffs between versions

### Requirements
- [ ] Create revisions table
- [ ] Record all changes to articles/hadith/media
- [ ] Version history view (timeline of changes)
- [ ] Show who made what changes when
- [ ] Diff viewer (show changes between versions)
- [ ] Rollback to previous version button
- [ ] Compare two versions side-by-side

---

## PHASE 3 SUMMARY

| Task | Feature | Priority | Effort | Status |
|------|---------|----------|--------|--------|
| 16. Bulk Export | CSV/Excel export | P2 | 40h | ⏳ Pending |
| 17. Bulk Import | CSV/Excel import | P2 | 50h | ⏳ Pending |
| 18. Content Analytics | Views, favorites, trends | P2 | 50h | ⏳ Pending |
| 19. User Analytics | Growth, retention, engagement | P2 | 40h | ⏳ Pending |
| 20. Report Builder | Custom reports + export | P2 | 45h | ⏳ Pending |
| 21. Media Library | File picker modal | P2 | 40h | ⏳ Pending |
| 22. Image Editor | Crop, resize, filters | P2 | 35h | ⏳ Pending |
| 23. CDN Integration | S3/CloudFront setup | P2 | 45h | ⏳ Pending |
| 24. Video Upload | Direct video file upload | P2 | 50h | ⏳ Pending |
| 25. Moderation Queue | Draft/review/publish workflow | P2 | 45h | ⏳ Pending |
| 26. Notifications | Email/in-app notifications | P2 | 50h | ⏳ Pending |
| 27. Content Scheduling | Schedule publish/unpublish | P2 | 40h | ⏳ Pending |
| 28. Revision History | Track versions + rollback | P2 | 55h | ⏳ Pending |

**Total Phase 3:** 13 advanced features, 585 hours (8 weeks)

---

## OVERALL PROGRESS TRACKING

### Phase 1 (Critical CRUD) - Weeks 1-4
- [ ] Week 1: Category CRUD (40h)
- [ ] Week 1-2: Tag CRUD (35h)
- [ ] Week 1-2: Series CRUD (40h)
- [ ] Week 2: MediaCategory CRUD (30h)
- [ ] Week 3: HadithBook CRUD (35h)
- [ ] Week 3-4: HadithChapter CRUD (40h)
- [ ] Week 4: MediaEpisode CRUD (50h)

**Subtotal Phase 1:** 270 hours

### Phase 2 (Enhancements) - Weeks 5-8
- [ ] Week 5: Quran Controller Split (45h)
- [ ] Week 5-6: ReciterController (35h)
- [ ] Week 6: TafseerController (45h)
- [ ] Week 6-7: HadithNarratorController (40h)
- [ ] Week 7: Enhance ArticleController (30h)
- [ ] Week 7: Enhance HadithController (35h)
- [ ] Week 7-8: Enhance MediaProgramController (30h)
- [ ] Week 8: Enhance RoleController (25h)

**Subtotal Phase 2:** 285 hours

### Phase 3 (Advanced Features) - Weeks 9-16
- [ ] Multiple advanced features (13 tasks, 585h)

**Subtotal Phase 3:** 585 hours

---

## GRAND TOTAL

| Phase | Duration | Effort | Team Size | Status |
|-------|----------|--------|-----------|--------|
| **Phase 1** | 4 weeks | 270h | 2 devs | ⏳ Pending |
| **Phase 2** | 4 weeks | 285h | 2 devs | ⏳ Pending |
| **Phase 3** | 8 weeks | 585h | 3 devs | ⏳ Pending |
| **TOTAL** | **16 weeks** | **1,140 hours** | **2-3 devs** | ⏳ Pending |

---

## DEPENDENCIES & BLOCKERS

### Phase 1 Blockers
- None - All tasks can proceed in parallel

### Phase 2 Blockers
- Task 12 (Enhance Articles) requires Task 1 (Categories), Task 2 (Tags), Task 3 (Series) completed
- Task 13 (Enhance Hadith) requires Task 5 (HadithBooks), Task 6 (HadithChapters) completed
- Task 14 (Enhance Media) requires Task 4 (MediaCategories) completed

### Phase 3 Blockers
- Most Phase 3 tasks don't have hard blockers
- Can run in parallel after Phase 1 & 2 complete

---

## IMPLEMENTATION RECOMMENDATIONS

### 1. Start Phase 1 Immediately
- Critical for core functionality
- Unblocks all article/hadith/media management
- Estimate: 4 weeks with 2 developers

### 2. Parallel Development
- Assign one developer per task (if 7 devs available) to complete Phase 1 in 1 week
- Or 2 developers alternating tasks, completing Phase 1 in 4 weeks

### 3. Phase 2 After Phase 1
- Wait for Phase 1 completion to avoid rework
- Phase 2 enhances functionality created in Phase 1
- Estimate: 4 weeks with 2 developers

### 4. Phase 3 After Phase 2
- Nice to have features
- Can be developed in parallel with Phase 2 if resources allow
- Estimate: 8 weeks with 3 developers

### 5. Code Quality
- Code review for every task
- Automated testing (unit + feature tests)
- Manual QA testing
- Performance testing for bulk import/export

---

## QUALITY ASSURANCE CHECKLIST

### Every Task Must Include
- [ ] Unit tests for business logic
- [ ] Feature tests for API/routes
- [ ] Manual testing in multiple browsers (Chrome, Firefox, Safari, Edge)
- [ ] Dark mode testing
- [ ] Mobile responsiveness testing
- [ ] Accessibility testing (WCAG AA compliance)
- [ ] Performance testing (load time < 3s, AJAX < 500ms)
- [ ] Security testing (permission checks, input validation)

---

## DEPLOYMENT CHECKLIST

### Before Going Live
- [ ] All Phase 1 tasks completed and tested
- [ ] Database migrations applied
- [ ] Cache cleared (config:clear, cache:clear, route:clear)
- [ ] Storage symlink created (php artisan storage:link)
- [ ] File upload directories have correct permissions
- [ ] Email configuration verified (for notifications)
- [ ] S3/CDN configuration verified (if using)
- [ ] SSL certificate valid
- [ ] Backup strategy in place
- [ ] Monitoring/logging configured

---

## NOTES & TIPS

1. **Use existing patterns** - Follow same code style as existing controllers
2. **DRY principle** - Reuse components (forms, modals, filters)
3. **Alpine.js** - Keep JavaScript logic simple using Alpine
4. **Form requests** - Always use form requests for validation
5. **Services** - Put business logic in services, not controllers
6. **Repositories** - Use repositories for data access (no direct Eloquent in controllers)
7. **Migrations** - Always create migrations for schema changes
8. **Seeding** - Create seeders for master data (categories, languages, etc.)
9. **Documentation** - Document complex features and APIs
10. **Testing** - Write tests as you code, not after

---

## RESOURCES NEEDED

### Development
- 2-3 Laravel developers
- 1 Frontend developer (Blade/Alpine.js/Tailwind)
- 1 UI/UX designer (for complex components)

### Tools
- Laravel 12+
- MySQL 8.0+
- Redis (for caching)
- PHPUnit (testing)
- Laravel Dusk (browser testing)

### Libraries (To Install)
- `spatie/laravel-permission` - Role/permission management
- `spatie/laravel-translatable` - Multi-language support
- `maatwebsite/excel` - Excel import/export
- `phpoffice/phpspreadsheet` - Spreadsheet handling
- `intervention/image` - Image manipulation
- `predis/predis` - Redis client

---

**Document Created:** 2025-10-30
**Last Updated:** 2025-10-30
**Status:** Ready for Implementation
**Next Step:** Start Phase 1, Week 1 - Task 1 (CategoryController)
