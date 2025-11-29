# Nawawi40 JSON Mapping Analysis

## Overview

This document analyzes the structure of the Nawawi40 JSON files and compares them with the current Hadith sync implementation to identify mapping gaps and missing functionality.

**Location:** `/Volumes/Data/projects/the-last-massenger/prophet-data-generator/hadith/prompts/nawawi40/`
**Total Files:** 42 JSON files (each ~19KB)
**Naming Pattern:** `hadith_{number}_output.json` (e.g., `hadith_40944_output.json`)

---

## Current Sync Command Structure

### Expected JSON Format (JsonDataTransformer)

The current `JsonDataTransformer` expects this structure:

```json
{
  "metadata": {
    "id": 1,
    "arabic": {
      "title": "صحيح البخاري",
      "author": "البخاري"
    },
    "english": {
      "title": "Sahih al-Bukhari",
      "author": "Imam al-Bukhari"
    }
  },
  "chapters": [
    {
      "id": 1,
      "arabic": "باب كيف كان بدء الوحي",
      "english": "How the Divine Inspiration started"
    }
  ],
  "hadiths": [
    {
      "id": 1,
      "idInBook": 1,
      "chapterId": 1,
      "arabic": "إنما الأعمال بالنيات...",
      "english": {
        "narrator": "Narrated by...",
        "text": "Actions are judged by intentions..."
      }
    }
  ]
}
```

**Key Assumptions:**
- Single collection per file
- Single book per file
- Hadiths belong to one collection/book
- Simple text-only translations
- No narrator chains (sanad)
- No grading information
- No themes or keywords

---

## Actual Nawawi40 JSON Structure

### Real JSON Format

