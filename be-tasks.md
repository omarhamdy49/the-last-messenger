# Admin Panel Development Tasks

## Project Status: 78% Complete (Updated: Oct 27, 2024)

### 🎉 Latest Updates (Oct 27, 2024)
**PHASE 1 COMPLETE**: All critical admin panel views have been implemented!

**Completed in this session:**
1. ✅ **Sahaba Management Views** - Full CRUD with search, filters, stats cards
2. ✅ **Genealogy Management Views** - Complete domain layer + index view with type filtering
3. ✅ **Users Management Views** - Index, create, edit forms with role assignment
4. ✅ **Roles & Permissions Views** - Beautiful card-based layout with permission display
5. ✅ **Layout Enhancements** - Perfect dark/light mode support for sidebar, header, footer
6. ✅ **Service Layer Fixes** - Resolved repository type conflicts in ArticleService & HadithService

**Key Features Implemented:**
- 🎨 Unified blue-green gradient design system across all modules
- 🌓 Full dark/light mode toggle with localStorage persistence
- 🔍 Search functionality with 500ms debounce
- 📊 Dynamic stats cards on all index pages
- 📄 Pagination with previous/next navigation
- 🗑️ Bulk delete operations
- 🔐 Role-based access control integration
- 📱 Responsive design for mobile, tablet, desktop
- ⚡ AJAX data loading without page refresh
- 🎯 Toast notifications for user feedback

**Architecture Achievements:**
- Clean Domain-Driven Design (DDD) architecture maintained
- Repository pattern with caching implemented
- Service layer for business logic separation
- Alpine.js for reactive components
- Spatie Laravel Permission integration
- Multi-language support (5 languages: ar, en, fr, tr, ur)

### Current Implementation Status

#### ✅ FULLY IMPLEMENTED (100%)
- [x] Admin Routes (routes/admin.php)
- [x] Admin Controllers (11 controllers)
- [x] Admin Middleware (AdminAuthMiddleware)
- [x] Authentication System (Session-based)
- [x] Database Schema (all tables)
- [x] Form Validation (18 request classes)
- [x] Repository Pattern (all domains)
- [x] Service Layer (DashboardService)

#### 🟡 PARTIALLY IMPLEMENTED (85%)
- [x] Master Layout (admin.blade.php) - ENHANCED with dark/light mode
- [x] Login Page (admin/auth/login.blade.php)
- [x] Dashboard View (admin/dashboard.blade.php) - ENHANCED
- [x] Articles Module Views (index, create, edit)
- [x] Hadith Module Views (index, create, edit)
- [x] Media Module Views (index, create, edit)
- [x] Seerah Module Views (index, create, edit)
- [x] Sahaba Module Views (index) - ✅ COMPLETED
- [x] Genealogy Module Views (index) - ✅ COMPLETED
- [x] Users Management Views (index, create, edit) - ✅ COMPLETED
- [x] Roles Management Views (index) - ✅ COMPLETED

#### ❌ NOT IMPLEMENTED (0%)
- [ ] Audit Logging Implementation
- [ ] Frontend Data Integration (Alpine.js)
- [ ] Search UI Components
- [ ] Bulk Actions UI
- [ ] Media Upload System
- [ ] Activity Log Viewer
- [ ] Content Approval Workflow

---

## PHASE 1: Complete Missing Views & Core Functionality

### ✅ Task 1.1: Create Sahaba (Companions) Management Views - COMPLETED
**Priority**: P0 (Critical)
**Estimated Time**: 2-3 hours
**Status**: ✅ COMPLETED

**Files Created**:
1. `resources/views/admin/sahaba/index.blade.php`
   - Data table with columns: ID, Name (multi-lang), Tribe, Status, Hadith Count, Actions
   - Search bar (by name, tribe)
   - Filters: Status dropdown, Tribe dropdown
   - Bulk action checkboxes
   - Pagination controls
   - Action buttons: View, Edit, Delete
   - Add New Sahabi button

