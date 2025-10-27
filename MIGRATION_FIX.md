# Migration Error Fix - JSON Full-Text Search

## Problem
```
SQLSTATE[42000]: Syntax error or access violation: 3152 JSON column 'title' 
supports indexing only via generated columns on a specified JSON path.
```

## Root Cause
MySQL does not support creating full-text indexes directly on JSON columns. We were trying to:
```sql
ALTER TABLE articles ADD FULLTEXT fulltext_title_excerpt (title, excerpt)
```

But both `title` and `excerpt` are JSON columns for multi-language support.

## Solution Applied
Created **generated columns** to extract specific language values from JSON, then added full-text indexes on those generated columns.

### Articles Migration Fix
```sql
-- Create generated columns for Arabic language (default)
ALTER TABLE articles ADD COLUMN title_searchable TEXT 
  GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(title, '$.ar'))) STORED;

ALTER TABLE articles ADD COLUMN excerpt_searchable TEXT 
  GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(excerpt, '$.ar'))) STORED;

-- Add full-text index on generated columns
ALTER TABLE articles ADD FULLTEXT fulltext_search (title_searchable, excerpt_searchable);
```

### Hadith Migration Fix
```sql
-- Full-text on Arabic text (already TEXT column, not JSON)
ALTER TABLE hadith ADD FULLTEXT fulltext_arabic_text (arabic_text);

-- Generated column for English translation
ALTER TABLE hadith ADD COLUMN translation_en_searchable TEXT 
  GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(translations, '$.en'))) STORED;

-- Full-text index on English translation
ALTER TABLE hadith ADD FULLTEXT fulltext_translation_en (translation_en_searchable);
```

## How to Apply the Fix

### Option 1: Fresh Migration (Recommended if no data)
```bash
php artisan migrate:fresh
```

### Option 2: Using the Reset Script
```bash
./reset-database.sh
```

### Option 3: Manual MySQL Cleanup
```sql
-- Connect to MySQL
mysql -u your_user -p

-- Use your database
USE the_last_messenger;

-- Drop all tables
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS articles, categories, tags, taggables, series, favorites, user_interactions;
DROP TABLE IF EXISTS hadith, hadith_books, hadith_chapters, hadith_narrators, hadith_narrator_chain;
DROP TABLE IF EXISTS media_categories, media_programs, media_episodes;
DROP TABLE IF EXISTS users, password_reset_tokens, sessions, personal_access_tokens;
DROP TABLE IF EXISTS roles, permissions, role_has_permissions, model_has_permissions, model_has_roles;
DROP TABLE IF EXISTS media, migrations;
SET FOREIGN_KEY_CHECKS = 1;

-- Exit and run migrations
exit;
```

Then:
```bash
php artisan migrate
```

## Files Modified
1. ✅ `database/migrations/2025_10_27_100003_create_articles_table.php`
   - Added `use Illuminate\Support\Facades\DB;`
   - Changed full-text index to use generated columns

2. ✅ `database/migrations/2025_10_27_110003_create_hadith_table.php`
   - Added generated column for English translation search
   - Added full-text index on generated column

## Benefits of This Approach

### 1. Multi-Language Support
- JSON columns store all languages: `{"ar": "العنوان", "en": "Title", "fr": "Titre"}`
- Generated columns extract specific language for indexing

### 2. Search Performance
- Full-text indexes are fast (MATCH...AGAINST)
- Generated columns are automatically updated
- Indexed columns are STORED (pre-computed)

### 3. Flexibility
- Can add more languages to JSON without schema changes
- Can add more generated columns for other languages later
- Example:
  ```sql
  ALTER TABLE articles ADD COLUMN title_en_searchable TEXT 
    GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(title, '$.en'))) STORED;
  ALTER TABLE articles ADD FULLTEXT fulltext_title_en (title_en_searchable);
  ```

## Using Full-Text Search in Queries

### Articles Search (Arabic)
```php
// In ArticleRepository
public function search(string $query): Collection
{
    return $this->model
        ->whereRaw("MATCH(title_searchable, excerpt_searchable) AGAINST(? IN NATURAL LANGUAGE MODE)", [$query])
        ->get();
}
```

### Hadith Search (Arabic Text)
```php
// In HadithRepository
public function searchArabic(string $query): Collection
{
    return $this->model
        ->whereRaw("MATCH(arabic_text) AGAINST(? IN NATURAL LANGUAGE MODE)", [$query])
        ->with(['book', 'chapter'])
        ->get();
}
```

### Hadith Search (English Translation)
```php
// In HadithRepository
public function searchEnglish(string $query): Collection
{
    return $this->model
        ->whereRaw("MATCH(translation_en_searchable) AGAINST(? IN NATURAL LANGUAGE MODE)", [$query])
        ->with(['book', 'chapter'])
        ->get();
}
```

## Verification Steps

After running migrations successfully:

```bash
# 1. Check migration status
php artisan migrate:status

# 2. Check table structure
php artisan tinker

# In tinker:
DB::select("SHOW COLUMNS FROM articles");
DB::select("SHOW INDEX FROM articles");

# 3. Test full-text search
DB::select("SHOW INDEX FROM articles WHERE Key_name LIKE 'fulltext%'");
DB::select("SHOW INDEX FROM hadith WHERE Key_name LIKE 'fulltext%'");
```

## Expected Output

After successful migration, you should have:

### Articles Table Columns
- id, slug, user_id, category_id, series_id
- title (JSON), excerpt (JSON), content (JSON)
- **title_searchable** (generated TEXT column)
- **excerpt_searchable** (generated TEXT column)
- featured_image, reading_time_minutes
- views_count, favorites_count, status
- published_at, created_at, updated_at, deleted_at

### Articles Indexes
- PRIMARY (id)
- UNIQUE (slug)
- INDEX (user_id, category_id, series_id, status, published_at, etc.)
- **FULLTEXT (title_searchable, excerpt_searchable)**

### Hadith Table Columns
- id, book_id, chapter_id, hadith_number
- arabic_text (TEXT)
- narrator_chain (TEXT)
- translations (JSON)
- **translation_en_searchable** (generated TEXT column)
- grade, reference_url, views_count
- created_at, updated_at

### Hadith Indexes
- PRIMARY (id)
- INDEX (book_id, chapter_id, grade, etc.)
- **FULLTEXT (arabic_text)**
- **FULLTEXT (translation_en_searchable)**

## Future Enhancements

To add more searchable languages:

```sql
-- Add French search for articles
ALTER TABLE articles ADD COLUMN title_fr_searchable TEXT 
  GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(title, '$.fr'))) STORED;
ALTER TABLE articles ADD FULLTEXT fulltext_title_fr (title_fr_searchable);

-- Add Urdu search for hadith
ALTER TABLE hadith ADD COLUMN translation_ur_searchable TEXT 
  GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(translations, '$.ur'))) STORED;
ALTER TABLE hadith ADD FULLTEXT fulltext_translation_ur (translation_ur_searchable);
```

## References
- [MySQL JSON Functions](https://dev.mysql.com/doc/refman/8.0/en/json-function-reference.html)
- [MySQL Generated Columns](https://dev.mysql.com/doc/refman/8.0/en/create-table-generated-columns.html)
- [MySQL Full-Text Search](https://dev.mysql.com/doc/refman/8.0/en/fulltext-search.html)

---

**Status**: ✅ Fixed and ready for migration  
**Last Updated**: October 27, 2025  
**Migration Count**: 20+ tables ready