```json
{
  "hadith_text": {
    "ar": "إنَّمَا الْأَعْمَالُ بِالنِّيَّاتِ...",
    "ar_notashkeel": "انما الاعمال بالنيات...",
    "en": "Actions are (judged) by motives (niyyah)..."
  },
  "verification_status": "verified",
  "uncertainty_notes": null,

  "collections": [
    {
      "name_en": "Sahih al-Bukhari",
      "name_ar": "صحيح البخاري",
      "hadith_number": "1",
      "book_number": 1,
      "book_name_en": "Revelation",
      "book_name_ar": "كتاب بدء الوحي",
      "chapter_number": 1,
      "chapter_name_en": "How the Divine Inspiration started",
      "chapter_name_ar": "باب كيف كان بدء الوحي",
      "verified": true
    },
    {
      "name_en": "Sahih Muslim",
      "name_ar": "صحيح مسلم",
      "hadith_number": "1907",
      "book_number": 33,
      "book_name_en": "The Book of Leadership",
      "book_name_ar": "كتاب الإمارة",
      "chapter_number": 45,
      "chapter_name_en": "The reward of one whose intention is sincere",
      "chapter_name_ar": "باب قوله صلى الله عليه وسلم إنما الأعمال بالنية",
      "verified": true
    }
    // ... 4 more collections (Abu Dawud, Tirmidhi, Nasa'i, Ibn Majah)
  ],

  "sanad": {
    "completeness": "complete",
    "notes": "Full authenticated chain from Imam al-Bukhari's narration",
    "narrators": [
      {
        "narrator_en": "Al-Humaydi Abdullah ibn al-Zubayr",
        "name_ar": "الحميدي عبد الله بن الزبير",
        "full_name_ar": "أبو بكر عبد الله بن الزبير...",
        "order": 1,
        "narrated_from": "حدثنا",
        "narrated_from_en": "haddathana (narrated to us)",
        "death_year_hijri": 219,
        "reliability": "ثقة حافظ فقيه",
        "reliability_en": "Trustworthy, Hadith master, jurist",
        "bio_ar": "إمام ثقة حافظ فقيه...",
        "bio_en": "Trustworthy Imam, Hadith master...",
        "verified": true
      }
      // ... more narrators
    ]
  },

  "companion": {
    "name_en": "Umar ibn al-Khattab",
    "name_ar": "عمر بن الخطاب",
    "full_name_ar": "أبو حفص عمر بن الخطاب...",
    "title_en": "Amir al-Mu'minin, Al-Faruq",
    "title_ar": "أمير المؤمنين، الفاروق",
    "birth_year_hijri": -40,
    "death_year_hijri": 23,
    "conversion_year": "Year 6 before Hijrah",
    "bio_en": "The second Rightly Guided Caliph...",
    "bio_ar": "الخليفة الراشد الثاني...",
    "virtues": "One of the ten promised Paradise...",
    "verified": true
  },

  "themes_en": [
    "Intentions (Niyyah)",
    "Sincerity (Ikhlas)",
    "Migration (Hijrah)"
  ],
  "themes_ar": [
    "النيات",
    "الإخلاص",
    "الهجرة"
  ],

  "keywords": [
    "النيات",
    "الأعمال",
    "الهجرة"
  ],

  "grading": {
    "status": "sahih",
    "agreement": "muttafaq 'alayh",
    "authenticated_by": [
      "Imam al-Bukhari",
      "Imam Muslim",
      "Imam Abu Dawud"
    ],
    "albani_grade": "sahih",
    "notes_en": "Agreed upon (Muttafaq 'alayh)...",
    "notes_ar": "متفق عليه من طريق البخاري ومسلم...",
    "verified": true
  },

  "significance_en": "This is one of the most foundational hadiths...",
  "significance_ar": "هذا من أهم الأحاديث وأعظمها...",

  "related_verses": [
    {
      "surah": 98,
      "surah_name_en": "Al-Bayyinah",
      "surah_name_ar": "البينة",
      "ayah": 5,
      "text_ar": "وَمَا أُمِرُوا إِلَّا لِيَعْبُدُوا اللَّهَ...",
      "text_en": "And they were not commanded except to worship Allah...",
      "relevance": "Emphasizes sincerity (ikhlas)..."
    }
  ],

  "scholarly_comments": [
    {
      "scholar_en": "Imam al-Bukhari",
      "scholar_ar": "الإمام البخاري",
      "comment_en": "He chose this hadith as the first...",
      "comment_ar": "اختار هذا الحديث ليكون أول حديث...",
      "source": "Fath al-Bari, Introduction to Sahih al-Bukhari",
      "verified": true
    }
  ],

  "applications": [
    {
      "category_en": "Worship and Rituals",
      "category_ar": "العبادات والشعائر",
      "description_en": "All acts of worship require sincere intention...",
      "description_ar": "جميع العبادات تحتاج إلى إخلاص النية..."
    }
  ],

  "historical_context": {
    "occasion_en": "The hadith refers to the story of a man...",
    "occasion_ar": "يشير الحديث إلى قصة رجل...",
    "lesson_en": "This shows that even the great act of Hijrah...",
    "lesson_ar": "هذا يدل على أن حتى العمل العظيم..."
  },

  "legal_rulings": {
    "fiqh_principles_en": [
      "Intention is a condition for the validity of worship",
      "Intention must be present at the beginning of the act"
    ],
    "fiqh_principles_ar": [
      "النية شرط لصحة العبادة",
      "يجب أن تكون النية حاضرة عند بداية العمل"
    ]
  }
}
```

**Key Characteristics:**
- **SINGLE HADITH per file** (not a collection)
- **MULTIPLE COLLECTIONS** per hadith (cross-referenced across 6 major collections)
- Rich metadata: sanad, companion, themes, keywords, grading, scholarly commentary
- Quranic verses related to the hadith
- Practical applications and legal rulings
- Historical context
- Significantly different structure than expected

---

## Database Tables vs JSON Data

### ✅ **MAPPED TABLES** (Currently Working)

#### 1. `hadith_collections`
```
JSON Source: collections[].name_en, collections[].name_ar
Status: ✅ CAN BE MAPPED
```

**Mapping:**
```php
foreach ($json['collections'] as $collection) {
    [
        'name_ar' => $collection['name_ar'],
        'name_en' => $collection['name_en'],
        'compiler' => null, // NOT in JSON
        'death_year_hijri' => null, // NOT in JSON
    ]
}
```

**⚠️ Missing Fields:**
- `compiler` - Not present in JSON
- `death_year_hijri` - Not present in JSON

---

#### 2. `hadith_books`
```
JSON Source: collections[].book_name_en, collections[].book_name_ar, collections[].book_number
Status: ✅ CAN BE MAPPED
```

