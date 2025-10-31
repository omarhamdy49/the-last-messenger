# Bulk Import Guide

This document provides comprehensive guidance for using the bulk import functionality in The Last Messenger platform.

## Overview

The bulk import feature allows administrators to import large datasets from CSV or Excel files for all major modules. The system supports:

- **File Formats**: CSV, XLSX, XLS
- **Max File Size**: 10MB
- **Supported Modules**: 10 different content types
- **Features**: Validation, duplicate detection, dry-run mode, progress tracking, error reporting

## General Workflow

1. **Prepare File**: Create CSV/Excel with required columns
2. **Upload & Parse**: Upload file and system parses headers and data
3. **Validate**: System validates all rows and reports errors
4. **Preview**: Review first 10 rows to verify mapping
5. **Execute**: Run import (immediate for <1000 rows, queued for larger)
6. **Review**: Check status, success rate, and any errors

## API Endpoints

### 1. Get Import Form Information
```
GET /su/portal/v1/admin/import/{module}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "module": "articles",
    "required_columns": ["title_ar", "title_en", "content_ar", "content_en", "author_email"],
    "optional_columns": ["title_fr", "title_tr", "title_ur", "excerpt_ar", "category_name", "tags"],
    "sample_data": [...]
  }
}
```

### 2. Parse File
```
POST /su/portal/v1/admin/import/{module}/parse
Content-Type: multipart/form-data

Body:
- file: CSV/Excel file
- has_header: true/false (default: true)
- skip_duplicates: true/false
- update_existing: true/false
- dry_run: true/false
```

**Response:**
```json
{
  "success": true,
  "data": {
    "import_id": 123,
    "total_rows": 500,
    "preview": [...],
    "message": "File parsed successfully"
  }
}
```

### 3. Validate Data
```
POST /su/portal/v1/admin/import/{module}/validate

Body:
{
  "import_id": 123
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "is_valid": true,
    "total_errors": 0,
    "total_warnings": 5,
    "errors": [],
    "warnings": [...]
  }
}
```

### 4. Execute Import
```
POST /su/portal/v1/admin/import/{module}/execute

Body:
{
  "import_id": 123,
  "confirm": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "import_id": 123,
    "status": "completed",
    "result": {
      "total_rows": 500,
      "success_count": 495,
      "error_count": 5,
      "skipped_count": 0,
      "success_rate": 99.0
    }
  }
}
```

### 5. Check Status
```
GET /su/portal/v1/admin/import/{importId}/status
```

### 6. View Import History
```
GET /su/portal/v1/admin/import/history?per_page=15
```

### 7. View Import Errors
```
GET /su/portal/v1/admin/import/{importId}/errors?per_page=50
```

### 8. Delete Import
```
DELETE /su/portal/v1/admin/import/{importId}
```

## Module-Specific Formats

### 1. Articles (Blog/Seerah)

**Module Name**: `articles`

**Required Columns:**
- `title_ar` - Arabic title
- `title_en` - English title
- `content_ar` - Arabic content
- `content_en` - English content
- `author_email` - Email of existing user (author)

**Optional Columns:**
- `title_fr`, `title_tr`, `title_ur` - Additional language titles
- `content_fr`, `content_tr`, `content_ur` - Additional language content
- `excerpt_ar`, `excerpt_en`, etc. - Article excerpts
- `category_name` - Category name (auto-created if not exists)
- `tags` - Comma-separated tags (e.g., "Prophet,Biography,History")
- `status` - draft, published, archived (default: draft)
- `published_at` - Publication date (YYYY-MM-DD)

**Sample CSV:**
```csv
title_ar,title_en,content_ar,content_en,author_email,category_name,tags,status
مقال عن السيرة,Article about Seerah,محتوى المقال,Article content,admin@example.com,Seerah,"Prophet,Biography",published
```

### 2. Hadith

**Module Name**: `hadith`

**Required Columns:**
- `arabic_text` - Hadith text in Arabic
- `english_text` - Hadith translation in English
- `book_name` - Hadith book name (e.g., "Sahih Bukhari")
- `chapter_name` - Chapter name
- `hadith_number` - Hadith number in book

**Optional Columns:**
- `narrators` - Pipe-separated narrator names (e.g., "Umar|Abdullah")
- `grade` - sahih, hasan, daif, mawdu, mursal
- `reference` - Reference URL or citation

**Sample CSV:**
```csv
arabic_text,english_text,book_name,chapter_name,hadith_number,narrators,grade
إنما الأعمال بالنيات,Actions are by intentions,Sahih Bukhari,Book of Revelation,1,Umar ibn Al-Khattab|Abdullah,sahih
```

### 3. Media Programs

**Module Name**: `media_programs`

