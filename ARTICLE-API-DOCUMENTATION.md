# Article Management API Documentation

## Overview
Comprehensive backend services for article management with multi-language support, version control, and media handling.

## Architecture

### Components Created
1. **ArticleController** (`app/Http/Controllers/Admin/ArticleController.php`)
   - Extends AdminController base class
   - Full CRUD operations
   - Bulk operations
   - Special actions (duplicate, preview, restore)

2. **ArticleRepository** (`app/Repositories/ArticleRepository.php`)
   - Data access layer
   - Query optimization with caching
   - Advanced filtering and search

3. **Form Requests**
   - `StoreArticleRequest.php` - Validation for creating articles
   - `UpdateArticleRequest.php` - Validation for updating articles

4. **ArticleResource** (`app/Http/Resources/ArticleResource.php`)
   - JSON API resource transformation
   - Includes locales, categories, tags, media, versions

5. **Version Service Integration**
   - Uses existing `VersionService` for content versioning
   - Automatic version snapshots on create/update

## API Endpoints

### Base URL: `/admin/articles`

---

### 1. List Articles
```http
GET /admin/articles
```

**Query Parameters:**
- `per_page` (int, optional): Items per page (default: 15, max: 100)
- `page` (int, optional): Page number
- `sort` (string, optional): Sort field (default: 'created_at')
- `order` (string, optional): Sort order 'asc' or 'desc' (default: 'desc')
- `q` (string, optional): Search query
- `status` (string, optional): 'draft', 'published', 'featured', 'pinned'
- `category_id` (int, optional): Filter by category
- `author_id` (int, optional): Filter by author
- `is_featured` (bool, optional): Filter featured articles
- `is_pinned` (bool, optional): Filter pinned articles
- `locale` (string, optional): Filter by available locale
- `date_from` (date, optional): Filter from date
- `date_to` (date, optional): Filter to date

**Response (JSON):**
```json
{
  "success": true,
  "message": "Articles retrieved successfully",
  "data": [
    {
      "id": 1,
      "thumb": "https://example.com/storage/articles/thumbs/image.jpg",
      "read_time": 5,
      "views": 1250,
      "is_featured": true,
      "is_pinned": false,
      "hijri_date": "1 Muharram 1446",
      "created_at": "2024-11-07T10:00:00Z",
      "updated_at": "2024-11-07T12:30:00Z",
      "writer": {
        "id": 2,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "category": {
        "id": 5,
        "name": "Seerah",
        "slug": "seerah"
      },
      "categories": [
        {"id": 5, "name": "Seerah", "slug": "seerah"},
        {"id": 8, "name": "History", "slug": "history"}
      ],
      "locales": {
        "ar": {
          "title": "عنوان المقال",
          "slug": "article-title",
          "excerpt": "مقتطف المقال",
          "is_ready": true,
          "url": "https://example.com/ar/articles/article-title"
        },
        "en": {
          "title": "Article Title",
          "slug": "article-title-en",
          "excerpt": "Article excerpt",
          "is_ready": true,
          "url": "https://example.com/en/articles/article-title-en"
        }
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "https://example.com/admin/articles?page=1",
    "last": "https://example.com/admin/articles?page=10",
    "prev": null,
    "next": "https://example.com/admin/articles?page=2"
  }
}
```

---

### 2. Create Article
```http
POST /admin/articles
Content-Type: multipart/form-data
```

**Request Body:**
```json
{
  "title": {
    "ar": "عنوان المقال بالعربية",
    "en": "Article Title in English",
    "fr": "Titre de l'article en français"
  },
  "slug": {
    "ar": "article-title-ar",
    "en": "article-title-en"
  },
  "excerpt": {
    "ar": "مقتطف المقال",
    "en": "Article excerpt"
  },
  "description": {
    "ar": "وصف المقال",
    "en": "Article description"
  },
  "content": {
    "ar": "<p>محتوى المقال بالعربية</p>",
    "en": "<p>Article content in English</p>"
  },
  "summary": {
    "ar": "ملخص المقال",
    "en": "Article summary"
  },
  "seo_title": {
    "ar": "عنوان SEO",
    "en": "SEO Title"
  },
  "seo_description": {
    "ar": "وصف SEO",
    "en": "SEO Description"
  },
  "is_ready": {
    "ar": true,
    "en": true,
    "fr": false
  },
  "category_id": 5,
  "additional_categories": [8, 12],
  "tags": [1, 3, 7],
  "status": "published",
  "published_at": "2024-11-10T15:00:00Z",
  "is_featured": true,
  "is_pinned": false,
  "thumb": "(file upload)",
  "gallery": [15, 22, 33],
  "writer_id": 2,
  "read_time": 5,
  "hijri_date": "1 Muharram 1446"
}
```

