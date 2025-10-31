# Bulk Import - Quick Reference Card

## Available Modules

| Module | Required Columns | Description |
|--------|-----------------|-------------|
| `articles` | title_ar, title_en, content_ar, content_en, author_email | Blog/Seerah articles |
| `hadith` | arabic_text, english_text, book_name, chapter_name, hadith_number | Hadith database |
| `media_programs` | title_ar, title_en, description_ar, description_en | Video programs |
| `seerah_events` | title_ar, title_en, description_ar, description_en, event_date | Timeline events |
| `sahaba` | name_ar, name_en | Companion profiles |
| `quran_surahs` | number, name_ar, name_en, revelation_place, verses_count | Quran chapters |
| `quran_verses` | surah_number, ayah_number, arabic_text, english_translation | Quran verses |
| `reciters` | name_ar, name_en | Quran reciters |
| `tafseer` | surah_number, ayah_number, source, author, tafseer_ar, tafseer_en | Interpretations |
| `hadith_narrators` | name_ar, name_en | Narrator biographies |

## Quick Start API Flow

```bash
# 1. Get column information
curl -X GET "/su/portal/v1/admin/import/{module}" \
  -H "Authorization: Bearer TOKEN"

# 2. Upload file (dry-run first)
curl -X POST "/su/portal/v1/admin/import/{module}/parse" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@data.csv" \
  -F "dry_run=true"

# 3. Execute import
curl -X POST "/su/portal/v1/admin/import/{module}/execute" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"import_id": 123, "confirm": true}'

# 4. Check status
curl -X GET "/su/portal/v1/admin/import/{id}/status" \
  -H "Authorization: Bearer TOKEN"
```

## Import Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `has_header` | boolean | true | First row contains column headers |
| `skip_duplicates` | boolean | false | Skip existing records |
| `update_existing` | boolean | false | Update existing records |
| `dry_run` | boolean | false | Test mode (no data insertion) |

## File Requirements

- **Formats**: CSV, XLSX, XLS
- **Max Size**: 10MB
- **Encoding**: UTF-8 (for Arabic support)
- **Headers**: Column names should match field names

## Common CSV Patterns

### Multi-Language Fields
```csv
title_ar,title_en,title_fr,title_tr,title_ur
عنوان,Title,Titre,Başlık,عنوان
```

### Comma-Separated Values
```csv
tags
"Prophet,Biography,History"
```

### Pipe-Separated Values
```csv
narrators
"Umar ibn Al-Khattab|Abdullah ibn Umar"
```

### Dates
```csv
published_at
2024-01-15
```

### Boolean Values
```csv
featured
true
```

## Processing Logic

- **< 1000 rows**: Immediate processing
- **>= 1000 rows**: Background queue
- **Chunk size**: 100 rows per batch
- **Timeout**: 1 hour max
- **Retries**: 3 attempts on failure

## Status Values

| Status | Description |
|--------|-------------|
| `pending` | Waiting to be processed |
| `parsing` | Reading file contents |
| `validating` | Checking data validity |
| `processing` | Inserting records |
| `completed` | Successfully finished |
| `failed` | Error occurred |
| `cancelled` | Manually stopped |

## Error Severity

| Severity | Meaning | Action |
|----------|---------|--------|
| `error` | Critical - row skipped | Fix and re-import |
| `warning` | Non-critical - row imported | Review for quality |

## Importer Classes

| Module | Class | Location |
|--------|-------|----------|
| articles | ArticleImporter | app/Domain/Shared/Importers/ArticleImporter.php |
| hadith | HadithImporter | app/Domain/Shared/Importers/HadithImporter.php |
| media_programs | MediaProgramImporter | app/Domain/Shared/Importers/MediaProgramImporter.php |
| seerah_events | SeerahEventImporter | app/Domain/Shared/Importers/SeerahEventImporter.php |
| sahaba | SahabiImporter | app/Domain/Shared/Importers/SahabiImporter.php |
| quran_surahs | QuranSurahImporter | app/Domain/Shared/Importers/QuranSurahImporter.php |
| quran_verses | QuranVerseImporter | app/Domain/Shared/Importers/QuranVerseImporter.php |
| reciters | ReciterImporter | app/Domain/Shared/Importers/ReciterImporter.php |
| tafseer | TafseerImporter | app/Domain/Shared/Importers/TafseerImporter.php |
| hadith_narrators | HadithNarratorImporter | app/Domain/Shared/Importers/HadithNarratorImporter.php |

## Key Methods in BaseImporter

```php
abstract class BaseImporter
{
    // Must implement in child classes
    abstract protected function getValidationRules(): array;
    abstract protected function getColumnMapping(): array;
    abstract protected function importRow(array $row): mixed;
    abstract protected function isDuplicate(array $row): bool;

    // Available helpers
    protected function buildMultiLanguageField(array $row, string $prefix): array;
    protected function transformDate(?string $date): ?string;
    protected function transformBoolean(mixed $value): bool;
    protected function addError(string $field, string $message): void;

    // Public methods
    public function parseFile(): array;
    public function validate(): array;
    public function preview(int $limit = 10): array;
    public function import(): ImportResult;
}
```

## Adding New Importer

1. Create class extending `BaseImporter`
2. Implement 4 abstract methods
3. Add to `ImportController::getImporter()`
4. Add to `ProcessImport::getImporter()`
5. Add to `ImportRequest` validation
6. Update `isValidModule()` method

## Common Issues & Fixes

| Issue | Fix |
|-------|-----|
| "Author not found" | Ensure user email exists in users table |
| "Duplicate detected" | Enable skip_duplicates or update_existing |
| "Invalid date format" | Use YYYY-MM-DD format |
| "Encoding error" | Save CSV as UTF-8 encoding |
| "Missing column" | Check required columns list |

## Testing Checklist

- [ ] Dry-run mode works
- [ ] Valid data imports successfully
- [ ] Invalid data shows errors
- [ ] Duplicates handled correctly
- [ ] Large files queue properly
- [ ] Errors logged to database
- [ ] Activity logged correctly
- [ ] Files cleaned up on delete

## Performance Tips

1. Split large files (>10MB) into smaller chunks
2. Use dry-run first to catch errors early
3. Import during off-peak hours for large datasets
4. Monitor queue worker for background jobs
5. Clean up old imports regularly

## Required Permissions

- Authentication: Sanctum token required
- Roles: `super_admin`, `admin`, or `editor`
- Per-import ownership: User can only access their own imports (unless super_admin)

## Documentation

- **Full Guide**: BULK_IMPORT_GUIDE.md
- **Implementation**: BULK_IMPORT_IMPLEMENTATION.md
- **Tasks**: bulk-import-tasks.md

## Support Commands

```bash
# Run queue worker
php artisan queue:work

# Check import status in database
php artisan tinker
>>> Import::with('errors')->find(123);

# Clear failed jobs
php artisan queue:flush

# View logs
tail -f storage/logs/laravel.log

# Test syntax
php -l app/Domain/Shared/Importers/ArticleImporter.php
```

---

**Last Updated**: 2025-10-31
**Version**: 1.0
**Status**: Production Ready ✅
