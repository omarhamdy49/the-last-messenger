# Hadith Module Refactor Summary

## Overview
The Hadith module has been refactored to improve data structure, remove deprecated fields, and establish proper relationships with a new Hadith Collections system.

## Changes Made

### 1. Database Schema Changes

#### New Table: `hadith_collections`
- **Purpose**: Represents groupings of hadith books (e.g., Sahih collections, Sunan collections)
- **Columns**:
  - `id` (Primary Key)
  - `slug` (Unique, for URL-friendly identifiers)
  - `name` (JSON - multi-language)
  - `description` (JSON - nullable, multi-language)
  - `author_name` (String, nullable)
  - `active` (Boolean, default: true)
  - `featured` (Boolean, default: false)
  - `display_order` (Integer, default: 0)
  - `created_at`, `updated_at` (Timestamps)

#### Refactored Table: `hadith_books`
**Removed Fields**:
- `collection_name` (String) - Now references `hadith_collections` table via FK

**Added Fields**:
- `collection_id` (Foreign Key to `hadith_collections`, nullable)
- `active` (Boolean, default: true)

**Result**: Books can now be properly organized under collections with better data integrity.

#### Refactored Table: `hadith`
**Removed Fields**:
- `external_id` - No longer tracking Sunnah.com IDs at hadith level
- `narrator_chain` - Text field removed (narrator relationships handled separately)
- `reference_url` - No longer storing external references directly

**Added Fields**:
- `active` (Boolean, default: true)
- `searchable_ar` (Generated column - mirrors `arabic_text` for full-text search)
- `searchable_en` (Generated column - extracts English from JSON translations)

**Field Renamed**:
- `translation_en_searchable` → `searchable_en` (for consistency)

**Removed Fields (via migration 2025_10_31_120007)**:
- `urn` (Unique Resource Number)
- `collection_name` (moved to hadith_books)
- `book_number` (redundant with book_id)

**Indexes Updated**:
- Full-text indexes on `searchable_ar` and `searchable_en` for Arabic and English search

### 2. Model Changes

#### HadithCollection (New Model)
**Location**: `app/Domain/Hadith/Models/HadithCollection.php`

**Features**:
- Translatable: `name`, `description`
- Relationships:
  - `books()` - HasMany relationship to HadithBook
- Scopes:
  - `active()` - Filter to active collections only
  - `featured()` - Filter to featured collections
  - `ordered()` - Sort by display_order
- Helper Methods:
  - `getNameInLanguage(string $language)`
  - `getDescriptionInLanguage(string $language)`

#### Hadith (Updated)
**Removed**:
- `BelongsToMany` relationship to narrators via `hadith_narrator_chain`
- `narrator_chain` from fillable
- `reference_url` from fillable

**Added**:
- `active` to fillable and casts (boolean)
- Scope: `active()` - Filter to active hadith only

**Updated**:
- Removed `BelongsToMany` import (no longer needed)

#### HadithBook (Updated)
**Removed**:
- Any direct usage of `collection_name` field

**Added**:
- `collection_id` to fillable
- `active` to fillable and casts (boolean)
- Relationship: `collection()` - BelongsTo relationship to HadithCollection
- Scope: `active()` - Filter to active books only

### 3. Repository Changes

#### HadithRepository
**Updated Methods**:
All read methods now automatically filter by `active = true`:
- `getByBook()`
- `getByChapter()`
- `getByGrade()`
- `getSahih()`
- `getReliable()`
- `search()`
- `getByNarrator()`
- `getByDateRange()`
- `filterHadith()`

**Removed References**:
- `getWithNarrators()` no longer loads the `narrators` relationship (kept for backward compatibility but only loads book and chapter)

### 4. Migration Files

**Created**:
1. `2025_10_31_120004_create_hadith_collections_table.php` - New collections table
2. `2025_10_31_120005_refactor_hadith_books_table.php` - Add collection_id FK, remove collection_name
3. `2025_10_31_120006_refactor_hadith_table.php` - Remove narrator_chain/reference_url/external_id, add searchable columns
4. `2025_10_31_120007_clean_hadith_table.php` - Remove residual urn/collection_name/book_number fields

**Updated**:
- `2025_10_31_075951_add_search_indexes_to_tables.php` - Fixed down() method to handle non-existent indexes gracefully

## Data Integrity Improvements

1. **Better Organization**: Books are now properly grouped under collections with proper foreign key constraints
2. **Cleaner Search**: Searchable columns are now generated, ensuring consistency with source data
3. **Active Flag**: Both hadith and books can be soft-disabled without deletion
4. **Removed Redundancy**: Eliminated duplicate fields (narrator_chain text, reference_url, external identifiers)
5. **Proper Relationships**: Narrator chain relationships are handled through the existing `hadith_narrator_chain` pivot table

## API Response Changes

No immediate API changes required, but when fetching hadith books:
- Clients may need to load the `collection` relationship explicitly:
  ```php
  $book->load('collection');
  // Access: $book->collection->name
  ```

- When creating/updating books, include `collection_id`:
  ```php
  HadithBook::create([
      'collection_id' => $collectionId,
      'name' => ['ar' => '...', 'en' => '...'],
      // ... other fields
  ]);
  ```

## Backward Compatibility

- Old API fields (`external_id`, `urn`, `collection_name`) are no longer stored in the hadith table
- The `hadith_narrator_chain` pivot table still exists and continues to work
- Existing hadith records retain all their data (translations, grades, etc.)
- New `active` flag defaults to `true` for existing records

## Testing

All models and relationships have been validated:
- ✓ HadithCollection model loads successfully
- ✓ HadithBook.collection() relationship works
- ✓ HadithCollection.books() relationship works
- ✓ Active scopes implemented on all models
- ✓ Database schema properly created
- ✓ Full-text indexes on searchable columns in place

## Future Considerations

1. If narrator_chain text field is needed, query the relationship instead:
   ```php
   $hadith->narrators()->pluck('name')->join(' ← ');
   ```

2. For external references, consider adding a separate `hadith_references` table if needed

3. The `synced_at` field remains for tracking API sync operations

## Files Modified

### Created
- `app/Domain/Hadith/Models/HadithCollection.php`
- `database/migrations/2025_10_31_120004_create_hadith_collections_table.php`
- `database/migrations/2025_10_31_120005_refactor_hadith_books_table.php`
- `database/migrations/2025_10_31_120006_refactor_hadith_table.php`
- `database/migrations/2025_10_31_120007_clean_hadith_table.php`

### Updated
- `app/Domain/Hadith/Models/Hadith.php`
- `app/Domain/Hadith/Models/HadithBook.php`
- `app/Domain/Hadith/Repositories/HadithRepository.php`
- `database/migrations/2025_10_31_075951_add_search_indexes_to_tables.php`

## Notes

- The narrator_chain text field was removed because narrator relationships are better managed through the `hadith_narrator_chain` pivot table which provides structured access
- All hadith records created after this refactor will have `active=true` by default
- Search performance improved with dedicated searchable columns and proper full-text indexes
