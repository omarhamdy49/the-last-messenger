# API Documentation

**The Last Messenger** - Admin Panel API Reference
**Version**: 1.0.0
**Last Updated**: 2025-10-29
**Base URL**: `https://yourdomain.com/su/portal/v1/admin`

---

## 📚 Table of Contents

1. [Authentication](#authentication)
2. [Dashboard](#dashboard)
3. [Articles](#articles)
4. [Hadith](#hadith)
5. [Media Programs](#media-programs)
6. [Quran](#quran)
7. [Sahaba](#sahaba)
8. [Seerah Events](#seerah-events)
9. [Genealogy](#genealogy)
10. [Users](#users)
11. [Roles & Permissions](#roles--permissions)
12. [Activity Logs](#activity-logs)
13. [Media Upload](#media-upload)
14. [Error Responses](#error-responses)

---

## 🔐 Authentication

All admin API endpoints require authentication via Laravel Sanctum session-based authentication.

### Login

```http
POST /su/portal/v1/admin/login
Content-Type: application/x-www-form-urlencoded
```

**Parameters**:
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| email | string | Yes | Admin email address |
| password | string | Yes | Admin password |
| remember | boolean | No | Remember login (default: false) |

**Request**:
```http
POST /su/portal/v1/admin/login
Content-Type: application/x-www-form-urlencoded

email=admin@thelastmessenger.com&password=Admin123!&remember=1
```

**Success Response (302)**:
```http
HTTP/1.1 302 Found
Location: /su/portal/v1/admin/dashboard
Set-Cookie: laravel_session=...
```

**Error Response (302)**:
```http
HTTP/1.1 302 Found
Location: /su/portal/v1/admin/login
```

With session error:
```
بيانات الدخول غير صحيحة. يرجى المحاولة مرة أخرى.
```

---

### Logout

```http
POST /su/portal/v1/admin/logout
```

**Response (302)**:
```http
HTTP/1.1 302 Found
Location: /su/portal/v1/admin/login
```

---

## 📊 Dashboard

### Get Statistics

```http
GET /su/portal/v1/admin/dashboard/statistics
Accept: application/json
```

**Response (200)**:
```json
{
  "success": true,
  "message": "Dashboard statistics retrieved successfully",
  "data": {
    "articles": {
      "total": 152,
      "published": 120,
      "draft": 25,
      "archived": 7
    },
    "hadith": {
      "total": 347,
      "sahih": 300,
      "hasan": 40,
      "daif": 7
    },
    "media_programs": {
      "total": 89,
      "published": 75,
      "draft": 14
    },
    "users": {
      "total": 1247,
      "verified": 1100,
      "unverified": 147
    }
  }
}
```

---

### Get Recent Activity

```http
GET /su/portal/v1/admin/dashboard/activity?limit=10
Accept: application/json
```

**Query Parameters**:
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| limit | integer | 10 | Number of activities to return |

**Response (200)**:
```json
{
  "success": true,
  "message": "Recent activity retrieved successfully",
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "user_name": "Admin User",
      "action": "create",
      "model_type": "Article",
      "model_id": 45,
      "title": "Created article: New Seerah Article",
      "description": "Article created in السيرة النبوية category",
      "time": "2 hours ago",
      "icon": "file-plus",
      "color": "bg-blue-500",
      "created_at": "2025-10-29T10:30:00Z"
    }
  ]
}
```

---

### Get Content Growth

```http
GET /su/portal/v1/admin/dashboard/growth
Accept: application/json
```

**Response (200)**:
```json
{
  "success": true,
  "message": "Content growth data retrieved successfully",
  "data": {
    "months": ["يناير", "فبراير", "مارس", "أبريل", "مايو", "يونيو"],
    "articles": [12, 19, 15, 25, 28, 35],
    "hadith": [8, 12, 18, 22, 25, 30],
    "media_programs": [5, 8, 10, 15, 18, 22]
  }
}
```

---

### Get Popular Content

```http
GET /su/portal/v1/admin/dashboard/popular?limit=10
Accept: application/json
```

**Query Parameters**:
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| limit | integer | 10 | Number of items to return |

**Response (200)**:
```json
{
  "success": true,
  "message": "Popular content retrieved successfully",
  "data": [
    {
      "id": 45,
      "type": "article",
      "title": {"ar": "عنوان المقال", "en": "Article Title"},
      "views": 1250,
      "favorites": 89,
      "url": "/articles/45"
    }
  ]
}
```

---

## 📝 Articles

### List Articles

```http
GET /su/portal/v1/admin/articles?page=1&per_page=15
Accept: application/json
```

**Query Parameters**:
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Page number |
| per_page | integer | 15 | Items per page |
| status | string | null | Filter by status (published, draft, archived) |
| category_id | integer | null | Filter by category |
| search | string | null | Search query |

**Response (200)**:
```json
{
  "data": [
    {
      "id": 1,
      "title": {
        "ar": "عنوان المقال",
        "en": "Article Title",
        "fr": "Titre de l'article",
        "tr": "Makale Başlığı",
        "ur": "عنوان"
      },
      "excerpt": {
        "ar": "مقتطف...",
        "en": "Excerpt..."
      },
      "slug": "article-title",
      "status": "published",
      "category": {
        "id": 1,
        "name": {"ar": "السيرة النبوية", "en": "Seerah"}
      },
      "series": {
        "id": 1,
        "name": {"ar": "سلسلة السيرة", "en": "Seerah Series"}
      },
      "tags": [
        {"id": 1, "name": {"ar": "السيرة", "en": "Biography"}},
        {"id": 2, "name": {"ar": "النبي", "en": "Prophet"}}
      ],
      "author": {
        "id": 1,
        "name": "Admin User"
      },
      "views": 1250,
      "favorites_count": 89,
      "is_featured": true,
      "created_at": "2025-10-29T10:30:00Z",
      "updated_at": "2025-10-29T12:45:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 11,
    "per_page": 15,
    "to": 15,
    "total": 152
  },
  "links": {
    "first": "http://yourdomain.com/su/portal/v1/admin/articles?page=1",
    "last": "http://yourdomain.com/su/portal/v1/admin/articles?page=11",
    "prev": null,
    "next": "http://yourdomain.com/su/portal/v1/admin/articles?page=2"
  }
}
```

---

### Get Single Article

```http
GET /su/portal/v1/admin/articles/{id}
Accept: application/json
```

**Response (200)**:
```json
{
  "success": true,
  "message": "Resource retrieved successfully",
  "data": {
    "id": 1,
    "title": {...},
    "content": {...},
    "excerpt": {...},
    "meta_description": {...},
    "slug": "article-title",
    "status": "published",
    "category": {...},
    "series": {...},
    "tags": [...],
    "author": {...},
    "views": 1250,
    "created_at": "2025-10-29T10:30:00Z"
  }
}
```

---

### Create Article

```http
POST /su/portal/v1/admin/articles
Content-Type: application/json
Accept: application/json
```

**Request Body**:
```json
{
  "title": {
    "ar": "عنوان المقال",
    "en": "Article Title",
    "fr": "Titre de l'article",
    "tr": "Makale Başlığı",
    "ur": "عنوان"
  },
  "content": {
    "ar": "<p>محتوى المقال بالعربية...</p>",
    "en": "<p>Article content in English...</p>",
    "fr": "<p>Contenu en français...</p>",
    "tr": "<p>Türkçe içerik...</p>",
    "ur": "<p>اردو مواد...</p>"
  },
  "excerpt": {
    "ar": "مقتطف قصير",
    "en": "Short excerpt"
  },
  "meta_description": {
    "ar": "وصف تعريفي",
    "en": "Meta description"
  },
  "slug": "article-title",
  "status": "published",
  "category_id": 1,
  "series_id": 1,
  "tags": [1, 2, 3],
  "image_url": "https://example.com/image.jpg",
  "is_featured": true
}
```

**Success Response (201)**:
```json
{
  "success": true,
  "message": "Resource created successfully",
  "data": {
    "id": 153,
    "title": {...},
    "status": "published",
    "created_at": "2025-10-29T14:00:00Z"
  }
}
```

**Validation Error (422)**:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "title.ar": ["The Arabic title field is required."],
    "content.ar": ["The Arabic content field is required."],
    "category_id": ["The selected category is invalid."]
  }
}
```

---

### Update Article

```http
PUT /su/portal/v1/admin/articles/{id}
Content-Type: application/json
Accept: application/json
```

**Request Body** (partial update supported):
```json
{
  "title": {
    "ar": "عنوان محدث"
  },
  "status": "published"
}
```

**Response (200)**:
```json
{
  "success": true,
  "message": "Resource updated successfully",
  "data": {
    "id": 153,
    "title": {...},
    "updated_at": "2025-10-29T15:30:00Z"
  }
}
```

---

### Delete Article

```http
DELETE /su/portal/v1/admin/articles/{id}
Accept: application/json
```

**Response (200)**:
```json
{
  "success": true,
  "message": "Resource deleted successfully",
  "data": null
}
```

---

### Search Articles

```http
GET /su/portal/v1/admin/articles/search?q=seerah&status=published
Accept: application/json
```

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| q | string | Yes | Search query |
| status | string | No | Filter by status |
| category_id | integer | No | Filter by category |

**Response (200)**:
```json
{
  "success": true,
  "message": "Search results retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": {...},
      "excerpt": {...},
      "status": "published"
    }
  ]
}
```

---

### Bulk Delete Articles

```http
POST /su/portal/v1/admin/articles/bulk-delete
Content-Type: application/json
Accept: application/json
```

**Request Body**:
```json
{
  "ids": [1, 2, 3, 4, 5]
}
```

**Response (200)**:
```json
{
  "success": true,
  "message": "5 items deleted successfully",
  "data": {
    "deleted_count": 5
  }
}
```

---

### Bulk Publish Articles

```http
POST /su/portal/v1/admin/articles/bulk-publish
Content-Type: application/json
```

**Request Body**:
```json
{
  "ids": [1, 2, 3]
}
```

**Response (200)**:
```json
{
  "success": true,
  "message": "3 items published successfully",
  "data": {
    "published_count": 3
  }
}
```

---

### Bulk Archive Articles

```http
POST /su/portal/v1/admin/articles/bulk-archive
Content-Type: application/json
```

**Request Body**:
```json
{
  "ids": [1, 2, 3]
}
```

**Response (200)**:
```json
{
  "success": true,
  "message": "3 items archived successfully",
  "data": {
    "archived_count": 3
  }
}
```

---

## 📖 Hadith

### List Hadith

```http
GET /su/portal/v1/admin/hadith?page=1&per_page=15
Accept: application/json
```

**Query Parameters**:
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Page number |
| per_page | integer | 15 | Items per page |
| book_id | integer | null | Filter by book |
| grade | string | null | Filter by grade (sahih, hasan, daif) |

**Response (200)**:
```json
{
  "data": [
    {
      "id": 1,
      "arabic_text": "عن أبي هريرة رضي الله عنه...",
      "english_text": "Narrated Abu Hurairah...",
      "hadith_number": "1",
      "book": {
        "id": 1,
        "name": {"ar": "صحيح البخاري", "en": "Sahih Bukhari"}
      },
      "chapter": {
        "id": 1,
        "name": {"ar": "كتاب الإيمان", "en": "Book of Faith"}
      },
      "grade": "sahih",
      "narrators": [
        {"id": 1, "name": {"ar": "أبو هريرة", "en": "Abu Hurairah"}}
      ],
      "created_at": "2025-10-29T10:30:00Z"
    }
  ],
  "meta": {...}
}
```

---

### Create Hadith

```http
POST /su/portal/v1/admin/hadith
Content-Type: application/json
```

**Request Body**:
```json
{
  "arabic_text": "عن أبي هريرة رضي الله عنه قال...",
  "english_text": "Narrated Abu Hurairah...",
  "explanation": {
    "ar": "شرح الحديث...",
    "en": "Hadith explanation..."
  },
  "hadith_number": "1",
  "book_id": 1,
  "chapter_id": 1,
  "grade": "sahih",
  "narrators": [1, 2, 3]
}
```

---

## 🎥 Media Programs

### List Media Programs

```http
GET /su/portal/v1/admin/media-programs?page=1
Accept: application/json
```

**Response (200)**:
```json
{
  "data": [
    {
      "id": 1,
      "title": {
        "ar": "برنامج السيرة",
        "en": "Biography Program"
      },
      "description": {...},
      "thumbnail_url": "https://example.com/thumb.jpg",
      "banner_url": "https://example.com/banner.jpg",
      "status": "published",
      "category": {
        "id": 1,
        "name": {"ar": "وثائقي", "en": "Documentary"}
      },
      "episodes_count": 30,
      "views": 15420,
      "created_at": "2025-10-29T10:30:00Z"
    }
  ],
  "meta": {...}
}
```

---

### Create Media Program

```http
POST /su/portal/v1/admin/media-programs
Content-Type: application/json
```

**Request Body**:
```json
{
  "title": {
    "ar": "برنامج السيرة النبوية",
    "en": "Prophet Biography Program"
  },
  "description": {
    "ar": "وصف البرنامج...",
    "en": "Program description..."
  },
  "thumbnail_url": "https://example.com/thumb.jpg",
  "banner_url": "https://example.com/banner.jpg",
  "trailer_url": "https://youtube.com/watch?v=...",
  "category_id": 1,
  "status": "published",
  "tags": [1, 2]
}
```

---

## 📿 Quran

### List Verses

```http
GET /su/portal/v1/admin/quran?page=1
Accept: application/json
```

**Response (200)**:
```json
{
  "data": [
    {
      "id": 1,
      "surah_id": 1,
      "verse_number": 1,
      "arabic_text": "بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ",
      "translations": {
        "en": "In the name of Allah, the Entirely Merciful, the Especially Merciful.",
        "fr": "Au nom d'Allah, le Tout Miséricordieux, le Très Miséricordieux.",
        "tr": "Rahman ve Rahim Allah'ın adıyla.",
        "ur": "شروع اللہ کے نام سے جو بڑا مہربان نہایت رحم والا ہے"
      },
      "surah": {
        "id": 1,
        "name": {"ar": "الفاتحة", "en": "Al-Fatihah"},
        "verse_count": 7
      }
    }
  ],
  "meta": {...}
}
```

---

## 👥 Sahaba (Companions)

### List Sahaba

```http
GET /su/portal/v1/admin/sahaba?page=1
Accept: application/json
```

**Response (200)**:
```json
{
  "data": [
    {
      "id": 1,
      "name": {
        "ar": "أبو بكر الصديق",
        "en": "Abu Bakr As-Siddiq"
      },
      "full_name": {...},
      "kunya": {...},
      "laqab": {...},
      "tribe": {...},
      "status": "sahabi",
      "gender": "male",
      "hadith_count": 142,
      "importance_rank": 10,
      "is_featured": true,
      "birth_date_hijri": "51 BH",
      "death_date_hijri": "13 AH",
      "created_at": "2025-10-29T10:30:00Z"
    }
  ],
  "meta": {...}
}
```

---

### Create Sahabi

```http
POST /su/portal/v1/admin/sahaba
Content-Type: application/json
```

**Request Body**:
```json
{
  "name": {
    "ar": "أبو بكر الصديق",
    "en": "Abu Bakr As-Siddiq",
    "fr": "Abou Bakr As-Siddiq",
    "tr": "Ebu Bekir es-Sıddık",
    "ur": "ابو بکر صدیق"
  },
  "full_name": {...},
  "kunya": {...},
  "laqab": {...},
  "biography": {...},
  "virtues": {...},
  "famous_narrations": {...},
  "tribe": {...},
  "clan": {...},
  "status": "sahabi",
  "gender": "male",
  "birth_date_hijri": "51 BH",
  "birth_date_gregorian": "573-01-01",
  "death_date_hijri": "13 AH",
  "death_date_gregorian": "634-08-23",
  "birth_place": {...},
  "death_place": {...},
  "burial_place": {...},
  "conversion_date": "1 BH",
  "conversion_order": 1,
  "hadith_count": 142,
  "importance_rank": 10,
  "is_featured": true
}
```

---

## 📅 Seerah Events

### List Events

```http
GET /su/portal/v1/admin/seerah-events?page=1
Accept: application/json
```

**Response (200)**:
```json
{
  "data": [
    {
      "id": 1,
      "title": {
        "ar": "ولادة النبي ﷺ",
        "en": "Birth of the Prophet ﷺ"
      },
      "description": {...},
      "hijri_date": "12 Rabi' al-Awwal 1 BH",
      "gregorian_date": "570-04-22",
      "location": {...},
      "coordinates": {
        "latitude": 21.4225,
        "longitude": 39.8262
      },
      "category": "birth",
      "importance": 10,
      "participants": [
        {"id": 1, "name": {...}}
      ],
      "created_at": "2025-10-29T10:30:00Z"
    }
  ],
  "meta": {...}
}
```

---

## 🌳 Genealogy

### List People

```http
GET /su/portal/v1/admin/genealogy?page=1
Accept: application/json
```

**Response (200)**:
```json
{
  "data": [
    {
      "id": 1,
      "full_name": {
        "ar": "محمد بن عبد الله",
        "en": "Muhammad ibn Abdullah"
      },
      "kunya": {...},
      "laqab": {...},
      "biography": {...},
      "type": "prophet",
      "birth_date_hijri": "12 Rabi' al-Awwal 53 BH",
      "death_date_hijri": "12 Rabi' al-Awwal 11 AH",
      "father": {
        "id": 2,
        "name": {"ar": "عبد الله", "en": "Abdullah"}
      },
      "mother": {
        "id": 3,
        "name": {"ar": "آمنة", "en": "Aminah"}
      },
      "spouses": [
        {"id": 4, "name": {"ar": "خديجة", "en": "Khadijah"}}
      ],
      "children_count": 7,
      "generation": 1,
      "created_at": "2025-10-29T10:30:00Z"
    }
  ],
  "meta": {...}
}
```

---

## 👤 Users

### List Users

```http
GET /su/portal/v1/admin/users?page=1
Accept: application/json
```

**Response (200)**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "Admin User",
      "email": "admin@thelastmessenger.com",
      "language": "ar",
      "email_verified_at": "2025-10-29T10:00:00Z",
      "roles": [
        {"id": 1, "name": "super_admin"}
      ],
      "permissions": [],
      "created_at": "2025-10-29T10:00:00Z"
    }
  ],
  "meta": {...}
}
```

---

### Create User

```http
POST /su/portal/v1/admin/users
Content-Type: application/json
```

**Request Body**:
```json
{
  "name": "New User",
  "email": "user@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "language": "ar",
  "roles": [2, 3]
}
```

---

## 🔐 Roles & Permissions

### List Roles

```http
GET /su/portal/v1/admin/roles
Accept: application/json
```

**Response (200)**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "super_admin",
      "guard_name": "web",
      "permissions": [
        {"id": 1, "name": "articles.create"},
        {"id": 2, "name": "articles.update"}
      ],
      "users_count": 1,
      "created_at": "2025-10-29T10:00:00Z"
    }
  ]
}
```

---

### List Permissions

```http
GET /su/portal/v1/admin/permissions
Accept: application/json
```

**Response (200)**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "articles.create",
      "guard_name": "web",
      "created_at": "2025-10-29T10:00:00Z"
    }
  ]
}
```

---

## 📋 Activity Logs

### List Activity Logs

```http
GET /su/portal/v1/admin/activity-logs?page=1
Accept: application/json
```

**Query Parameters**:
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Page number |
| user_id | integer | null | Filter by user |
| action | string | null | Filter by action (create, update, delete) |
| model_type | string | null | Filter by model |

**Response (200)**:
```json
{
  "data": [
    {
      "id": 1,
      "user": {
        "id": 1,
        "name": "Admin User"
      },
      "action": "create",
      "model_type": "Article",
      "model_id": 45,
      "old_values": null,
      "new_values": {
        "title": {"ar": "عنوان جديد"},
        "status": "published"
      },
      "ip_address": "192.168.1.1",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2025-10-29T10:30:00Z"
    }
  ],
  "meta": {...}
}
```

---

### Get Single Activity Log

```http
GET /su/portal/v1/admin/activity-logs/{id}
Accept: application/json
```

**Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user": {...},
    "action": "update",
    "model_type": "Article",
    "model_id": 45,
    "old_values": {
      "title": {"ar": "العنوان القديم"},
      "status": "draft"
    },
    "new_values": {
      "title": {"ar": "العنوان الجديد"},
      "status": "published"
    },
    "ip_address": "192.168.1.1",
    "user_agent": "Mozilla/5.0...",
    "created_at": "2025-10-29T10:30:00Z"
  }
}
```

---

### Get User Activity

```http
GET /su/portal/v1/admin/activity-logs/user/{userId}
Accept: application/json
```

**Response (200)**:
```json
{
  "success": true,
  "data": [...]
}
```

---

### Get Model History

```http
GET /su/portal/v1/admin/activity-logs/model/{type}/{id}
Accept: application/json
```

**Example**:
```http
GET /su/portal/v1/admin/activity-logs/model/Article/45
```

---

## 📁 Media Upload

### Upload File

```http
POST /su/portal/v1/admin/media/upload
Content-Type: multipart/form-data
```

**Request Body**:
```
file: (binary)
model_type: article
model_id: 45
collection: featured_image
```

**Response (201)**:
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "id": 1,
    "file_name": "image.jpg",
    "mime_type": "image/jpeg",
    "size": 245760,
    "human_readable_size": "240 KB",
    "url": "https://yourdomain.com/storage/media/1/image.jpg",
    "conversions": {
      "thumb": "https://yourdomain.com/storage/media/1/conversions/image-thumb.jpg",
      "medium": "https://yourdomain.com/storage/media/1/conversions/image-medium.jpg",
      "large": "https://yourdomain.com/storage/media/1/conversions/image-large.jpg"
    }
  }
}
```

---

### List Media

```http
GET /su/portal/v1/admin/media/list?model_type=article&model_id=45
Accept: application/json
```

**Response (200)**:
```json
{
  "success": true,
  "data": {
    "media": [
      {
        "id": 1,
        "collection_name": "featured_image",
        "file_name": "image.jpg",
        "mime_type": "image/jpeg",
        "size": 245760,
        "url": "...",
        "conversions": {...},
        "order": 1,
        "created_at": "2025-10-29T10:30:00Z"
      }
    ],
    "count": 1
  }
}
```

---

### Delete Media

```http
DELETE /su/portal/v1/admin/media/{id}
Accept: application/json
```

**Response (200)**:
```json
{
  "success": true,
  "message": "Media deleted successfully",
  "data": {
    "id": 1
  }
}
```

---

### Reorder Media

```http
POST /su/portal/v1/admin/media/reorder
Content-Type: application/json
```

**Request Body**:
```json
{
  "media_ids": [3, 1, 2]
}
```

**Response (200)**:
```json
{
  "success": true,
  "message": "Media reordered successfully",
  "data": {
    "reordered_count": 3
  }
}
```

---

## ❌ Error Responses

### Validation Error (422)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "title.ar": [
      "The Arabic title field is required."
    ],
    "email": [
      "The email has already been taken."
    ],
    "category_id": [
      "The selected category is invalid."
    ]
  }
}
```

---

### Not Found (404)

```json
{
  "success": false,
  "message": "Resource not found"
}
```

---

### Unauthorized (401)

```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

---

### Forbidden (403)

```json
{
  "success": false,
  "message": "Unauthorized access"
}
```

---

### Server Error (500)

```json
{
  "success": false,
  "message": "Internal server error"
}
```

---

## 🔑 HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Request successful |
| 201 | Created - Resource created |
| 204 | No Content - Successful, no response body |
| 302 | Found - Redirect |
| 400 | Bad Request - Invalid request |
| 401 | Unauthorized - Not authenticated |
| 403 | Forbidden - Not authorized |
| 404 | Not Found - Resource doesn't exist |
| 422 | Unprocessable Entity - Validation failed |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error - Server error |

---

## 📊 Rate Limiting

All admin API endpoints are rate-limited to **60 requests per minute** per IP address.

**Rate Limit Headers**:
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1698499260
```

**Rate Limit Exceeded (429)**:
```json
{
  "success": false,
  "message": "Too many requests. Please try again later."
}
```

---

## 🔐 CORS Configuration

The API supports Cross-Origin Resource Sharing (CORS) with the following configuration:

- **Allowed Origins**: Your frontend domain
- **Allowed Methods**: GET, POST, PUT, DELETE, OPTIONS
- **Allowed Headers**: Content-Type, Authorization, X-Requested-With
- **Credentials**: Supported

---

## 📝 Request Headers

**Required Headers**:
```http
Accept: application/json
Content-Type: application/json
X-Requested-With: XMLHttpRequest
```

**For Session Auth**:
```http
X-CSRF-TOKEN: {csrf_token}
```

---

## 🎉 Conclusion

This API documentation covers all available admin panel endpoints. For more information:

- 📧 Email: api@thelastmessenger.com
- 📖 Full Documentation: https://docs.thelastmessenger.com
- 🐛 Report Issues: https://github.com/your-org/the-last-messenger-be/issues

---

**Document Version**: 1.0.0
**Last Updated**: 2025-10-29
**Maintained By**: The Last Messenger API Team