**Response (JSON - 201 Created):**
```json
{
  "success": true,
  "message": "Article created successfully",
  "data": {
    "id": 123,
    "thumb": "https://example.com/storage/articles/thumbs/new-image.jpg",
    "read_time": 5,
    "views": 0,
    "is_featured": true,
    "is_pinned": false,
    "hijri_date": "1 Muharram 1446",
    "created_at": "2024-11-07T14:00:00Z",
    "updated_at": "2024-11-07T14:00:00Z",
    "current_version": 1,
    "locales": {
      "ar": {
        "title": "عنوان المقال بالعربية",
        "slug": "article-title-ar",
        "is_ready": true
      },
      "en": {
        "title": "Article Title in English",
        "slug": "article-title-en",
        "is_ready": true
      }
    },
    "urls": {
      "admin_edit": "https://example.com/admin/articles/123/edit",
      "admin_show": "https://example.com/admin/articles/123",
      "preview": "https://example.com/admin/articles/123/preview"
    }
  }
}
```

---

### 3. Show Article
```http
GET /admin/articles/{id}
```

**Response (JSON):**
```json
{
  "success": true,
  "message": "Article retrieved successfully",
  "data": {
    "id": 123,
    "thumb": "https://example.com/storage/articles/thumbs/image.jpg",
    "read_time": 5,
    "views": 1250,
    "is_featured": true,
    "is_pinned": false,
    "hijri_date": "1 Muharram 1446",
    "statistics": {
      "total_shares": 45,
      "avg_read_percentage": 78
    },
    "references": [
      "Sahih Bukhari, Book 64, Hadith 123",
      "Tafsir Ibn Kathir, Surah Al-Baqarah"
    ],
    "created_at": "2024-11-01T10:00:00Z",
    "updated_at": "2024-11-07T12:30:00Z",
    "writer": {
      "id": 2,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "category": {
      "id": 5,
      "name": "Seerah",
      "slug": "seerah"
    },
    "categories": [
      {"id": 5, "name": "Seerah", "slug": "seerah"},
      {"id": 8, "name": "History", "slug": "history"}
    ],
    "tags": [
      {
        "id": 1,
        "slug": "prophet-muhammad",
        "name": {
          "ar": "النبي محمد",
          "en": "Prophet Muhammad",
          "fr": "Prophète Muhammad"
        }
      }
    ],
    "locales": {
      "ar": {
        "title": "عنوان المقال",
        "slug": "article-title",
        "excerpt": "مقتطف المقال",
        "description": "وصف المقال",
        "content": {
          "body": "<p>محتوى المقال</p>",
          "toc": [
            {"id": "section-1", "text": "القسم الأول", "level": 2},
            {"id": "section-2", "text": "القسم الثاني", "level": 2}
          ],
          "summary": "ملخص المقال"
        },
        "seo_title": "عنوان SEO",
        "seo_description": "وصف SEO",
        "is_ready": true,
        "url": "https://example.com/ar/articles/article-title"
      },
      "en": {
        "title": "Article Title",
        "slug": "article-title-en",
        "excerpt": "Article excerpt",
        "content": {
          "body": "<p>Article content</p>",
          "toc": [
            {"id": "section-1", "text": "Section 1", "level": 2}
          ],
          "summary": "Article summary"
        },
        "is_ready": true,
        "url": "https://example.com/en/articles/article-title-en"
      }
    },
    "media": [
      {
        "id": 15,
        "file_name": "image1.jpg",
        "mime_type": "image/jpeg",
        "size": 245678,
        "url": "https://example.com/storage/media/image1.jpg"
      }
    ],
    "versions": [
      {
        "id": 45,
        "version_number": 3,
        "change_type": "updated",
        "change_summary": "Article updated",
        "changed_by": {
          "id": 2,
          "name": "John Doe"
        },
        "created_at": "2024-11-07T12:30:00Z"
      },
      {
        "id": 33,
        "version_number": 2,
        "change_type": "updated",
        "change_summary": "Content revision",
        "changed_by": {
          "id": 2,
          "name": "John Doe"
        },
        "created_at": "2024-11-05T09:15:00Z"
      }
    ],
    "current_version": 3,
    "urls": {
      "admin_edit": "https://example.com/admin/articles/123/edit",
      "admin_show": "https://example.com/admin/articles/123",
      "preview": "https://example.com/admin/articles/123/preview"
    }
  }
}
```

