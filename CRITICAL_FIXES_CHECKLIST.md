# Critical Fixes Implementation Checklist

## PRIORITY 1: DELETE DUPLICATE FILE

### Task 1.1: Remove Duplicate UserInteraction Model
- **Status:** [ ] TODO
- **File to DELETE:** `app/Domain/Blog/Models/UserInteraction.php`
- **Keep:** `app/Domain/Shared/Models/UserInteraction.php`
- **Reason:** Shared version has proper enum support and engagement tracking
- **Time:** 2 minutes

**Steps:**
1. Delete `app/Domain/Blog/Models/UserInteraction.php`
2. Search for imports of `App\Domain\Blog\Models\UserInteraction` and update to `App\Domain\Shared\Models\UserInteraction`
3. Verify no references to Blog UserInteraction remain

---

## PRIORITY 2: FIX QURAN MODEL CLASS NAMES

### Task 2.1: Fix QuranVerse → Verse References

**Status:** [ ] TODO  
**Severity:** CRITICAL - 10 files to update  
**Time:** 15-20 minutes

**Files to update:**

1. **app/Http/Requests/StoreInteractionRequest.php**
   - Line 40: `'App\\Domain\\Quran\\Models\\QuranVerse'` → `Verse::class`
   - Line 83: `'QuranVerse' => 'App\\Domain\\Quran\\Models\\QuranVerse'` → `'Verse' => Verse::class`
   - Add import: `use App\Domain\Quran\Models\Verse;`

2. **app/Http/Requests/ScheduleContentRequest.php**
   - Line 41: Same as above
   - Line 105: Same as above
   - Add import: `use App\Domain\Quran\Models\Verse;`

3. **app/Http/Controllers/Admin/ActivityLogController.php**
   - Line 103: Update string reference
   - Line 198: Update string reference
   - Line 225: Update string reference
   - Add import: `use App\Domain\Quran\Models\Verse;`

4. **app/Http/Controllers/Admin/QuranController.php**
   - Line 200: `\App\Domain\Quran\Models\QuranVerse::class` → `Verse::class`
   - Add import: `use App\Domain\Quran\Models\Verse;`

5. **app/Http/Controllers/Admin/SchedulerController.php**
   - Lines 88, 120: Update references
   - Add import: `use App\Domain\Quran\Models\Verse;`

6. **app/Http/Controllers/Admin/VersionController.php**
   - Line 274: Update reference
   - Add import: `use App\Domain\Quran\Models\Verse;`

7. **app/Http/Controllers/Api/InteractionController.php**
   - Line 140: Update reference
   - Add import: `use App\Domain\Quran\Models\Verse;`

8. **app/Domain/Shared/Importers/TafseerImporter.php**
   - Lines 8, 9: Update import statements
   - Line 62: Update query
   - Line 81: Update query

9. **app/Domain/Shared/Enums/SearchModule.php**
   - Line 120: `\App\Domain\Quran\Models\QuranVerse::class` → `Verse::class`
   - Add import: `use App\Domain\Quran\Models\Verse;`

10. **app/Domain/Shared/Models/ContentSchedule.php**
    - Line 303: Update string reference
    - Add import: `use App\Domain\Quran\Models\Verse;`

### Task 2.2: Fix QuranSurah → Surah References

**Status:** [ ] TODO  
**Severity:** CRITICAL - 5 files to update  
**Time:** 10-15 minutes

**Files to update:**

1. **app/Http/Requests/StoreInteractionRequest.php**
   - Line 41: `'App\\Domain\\Quran\\Models\\QuranSurah'` → `Surah::class`
   - Line 84: `'QuranSurah' => 'App\\Domain\\Quran\\Models\\QuranSurah'` → `'Surah' => Surah::class`
   - Add import: `use App\Domain\Quran\Models\Surah;`

2. **app/Http/Requests/ScheduleContentRequest.php**
   - Lines 42, 106: Same as above
   - Add import: `use App\Domain\Quran\Models\Surah;`

3. **app/Http/Controllers/Admin/SchedulerController.php**
   - Lines 89, 121: Update references
   - Add import: `use App\Domain\Quran\Models\Surah;`

4. **app/Domain/Shared/Importers/TafseerImporter.php**
   - Line 8: Update import: `use App\Domain\Quran\Models\Surah;`
   - Lines 56, 80: Update queries

5. **app/Domain/Shared/Models/ContentSchedule.php**
   - Line 304: Update string reference
   - Add import: `use App\Domain\Quran\Models\Surah;`

### Task 2.3: Fix QuranTafseer → Tafseer References

**Status:** [ ] TODO  
**Severity:** CRITICAL - 1 file to update  
**Time:** 5 minutes

**File to update:**

**app/Domain/Shared/Importers/TafseerImporter.php**
- Line 7: `use App\Domain\Quran\Models\QuranTafseer;` → `use App\Domain\Quran\Models\Tafseer;`
- Line 16: Class name already correct: `class TafseerImporter`
- Line 78: Return type: `protected function importRow(array $row): QuranTafseer` → `protected function importRow(array $row): Tafseer`
- Lines 70, 87: `QuranTafseer::` → `Tafseer::`
- Class name: `return QuranTafseer::create(` → `return Tafseer::create(`

---

## PRIORITY 3: FIX DATABASE FIELD MISMATCHES

### Task 3.1: Fix Tafseer Importer Field Names

**Status:** [ ] TODO  
**Severity:** HIGH - Will cause import failures  
**Time:** 5 minutes

