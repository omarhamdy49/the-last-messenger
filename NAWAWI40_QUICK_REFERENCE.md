# Nawawi40 JSON Import - Quick Reference

## Current Situation

### ❌ **INCOMPATIBLE STRUCTURE**

The current `hadith:sync-json` command **CANNOT** import Nawawi40 JSON files because:

1. **Expected:** 1 file = 1 collection with many hadiths
2. **Actual:** 1 file = 1 hadith appearing in 6+ collections

### Current Command Usage

```bash
# This will FAIL with nawawi40 files:
php artisan hadith:sync-json /Volumes/Data/projects/the-last-massenger/prophet-data-generator/hadith/prompts/nawawi40/hadith_40944_output.json

# This will also FAIL:
php artisan hadith:sync-json /Volumes/Data/projects/the-last-massenger/prophet-data-generator/hadith/prompts/nawawi40 --recursive
```

**Error:** JSON structure validation will fail because it expects `metadata.id`, `chapters[]`, and `hadiths[]` arrays.

---

## What the Files Contain

### 📁 File Location
```
/Volumes/Data/projects/the-last-massenger/prophet-data-generator/hadith/prompts/nawawi40/
```

### 📊 Statistics
- **Total Files:** 42
- **File Pattern:** `hadith_{number}_output.json`
- **Size per File:** ~19KB
- **Structure:** 1 hadith per file (Nawawi's 40 Hadith collection)

### 🔍 Each File Contains

**Core Hadith:**
- Arabic text (with tashkeel)
- Arabic text (without tashkeel)
- English translation

**Appears in 6 Collections:**
1. Sahih al-Bukhari
2. Sahih Muslim
3. Sunan Abu Dawud
4. Sunan al-Tirmidhi
5. Sunan al-Nasa'i
6. Sunan Ibn Majah

**Rich Metadata:**
- Complete chain of narrators (sanad) with biographies
- Companion who narrated the hadith (with full biography)
- Themes/topics (6-10 per hadith)
- Keywords (7-10 per hadith)
- Authentication/grading details
- 4-6 scholarly commentaries per hadith
- 3-5 related Quranic verses
- 5-6 practical applications
- Historical context and significance
- Legal rulings (fiqh principles)

---

## Database Mapping Status

### ✅ **Can Be Mapped** (Basic Import Possible)

| Data | JSON Location | Database Table | Status |
|------|---------------|----------------|---------|
| Collections | `collections[].name_*` | `hadith_collections` | ✅ Ready |
| Books | `collections[].book_*` | `hadith_books` | ✅ Ready |
| Chapters | `collections[].chapter_*` | `hadith_chapters` | ✅ Ready |
| Hadith Text | `hadith_text.*` | `hadiths` | ✅ Ready |
| Translations | `hadith_text.ar/en` | `hadith_translations` | ✅ Ready |
| Grading | `grading.status/agreement` | `hadiths` | ✅ Ready |

### 🟡 **Tables Exist But Not Mapped** (Need Transformer Logic)

| Data | JSON Location | Database Table | Action Needed |
|------|---------------|----------------|---------------|
| Narrators | `sanad.narrators[]` | `hadith_narrators` | Create mapping logic |
| Chain | `sanad.narrators[]` | `hadith_isnad` | Create mapping logic |
| Themes | `themes_*[]` | `hadith_themes` | Create mapping logic |
| Keywords | `keywords[]` | `hadith_keywords` | Create mapping logic |
| Gradings | `grading.authenticated_by[]` | `hadith_gradings` | Create mapping logic |
| Commentary | `scholarly_comments[]` | `hadith_commentaries` | Create mapping logic |
| Scholars | Various | `hadith_scholars` | Create mapping logic |

### ❌ **No Tables Yet** (Database Schema Needed)

| Data | JSON Location | Action Needed |
|------|---------------|---------------|
| Companion Info | `companion{}` | Create `companions` table |
| Related Verses | `related_verses[]` | Create `hadith_related_verses` table |
| Applications | `applications[]` | Create `hadith_applications` table |
| Historical Context | `historical_context{}` | Add columns to `hadiths` table |
| Fiqh Rulings | `legal_rulings.*` | Create `hadith_fiqh_rulings` table |
| Significance | `significance_*` | Add columns to `hadiths` table |
| Verification | `verification_status` | Add columns to `hadiths` table |

---

## What Needs to Be Built

### 1️⃣ New Transformer (High Priority)

**File:** `app/Services/Json/Nawawi40DataTransformer.php`

**Purpose:** Transform single-hadith structure to multi-collection database records

**Key Methods:**
```php
public function transformHadithToCollections(array $json): array
{
    // Returns array of collection/book/chapter/hadith combinations
}

public function transformNarrators(array $sanadData): array
{
    // Extract narrator details from sanad
}

public function transformThemes(array $themesEn, array $themesAr): array
{
    // Create theme records
}

public function transformKeywords(array $keywords): array
{
    // Create keyword records
}

public function transformCompanion(array $companionData): array
{
    // Transform companion information
}
```

### 2️⃣ Update Import Service (High Priority)

**File:** `app/Domain/Hadith/Services/JsonImportService.php`

**Changes:**
- Detect JSON structure (old vs nawawi40)
- Route to appropriate transformer
- Handle multi-collection loop per hadith
- Batch insert for multiple hadiths sharing same collection

### 3️⃣ Database Migrations (Medium Priority)

**New Tables:**
```sql
-- Companions (Sahabah)
CREATE TABLE companions (
  id BIGINT PRIMARY KEY,
  name_ar VARCHAR(255),
  name_en VARCHAR(255),
  full_name_ar TEXT,
  title_ar VARCHAR(255),
  title_en VARCHAR(255),
  birth_year_hijri INT,
  death_year_hijri INT,
  conversion_year VARCHAR(100),
  bio_ar TEXT,
  bio_en TEXT,
  virtues TEXT,
  verified BOOLEAN
);

-- Related Quranic Verses
CREATE TABLE hadith_related_verses (
  id BIGINT PRIMARY KEY,
  hadith_id BIGINT,
  surah INT,
  surah_name_ar VARCHAR(255),
  surah_name_en VARCHAR(255),
  ayah INT,
  text_ar TEXT,
  text_en TEXT,
  relevance TEXT
);

-- Practical Applications
CREATE TABLE hadith_applications (
  id BIGINT PRIMARY KEY,
  hadith_id BIGINT,
  category_ar VARCHAR(255),
  category_en VARCHAR(255),
  description_ar TEXT,
  description_en TEXT
);

-- Fiqh Rulings
CREATE TABLE hadith_fiqh_rulings (
  id BIGINT PRIMARY KEY,
  hadith_id BIGINT,
  principle TEXT,
  language_code VARCHAR(10)
);
```

**Extend Existing Tables:**
```sql
ALTER TABLE hadiths ADD COLUMN significance_ar TEXT;
ALTER TABLE hadiths ADD COLUMN significance_en TEXT;
ALTER TABLE hadiths ADD COLUMN historical_context_ar TEXT;
ALTER TABLE hadiths ADD COLUMN historical_context_en TEXT;
ALTER TABLE hadiths ADD COLUMN verification_status VARCHAR(50);
ALTER TABLE hadiths ADD COLUMN uncertainty_notes TEXT;

-- Add companion reference
ALTER TABLE hadiths ADD COLUMN companion_id BIGINT;
ALTER TABLE hadiths ADD FOREIGN KEY (companion_id) REFERENCES companions(id);
```

### 4️⃣ Admin Interface (Low Priority)

**Views to Create:**
- Narrator chain visualization
- Scholarly commentary tabs
- Related verses sidebar
- Practical applications section
- Theme/keyword filtering

---

## Expected Database Records After Import

When all 42 Nawawi40 JSON files are imported:

| Entity | Estimated Count | Notes |
|--------|-----------------|-------|
| Collections | 6 | Bukhari, Muslim, Abu Dawud, Tirmidhi, Nasa'i, Ibn Majah |
| Books | ~150 | Varies by collection |
| Chapters | ~200 | Depends on chapter overlap |
| Hadiths | ~250 | 42 unique × ~6 collections each |
| Translations | ~500 | 2 languages × 250 hadiths |
| Narrators | ~200-300 | Unique narrators across all chains |
| Companions | ~10-20 | Unique companions |
| Themes | ~50-100 | Unique themes |
| Keywords | ~100-200 | Unique keywords |
| Scholarly Comments | ~200-250 | 4-6 per hadith × 42 hadiths |
| Related Verses | ~150-200 | 3-5 per hadith × 42 hadiths |
| Applications | ~200-250 | 5-6 per hadith × 42 hadiths |

---

## Sample Import Workflow (Future)

Once the new transformer is built:

```bash
# 1. Import all nawawi40 hadiths
php artisan hadith:sync-json \
  /Volumes/Data/projects/the-last-massenger/prophet-data-generator/hadith/prompts/nawawi40 \
  --recursive \
  --collection=nawawi40

# 2. Verify import
php artisan hadith:verify nawawi40

# 3. Check statistics
php artisan hadith:stats nawawi40

# Output:
# ✅ Collections: 6
# ✅ Books: 152
# ✅ Chapters: 204
# ✅ Hadiths: 252 (42 unique)
# ✅ Translations: 504
# ✅ Narrators: 287
# ✅ Themes: 94
# ✅ Keywords: 187
# ✅ Scholarly Comments: 246
```

---

## Priority Roadmap

### 🔴 **Phase 1: Make It Work** (Week 1)
1. Create `Nawawi40DataTransformer`
2. Update `JsonImportService` with structure detection
3. Import core data: collections, books, chapters, hadiths, translations
4. Test with 1-2 files, then full import

### 🟡 **Phase 2: Enrich Data** (Week 2-3)
1. Map narrators and isnad chains
2. Map themes and keywords
3. Map scholarly commentary
4. Map grading details

### 🟢 **Phase 3: Full Features** (Week 4+)
1. Create missing tables (companions, verses, applications, fiqh)
2. Extend hadiths table (significance, context, verification)
3. Build admin interface for rich data
4. Create API endpoints for frontend consumption

---

## Testing Strategy

### Test File
```bash
# Use this file for initial testing (Hadith #1 - most famous)
/Volumes/Data/projects/the-last-massenger/prophet-data-generator/hadith/prompts/nawawi40/hadith_40944_output.json
```

### Verification Queries
```sql
-- Check if hadith appears in multiple collections
SELECT c.name_en, b.title_en, h.hadith_number
FROM hadiths h
JOIN hadith_books b ON h.book_id = b.id
JOIN hadith_collections c ON b.collection_id = c.id
WHERE h.id = 1; -- Should return 6 rows (one for each collection)

-- Verify translations
SELECT language_code, LEFT(matn, 50) as preview
FROM hadith_translations
WHERE hadith_id = 1;

-- Check narrator chain
SELECT n.name_en, i.position_in_chain, i.transmission_method
FROM hadith_isnad i
JOIN hadith_narrators n ON i.narrator_id = n.id
WHERE i.hadith_id = 1
ORDER BY i.position_in_chain;
```

---

## Quick Commands Reference

### Current State (API Sync)
```bash
# Sync from Sunnah.com API
php artisan hadith:sync

# Sync specific collection
php artisan hadith:sync --collection=bukhari

# Test connection
php artisan hadith:sync --test-connection

# List available collections
php artisan hadith:sync --list-collections
```

### JSON Import (Doesn't Work Yet)
```bash
# Will fail with nawawi40 files ❌
php artisan hadith:sync-json /path/to/nawawi40/hadith_40944_output.json

# After implementing Nawawi40DataTransformer ✅
php artisan hadith:sync-json /path/to/nawawi40/hadith_40944_output.json --type=nawawi40
```

---

## Developer Notes

### Why Current Import Fails

**Expected Structure:**
```json
{
  "metadata": { ... },
  "chapters": [ ... ],
  "hadiths": [ { "id": 1 }, { "id": 2 } ]
}
```

**Actual Structure:**
```json
{
  "hadith_text": { ... },
  "collections": [
    { "name_en": "Bukhari", "hadith_number": "1" },
    { "name_en": "Muslim", "hadith_number": "1907" }
  ]
}
```

### Key Insight

The Nawawi40 collection is a **curated anthology** of 40 famous hadiths. Each hadith:
- Is already authenticated in the 6 major collections
- Has the same text but different numbering in each collection
- Contains rich scholarly analysis and commentary

This is fundamentally different from the Sunnah.com API format, which organizes hadiths hierarchically within a single collection.

---

**Document Version:** 1.0
**Last Updated:** November 21, 2025
**Status:** Analysis Complete ✅