---

### 4. Update Article
```http
PUT /admin/articles/{id}
Content-Type: multipart/form-data
```

**Request Body:** (Same structure as Create)

**Response (JSON):**
```json
{
  "success": true,
  "message": "Article updated successfully",
  "data": {
    // Same structure as Show response
  }
}
```

---

### 5. Delete Article (Soft Delete)
```http
DELETE /admin/articles/{id}
```

**Response (JSON):**
```json
{
  "success": true,
  "message": "Article deleted successfully",
  "data": null
}
```

---

### 6. Bulk Delete Articles
```http
POST /admin/articles/bulk-destroy
Content-Type: application/json
```

**Request Body:**
```json
{
  "ids": [1, 5, 12, 23]
}
```

**Response (JSON):**
```json
{
  "success": true,
  "message": "4 articles deleted successfully",
  "data": {
    "success": 4,
    "failed": 0,
    "errors": {}
  }
}
```

---

### 7. Bulk Publish Articles
```http
POST /admin/articles/bulk-publish
Content-Type: application/json
```

**Request Body:**
```json
{
  "ids": [1, 5, 12, 23]
}
```

**Response (JSON):**
```json
{
  "success": true,
  "message": "4 articles published successfully",
  "data": {
    "success": 4,
    "failed": 0,
    "errors": {}
  }
}
```

---

### 8. Duplicate Article
```http
POST /admin/articles/{id}/duplicate
```

**Response (JSON):**
```json
{
  "success": true,
  "message": "Article duplicated successfully",
  "data": {
    "id": 124,
    "thumb": "https://example.com/storage/articles/thumbs/image.jpg",
    "is_featured": false,
    "is_pinned": false,
    "views": 0,
    "locales": {
      "ar": {
        "title": "عنوان المقال",
        "slug": "article-title-copy-abc123",
        "is_ready": false
      }
    },
    "urls": {
      "admin_edit": "https://example.com/admin/articles/124/edit"
    }
  }
}
```

---

### 9. Preview Article
```http
GET /admin/articles/{id}/preview?locale=ar
```

**Response:** HTML page with article preview

---

### 10. Restore Deleted Article
```http
POST /admin/articles/{id}/restore
```

**Response (JSON):**
```json
{
  "success": true,
  "message": "Article restored successfully",
  "data": {
    "id": 123,
    // Article data
  }
}
```

---

### 11. Permanently Delete Article
```http
DELETE /admin/articles/{id}/force-delete
```

**Response (JSON):**
```json
{
  "success": true,
  "message": "Article permanently deleted",
  "data": null
}
```

---

### 12. Get Article Statistics
```http
GET /admin/articles/statistics
```

**Response (JSON):**
```json
{
  "success": true,
  "message": "Statistics retrieved successfully",
  "data": {
    "total_articles": 245,
    "published_articles": 198,
    "draft_articles": 47,
    "featured_articles": 12,
    "pinned_articles": 5,
    "total_views": 125478,
    "average_read_time": 6,
    "recent_articles": 23,
    "by_category": [
      {"id": 5, "name": "Seerah", "count": 78},
      {"id": 8, "name": "History", "count": 56}
    ],
    "by_locale": [
      {"locale": "ar", "name": "Arabic", "count": 198},
      {"locale": "en", "name": "English", "count": 187},
      {"locale": "fr", "name": "French", "count": 45}
    ],
    "top_viewed": [
      {
        "id": 23,
        "views": 5678,
        "locales": {"ar": {"title": "المقال الأكثر مشاهدة"}}
      }
    ],
    "recent": [
      {
        "id": 123,
        "created_at": "2024-11-07T10:00:00Z",
        "locales": {"ar": {"title": "أحدث مقال"}}
      }
    ]
  }
}
```

---

### 13. Toggle Featured Status
```http
POST /admin/articles/{id}/toggle-feature
```

**Response (JSON):**
```json
{
  "success": true,
  "message": "Featured status updated",
  "data": {
    "is_featured": true
  }
}
```