**Mapping:**
```php
foreach ($json['collections'] as $collection) {
    [
        'collection_id' => $collectionId, // From parent
        'book_number' => $collection['book_number'],
        'title_ar' => $collection['book_name_ar'],
        'title_en' => $collection['book_name_en'],
        'chapter_title_ar' => null, // Different in JSON (chapter level)
        'chapter_title_en' => null, // Different in JSON (chapter level)
    ]
}
```

**✅ All Required Fields Available**

---

#### 3. `hadith_chapters`
```
JSON Source: collections[].chapter_name_en, collections[].chapter_name_ar, collections[].chapter_number
Status: ✅ CAN BE MAPPED
```

**Mapping:**
```php
foreach ($json['collections'] as $collection) {
    [
        'book_id' => $bookId, // From parent
        'chapter_number' => $collection['chapter_number'],
        'external_chapter_id' => null, // NOT in JSON
        'name' => json_encode([
            'ar' => $collection['chapter_name_ar'],
            'en' => $collection['chapter_name_en'],
        ]),
        'description' => null, // NOT in JSON
        'display_order' => $collection['chapter_number'],
    ]
}
```

**⚠️ Missing Fields:**
- `external_chapter_id` - Not present
- `description` - Not present

---

#### 4. `hadiths`
```
JSON Source: hadith_text, collections[].hadith_number, grading.status, grading.agreement
Status: ✅ CAN BE MAPPED
```

**Mapping:**
```php
[
    'book_id' => $bookId, // From parent
    'hadith_number' => $collection['hadith_number'],
    'grading_status' => $json['grading']['status'], // ✅ AVAILABLE
    'grading_agreement' => $json['grading']['agreement'], // ✅ AVAILABLE
]
```

**✅ All Required Fields Available**

---

#### 5. `hadith_translations`
```
JSON Source: hadith_text.ar, hadith_text.en
Status: ✅ CAN BE MAPPED
```

**Mapping:**
```php
// Arabic translation
[
    'hadith_id' => $hadithId,
    'language_code' => 'ar',
    'matn' => $json['hadith_text']['ar'],
    'translator_name' => null, // NOT in JSON
    'notes' => null,
]

// English translation
[
    'hadith_id' => $hadithId,
    'language_code' => 'en',
    'matn' => $json['hadith_text']['en'],
    'translator_name' => null, // NOT in JSON
    'notes' => null,
]
```

**✅ Core Fields Available**
**⚠️ Missing:** `translator_name`

---

### ❌ **UNMAPPED TABLES** (Defined But Not Used)

#### 6. `hadith_narrators` - NOT MAPPED ❌
```
JSON Source: sanad.narrators[]
Status: ❌ NOT CURRENTLY MAPPED
Available Data: ✅ RICH DATA AVAILABLE
```

**Available Fields from JSON:**
- `narrator_en` ✅
- `name_ar` ✅
- `full_name_ar` ✅
- `death_year_hijri` ✅
- `reliability` / `reliability_en` ✅
- `bio_ar` / `bio_en` ✅
- `verified` ✅

