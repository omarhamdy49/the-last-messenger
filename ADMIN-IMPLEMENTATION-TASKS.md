# Admin Panel Implementation Tasks

## Status: In Progress

### Tasks Summary
- [x] Fix dashboard route naming (admin.dashboard.*)
- [ ] Create missing Quran views (create, edit, show)
- [ ] Create missing Seerah show view
- [ ] Create missing Genealogy views (create, edit, show)
- [ ] Create missing Sahaba show view
- [ ] Create missing Users show view
- [ ] Create missing Roles views (create, edit, show)
- [ ] Test all routes and views

### Detailed Task List

#### Route Fixes
- **Fixed**: Dashboard route from `name('dashboard.')` to `name('admin.dashboard.')`
  - File: `routes/admin.php` (line 77)
  - Updated all route references in sidebar and views

#### Missing Blade Views to Create

**Quran Module** (3 views):
- [ ] `resources/views/admin/quran/create.blade.php` - Create new Surah
- [ ] `resources/views/admin/quran/edit.blade.php` - Edit existing Surah
- [ ] `resources/views/admin/quran/show.blade.php` - View Surah details

**Seerah Module** (1 view):
- [ ] `resources/views/admin/seerah-events/show.blade.php` - View Seerah event details

**Genealogy Module** (3 views):
- [ ] `resources/views/admin/genealogy/create.blade.php` - Create person
- [ ] `resources/views/admin/genealogy/edit.blade.php` - Edit person
- [ ] `resources/views/admin/genealogy/show.blade.php` - View person details

**Sahaba Module** (1 view):
- [ ] `resources/views/admin/sahaba/show.blade.php` - View companion details

**Users Module** (1 view):
- [ ] `resources/views/admin/users/show.blade.php` - View user profile

**Roles Module** (3 views):
- [ ] `resources/views/admin/roles/create.blade.php` - Create role
- [ ] `resources/views/admin/roles/edit.blade.php` - Edit role
- [ ] `resources/views/admin/roles/show.blade.php` - View role details

### Controllers Status
All 14 admin controllers are implemented:
- ✅ DashboardController
- ✅ ArticleController
- ✅ HadithController
- ✅ MediaProgramController
- ✅ QuranController
- ✅ SeerahEventController
- ✅ GenealogyController
- ✅ SahabiController
- ✅ UserController
- ✅ RoleController
- ✅ ActivityLogController
- ✅ LanguageSettingsController
- ✅ MediaUploadController

### Routes Status
All routes defined in `routes/admin.php` (prefixed with `/su/portal/v1/admin`):
- ✅ Dashboard routes (dashboard.*)
- ✅ Media upload routes (media.*)
- ✅ Article CRUD + bulk actions (articles.*)
- ✅ Hadith CRUD + bulk actions (hadith.*)
- ✅ Media programs CRUD + bulk actions (media-programs.*)
- ✅ Quran CRUD + bulk actions (quran.*)
- ✅ Sahaba CRUD + bulk actions (sahaba.*)
- ✅ Seerah events CRUD + bulk actions (seerah-events.*)
- ✅ Genealogy CRUD + bulk actions (genealogy.*)
- ✅ Users CRUD + bulk actions (users.*)
- ✅ Roles CRUD (roles.*)
- ✅ Activity logs routes (activity-logs.*)
- ✅ Language settings routes (settings.languages.*)

### Existing Views (Complete)
- ✅ admin/dashboard.blade.php
- ✅ admin/articles/(index, create, edit, show)
- ✅ admin/hadith/(index, create, edit, show)
- ✅ admin/media-programs/(index, create, edit, show)
- ✅ admin/quran/index.blade.php
- ✅ admin/seerah-events/(index, create, edit)
- ✅ admin/genealogy/index.blade.php
- ✅ admin/sahaba/(index, create, edit)
- ✅ admin/users/(index, create, edit)
- ✅ admin/roles/index.blade.php
- ✅ admin/activity-logs/(index, show)
- ✅ admin/settings/languages/index.blade.php
- ✅ admin/auth/login.blade.php
- ✅ admin/components-demo.blade.php
- ✅ Partials (layouts/admin.blade.php, sidebar, header, footer, etc.)

### Next Steps
1. Create all missing views with proper styling
2. Ensure all forms support multi-language input (ar, en, fr, tr, ur)
3. Test each route and view combination
4. Verify error handling and validation messages
5. Test dark mode toggle functionality
6. Test responsive design on mobile/tablet/desktop
