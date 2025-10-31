# Article Module - Quick Reference Guide
## Fast Implementation Checklist for Developers

---

## 🚀 Quick Start

**Total Duration:** 20-26 days (4-6 developers working in parallel)
**Language:** PHP 8.2+ / Laravel 11
**Database:** MySQL 8.0+
**Recommended Parallel Approach:** Assign teams to each task simultaneously

---

## 📋 Task Assignment Matrix

### Team 1: Database & Models (Days 1-6)
```
Assign to: 1 senior developer
Parallel: Weeks 1-2
Tasks:
  ✓ TASK A1: Database schema & migrations (3-4 days)
  ✓ TASK A2: Models & relationships (2-3 days)
```

**Files to Create:**
- `database/migrations/2025_10_31_000001_create_articles_table.php`
- `database/migrations/2025_10_31_000002_create_article_translations_table.php`
- `database/migrations/2025_10_31_000003_create_article_category_table.php`
- `database/migrations/2025_10_31_000004_create_categories_table.php`
- `database/migrations/2025_10_31_000005_create_authors_table.php`
- `app/Models/Article.php`
- `app/Models/Category.php`
- `database/factories/ArticleFactory.php`
- `database/seeders/ArticleSeeder.php`

**Deliverable:** Database tables created, models working, relationships tested

---

### Team 2: Controllers & Routes (Days 4-7)
```
Assign to: 1 mid-level developer
Parallel: Weeks 1-2
Dependencies: TASK A2 (Models)
Tasks:
  ✓ TASK A3: Controllers & routes (3-4 days)
```

**Files to Create:**
- `app/Http/Controllers/Admin/ArticleController.php`
- `routes/api.php` (article endpoints)
- `routes/web.php` (admin routes)
- `app/Http/Requests/StoreArticleRequest.php`
- `app/Http/Requests/UpdateArticleRequest.php`

**Deliverable:** All CRUD endpoints working, routes registered, validation rules active

---

### Team 3: Form Components (Days 7-10)
```
Assign to: 2 developers
Parallel: Weeks 2-3
Dependencies: TASK A3 (Controllers)
Tasks:
  ✓ TASK A4: Form components with Livewire (3-4 days)
```

**Files to Create:**
- `app/Livewire/Admin/ArticleForm.php`
- `resources/views/livewire/admin/article-form.blade.php`
- `resources/views/admin/articles/index.blade.php`
- `resources/views/admin/articles/show.blade.php`
- `resources/views/admin/articles/create.blade.php`
- `resources/views/admin/articles/edit.blade.php`

**Deliverable:** All form pages working, Livewire binding functional, validation showing

---

### Team 4: WYSIWYG Editor (Days 10-12)
```
Assign to: 1 developer
Parallel: Weeks 2-3
Dependencies: TASK A4 (Forms)
Tasks:
  ✓ TASK A5: WYSIWYG editor integration (2-3 days)
```

**Files to Create:**
- `resources/views/layouts/admin.blade.php` (TinyMCE script)
- `public/css/tinymce-content.css`
- `resources/js/components/ArticleEditor.js`

**Configuration:**
- Install TinyMCE via npm or CDN
- Setup Arabic language support
- Configure file upload handlers
- Test with both RTL and LTR content

**Deliverable:** Editor loads in form, saves content correctly, supports Arabic

---

### Team 5: Image Upload (Days 12-14)
```
Assign to: 1 developer
Parallel: Weeks 3
Dependencies: TASK A5 (Editor) - optional, can be parallel
Tasks:
  ✓ TASK A6: Image upload manager (2-3 days)
```

**Files to Create:**
- `app/Services/ImageUploadService.php`
- Setup `intervention/image` package
- Create storage folders structure

**Install:**
```bash
composer require intervention/image
npm install sharp --save-dev
```

**Deliverable:** Images upload, thumbnails generate, files stored, cleanup works

---

### Team 6: SEO & Workflow (Days 14-18)
```
Assign to: 1 developer
Parallel: Weeks 3-4
Dependencies: TASK A6 (Images)
Tasks:
  ✓ TASK A7: SEO metadata (2 days)
  ✓ TASK A8: Publishing workflow (2 days)
```

**Files to Create:**
- `app/Services/SeoService.php`
- `database/migrations/2025_10_31_000006_add_workflow_to_articles.php`
- Update `app/Models/Article.php` with workflow methods

**Deliverable:** SEO validation working, publishing states functional, workflow tested

---

## 🛠️ Installation & Setup (First Day)

### 1. Create Laravel Project
```bash
laravel new the-last-messenger-be
cd the-last-messenger-be
```