---

### 14. Toggle Pinned Status
```http
POST /admin/articles/{id}/toggle-pin
```

**Response (JSON):**
```json
{
  "success": true,
  "message": "Pinned status updated",
  "data": {
    "is_pinned": true
  }
}
```

---

### 15. Increment Article Views
```http
POST /admin/articles/{id}/increment-views
```

**Response (JSON):**
```json
{
  "success": true,
  "message": "Views incremented",
  "data": {
    "views": 1251
  }
}
```

---

### 16. Get Trashed Articles
```http
GET /admin/articles/trash
```

**Response:** Same structure as List Articles

---

## Features

### Multi-Language Support
- Support for 5 locales: Arabic (ar), English (en), French (fr), Turkish (tr), Urdu (ur)
- Each locale has: title, slug, excerpt, description, content, SEO fields
- Locale-specific readiness flag (is_ready)
- Automatic slug generation if not provided

### Version Control
- Automatic version snapshots on create/update/delete
- Full version history with diff tracking
- Revert to previous versions
- Version comparison
- Track changes by user, IP, and user agent

### Content Management
- Multi-category support (primary + additional)
- Polymorphic tagging system
- Media gallery support (via Spatie Media Library)
- Thumbnail image upload
- Automatic table of contents generation from headings
- Automatic read time calculation (200 words/min)

### Publishing
- Status: draft, published, scheduled, archived
- Scheduled publishing (via queue jobs)
- Featured/Pinned articles
- Soft delete with restore capability
- Force delete (permanent)

### Search & Filtering
- Full-text search across title, excerpt, description, content
- Filter by: status, category, author, locale, date range, featured, pinned
- Sort by: created_at, views, alphabetical
- Pagination support

### Performance
- Repository pattern for data access
- Query optimization with eager loading
- Redis caching for statistics and featured articles
- Cache invalidation on updates

### Security
- Permission-based authorization (view/create/edit/delete articles)
- Form request validation
- CSRF protection
- SQL injection prevention via Eloquent ORM

### Activity Logging
- All CRUD operations logged
- User tracking
- IP address and user agent recording
- Metadata support for additional context

## Database Schema

### Tables
- **articles**: Main article table
- **article_locale**: Multi-language content (JSON content field)
- **article_category**: Many-to-many pivot table
- **articles_media**: Media gallery pivot table
- **taggables**: Polymorphic tag relationships
- **content_versions**: Version snapshots and diffs

### Key Relationships
- Article → Writer (BelongsTo User)
- Article → Category (BelongsTo - Primary)
- Article → Categories (BelongsToMany - Additional)
- Article → ArticleLocales (HasMany)
- Article → Tags (MorphToMany)
- Article → Media (BelongsToMany)
- Article → Versions (MorphMany)
- Article → Gallery (HasMany)

## Error Handling

All endpoints return consistent error responses:

**Validation Error (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "title.ar": ["Arabic title is required"],
    "content.en": ["English content is required"]
  }
}
```

**Not Found (404):**
```json
{
  "success": false,
  "message": "Article not found"
}
```

**Unauthorized (403):**
```json
{
  "success": false,
  "message": "You do not have permission to perform this action."
}
```

**Server Error (500):**
```json
{
  "success": false,
  "message": "Failed to create article: Database connection error"
}
```

## Usage Examples

### Create Article with cURL
```bash
curl -X POST https://example.com/admin/articles \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": {
      "ar": "عنوان المقال",
      "en": "Article Title"
    },
    "content": {
      "ar": "<p>محتوى المقال</p>",
      "en": "<p>Article content</p>"
    },
    "category_id": 5,
    "status": "published",
    "is_featured": true
  }'
```

### Search Articles
```bash
curl -X GET "https://example.com/admin/articles?q=prophet&locale=ar&status=published&per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Bulk Operations
```bash
curl -X POST https://example.com/admin/articles/bulk-publish \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"ids": [1, 2, 3, 4]}'
```

## Notes

- All timestamps are in ISO 8601 format (UTC)
- File uploads use multipart/form-data
- Maximum thumbnail size: 5MB
- Supported image formats: jpeg, jpg, png, webp
- Cache TTL: Statistics (30 min), Featured articles (60 min), Trending (30 min)
- Soft deletes are enabled (deleted articles can be restored)
- Version pruning recommended: Keep last 10 versions per article