**Table Schema:**
```sql
CREATE TABLE hadith_narrators (
  id BIGINT PRIMARY KEY,
  name_ar VARCHAR(255),
  name_en VARCHAR(255),
  full_name_ar TEXT,
  full_name_en TEXT,
  birth_year_hijri INT,
  death_year_hijri INT,
  reliability_grade VARCHAR(100),
  biography TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

**💡 ACTION NEEDED:** Create mapping logic for narrator data

---

#### 7. `hadith_isnad` - NOT MAPPED ❌
```
JSON Source: sanad.narrators[]
Status: ❌ NOT CURRENTLY MAPPED
Available Data: ✅ AVAILABLE
```

**Available Fields from JSON:**
- `order` ✅ (narrator order in chain)
- `narrated_from` / `narrated_from_en` ✅ (transmission method)

**Table Schema:**
```sql
CREATE TABLE hadith_isnad (
  id BIGINT PRIMARY KEY,
  hadith_id BIGINT,
  narrator_id BIGINT,
  position_in_chain INT,
  transmission_method VARCHAR(50),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

**💡 ACTION NEEDED:** Create mapping for chain of narration

---

#### 8. `hadith_themes` - NOT MAPPED ❌
```
JSON Source: themes_en[], themes_ar[]
Status: ❌ NOT CURRENTLY MAPPED
Available Data: ✅ RICH DATA AVAILABLE
```

**Available Fields from JSON:**
- `themes_en` array ✅
- `themes_ar` array ✅

**Table Schema:**
```sql
CREATE TABLE hadith_themes (
  id BIGINT PRIMARY KEY,
  name_ar VARCHAR(255),
  name_en VARCHAR(255),
  description TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);

-- Pivot table
CREATE TABLE hadith_hadith_themes (
  hadith_id BIGINT,
  theme_id BIGINT,
  PRIMARY KEY (hadith_id, theme_id)
);
```

**💡 ACTION NEEDED:** Create mapping for themes

---

#### 9. `hadith_keywords` - NOT MAPPED ❌
```
JSON Source: keywords[]
Status: ❌ NOT CURRENTLY MAPPED
Available Data: ✅ AVAILABLE
```

**Available Fields from JSON:**
- `keywords` array ✅ (Arabic keywords)

**Table Schema:**
```sql
CREATE TABLE hadith_keywords (
  id BIGINT PRIMARY KEY,
  keyword VARCHAR(255),
  language_code VARCHAR(10),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

**💡 ACTION NEEDED:** Create mapping for keywords

---

#### 10. `hadith_gradings` - NOT MAPPED ❌
```
JSON Source: grading.authenticated_by[]
Status: ❌ NOT CURRENTLY MAPPED
Available Data: ✅ RICH DATA AVAILABLE
```

**Available Fields from JSON:**
- `authenticated_by` array ✅ (list of scholars who authenticated)
- `albani_grade` ✅ (specific grading by Shaykh al-Albani)
- `notes_en` / `notes_ar` ✅ (grading notes)

**Table Schema:**
```sql
CREATE TABLE hadith_gradings (
  id BIGINT PRIMARY KEY,
  hadith_id BIGINT,
  scholar_id BIGINT,
  grading_level_id BIGINT,
  notes TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

**💡 ACTION NEEDED:** Parse scholar names and create gradings

---

#### 11. `hadith_commentaries` - NOT MAPPED ❌
```
JSON Source: scholarly_comments[]
Status: ❌ NOT CURRENTLY MAPPED
Available Data: ✅ EXTENSIVE DATA AVAILABLE
```

**Available Fields from JSON:**
- `scholar_en` / `scholar_ar` ✅
- `comment_en` / `comment_ar` ✅
- `source` ✅ (reference book)
- `verified` ✅

**Table Schema:**
```sql
CREATE TABLE hadith_commentaries (
  id BIGINT PRIMARY KEY,
  hadith_id BIGINT,
  scholar_id BIGINT,
  commentary TEXT,
  language_code VARCHAR(10),
  source_reference VARCHAR(500),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

**💡 ACTION NEEDED:** Create mapping for scholarly commentary

---

#### 12. `hadith_scholars` - NOT MAPPED ❌
```
JSON Source: grading.authenticated_by[], scholarly_comments[].scholar_*
Status: ❌ NOT CURRENTLY MAPPED
Available Data: ✅ PARTIAL DATA AVAILABLE
```

**Available Fields from JSON:**
- Scholar names from `authenticated_by` ✅
- Scholar names from `scholarly_comments` ✅

**Table Schema:**
```sql
CREATE TABLE hadith_scholars (
  id BIGINT PRIMARY KEY,
  name_ar VARCHAR(255),
  name_en VARCHAR(255),
  full_name_ar TEXT,
  full_name_en TEXT,
  birth_year_hijri INT,
  death_year_hijri INT,
  bio TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

**⚠️ Limited Data:** Only names available, not full bio

**💡 ACTION NEEDED:** Create scholar records from names

---

#### 13. `hadith_sources` - NOT MAPPED ❌
```
JSON Source: scholarly_comments[].source, collections[]
Status: ❌ NOT CURRENTLY MAPPED
Available Data: ✅ PARTIAL DATA AVAILABLE
```

**Available Fields from JSON:**
- Source books from `scholarly_comments[].source` ✅
- Collection names ✅

**Table Schema:**
```sql
CREATE TABLE hadith_sources (
  id BIGINT PRIMARY KEY,
  title VARCHAR(255),
  author VARCHAR(255),
  publication_year INT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

**💡 ACTION NEEDED:** Extract source references

---

### 📊 **NEW DATA IN JSON (No Tables Yet)**

#### 14. Companion Information - NO TABLE ❌
```
JSON Source: companion{}
Status: ❌ NO TABLE EXISTS
Available Data: ✅ EXTREMELY RICH DATA
```

**Available Fields:**
- `name_en` / `name_ar` ✅
- `full_name_ar` ✅
- `title_en` / `title_ar` ✅ (e.g., "Al-Faruq")
- `birth_year_hijri` / `death_year_hijri` ✅
- `conversion_year` ✅
- `bio_en` / `bio_ar` ✅ (detailed biography)
- `virtues` ✅
- `verified` ✅

**💡 ACTION NEEDED:** Create `companions` table or link to existing Sahabah module

---

#### 15. Related Quranic Verses - NO TABLE ❌
```
JSON Source: related_verses[]
Status: ❌ NO TABLE EXISTS
Available Data: ✅ RICH DATA
```

**Available Fields:**
- `surah` (number) ✅
- `surah_name_en` / `surah_name_ar` ✅
- `ayah` (verse number) ✅
- `text_ar` / `text_en` ✅
- `relevance` (explanation) ✅

**💡 ACTION NEEDED:** Create `hadith_related_verses` table

---

#### 16. Practical Applications - NO TABLE ❌
```
JSON Source: applications[]
Status: ❌ NO TABLE EXISTS
Available Data: ✅ RICH DATA
```

**Available Fields:**
- `category_en` / `category_ar` ✅
- `description_en` / `description_ar` ✅

**💡 ACTION NEEDED:** Create `hadith_applications` table

---

#### 17. Historical Context - NO TABLE ❌
```
JSON Source: historical_context{}
Status: ❌ NO TABLE EXISTS
Available Data: ✅ AVAILABLE
```

**Available Fields:**
- `occasion_en` / `occasion_ar` ✅
- `lesson_en` / `lesson_ar` ✅

**💡 ACTION NEEDED:** Add columns to `hadiths` table or create `hadith_context` table

---

#### 18. Legal Rulings (Fiqh) - NO TABLE ❌
```
JSON Source: legal_rulings.fiqh_principles_en[], legal_rulings.fiqh_principles_ar[]
Status: ❌ NO TABLE EXISTS
Available Data: ✅ AVAILABLE
```

**Available Fields:**
- `fiqh_principles_en` array ✅
- `fiqh_principles_ar` array ✅

**💡 ACTION NEEDED:** Create `hadith_fiqh_rulings` table

---

#### 19. Significance/Importance - NO TABLE ❌
```
JSON Source: significance_en, significance_ar
Status: ❌ NO TABLE EXISTS
Available Data: ✅ EXTENSIVE TEXT
```

**Available Fields:**
- `significance_en` ✅ (detailed explanation)
- `significance_ar` ✅

**💡 ACTION NEEDED:** Add columns to `hadiths` table

---

#### 20. Verification Status - NO TABLE ❌
```
JSON Source: verification_status, uncertainty_notes
Status: ❌ NO TABLE EXISTS
Available Data: ✅ AVAILABLE
```

**Available Fields:**
- `verification_status` ✅ (e.g., "verified")
- `uncertainty_notes` ✅

**💡 ACTION NEEDED:** Add columns to `hadiths` table

---

## Summary: What's Missing

### 🔴 **CRITICAL GAPS**

1. **Structure Mismatch:**
   - Current transformer expects: 1 file = 1 collection = 1 book = N hadiths
   - Actual nawawi40: 1 file = 1 hadith appearing in MULTIPLE collections/books

2. **No Multi-Collection Support:**
   - Current code cannot handle a single hadith appearing in 6 different collections
   - Need to create multiple records per hadith (one for each collection)

3. **Companion/Narrator Data Not Imported:**
   - Tables exist but no mapping logic
   - Sanad (chain of narration) completely ignored

4. **Themes & Keywords Not Imported:**
   - Tables exist but no mapping logic
   - Rich thematic data unused

5. **Scholarly Commentary Not Imported:**
   - Tables exist but no mapping logic
   - Valuable scholarly insights lost

6. **Grading Details Not Fully Utilized:**
   - Only status and agreement saved
   - Individual scholar gradings ignored
   - Albani grading ignored

### 🟡 **MISSING TABLES/COLUMNS**

7. **Companion Information:**
   - No table for companion (Sahabi) details
   - Need to create or link to Sahabah module

8. **Related Quranic Verses:**
   - No table for verse relationships
   - Cross-referencing impossible

9. **Practical Applications:**
   - No table for practical applications
   - Educational value lost

10. **Historical Context:**
    - No fields for occasion/lesson
    - Context not preserved

11. **Legal Rulings (Fiqh):**
    - No table for fiqh principles
    - Legal insights not stored

12. **Significance/Importance:**
    - No fields for detailed significance
    - Educational value not captured

13. **Verification Status:**
    - No fields for verification status
    - Data quality indicators lost

14. **Arabic Without Tashkeel:**
    - `ar_notashkeel` field available but not used
    - Useful for search functionality

---

## Recommended Actions

### Phase 1: Fix Current Import (Minimum Viable)

1. **Create New Transformer:** `Nawawi40DataTransformer.php`
   - Handle single-hadith-per-file structure
   - Support multi-collection mapping
   - Extract all available fields

2. **Update JsonImportService:**
   - Detect JSON structure type (old vs nawawi40)
   - Route to appropriate transformer
   - Handle multi-collection loop

3. **Import Core Data:**
   - ✅ Collections, Books, Chapters
   - ✅ Hadiths (with grading)
   - ✅ Translations (ar, en, ar_notashkeel)

### Phase 2: Enable Rich Features

4. **Map Existing Tables:**
   - ✅ Narrators (hadith_narrators)
   - ✅ Isnad chain (hadith_isnad)
   - ✅ Themes (hadith_themes + pivot)
   - ✅ Keywords (hadith_keywords)
   - ✅ Gradings (hadith_gradings)
   - ✅ Commentaries (hadith_commentaries)
   - ✅ Scholars (hadith_scholars)

5. **Create Missing Tables:**
   - ✅ Companions table (or link to Sahabah module)
   - ✅ hadith_related_verses
   - ✅ hadith_applications
   - ✅ hadith_fiqh_rulings

6. **Extend Hadiths Table:**
   - Add columns: significance_en, significance_ar
   - Add columns: historical_context_en, historical_context_ar
   - Add columns: verification_status, uncertainty_notes

### Phase 3: Optimize & Enhance

7. **Search Optimization:**
   - Use `ar_notashkeel` for better Arabic search
   - Full-text index on keywords
   - Theme-based filtering

8. **Admin Interface:**
   - Display narrator chains
   - Show scholarly commentary
   - Related verses sidebar
   - Practical applications tab

9. **API Enhancements:**
   - Return full hadith with all metadata
   - Filter by themes
   - Filter by scholars
   - Cross-reference with Quran verses

---

## File Statistics

- **Total JSON Files:** 42
- **File Size:** ~19KB per file
- **Total Size:** ~798KB
- **Hadiths:** 42 unique hadiths
- **Collections per Hadith:** ~6 (Bukhari, Muslim, Abu Dawud, Tirmidhi, Nasa'i, Ibn Majah)
- **Expected Database Records:**
  - Collections: 6
  - Books: ~150 (42 hadiths × ~6 collections, but many share books)
  - Chapters: ~200
  - Hadiths: ~250 (42 hadiths × 6 collections)
  - Translations: ~500 (250 × 2 languages)
  - Narrators: ~200-300 unique narrators
  - Themes: ~50-100 unique themes
  - Keywords: ~100-200 unique keywords

---

## Next Steps

**IMMEDIATE:**
1. Create `Nawawi40DataTransformer` class
2. Update `SyncHadithFromJsonCommand` to use new transformer
3. Test import with one file
4. Verify multi-collection creation

**SHORT TERM:**
1. Map narrator data
2. Map themes and keywords
3. Map scholarly commentary

**LONG TERM:**
1. Create missing tables
2. Extend hadiths table
3. Build admin interface for rich data
4. Enhance API endpoints

---

**Analysis Date:** November 21, 2025
**Analyst:** Claude Code
**Status:** Documentation Complete ✅