### 2. Configure Database
```bash
# .env file
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=last_messenger
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Install Dependencies
```bash
# Core packages
composer require livewire/livewire
composer require spatie/laravel-medialibrary
composer require spatie/laravel-translatable
composer require intervention/image
composer require laravel/sanctum

# Frontend
npm install
npm install tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

### 4. Setup Database
```bash
php artisan migrate
php artisan db:seed --class=ArticleSeeder
```

### 5. Create Admin User
```bash
php artisan tinker
User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password'), 'role' => 'admin']);
```

---

## 📝 Implementation Checklist

### Phase 1: Database & Models
```
Day 1-2:
  [ ] Create articles table migration
  [ ] Create article_translations table
  [ ] Create categories pivot table
  [ ] Run migrations
  [ ] Verify all tables in database

Day 3-4:
  [ ] Create Article model
  [ ] Create Category model
  [ ] Add relationships
  [ ] Create factories
  [ ] Create seeders
  [ ] Test relationships with Tinker
  [ ] Seed sample data
```

### Phase 2: Controllers & Routes
```
Day 5-6:
  [ ] Create ArticleController
  [ ] Implement index() method
  [ ] Implement create/store methods
  [ ] Implement edit/update methods
  [ ] Implement delete/destroy methods
  [ ] Add validation request classes
  [ ] Register routes (web + API)
  [ ] Test all endpoints with Postman

Day 7:
  [ ] Add search/filter logic
  [ ] Add bulk operations
  [ ] Add activity logging
  [ ] Test permissions middleware
```

### Phase 3: Frontend Forms
```
Day 8:
  [ ] Create ArticleForm Livewire component
  [ ] Create form Blade template
  [ ] Implement form validation
  [ ] Test create/edit flows
  [ ] Add error messages

Day 9:
  [ ] Create listing view (index)
  [ ] Add search/filter to listing
  [ ] Add pagination
  [ ] Add bulk actions
  [ ] Style with Tailwind

Day 10:
  [ ] Create show/detail view
  [ ] Add edit link
  [ ] Add delete button
  [ ] Implement confirmation dialogs
  [ ] Add success/error notifications
```

### Phase 4: WYSIWYG Editor
```
Day 11:
  [ ] Install TinyMCE (npm or CDN)
  [ ] Create custom CSS for Arabic
  [ ] Configure Arabic language
  [ ] Setup file upload handler
  [ ] Test editor in form

Day 12:
  [ ] Test content save/load
  [ ] Test with Arabic content
  [ ] Test image embedding
  [ ] Test undo/redo
  [ ] Optimize editor settings
```

### Phase 5: Image Upload
```
Day 13:
  [ ] Create ImageUploadService
  [ ] Install intervention/image
  [ ] Create upload directory
  [ ] Implement file validation
  [ ] Test thumbnail generation

Day 14:
  [ ] Add image preview
  [ ] Implement image deletion
  [ ] Add error handling
  [ ] Test with various image sizes
  [ ] Optimize image quality
```

### Phase 6: SEO & Workflow
```
Day 15:
  [ ] Create SeoService
  [ ] Add SEO fields to model
  [ ] Create SEO form inputs
  [ ] Add validation rules
  [ ] Test meta tag generation

Day 16:
  [ ] Add publishing status field
  [ ] Implement publish() method
  [ ] Create workflow migrations
  [ ] Add status indicators in UI
  [ ] Test all workflow states

Day 17-18:
  [ ] Integration testing
  [ ] Bug fixes
  [ ] Performance optimization
  [ ] Code review
  [ ] Documentation
```

---

## 🧪 Testing Checklist

### Unit Tests
```
[ ] ArticleFactory generates valid articles
[ ] Article model relationships work
[ ] Article scopes return correct results
[ ] SeoService generates valid tags
[ ] ImageUploadService handles uploads
[ ] Category hierarchies work
```

### Integration Tests
```
[ ] Can create article with controller
[ ] Can update article with validation
[ ] Can delete article with cascade
[ ] Can attach/detach categories
[ ] Can upload featured image
[ ] Can publish article
[ ] SEO tags generated on publish
```

### Feature Tests
```
[ ] Admin can access article list
[ ] Admin can create new article
[ ] Admin can edit existing article
[ ] Admin can delete article
[ ] Admin can search articles
[ ] Admin can filter by status
[ ] Admin can filter by category
[ ] Admin can bulk delete
[ ] Images are stored correctly
[ ] Thumbnails are created
```