**Required Columns:**
- `title_ar` - Arabic title
- `title_en` - English title
- `description_ar` - Arabic description
- `description_en` - English description

**Optional Columns:**
- `title_fr`, `title_tr`, `title_ur` - Additional titles
- `description_fr`, `description_tr`, `description_ur` - Additional descriptions
- `category_name` - Media category name
- `status` - draft, published, archived

**Sample CSV:**
```csv
title_ar,title_en,description_ar,description_en,category_name,status
برنامج السيرة,Seerah Program,وصف البرنامج,Program description,Documentary,published
```

### 4. Seerah Events

**Module Name**: `seerah_events`

**Required Columns:**
- `title_ar` - Arabic event title
- `title_en` - English event title
- `description_ar` - Arabic description
- `description_en` - English description
- `event_date` - Gregorian date (YYYY-MM-DD)

**Optional Columns:**
- `title_fr`, `title_tr`, `title_ur`
- `description_fr`, `description_tr`, `description_ur`
- `hijri_date` - Hijri date
- `location` - Event location
- `status` - draft, published

**Sample CSV:**
```csv
title_ar,title_en,description_ar,description_en,event_date,hijri_date,location
ولادة النبي,Birth of the Prophet,وصف الحدث,Event description,571-04-22,1 Rabi al-Awwal,Makkah
```

### 5. Sahaba (Companions)

**Module Name**: `sahaba`

**Required Columns:**
- `name_ar` - Arabic name
- `name_en` - English name

**Optional Columns:**
- `name_fr`, `name_tr`, `name_ur`
- `tribe` - Tribe name
- `birth_date` - Birth date
- `death_date` - Death date
- `battles` - Comma-separated battles participated in
- `status` - published, draft

**Sample CSV:**
```csv
name_ar,name_en,tribe,birth_date,battles,status
أبو بكر الصديق,Abu Bakr as-Siddiq,Taym,573-10-27,"Badr,Uhud,Khandaq",published
```

### 6. Quran Surahs (Chapters)

**Module Name**: `quran_surahs`

**Required Columns:**
- `number` - Surah number (1-114)
- `name_ar` - Arabic name
- `name_en` - English name
- `revelation_place` - makkah or madinah
- `verses_count` - Number of verses

**Optional Columns:**
- `name_fr`, `name_tr`, `name_ur`
- `meaning_ar`, `meaning_en`, `meaning_fr`, `meaning_tr`, `meaning_ur` - Name meanings
- `revelation_order` - Chronological order (1-114)
- `words_count` - Total words in surah
- `letters_count` - Total letters in surah
- `juz` - Juz numbers (comma-separated if spans multiple)
- `page_number` - Starting page number
- `description_ar`, `description_en`, etc. - Surah description
- `theme_ar`, `theme_en`, etc. - Main themes

**Sample CSV:**
```csv
number,name_ar,name_en,revelation_place,verses_count,revelation_order,meaning_en,juz
1,الفاتحة,Al-Fatihah,makkah,7,5,The Opening,1
```

### 7. Quran Verses

**Module Name**: `quran_verses`

**Required Columns:**
- `surah_number` - Surah number (1-114)
- `ayah_number` - Verse number
- `arabic_text` - Arabic text (Uthmani script)
- `english_translation` - English translation

**Optional Columns:**
- `french_translation`, `turkish_translation`, `urdu_translation`
- `transliteration` - Romanized Arabic
- `juz_number` - Juz number
- `page_number` - Page number in Mushaf

**Sample CSV:**
```csv
surah_number,ayah_number,arabic_text,english_translation,transliteration,juz_number,page_number
1,1,بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ,In the name of Allah the Most Gracious the Most Merciful,Bismillah ir-Rahman ir-Rahim,1,1
```

### 8. Reciters

**Module Name**: `reciters`

**Required Columns:**
- `name_ar` - Arabic name
- `name_en` - English name

**Optional Columns:**
- `name_fr`, `name_tr`, `name_ur`
- `country` - Country of origin
- `style` - Recitation style (e.g., Hafs, Warsh)
- `biography_ar`, `biography_en`, etc. - Biography
- `featured` - true/false (default: false)

**Sample CSV:**
```csv
name_ar,name_en,country,style,featured
عبد الباسط عبد الصمد,Abdul Basit Abdul Samad,Egypt,Hafs,true
```

### 9. Tafseer (Verse Interpretations)

**Module Name**: `tafseer`

**Required Columns:**
- `surah_number` - Surah number
- `ayah_number` - Verse number
- `source` - Tafseer source name
- `author` - Author name
- `tafseer_ar` - Arabic interpretation
- `tafseer_en` - English interpretation

**Optional Columns:**
- `tafseer_fr`, `tafseer_tr`, `tafseer_ur`

