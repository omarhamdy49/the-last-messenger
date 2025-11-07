# Article Module - Quick Reference Guide

## Key Files at a Glance

### Models (3 files)
- **Article.php** - Main model with 6 relationships
- **ArticleLocale.php** - Multi-language content storage
- **ArticleGallery.php** - Gallery images with captions

### Controllers (1 file)
- **ArticleController.php** - 7 CRUD methods + 3 bulk methods (search, bulkDelete, bulkPublish, bulkArchive)

### Validation (2 files)
- **StoreArticleRequest.php** - 40+ validation rules
- **UpdateArticleRequest.php** - Similar to Store with slug exclusion

### Views (5 files)
| View | Status | Features |
|------|--------|----------|
| index | ✅ Complete | List, filter, paginate (20/page) |
| create | ⚠️ Partial | Arabic only, no WYSIWYG |
| edit | ✅ Complete | Multi-language, all locales |
| show | ✅ Complete | Dark mode, tabs, analytics |
| trash | ✅ Complete | Recovery, permanent delete |

### Database (4 tables)
- articles (11 columns + timestamps)
- article_locale (9 columns + timestamps)
- article_category (pivot)
- articles_gallery (5 columns + timestamps)

## Quick Stats

| Metric | Count |
|--------|-------|
| Models | 3 |
| Controllers | 1 |
| Views | 5 |
| Database Tables | 4 |
| Controller Methods | 10 |
| Validation Rules | 40+ |
| Supported Languages | 5 (AR, EN, FR, TR, UR) |
| Articles per Page | 20 |

## Workflow

### Creating an Article
1. Form: `/admin/articles/create` (Arabic only)
2. POST: `/admin/articles` (StoreArticleRequest)
3. Creates: Article + ArticleLocale + attaches Categories
4. Redirect: Index with success message

### Editing an Article
1. Form: `/admin/articles/{id}/edit` (All languages)
2. PUT: `/admin/articles/{id}` (UpdateArticleRequest)
3. Updates: Article + ArticleLocale records + syncs Categories
4. Redirect: Index with success message

### Viewing an Article
1. Show page: `/admin/articles/{id}`
2. Display: All locales with language tabs
3. Features: Dark mode, status cards, metadata

### Deleting an Article
1. Soft delete: Sets deleted_at timestamp
2. Cleanup: Thumbnail file, gallery images
3. Cascade: Locales, gallery, categories

## Important Properties

### Article Fields
- `thumb` - Thumbnail path
- `writer_id` - Author
- `read_time` - Minutes (required)
- `views` - Count (auto)
- `is_featured` - Boolean flag
- `is_pinned` - Boolean flag
- `category_id` - Primary category
- `hijri_date` - Islamic calendar date
- `statistics` - JSON metrics
- `references` - JSON array

### ArticleLocale Fields
- `locale` - Language code
- `title` - Translated title (required)
- `slug` - URL slug
- `excerpt` - Short preview
- `content` - JSON: {body, toc, summary}
- `seo_metadata` - JSON: {title, desc, keywords}
- `is_ready` - Translation status

## Relationships at a Glance

```
Article
├── hasMany → ArticleLocale (one per language)
├── hasMany → ArticleGallery (ordered)
├── belongsTo → User (writer)
├── belongsTo → Category (primary)
└── belongsToMany → Category (via pivot)

ArticleLocale
└── belongsTo → Article

ArticleGallery
└── belongsTo → Article
```

## API Routes (Disabled)

Currently commented out in `routes/api.php`:
- GET /articles
- GET /articles/featured
- GET /articles/popular
- GET /articles/recent
- GET /articles/{slug}
- POST /articles (protected)
- PUT /articles/{id} (protected)
- DELETE /articles/{id} (protected)

## Validation Highlights

### Required Fields
- `title.ar` - Arabic title (min: -, max: 500)
- `content.ar` - Arabic content (min: 50, max: -)
- `read_time` - Integer, min: 0

### Optional Fields with Limits
- Titles (en/fr/tr/ur) - max: 500 chars
- Excerpts - max: 1000 chars
- Content (en/fr/tr/ur) - min: 50 chars (if provided)

### File Uploads
- `featured_image` - JPEG/PNG/WebP/GIF, max: 10MB
- `og_image` - JPEG/PNG/WebP, max: 5MB
- `gallery` - Up to 10 images, each max: 10MB

## Missing Implementations

### High Priority
1. WYSIWYG Editor integration
2. Multi-language create form
3. Media library picker UI
4. SEO metadata editor UI
5. Public API implementation

### Medium Priority
1. Search/Filter UI
2. Bulk operations UI
3. Gallery management UI
4. Author assignment UI
5. Analytics dashboard

### Low Priority
1. Export (CSV/PDF)
2. Revision history
3. Draft scheduling
4. Related articles
5. Comments system

## File Sizes
- ArticleController: ~317 lines
- StoreArticleRequest: ~318 lines
- Article model: ~145 lines
- Index view: ~134 lines
- Edit view: ~112 lines
- Show view: ~240 lines

## Key Takeaways

✅ **Strengths:**
- Multi-language architecture
- Comprehensive validation
- Soft delete support
- Feature-rich show page
- Clean code structure

⚠️ **Weaknesses:**
- Create form only supports Arabic
- No WYSIWYG editor
- No public API
- Missing SEO UI
- Incomplete gallery management

🎯 **Best For:**
- Admin panel article management
- Multi-language content storage
- Basic CRUD operations
- Dashboard display

❌ **Not Ready For:**
- Public API consumption
- User content creation
- Rich media editing
- Advanced publishing workflows

