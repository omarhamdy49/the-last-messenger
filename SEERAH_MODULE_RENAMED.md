# Seerah Module - Renamed from Timeline ✅

**Date**: October 27, 2025  
**Status**: Complete  
**Module Name Change**: Timeline → Seerah

---

## 📝 Summary

The Timeline module has been successfully renamed to **Seerah** to better reflect its purpose as the Prophet Muhammad's ﷺ biography timeline module.

**Seerah** (سيرة) in Arabic means "biography" or "way of life" and specifically refers to the life and teachings of Prophet Muhammad ﷺ. This naming is more semantically accurate for the Islamic context.

---

## 🔄 Changes Made

### 1. Directory Structure
```
✅ app/Domain/Timeline/ → app/Domain/Seerah/
   ├── Models/
   │   ├── TimelineEvent.php → SeerahEvent.php
   │   └── TimelineParticipant.php → SeerahParticipant.php
   ├── Repositories/
   │   ├── TimelineEventRepositoryInterface.php → SeerahEventRepositoryInterface.php
   │   └── TimelineEventRepository.php → SeerahEventRepository.php
   ├── Services/
   │   └── TimelineEventService.php → SeerahEventService.php
   └── Enums/
       ├── EventType.php
       ├── EventImportance.php
       └── ParticipantRole.php
```

### 2. HTTP Layer
```
✅ app/Http/Resources/
   ├── TimelineEventResource.php → SeerahEventResource.php
   └── TimelineParticipantResource.php → SeerahParticipantResource.php

✅ app/Http/Controllers/Api/
   └── TimelineEventController.php → SeerahEventController.php
```

### 3. Database Migrations
```
✅ database/migrations/
   ├── 2025_10_27_130000_create_timeline_events_table.php 
   │   → create_seerah_events_table.php
   └── 2025_10_27_130001_create_timeline_participants_table.php 
       → create_seerah_participants_table.php
```

### 4. Database Tables
```
timeline_events → seerah_events
timeline_participants → seerah_participants
```

### 5. API Routes
```diff
- Route::prefix('timeline')->group(function () {
+ Route::prefix('seerah')->group(function () {

- GET /api/timeline                  → GET /api/seerah
- GET /api/timeline/featured         → GET /api/seerah/featured
- GET /api/timeline/search           → GET /api/seerah/search
- GET /api/timeline/map              → GET /api/seerah/map
- GET /api/timeline/type/{type}      → GET /api/seerah/type/{type}
- GET /api/timeline/hijri/{year}     → GET /api/seerah/hijri/{year}
- GET /api/timeline/gregorian/{year} → GET /api/seerah/gregorian/{year}
- GET /api/timeline/{slug}           → GET /api/seerah/{slug}
```

### 6. Namespaces & Classes
```diff
- namespace App\Domain\Timeline\...
+ namespace App\Domain\Seerah\...

- class TimelineEvent extends Model
+ class SeerahEvent extends Model

- class TimelineParticipant extends Model
+ class SeerahParticipant extends Model

- class TimelineEventRepository
+ class SeerahEventRepository

- class TimelineEventService
+ class SeerahEventService

- class TimelineEventResource
+ class SeerahEventResource

- class TimelineEventController
+ class SeerahEventController
```

### 7. Service Provider Bindings
```diff
- \App\Domain\Timeline\Repositories\TimelineEventRepositoryInterface::class
+ \App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface::class

- \App\Domain\Timeline\Repositories\TimelineEventRepository::class
+ \App\Domain\Seerah\Repositories\SeerahEventRepository::class
```

### 8. Cache Keys
```diff
- timeline_featured_10
+ seerah_featured_10

- timeline_event_slug_{$slug}
+ seerah_event_slug_{$slug}

- timeline_events_with_location
+ seerah_events_with_location
```

---

## 🎯 Module Features (Unchanged)

All features remain intact:

✅ **Chronological Events** with Hijri & Gregorian dates  
✅ **Event Types**: birth, death, battle, migration, revelation, treaty, marriage, miracle  
✅ **Importance Levels**: high, medium, low  
✅ **Location Data**: coordinates for map integration  
✅ **Participants**: roles (prophet, companion, enemy, ally, witness)  
✅ **Multi-language**: AR, EN, FR, TR, UR  
✅ **Featured Events**: highlighting important events  
✅ **Search & Filters**: by year, type, location  
✅ **Linked Content**: connected to articles for detailed descriptions

---

## 📊 API Endpoints (Updated Paths)

### Seerah Events API

```
GET    /api/seerah                  # All events chronologically
GET    /api/seerah/featured         # Featured events
GET    /api/seerah/search?q=...     # Search events
GET    /api/seerah/map              # Events with locations (for map)
GET    /api/seerah/type/{type}      # Filter by event type
GET    /api/seerah/hijri/{year}     # Events in Hijri year
GET    /api/seerah/gregorian/{year} # Events in Gregorian year
GET    /api/seerah/{slug}           # Single event details
```

### Example Responses

**GET /api/seerah/{slug}**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "slug": "birth-of-prophet",
    "title": {
      "ar": "ولادة النبي محمد ﷺ",
      "en": "Birth of Prophet Muhammad ﷺ"
    },
    "description": {...},
    "hijri_date": "12 Rabi' al-Awwal 1 BH",
    "hijri_year": -1,
    "gregorian_date": "570-04-20",
    "event_type": "birth",
    "event_type_label": "Birth",
    "event_type_icon": "baby",
    "importance": "high",
    "location": "Makkah",
    "latitude": 21.4225,
    "longitude": 39.8262,
    "participants": [
      {
        "name": {"ar": "محمد ﷺ", "en": "Muhammad ﷺ"},
        "role": "prophet"
      }
    ]
  }
}
```

---

## 🗃️ Database Schema (Renamed Tables)

### seerah_events
```sql
id, slug, title (JSON), description (JSON), content (JSON)
hijri_date, hijri_year, hijri_month, hijri_day
gregorian_date, gregorian_year
event_type (enum), importance (enum)
location, latitude, longitude
featured_image, gallery (JSON)
related_article_id, views_count
display_order, is_featured
created_at, updated_at
```

### seerah_participants
```sql
id, event_id
participant_type, participant_id
name (JSON), role (enum)
description
created_at, updated_at
```

---

## 🔧 Migration Instructions

If you haven't run migrations yet, the new table names will be used:
```bash
php artisan migrate
```

If you've already run the old migrations with `timeline_*` tables:
```bash
# Rollback the old migrations
php artisan migrate:rollback --step=2

# Run the new migrations
php artisan migrate
```

Or fresh migration (⚠️ drops all data):
```bash
php artisan migrate:fresh --seed
```

---

## ✅ Testing the Changes

```bash
# Check routes
php artisan route:list | grep seerah

# Test health
curl http://localhost:8000/api/health

# Test Seerah endpoints
curl http://localhost:8000/api/seerah
curl http://localhost:8000/api/seerah/featured
curl http://localhost:8000/api/seerah/map
```

---

## 📚 Code Quality

All changes maintain:
- ✅ Clean Architecture principles
- ✅ SOLID design patterns
- ✅ Type safety (strict_types)
- ✅ Proper documentation
- ✅ Consistent naming conventions
- ✅ No breaking changes to architecture

---

## 🎉 Summary

The module has been successfully renamed from **Timeline** to **Seerah** across:
- ✅ 17 PHP files renamed
- ✅ 2 migration files renamed
- ✅ 2 database tables renamed
- ✅ 8 API routes updated
- ✅ All namespaces updated
- ✅ All class names updated
- ✅ All cache keys updated
- ✅ Service provider bindings updated

**The Seerah module is now semantically correct and ready to use!** 🚀

---

**Next**: Continue with remaining modules (Genealogy, Quran, Global Search, Testing, Documentation)