**Sample CSV:**
```csv
surah_number,ayah_number,source,author,tafseer_ar,tafseer_en
1,1,Tafsir Ibn Kathir,Ibn Kathir,التفسير العربي,English interpretation
```

### 10. Hadith Narrators

**Module Name**: `hadith_narrators`

**Required Columns:**
- `name_ar` - Arabic name
- `name_en` - English name

**Optional Columns:**
- `biography_ar`, `biography_en`
- `grade` - Narrator reliability grade
- `era` - Time period (e.g., "Sahaba", "Tabi'in")
- `country` - Country/region
- `birth_hijri` - Birth year (Hijri)
- `death_hijri` - Death year (Hijri)

**Sample CSV:**
```csv
name_ar,name_en,grade,era,birth_hijri,death_hijri
عمر بن الخطاب,Umar ibn Al-Khattab,Thiqah,Sahaba,584,644
```

## Import Options

### has_header (boolean, default: true)
Whether the first row contains column headers. If true, headers are used to map columns to database fields.

### skip_duplicates (boolean, default: false)
Skip rows that already exist in the database based on unique identifiers (e.g., slug for articles, number for surahs).

### update_existing (boolean, default: false)
Update existing records instead of skipping them. Only works if duplicate is detected.

### dry_run (boolean, default: false)
Test mode - parse, validate, and report results without actually inserting data into database.

## Error Handling

### Error Types

**Severity: error** - Critical errors that prevent import of the row
- Missing required fields
- Invalid data types
- Foreign key violations
- Validation failures

**Severity: warning** - Non-critical issues that don't prevent import
- Duplicate records (when skip_duplicates is true)
- Optional field validation failures
- Missing optional relationships

### Error Response Format
```json
{
  "row_number": 42,
  "field_name": "email",
  "error_message": "The email field must be a valid email address.",
  "severity": "error",
  "row_data": {...}
}
```

## Performance & Limitations

### File Size Limits
- Maximum file size: 10MB
- For larger datasets, split into multiple files

### Processing
- Files with < 1000 rows: Processed immediately
- Files with >= 1000 rows: Queued for background processing

### Chunking
- Data is processed in chunks of 100 rows
- Prevents memory exhaustion on large imports

### Timeout
- Background jobs timeout after 1 hour
- Failed jobs retry up to 3 times

## Best Practices

1. **Test First**: Always use dry_run mode to test your import before executing
2. **Start Small**: Test with a small subset (10-20 rows) before importing large files
3. **Check Columns**: Use the import form endpoint to see required/optional columns
4. **Validate Data**: Ensure all required fields are present and properly formatted
5. **Handle Duplicates**: Decide on skip_duplicates or update_existing strategy
6. **Multi-language**: Provide at least Arabic and English for translatable fields
7. **UTF-8 Encoding**: Ensure CSV files are UTF-8 encoded for proper Arabic character handling
8. **Review Errors**: Always check error report after import completes

## Troubleshooting

### "File is empty or could not be parsed"
- Ensure file is valid CSV/Excel format
- Check file encoding (should be UTF-8)
- Verify file is not corrupted

### "User with email X not found"
For articles, ensure author email exists in users table before importing.

### "Validation failed"
Check error details to see which fields failed validation. Common issues:
- Missing required fields
- Invalid data format (dates, emails, enums)
- Field length exceeds maximum

### "Duplicate record detected"
- Enable skip_duplicates to skip existing records
- Enable update_existing to update existing records
- Check unique identifiers (slug, number, etc.)

## Security

- Import functionality requires authentication (auth:sanctum)
- Users must have role: super_admin, admin, or editor
- Import records are associated with the user who created them
- Files are stored securely in storage/app/imports/{module}/
- Files are automatically deleted when import record is deleted

## Example Usage

### Using cURL

```bash
# 1. Get column information
curl -X GET "https://api.example.com/su/portal/v1/admin/import/articles" \
  -H "Authorization: Bearer YOUR_TOKEN"

# 2. Upload and parse file
curl -X POST "https://api.example.com/su/portal/v1/admin/import/articles/parse" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@articles.csv" \
  -F "has_header=true" \
  -F "skip_duplicates=false" \
  -F "dry_run=true"

# 3. Execute import
curl -X POST "https://api.example.com/su/portal/v1/admin/import/articles/execute" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"import_id": 123, "confirm": true}'

# 4. Check status
curl -X GET "https://api.example.com/su/portal/v1/admin/import/123/status" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Maintenance

### Cleanup Old Imports
Use the CleanupFailedImports job to remove old completed imports:

```bash
php artisan queue:work
```

This job automatically deletes completed imports older than 30 days.

## Support

For issues or questions about bulk import:
1. Check this guide first
2. Review import error log
3. Check Laravel logs: storage/logs/laravel.log
4. Contact system administrator