### Manual Tests
```
[ ] Create article with Arabic text
[ ] Create article with images
[ ] Use WYSIWYG editor (paste, format, insert)
[ ] Upload image via editor
[ ] Save article as draft
[ ] Publish article
[ ] View published article on frontend
[ ] Test responsive design
[ ] Test dark mode (if enabled)
[ ] Test all validation messages
```

---

## 🔗 File Dependencies

```
A1 (Database)
  ↓
A2 (Models) ← Uses tables from A1
  ↓
A3 (Controllers) ← Uses models from A2
  ↓
A4 (Forms) ← Uses controllers from A3
  ↓
A5 (Editor) ← Works with forms from A4
  ↓
A6 (Images) ← Parallel with A5, used by A4
  ↓
A7-A8 (SEO & Workflow) ← Final layer on top
```

**Can Run in Parallel:**
- A1 + A2 → A3 + A4 in parallel → A5 + A6 in parallel → A7 + A8

**Cannot Parallelize:**
- Database must be created first
- Models depend on database
- Controllers depend on models
- Forms depend on controllers

---

## 📦 Package Versions

```json
{
  "php": "^8.2",
  "laravel/framework": "^11.0",
  "livewire/livewire": "^3.0",
  "spatie/laravel-translatable": "^6.5",
  "spatie/laravel-medialibrary": "^10.0",
  "intervention/image": "^3.0",
  "laravel/sanctum": "^3.0"
}
```

---

## 📊 Success Metrics

### Functionality
- [ ] All CRUD operations work
- [ ] Form validation prevents bad data
- [ ] Images upload and resize correctly
- [ ] Multi-language content saves properly
- [ ] Publishing workflow functional
- [ ] Search and filtering work

### Performance
- [ ] Page loads < 2 seconds
- [ ] Form submission < 1 second
- [ ] Image upload < 5 seconds
- [ ] Database queries optimized (check Laravel Debugbar)
- [ ] No N+1 queries

### Quality
- [ ] No error logs
- [ ] All tests passing
- [ ] Code follows Laravel conventions
- [ ] Database migrations reversible
- [ ] Activity logged for all changes

### User Experience
- [ ] Forms are intuitive
- [ ] Validation messages are clear
- [ ] Success/error notifications work
- [ ] Responsive design works
- [ ] Editor is functional

---

## 🐛 Common Issues & Solutions

### Issue: Foreign Key Constraint Fails
**Solution:** Ensure related tables exist before migrations that reference them. Run migrations in order.

### Issue: Images Not Uploading
**Solution:** Check storage/app/public permissions (755). Ensure `Storage::disk('public')` is configured in filesystem.php

### Issue: TinyMCE Not Initializing
**Solution:** Check CDN key is valid. Ensure script loads before Livewire. Call `tinymce.init()` in Livewire lifecycle hook.

### Issue: Arabic Text Not Displaying
**Solution:** Add `dir="rtl"` to blade templates. Use Cairo font in CSS. Set `directionality: 'rtl'` in editor config.

### Issue: Form Validation Not Showing
**Solution:** Ensure error bag is displayed in template. Check Livewire component has proper @error directives.

---

## 📚 File References

**Main Implementation:**
- `/ARTICLE-MODULE-IMPLEMENTATION.md` ← Full detailed guide (this extends it)
- `/ADMIN-DASHBOARD-PLAN.md` ← Overall dashboard plan
- `/ADMIN-TASKS-BREAKDOWN.md` ← Phase 1 all tasks

**Related Modules Coming Next:**
- Hadith Module (similar structure)
- Quran Module
- Genealogy Module
- Media Module

---

## 🎯 Next Steps After Article Module

1. **Article Module Complete:** 20-26 days
2. **Author Manager:** 3-4 days
3. **Category Manager:** 2-3 days
4. **Statistics/Analytics:** 3-4 days
5. **Content Localization UI:** 4-5 days

---

## 💬 Questions & Support

**Q: Can we use a different WYSIWYG editor?**
A: Yes. CKEditor 5 is recommended. Same integration process.

**Q: Do we need SEO for phase 1?**
A: Recommended but not critical. Can be deferred to phase 2.

**Q: Can we skip featured images?**
A: Not recommended. Needed for frontend display.

**Q: How do we handle large datasets?**
A: Implement pagination (already in code), use indexes (in migration), consider Elasticsearch for search (phase 2).

---

**Status:** ✅ Ready for Implementation
**Version:** 1.0
**Last Updated:** 2025-10-31

**Start Date Recommendation:** Tomorrow
**Expected Completion:** 20-26 days with 4-6 developers
**Go Live:** Week 5-6 of project