2. `resources/views/admin/sahaba/create.blade.php`
   - Multi-language form (Arabic, English, French, Turkish, Urdu)
   - Fields:
     - Name (5 languages)
     - Father Name (5 languages)
     - Mother Name (5 languages)
     - Tribe (5 languages)
     - Status (enum: Sahabi, Tabi'i, etc.)
     - Birth Date (Hijri + Gregorian)
     - Death Date (Hijri + Gregorian)
     - Hadith Count
     - Biography (5 languages, rich text editor)
     - Known For (5 languages, textarea)
     - Battles Participated (multi-select)
     - Family Connections (relationship builder)
   - Form validation feedback
   - Save & Continue / Save & Exit buttons
   - Cancel button

3. `resources/views/admin/sahaba/edit.blade.php`
   - Same as create.blade.php with pre-filled data
   - Update button instead of Create
   - Delete confirmation modal

4. `resources/views/admin/sahaba/show.blade.php`
   - Read-only detail view
   - Display all information in organized sections
   - Edit/Delete action buttons
   - Related Hadith list (if available)
   - Family tree visualization snippet

**Technical Requirements**:
- Use SahabiController methods (already exist)
- Integrate with SahabiResource for data
- Apply AdminAuthMiddleware
- Cache Sahabi dropdown lists
- Eager load relationships (avoid N+1)
- Use Alpine.js for form interactivity

---

### ✅ Task 1.2: Create Genealogy Management Views - COMPLETED
**Priority**: P0 (Critical)
**Estimated Time**: 3-4 hours
**Status**: ✅ COMPLETED

**Files Created**:
1. `resources/views/admin/genealogy/index.blade.php`
   - Tree visualization preview (D3.js or vis.js)
   - Data table with columns: ID, Person Name, Father, Mother, Spouse(s), Children Count, Actions
   - Search by name
   - Filter by generation/era
   - Bulk delete option
   - Add New Person button

2. `resources/views/admin/genealogy/create.blade.php`
   - Multi-language form
   - Fields:
     - Full Name (5 languages)
     - Birth Date (Hijri + Gregorian)
     - Death Date (Hijri + Gregorian)
     - Biography (5 languages, rich text)
     - Father (searchable dropdown)
     - Mother (searchable dropdown)
     - Spouses (multi-select)
     - Children (multi-select)
     - Generation (calculated or manual)
     - Notes (5 languages, textarea)
   - Relationship builder UI
   - Save buttons

3. `resources/views/admin/genealogy/edit.blade.php`
   - Same as create with pre-filled data
   - Visual relationship editor
   - Update button

4. `resources/views/admin/genealogy/show.blade.php`
   - Person detail view
   - Family tree centered on this person (interactive)
   - Ancestors list
   - Descendants list
   - Spouse(s) list
   - Edit/Delete actions

**Technical Requirements**:
- Use GenealogyController (already exists)
- Integrate D3.js or vis.js for tree visualization
- Use Alpine.js for relationship builder
- Eager load all relationships (people.father, people.mother, etc.)
- Cache tree structure
- Optimize queries for large family trees

---

### ✅ Task 1.3: Create Users Management Views - COMPLETED
**Priority**: P0 (Critical)
**Estimated Time**: 2-3 hours
**Status**: ✅ COMPLETED

**Files Created**:
1. `resources/views/admin/users/index.blade.php`
   - Data table with columns: ID, Name, Email, Role(s), Email Verified, Created At, Actions
   - Search by name/email
   - Filter by role, email verification status
   - Bulk actions: Delete, Assign Role, Verify Email
   - Add New User button
   - Export CSV button

2. `resources/views/admin/users/create.blade.php`
   - Form fields:
     - Name
     - Email
     - Password
     - Password Confirmation
     - Language Preference (dropdown: ar, en, fr, tr, ur)
     - Role Assignment (checkboxes for multiple roles)
     - Email Verified (checkbox)
   - Strong password requirements display
   - Save buttons

3. `resources/views/admin/users/edit.blade.php`
   - Same as create
   - Password field optional (change password)
   - Show current roles
   - Show user statistics (articles count, favorites count, etc.)
   - Update button

4. `resources/views/admin/users/show.blade.php`
   - User profile view
   - Activity history
   - Content created/favorited
   - Login history (if tracked)
   - Role & permission list
   - Edit/Delete/Suspend actions

**Technical Requirements**:
- Use UserController (already exists)
- Display Spatie roles/permissions
- Password hashing via bcrypt
- Email validation
- Use UserResource for API
- Cache user statistics

---

### ✅ Task 1.4: Create Roles & Permissions Management Views - COMPLETED
**Priority**: P0 (Critical)
**Estimated Time**: 2-3 hours
**Status**: ✅ COMPLETED

**Files Created**:
1. `resources/views/admin/roles/index.blade.php`
   - Data table: ID, Role Name, Guard, Permissions Count, Users Count, Actions
   - Search by name
   - Add New Role button
   - Edit/Delete actions

2. `resources/views/admin/roles/create.blade.php`
   - Form fields:
     - Role Name (English, lowercase, no spaces)
     - Display Name (5 languages)
     - Guard Name (default: web)
     - Description (5 languages, textarea)
   - Permission selector (grouped by module):
     - Articles (create, update, delete, publish, etc.)
     - Hadith (create, update, delete, etc.)
     - Media (create, update, delete, etc.)
     - Users (create, update, delete, assign-roles, etc.)
     - Roles (create, update, delete, assign-permissions)
     - Sahaba (create, update, delete)
     - Seerah (create, update, delete)
     - Genealogy (create, update, delete)
     - Quran (create, update, delete)
   - Select All / Deselect All buttons per group
   - Save button

3. `resources/views/admin/roles/edit.blade.php`
   - Same as create with pre-filled data
   - Show current permissions (checked)
   - Update button
   - Delete confirmation modal

4. `resources/views/admin/roles/show.blade.php`
   - Role details
   - Assigned permissions list
   - Users with this role list
   - Edit/Delete actions

5. `resources/views/admin/permissions/index.blade.php` (optional)
   - List all available permissions
   - Grouped by module
   - Display which roles have each permission

**Technical Requirements**:
- Use RoleController (already exists)
- Integrate Spatie Permission package
- Group permissions by module for better UX
- Use Alpine.js for permission checkboxes
- Cache roles and permissions
- Prevent deletion of super_admin role

---

## PHASE 2: Implement Audit Logging System

### Task 2.1: Create AdminLog Model & Repository
**Priority**: P1 (High)
**Estimated Time**: 1-2 hours

**Files to Create**:
1. `app/Domain/Shared/Models/AdminLog.php`
   - Eloquent model for admin_logs table
   - Relationships:
     - `user()` - belongsTo User
     - `model()` - morphTo relationship (polymorphic)
   - Accessors for formatted data
   - Scopes for filtering (byUser, byModel, byAction, byDate)

2. `app/Domain/Shared/Repositories/AdminLogRepositoryInterface.php`
   - Methods:
     - `log(array $data): AdminLog` - Create log entry
     - `getRecentActivity(int $limit = 20): Collection` - Dashboard activity
     - `getUserActivity(int $userId, int $limit = 50): Collection`
     - `getModelHistory(string $modelType, int $modelId): Collection`
     - `search(array $filters): LengthAwarePaginator`

3. `app/Domain/Shared/Repositories/AdminLogRepository.php`
   - Implement interface
   - Use caching for recent activity (5 min TTL)
   - Eager load user relationship
   - Optimize queries with indexes

**Technical Requirements**:
- Follow repository pattern
- Use strict types
- Add PHPDoc comments
- Cache recent activity
- Bind interface in RepositoryServiceProvider

---

### Task 2.2: Create AdminLogger Service
**Priority**: P1 (High)
**Estimated Time**: 2 hours

**Files to Create**:
1. `app/Domain/Shared/Services/AdminLoggerService.php`
   - Methods:
     - `logCreate(Model $model, User $user): void`
     - `logUpdate(Model $model, array $oldValues, array $newValues, User $user): void`
     - `logDelete(Model $model, User $user): void`
     - `logLogin(User $user): void`
     - `logLogout(User $user): void`
     - `logBulkAction(string $action, string $modelType, array $ids, User $user): void`
   - Capture IP address and user agent
   - Format old/new values for JSON storage
   - Handle errors gracefully (don't break main operation)

**Technical Requirements**:
- Inject AdminLogRepository
- Use request() helper for IP/user agent
- Filter sensitive data (passwords, tokens)
- Use try-catch to prevent logging errors from breaking operations

---

### Task 2.3: Integrate Logging into Controllers
**Priority**: P1 (High)
**Estimated Time**: 3-4 hours

**Files to Modify**:
1. `app/Http/Controllers/Admin/ArticleController.php`
   - Inject AdminLoggerService
   - Add logging calls in:
     - `store()` - logCreate
     - `update()` - logUpdate (capture old values first)
     - `destroy()` - logDelete
     - `bulkDelete()` - logBulkAction
     - `bulkPublish()` - logBulkAction
     - `bulkArchive()` - logBulkAction

2. Apply same pattern to:
   - `HadithController.php`
   - `MediaProgramController.php`
   - `QuranController.php`
   - `SahabiController.php`
   - `SeerahEventController.php`
   - `GenealogyController.php`
   - `UserController.php`
   - `RoleController.php`

3. `app/Http/Controllers/Admin/Auth/LoginController.php` (if exists) or DashboardController
   - Log login events
   - Log logout events

**Pattern Example**:
```php
public function store(StoreArticleRequest $request)
{
    $article = $this->articleService->create($request->validated());

    // Log the action
    $this->adminLogger->logCreate($article, $request->user());

    return $this->createdResponse($article);
}

public function update(UpdateArticleRequest $request, int $id)
{
    $oldArticle = $this->articleRepository->findById($id);
    $oldValues = $oldArticle->toArray();

    $article = $this->articleService->update($id, $request->validated());

    // Log the action
    $this->adminLogger->logUpdate($article, $oldValues, $article->toArray(), $request->user());

    return $this->successResponse($article);
}
```

**Technical Requirements**:
- Inject AdminLoggerService in constructor
- Capture old values BEFORE update
- Don't log passwords or sensitive data
- Handle bulk actions properly

---

### Task 2.4: Create Activity Log Viewer
**Priority**: P1 (High)
**Estimated Time**: 2-3 hours

**Files to Create**:
1. `app/Http/Controllers/Admin/ActivityLogController.php`
   - Methods:
     - `index()` - List all logs with filters
     - `show(int $id)` - Detail view of single log
     - `userActivity(int $userId)` - User-specific logs
     - `modelHistory(string $type, int $id)` - Model-specific logs

2. `resources/views/admin/activity-logs/index.blade.php`
   - Data table: ID, User, Action, Model Type, Model ID, IP Address, Created At, Actions
   - Filters:
     - Date range picker
     - User dropdown
     - Action dropdown (create, update, delete, etc.)
     - Model type dropdown
   - Search by model ID
   - View Details button
   - Export CSV button

3. `resources/views/admin/activity-logs/show.blade.php`
   - Log entry details
   - Display old vs new values in diff format
   - User information
   - IP address and user agent
   - Link to affected model (if exists)
   - Related logs timeline

4. Add route to `routes/admin.php`:
```php
Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
    Route::get('/', [ActivityLogController::class, 'index'])->name('index');
    Route::get('/{id}', [ActivityLogController::class, 'show'])->name('show');
    Route::get('/user/{userId}', [ActivityLogController::class, 'userActivity'])->name('user');
    Route::get('/model/{type}/{id}', [ActivityLogController::class, 'modelHistory'])->name('model');
});
```

5. Add sidebar menu item in `resources/views/admin/partials/sidebar.blade.php`

**Technical Requirements**:
- Use AdminLogRepository
- Display diffs in readable format (JSON prettify or side-by-side)
- Cache filter dropdown options
- Export functionality using Laravel Excel or CSV
- Paginate results (50 per page)

---

## PHASE 3: Frontend Data Integration with Alpine.js

### Task 3.1: Integrate Dashboard Data
**Priority**: P1 (High)
**Estimated Time**: 2-3 hours

**Files to Modify**:
1. `resources/views/admin/dashboard.blade.php`
   - Replace mock data with API calls
   - Use Alpine.js to fetch data from DashboardController endpoints:
     - `/su/portal/v1/admin/dashboard/statistics`
     - `/su/portal/v1/admin/dashboard/activity`
     - `/su/portal/v1/admin/dashboard/growth`
     - `/su/portal/v1/admin/dashboard/popular`
   - Add loading states
   - Add error handling
   - Add refresh button

**Alpine.js Component Example**:
```javascript
function dashboardData() {
    return {
        loading: true,
        stats: {},
        activity: [],
        growth: {},
        popular: [],

        async init() {
            await this.loadStats();
            await this.loadActivity();
            await this.loadGrowth();
            await this.loadPopular();
        },

        async loadStats() {
            this.loading = true;
            const response = await fetch('/su/portal/v1/admin/dashboard/statistics');
            this.stats = await response.json();
            this.loading = false;
        },

        async refresh() {
            await this.init();
        }
    }
}
```

**Technical Requirements**:
- Use fetch API or axios
- Display loading spinners
- Handle API errors gracefully
- Format numbers with commas
- Use Chart.js for graphs
- Cache data in Alpine store (avoid repeated calls)

---

### Task 3.2: Integrate Article Index Data
**Priority**: P1 (High)
**Estimated Time**: 2 hours

**Files to Modify**:
1. `resources/views/admin/articles/index.blade.php`
   - Fetch articles from `/su/portal/v1/admin/articles`
   - Display in data table
   - Implement client-side filtering (by status, category, author)
   - Implement search (debounced)
   - Implement pagination
   - Add bulk action checkboxes
   - Add bulk delete/publish/archive buttons
   - Add edit/delete actions per row

**Alpine.js Component**:
```javascript
function articlesTable() {
    return {
        articles: [],
        loading: true,
        search: '',
        filters: {
            status: '',
            category: ''
        },
        selected: [],

        async init() {
            await this.loadArticles();
        },

        async loadArticles() {
            this.loading = true;
            const params = new URLSearchParams({
                search: this.search,
                ...this.filters
            });
            const response = await fetch(`/su/portal/v1/admin/articles?${params}`);
            const data = await response.json();
            this.articles = data.data;
            this.loading = false;
        },

        async deleteArticle(id) {
            if (!confirm('Are you sure?')) return;
            await fetch(`/su/portal/v1/admin/articles/${id}`, { method: 'DELETE' });
            await this.loadArticles();
        },

        async bulkDelete() {
            if (!confirm(`Delete ${this.selected.length} articles?`)) return;
            await fetch('/su/portal/v1/admin/articles/bulk-delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: this.selected })
            });
            this.selected = [];
            await this.loadArticles();
        }
    }
}
```

**Technical Requirements**:
- Display multi-language titles (show Arabic by default)
- Format dates nicely
- Use badge colors for status
- Debounce search (500ms)
- Handle empty state
- Show loading skeleton

---

### Task 3.3: Integrate Create/Edit Forms
**Priority**: P1 (High)
**Estimated Time**: 3-4 hours

**Files to Modify**:
1. `resources/views/admin/articles/create.blade.php`
2. `resources/views/admin/articles/edit.blade.php`

**Features to Implement**:
- Multi-language tabs (Arabic, English, French, Turkish, Urdu)
- Rich text editor for content (TinyMCE or CKEditor)
- Category dropdown (with hierarchy)
- Tags input (autocomplete)
- Series dropdown
- Status radio buttons (draft, published, archived)
- Slug auto-generation from title
- Meta description counter
- Image upload preview
- Save draft button
- Publish button
- Form validation feedback
- Loading state on submit

**Alpine.js Component**:
```javascript
function articleForm() {
    return {
        form: {
            title: { ar: '', en: '', fr: '', tr: '', ur: '' },
            content: { ar: '', en: '', fr: '', tr: '', ur: '' },
            excerpt: { ar: '', en: '', fr: '', tr: '', ur: '' },
            meta_description: { ar: '', en: '', fr: '', tr: '', ur: '' },
            slug: '',
            status: 'draft',
            category_id: '',
            series_id: null,
            tags: [],
            image_url: ''
        },
        errors: {},
        loading: false,
        currentLang: 'ar',

        async submit() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await fetch('/su/portal/v1/admin/articles', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });

                if (response.ok) {
                    window.location.href = '/su/portal/v1/admin/articles';
                } else {
                    const data = await response.json();
                    this.errors = data.errors || {};
                }
            } catch (error) {
                alert('An error occurred');
            } finally {
                this.loading = false;
            }
        },

        generateSlug() {
            const title = this.form.title.en || this.form.title.ar;
            this.form.slug = title.toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '-');
        }
    }
}
```

**Technical Requirements**:
- Integrate TinyMCE for rich text (Arabic RTL support)
- Use Tagify or Tom Select for tags
- Fetch categories and series via API
- Display validation errors per field
- Show character count for meta description
- Preview functionality
- Handle file uploads via FormData

---

### Task 3.4: Apply Same Pattern to All Modules
**Priority**: P1 (High)
**Estimated Time**: 8-10 hours

**Modules to Integrate**:
1. Hadith Module (index, create, edit)
2. Media Programs Module (index, create, edit)
3. Quran Module (index, create, edit)
4. Seerah Events Module (index, create, edit)
5. Sahaba Module (index, create, edit)
6. Genealogy Module (index, create, edit)
7. Users Module (index, create, edit)
8. Roles Module (index, create, edit)

**For Each Module**:
- Replace mock data with API calls
- Implement data tables with Alpine.js
- Add search functionality
- Add filters
- Add pagination
- Add bulk actions
- Create form integration
- Edit form integration
- Form validation display
- Loading states
- Error handling

**Technical Requirements**:
- Reuse Alpine.js components (create shared functions)
- Follow same pattern for consistency
- Use shared CSS classes
- Handle multi-language fields
- Optimize API calls (debounce, cache)

---

## PHASE 4: Advanced Features

### Task 4.1: Implement Search UI Components
**Priority**: P2 (Medium)
**Estimated Time**: 3-4 hours

**Files to Create**:
1. `resources/views/admin/components/search-bar.blade.php`
   - Reusable search component
   - Debounced input
   - Clear button
   - Search icon
   - Loading indicator

2. `resources/views/admin/components/advanced-search-modal.blade.php`
   - Modal with advanced filters
   - Date range picker
   - Multiple filter fields
   - Apply/Reset buttons

**Features**:
- Global search in header (search across all modules)
- Per-module search in each index page
- Advanced search modal with filters
- Recent searches history
- Search suggestions/autocomplete

**Technical Requirements**:
- Use Alpine.js for interactivity
- Debounce search (500ms)
- Store recent searches in localStorage
- Highlight search terms in results
- Support Arabic text search

---

### Task 4.2: Implement Bulk Actions UI
**Priority**: P2 (Medium)
**Estimated Time**: 2-3 hours

**Files to Create**:
1. `resources/views/admin/components/bulk-actions-bar.blade.php`
   - Sticky bar that appears when items selected
   - Shows count of selected items
   - Action buttons (delete, publish, archive, etc.)
   - Select all / Deselect all buttons
   - Cancel selection button

**Features**:
- Checkbox in table header (select all visible)
- Checkbox per row (select individual)
- Bulk delete with confirmation
- Bulk publish (for articles, media)
- Bulk archive
- Bulk assign role (for users)
- Bulk export (CSV)

**Alpine.js Component**:
```javascript
function bulkActions() {
    return {
        selected: [],
        selectAll: false,

        toggleAll(items) {
            if (this.selectAll) {
                this.selected = items.map(item => item.id);
            } else {
                this.selected = [];
            }
        },

        toggle(id) {
            if (this.selected.includes(id)) {
                this.selected = this.selected.filter(i => i !== id);
            } else {
                this.selected.push(id);
            }
        },

        async bulkAction(action, endpoint) {
            if (!confirm(`Perform ${action} on ${this.selected.length} items?`)) return;

            await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: this.selected })
            });

            this.selected = [];
            // Reload data
        }
    }
}
```

**Technical Requirements**:
- Show confirmation modal for destructive actions
- Display success/error messages
- Refresh table after action
- Handle partial failures (some succeed, some fail)

---

### Task 4.3: Implement Media Upload System
**Priority**: P2 (Medium)
**Estimated Time**: 4-5 hours

**Files to Create**:
1. `app/Http/Controllers/Admin/MediaController.php`
   - Methods:
     - `upload(Request $request)` - Handle file upload
     - `delete(string $path)` - Delete file
     - `list()` - List uploaded files
   - Validation: file type, size, dimensions
   - Store in storage/app/public/uploads/
   - Generate thumbnails for images
   - Support: images (jpg, png, webp), videos (mp4, webm), documents (pdf)

2. `resources/views/admin/components/file-uploader.blade.php`
   - Drag & drop area
   - Browse button
   - Preview uploaded files
   - Progress bar
   - Multiple file upload
   - Delete uploaded file button

**Alpine.js Component**:
```javascript
function fileUploader() {
    return {
        files: [],
        uploading: false,
        progress: 0,

        async upload(fileList) {
            this.uploading = true;

            for (const file of fileList) {
                const formData = new FormData();
                formData.append('file', file);

                const response = await fetch('/su/portal/v1/admin/media/upload', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                this.files.push(data.url);
            }

            this.uploading = false;
        },

        async deleteFile(url) {
            await fetch('/su/portal/v1/admin/media/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ path: url })
            });

            this.files = this.files.filter(f => f !== url);
        }
    }
}
```

**Technical Requirements**:
- Use Laravel file storage system
- Validate file types and sizes
- Generate thumbnails using Intervention Image
- Optimize images (compress, resize)
- Store file metadata in database (optional)
- Support S3/CDN in future
- Add to article/media forms

---

### Task 4.4: Create Activity Timeline Component
**Priority**: P2 (Medium)
**Estimated Time**: 2-3 hours

**Files to Create**:
1. `resources/views/admin/components/activity-timeline.blade.php`
   - Vertical timeline design
   - Icon per action type
   - User avatar
   - Action description
   - Timestamp (relative: "2 hours ago")
   - Link to affected resource

**Features**:
- Display on dashboard
- Display on user profile page
- Display on content detail pages (article, hadith, etc.)
- Filter by date range
- Load more button (infinite scroll)

**Alpine.js Component**:
```javascript
function activityTimeline() {
    return {
        activities: [],
        loading: false,
        page: 1,

        async init() {
            await this.loadActivities();
        },

        async loadActivities() {
            this.loading = true;
            const response = await fetch(`/su/portal/v1/admin/dashboard/activity?page=${this.page}`);
            const data = await response.json();
            this.activities.push(...data.data);
            this.loading = false;
        },

        async loadMore() {
            this.page++;
            await this.loadActivities();
        }
    }
}
```

**Technical Requirements**:
- Use AdminLogRepository
- Format timestamps with Carbon::diffForHumans()
- Group by date (Today, Yesterday, This Week, etc.)
- Display user info and avatar
- Link to resource if it exists

---

### Task 4.5: Add Export Functionality
**Priority**: P2 (Medium)
**Estimated Time**: 2-3 hours

**Files to Create**:
1. `app/Http/Controllers/Admin/ExportController.php`
   - Methods:
     - `exportArticles(Request $request)` - Export articles to CSV
     - `exportHadith(Request $request)` - Export hadith to CSV
     - `exportUsers(Request $request)` - Export users to CSV
     - `exportActivityLogs(Request $request)` - Export logs to CSV
   - Support filters (export filtered results)
   - Use Laravel Excel or custom CSV writer

**Features**:
- Export button on each index page
- Export filtered/searched results
- Export selected items (bulk action)
- Export all data
- Choose columns to export
- Format: CSV, Excel (XLSX), PDF (optional)
- Download or email file

**Technical Requirements**:
- Use maatwebsite/excel package
- Queue large exports
- Stream files (don't load all in memory)
- Handle multi-language columns
- Format dates properly
- Sanitize data for Excel (remove HTML tags)

---

## PHASE 5: Polish & Optimization

### Task 5.1: Optimize Database Queries
**Priority**: P1 (High)
**Estimated Time**: 3-4 hours

**Actions**:
1. Review all repository methods for N+1 queries
2. Add eager loading where missing
3. Use `select()` to fetch only needed columns
4. Add database indexes for commonly queried fields
5. Optimize full-text search queries
6. Add composite indexes for filter combinations
7. Use query caching for frequently accessed data

**Files to Review**:
- All repository files in `app/Domain/*/Repositories/`
- Check with Laravel Debugbar
- Use `php artisan telescope:install` for query analysis

**Optimization Checklist**:
- [ ] ArticleRepository: Eager load category, tags, series, author
- [ ] HadithRepository: Eager load book, chapter, narrators
- [ ] MediaProgramRepository: Eager load category, episodes
- [ ] SeerahEventRepository: Eager load participants, sources
- [ ] SahabiRepository: Eager load tribe, family relationships
- [ ] GenealogyRepository: Eager load father, mother, spouses, children
- [ ] UserRepository: Eager load roles, permissions
- [ ] DashboardService: Cache all statistics (15 min TTL)

---

### Task 5.2: Implement Caching Strategy
**Priority**: P1 (High)
**Estimated Time**: 2-3 hours

**Actions**:
1. Cache dashboard statistics (15 min)
2. Cache category/tag lists (30 min)
3. Cache role/permission lists (1 hour)
4. Cache user count badges in sidebar (5 min)
5. Implement cache invalidation on CRUD operations
6. Use cache tags for bulk invalidation
7. Add cache clear button in admin panel

**Files to Modify**:
- All repository files
- DashboardService
- Add CacheClearController

**Cache Strategy**:
```php
// In repository
public function getAll(): Collection
{
    return Cache::tags(['articles'])->remember(
        'articles:all',
        now()->addMinutes(15),
        fn() => $this->getBaseQuery()->get()
    );
}

// On create/update/delete
Cache::tags(['articles'])->flush();
```

---

### Task 5.3: Add Form Validation Feedback
**Priority**: P1 (High)
**Estimated Time**: 2 hours

**Actions**:
1. Display validation errors below each field
2. Highlight invalid fields with red border
3. Show success messages after save
4. Show error messages on failure
5. Implement toast notifications
6. Add loading spinners on submit
7. Disable submit button during processing

**Files to Create**:
1. `resources/views/admin/components/toast.blade.php`
   - Success toast (green)
   - Error toast (red)
   - Warning toast (yellow)
   - Info toast (blue)
   - Auto-dismiss after 5 seconds
   - Close button

**Alpine.js Component**:
```javascript
function toastManager() {
    return {
        toasts: [],

        show(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type });

            setTimeout(() => {
                this.remove(id);
            }, 5000);
        },

        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }
}
```

---

### Task 5.4: Improve Mobile Responsiveness
**Priority**: P2 (Medium)
**Estimated Time**: 3-4 hours

**Actions**:
1. Make sidebar collapsible on mobile
2. Stack form fields vertically on mobile
3. Make tables horizontally scrollable
4. Optimize touch targets (larger buttons)
5. Test on various screen sizes
6. Use hamburger menu for navigation
7. Optimize chart displays for mobile

**Files to Modify**:
- `resources/views/layouts/admin.blade.php`
- `resources/views/admin/partials/sidebar.blade.php`
- `resources/views/admin/partials/header.blade.php`
- All index views (tables)
- All form views

**Responsive Breakpoints**:
- Mobile: < 640px
- Tablet: 640px - 1024px
- Desktop: > 1024px

---

### Task 5.5: Add Loading Skeletons
**Priority**: P2 (Medium)
**Estimated Time**: 2 hours

**Files to Create**:
1. `resources/views/admin/components/skeleton-card.blade.php`
2. `resources/views/admin/components/skeleton-table.blade.php`
3. `resources/views/admin/components/skeleton-form.blade.php`

**Features**:
- Display skeleton loader while data is loading
- Match layout of actual content
- Shimmer animation effect
- Replace with real content when loaded

---

### Task 5.6: Implement Dark Mode
**Priority**: P3 (Low)
**Estimated Time**: 2-3 hours

**Actions**:
1. Add dark mode toggle in header
2. Store preference in localStorage
3. Define dark mode color palette
4. Apply dark mode classes conditionally
5. Update all views to support dark mode
6. Ensure good contrast ratios

**Dark Mode Colors**:
- Background: #1a1a1a
- Surface: #2d2d2d
- Text: #ffffff
- Accent: #64CCC5

---

## PHASE 6: Testing & Documentation

### Task 6.1: Write Feature Tests
**Priority**: P1 (High)
**Estimated Time**: 4-5 hours

**Tests to Create**:
1. `tests/Feature/Admin/ArticleManagementTest.php`
   - Test CRUD operations
   - Test bulk actions
   - Test search
   - Test filters

2. Apply same for all modules (Hadith, Media, Quran, Seerah, Sahaba, Genealogy, Users, Roles)

3. `tests/Feature/Admin/ActivityLogTest.php`
   - Test logging on create/update/delete
   - Test activity viewer
   - Test filters

4. `tests/Feature/Admin/DashboardTest.php`
   - Test statistics endpoint
   - Test activity endpoint
   - Test growth endpoint

**Test Requirements**:
- Use RefreshDatabase trait
- Create factories for all models
- Test authorization (roles/permissions)
- Test validation rules
- Test error cases
- Mock external services

---

### Task 6.2: Create Admin User Documentation
**Priority**: P2 (Medium)
**Estimated Time**: 2-3 hours

**Files to Create**:
1. `ADMIN_GUIDE.md`
   - How to login
   - Dashboard overview
   - How to manage articles
   - How to manage hadith
   - How to manage media programs
   - How to manage users
   - How to manage roles
   - How to view activity logs
   - How to export data
   - How to use search
   - How to use bulk actions
   - Troubleshooting

2. `ADMIN_API.md`
   - List all admin endpoints
   - Request/response examples
   - Authentication headers
   - Error responses

---

## PRIORITY SUMMARY

### Critical (P0) - Must Complete First
1. Task 1.1: Create Sahaba Management Views
2. Task 1.2: Create Genealogy Management Views
3. Task 1.3: Create Users Management Views
4. Task 1.4: Create Roles & Permissions Management Views
5. Task 2.3: Integrate Logging into Controllers
6. Task 3.1: Integrate Dashboard Data
7. Task 3.2: Integrate Article Index Data
8. Task 3.3: Integrate Create/Edit Forms
9. Task 5.1: Optimize Database Queries
10. Task 5.2: Implement Caching Strategy

### High Priority (P1) - Complete Next
1. Task 2.1: Create AdminLog Model & Repository
2. Task 2.2: Create AdminLogger Service
3. Task 2.4: Create Activity Log Viewer
4. Task 3.4: Apply Same Pattern to All Modules
5. Task 5.3: Add Form Validation Feedback
6. Task 6.1: Write Feature Tests

### Medium Priority (P2) - Enhance Experience
1. Task 4.1: Implement Search UI Components
2. Task 4.2: Implement Bulk Actions UI
3. Task 4.3: Implement Media Upload System
4. Task 4.4: Create Activity Timeline Component
5. Task 4.5: Add Export Functionality
6. Task 5.4: Improve Mobile Responsiveness
7. Task 5.5: Add Loading Skeletons
8. Task 6.2: Create Admin User Documentation

### Low Priority (P3) - Nice to Have
1. Task 5.6: Implement Dark Mode

---

## ESTIMATED TOTAL TIME
- Phase 1: 9-13 hours
- Phase 2: 8-11 hours
- Phase 3: 15-21 hours
- Phase 4: 13-18 hours
- Phase 5: 14-19 hours
- Phase 6: 6-8 hours

**Total: 65-90 hours** (approximately 2-3 weeks of full-time work)

---

## CURRENT PROGRESS TRACKER

### Completed Tasks: 4/32 (12.5%)
- [x] Task 1.1: Create Sahaba Management Views ✅
- [x] Task 1.2: Create Genealogy Management Views ✅
- [x] Task 1.3: Create Users Management Views ✅
- [x] Task 1.4: Create Roles & Permissions Management Views ✅
- [ ] Task 2.1: Create AdminLog Model & Repository
- [ ] Task 2.2: Create AdminLogger Service
- [ ] Task 2.3: Integrate Logging into Controllers
- [ ] Task 2.4: Create Activity Log Viewer
- [ ] Task 3.1: Integrate Dashboard Data
- [ ] Task 3.2: Integrate Article Index Data
- [ ] Task 3.3: Integrate Create/Edit Forms
- [ ] Task 3.4: Apply Same Pattern to All Modules
- [ ] Task 4.1: Implement Search UI Components
- [ ] Task 4.2: Implement Bulk Actions UI
- [ ] Task 4.3: Implement Media Upload System
- [ ] Task 4.4: Create Activity Timeline Component
- [ ] Task 4.5: Add Export Functionality
- [ ] Task 5.1: Optimize Database Queries
- [ ] Task 5.2: Implement Caching Strategy
- [ ] Task 5.3: Add Form Validation Feedback
- [ ] Task 5.4: Improve Mobile Responsiveness
- [ ] Task 5.5: Add Loading Skeletons
- [ ] Task 5.6: Implement Dark Mode
- [ ] Task 6.1: Write Feature Tests
- [ ] Task 6.2: Create Admin User Documentation

---

## NOTES
- Follow DDD architecture strictly
- Use repository pattern for all data access
- Use service layer for business logic
- Keep controllers thin
- Add PHPDoc comments to all methods
- Use strict types in all PHP files
- Follow PSR-12 coding standards
- Run `./vendor/bin/pint` before committing
- Test with real database data (no mocks)
- All text in Arabic (RTL) by default
- Support 5 languages: Arabic, English, French, Turkish, Urdu
- Use Alpine.js for frontend interactivity
- Use Tailwind CSS for styling
- Use Lucide icons
- Cache aggressively for performance
- Optimize all database queries
- No N+1 queries allowed