**File:** `app/Domain/Shared/Importers/TafseerImporter.php`

**Changes needed:**

```php
// Line 56-63: Current code
$surah = QuranSurah::where('surah_number', $row['surah_number'])->first();
if (! $surah) {
    return false;
}
$verse = QuranVerse::where('quran_surah_id', $surah->id)
    ->where('ayah_number', $row['ayah_number'])
    ->first();

// Should be:
$surah = Surah::where('number', $row['surah_number'])->first();  // 'surah_number' → 'number'
if (! $surah) {
    return false;
}
$verse = Verse::where('surah_id', $surah->id)  // 'quran_surah_id' → 'surah_id'
    ->where('verse_number', $row['ayah_number'])  // 'ayah_number' → 'verse_number'
    ->first();
```

**Lines 70-72:** Update Tafseer query field name
```php
// Current:
return QuranTafseer::where('quran_verse_id', $verse->id)

// Should be:
return Tafseer::where('verse_id', $verse->id)
```

**Lines 80-83:** Same fixes as lines 56-63

**Lines 87-92:** Update create call
```php
// Current:
return QuranTafseer::create([
    'quran_verse_id' => $verse->id,

// Should be:
return Tafseer::create([
    'verse_id' => $verse->id,
```

---

## PRIORITY 4: ADD MISSING ARTICLE FIELD

### Task 4.1: Add is_featured Column to Articles Table

**Status:** [ ] TODO  
**Severity:** MEDIUM - Feature filtering will fail  
**Time:** 10 minutes

**Option A: Create Migration (RECOMMENDED)**

```bash
php artisan make:migration add_is_featured_to_articles_table
```

Create migration file:
```php
public function up(): void
{
    Schema::table('articles', function (Blueprint $table) {
        $table->boolean('is_featured')->default(false)->after('featured_image');
        $table->index('is_featured');
    });
}

public function down(): void
{
    Schema::table('articles', function (Blueprint $table) {
        $table->dropColumn('is_featured');
    });
}
```

Run migration:
```bash
php artisan migrate
```

**Option B: Update Article Model**

If column won't be added, remove filtering code:
```php
// In app/Domain/Blog/Repositories/ArticleRepository.php, lines 281-283
// Remove or comment out:
// if (isset($filters['is_featured']) && $filters['is_featured']) {
//     $query->where('is_featured', (bool) $filters['is_featured']);
// }
```

---

## PRIORITY 5: SQL INJECTION PROTECTION

### Task 5.1: Fix Unsafe SQL in Verse Model

**Status:** [ ] TODO  
**Severity:** MEDIUM - Potential security issue  
**Time:** 5 minutes

**File:** `app/Domain/Quran/Models/Verse.php`

**Lines 187-192:**

```php
// Current (UNSAFE):
public function scopeSearchTranslation($query, string $searchTerm, string $language = 'en')
{
    return $query->whereRaw(
        "JSON_EXTRACT(translations, '$.{$language}') LIKE ?",
        ["%{$searchTerm}%"]
    );
}

// Should be (SAFE):
public function scopeSearchTranslation($query, string $searchTerm, string $language = 'en')
{
    // Validate language code
    $validLanguages = ['ar', 'en', 'fr', 'tr', 'ur'];
    if (!in_array($language, $validLanguages)) {
        $language = 'en';
    }
    
    return $query->whereRaw(
        "JSON_EXTRACT(translations, '$.{$language}') LIKE ?",
        ["%{$searchTerm}%"]
    );
}
```

---

## VERIFICATION STEPS

### Post-Fix Testing

**Task 5.1: Run Artisan Commands**
```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Check for syntax errors
php artisan tinker
> exit;

# Run migrations if applicable
php artisan migrate
```

**Task 5.2: Test Quran Module Operations**
```bash
php artisan tinker

# Test Verse model
>>> use App\Domain\Quran\Models\Verse;
>>> Verse::count();

# Test Surah model
>>> use App\Domain\Quran\Models\Surah;
>>> Surah::count();

# Test Tafseer model
>>> use App\Domain\Quran\Models\Tafseer;
>>> Tafseer::count();
```

**Task 5.3: Test Validation Rules**
- Test POST /api/interactions with QuranVerse/QuranSurah type
- Verify it accepts the corrected class names

**Task 5.4: Test Import Workflow**
- Test Tafseer bulk import from admin panel
- Verify field mapping works correctly

---

## IMPLEMENTATION ORDER

1. **First:** Delete `app/Domain/Blog/Models/UserInteraction.php`
2. **Second:** Fix all Quran class name references (most critical)
3. **Third:** Fix Tafseer importer field names
4. **Fourth:** Add is_featured column or remove filter
5. **Fifth:** Add SQL injection protection
6. **Finally:** Run all tests and verify

---

## ESTIMATED TOTAL TIME

- Priority 1: 2 min
- Priority 2: 35 min (most files affected)
- Priority 3: 5 min
- Priority 4: 10 min
- Priority 5: 5 min
- **Testing & Verification: 20 min**

**TOTAL: ~75 minutes (1.25 hours)**

---

## RISK ASSESSMENT

**High Risk:** Quran class name changes affect:
- Validation rules
- Import workflows
- Content scheduling
- Admin panel operations
- API interactions

**Mitigation:**
- Test after each change
- Run migrations if needed
- Clear all caches
- Test in development first

---

**Last Updated:** 2025-10-31  
**Priority Level:** CRITICAL - Must complete before deployment
