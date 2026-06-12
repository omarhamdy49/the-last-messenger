# Data Completeness & Correctness Audit — Quran & Hadith

**Date:** 2026-06-10
**Scope:** `the-last-messenger-be` MySQL DB `the_last_messenger` (Docker, host port 3308)
**Reference structures:** quran.com (114 surahs / 6236 verses), sunnah.com (collection sizes)
**Method:** Direct `tinker` / DB queries + `php artisan hadith:deduplicate --find-only`.
**Author:** Automated audit. Fabrication avoided; no data deleted; large/ambiguous issues reported, not changed.

---

## 0. Summary Table

| Entity | Stored | Reference | % | Status |
|---|---|---|---|---|
| Quran surahs | 114 | 114 | 100% | ✓ |
| Quran verses | 6236 | 6236 | 100% | ✓ |
| Per-surah verse counts (stored & actual vs canonical) | all 114 match | 114 | 100% | ✓ |
| Quran Arabic text (`lang=ar`) | 6236 | 6236 | 100% | ✓ |
| Quran translations `en` | 6236 | 6236 | 100% | ✓ |
| Quran translations `fr` | 6236 | 6236 | 100% | ✓ |
| Quran translations `tr` | 6236 | 6236 | 100% | ✓ |
| Quran translations `ur` | 6236 | 6236 | 100% | ✓ |
| Quran transliteration (`en-tl`) | 6236 | 6236 | 100% | ⚠ (dirty tajwid markup, see Q-5) |
| Quran tafsir (`ar`, active) | 6236 | 6236 | 100% | ✓ |
| Makki/Madani split | 86 / 28 | ≈86 / 28 | — | ✓ (plausible; spot-checks pass) |
| `bismillah_pre` correctness | was wrong for surah 9 | — | — | ✓ **FIXED** (see Fix #1) |
| Basmala embedded in verse-1 Arabic text | 110 surahs | should be 0 | — | ⚠ (reported, not changed — see Q-6) |
| Hadith total | 50,520 | — | — | ✓ (far richer than expected) |
| Hadith collections | 17 | — | — | ✓ |
| Hadith books | 406 | — | — | ✓ |
| Hadith Arabic text coverage | 50,395 / 50,520 | — | 99.8% | ⚠ (125 missing — all Muwatta Malik) |
| Hadith English text coverage | 47,100 / 50,520 | — | 93.2% | ⚠ |
| Hadith orphans (book→collection) | 0 | 0 | 100% | ✓ |
| Hadith duplicate groups | 85 | — | — | ⚠ (informational) |
| Hadith `grading_status` quality | 436 distinct free-text values | — | — | ✗ (messy — see H-8) |
| Hadith ungraded (empty grading) | 5,035 | — | — | ⚠ |

Legend: ✓ pass · ⚠ gap/quality issue (report) · ✗ serious quality issue.

---

## 1. QURAN — Findings

### Q-1. Counts ✓
```
surahs = 114, verses = 6236
```
**All 114 stored `verses_count` AND actual `COUNT(quran_verses)` per surah match the canonical ayah counts exactly** (verified against the full canonical array 7,286,…,6). Zero mismatches.

Query (abridged):
```php
$canonical = [1=>7, 2=>286, ... 114=>6];           // full canonical map
$stored = DB::table('quran_surahs')->pluck('verses_count','id');
$actual = DB::table('quran_verses')->groupBy('surah_id')
            ->select('surah_id', DB::raw('count(*) c'))->pluck('c','surah_id');
// → "ALL 114 surah counts MATCH canonical (stored & actual)"
```

### Q-2. Revelation place ✓
```
Mecca = 86, Medina = 28   (canonical ≈ 86 / 28)
```
Spot-checks all correct: Al-Fatiha(1)=Mecca, Al-Baqarah(2)=Medina, At-Tawbah(9)=Medina, An-Nas(114)=Mecca, Al-Ikhlas(112)=Mecca. Al-Insan(76)=Medina and An-Nasr(110)=Medina — both defensible (match quran.com). No famous misclassification found. (The previously-fixed Makki *controller* bug is not a data issue.)

### Q-3. Translation coverage ✓ — EXCELLENT
All five locales are at **100%** (6236/6236 rows each, zero empty/null text):
```
ar=6236  en=6236  fr=6236  tr=6236  ur=6236  en-tl=6236
```
Note: `ur` is **fully populated** (the prompt's "ur 0%" concern does **not** apply here). No gaps.

### Q-4. Tafsir coverage ✓
`quran_verse_tafsirs`: **6236 rows, lang=ar only, all `active=1`, zero empty text → 100% of verses have Arabic tafsir.** No non-Arabic tafsir exists (expected/acceptable).

### Q-5. Spot-checks + transliteration markup ⚠
Arabic text for 1:1, 2:255, 112:1, 36:1, 18:1 is **present and correct** (not empty/garbled).

**Transliteration data is still dirty:** the `en-tl` rows carry tajwid markup `<u>`/`<b>` tags:
```
en-tl rows containing <u> or <b> tags = 6229 / 6236
e.g. 112:1 → "Qul huwa All<u>a</u>hu a<u>h</u>ad<b>un</b>"
```
- The **Blade/Site** layer strips it: `app/Http/Controllers/Site/QuranController.php::cleanTransliteration()` (`strip_tags`) — display is fine.
- **But the public API does NOT strip it:** `app/Http/Resources/TranslationResource.php` returns raw `text`. So `/api/v1/quran/*` leaks `<u>/<b>` tags to API consumers. **The underlying data remains dirty.** → Recommendation FL-3.

### Q-6. Basmala embedded in verse-1 Arabic text ⚠ (REPORTED — not auto-fixed)
The Arabic text of **verse 1 of 110 surahs has the Basmala prepended** into the ayah text itself:
```
112:1 stored AR = "بِسْمِ ٱللَّهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ قُلْ هُوَ ٱللَّهُ أَحَدٌ"
36:1  stored AR = "بِسْمِ ... ٱلرَّحِيمِ يسٓ"
18:1  stored AR = "بِسْمِ ... ٱلرَّحِيمِ ٱلْحَمْدُ لِلَّهِ ..."
```
Breakdown of `verse_number=1` rows:
- **110** surahs: Basmala **embedded** before the real ayah-1 text.
- **1** surah (Al-Fatiha #1): text IS the Basmala — correct (Basmala is ayah 1 there).
- **3** surahs with **no** Basmala prefix: **9, 95, 97**. Surah 9 correctly has none; **95 (At-Tin) and 97 (Al-Qadr) are inconsistent** with the other 110 (they lack the prefix the others have).

**Why this is a problem:** On quran.com the Basmala is a *separate header* for surahs 2–114 (except 9); ayah 1's text does **not** include it. Embedding it means (a) ayah-1 text differs from canonical mushaf text, (b) UIs that render a Basmala header (this app does) show it **twice**, (c) search/highlighting of ayah 1 is skewed.

**Not auto-fixed** because stripping the Basmala from 110 verses is a bulk content mutation with edge cases (the 95/97 inconsistency proves the source data is uneven), and the “correct” canonical ayah-1 text must be sourced, not guessed. → Recommendation FL-1.

---

## 2. HADITH — Findings

### H-6. Totals & per-collection vs sunnah.com ⚠
```
total hadiths = 50,520   collections = 17   books = 406   orphans = 0
```
The dataset is **much larger and broader than the prompt assumed** — it is a wide sunnah.com-style sync, not just the `prophet-data-generator/hadith/production` dir. `total_hadiths` column = the collection's reference target; `actual` = rows present. Nearly every collection is **slightly short of its own stored target** (partial import):

| Collection | actual | stored target | sunnah.com ref | % vs ref |
|---|---|---|---|---|
| Sahih Muslim | 7,458 | 7,459 | ~7,500 | 99.4% |
| Sahih al-Bukhari | 7,064 | 7,277 | ~7,563 | 93.4% |
| Sunan al-Nasa'i | 5,746 | 5,768 | ~5,761 | 99.7% |
| Sunan Abi Dawud | 5,269 | 5,276 | ~5,274 | 99.9% |
| Mishkat al-Masabih | 4,427 | 4,428 | ~6,294 | 70.3% |
| Sunan Ibn Majah | 4,338 | 4,345 | ~4,341 | 99.9% |
| Jami' al-Tirmidhi | 3,995 | 4,053 | ~3,956 | 100%+ |
| Sunan al-Darimi | 3,406 | 3,406 | ~3,406 | 100% |
| Muwatta Malik | 1,983 | 1,985 | ~1,985 | 99.9% |
| Riyad as-Salihin | 1,895 | 1,896 | ~1,896 | 99.9% |
| Bulugh al-Maram | 1,732 | 1,767 | ~1,568 | 100%+ |
| Musnad Ahmad ibn Hanbal | 1,373 | 1,374 | ~28,000+ | **~4.9%** |
| Al-Adab Al-Mufrad | 1,313 | 1,326 | ~1,322 | 99.3% |
| Shama'il Muhammadiyah | 399 | 402 | ~399 | 99.3% |
| 40 Hadith Nawawi | 42 | 42 | 42 | 100% |
| 40 Hadith Shah Waliullah | 40 | 40 | 40 | 100% |
| 40 Hadith Qudsi | 40 | 40 | 40 | 100% |

**Biggest gaps to flag:**
- **Musnad Ahmad ibn Hanbal:** only **1,373** of the full ~28,000+ — i.e. a tiny fraction. This is the single largest completeness gap.
- **Mishkat al-Masabih:** 4,427 vs reference ~6,294 (~70%).
- **Sahih al-Bukhari:** 7,064 vs ~7,563 (≈500 short).
- Most others are within ~1% of their stored target (a handful of hadiths short each — minor import truncation).
- Tirmidhi/Bulugh exceed the loose sunnah.com count due to numbering/sub-hadith differences (not an error).

### H-7. Duplicates ⚠ (informational)
`php artisan hadith:deduplicate --find-only` → **85 duplicate groups** (SHA-256 of normalized Arabic text). Mostly 2 copies of the same narration appearing in different books/collections (legitimate in Islamic scholarship — same hadith cited in multiple contexts; e.g. Bukhari Kitab al-Fara'id ↔ another book). Safe/read-only; **do not auto-merge across collections.**

### H-8. Field integrity ⚠ / ✗
- **Arabic text:** 50,395 / 50,520 = **99.8%**. The **125 missing are all in Muwatta Malik** (likely Balaghat / structural entries without isnad text, or import gaps).
- **English text:** 47,100 / 50,520 = **93.2%** (3,420 without English).
- **`ar_notashkeel` (vowel-stripped Arabic):** only 25,610 rows = **50.7%** — half the corpus lacks the search-normalized Arabic field.
- **`grading_status` is messy free-text — 436 distinct values** ✗. Mixes English (`Sahih`, `Hasan`, `Da'if`), Arabic (`صحيح`, `حسن`, `ضَعِيف`), transliteration variants (`Da'if` / `Da’if` / `daif` / `Da if` / `Da,if`), status tokens (`needs_verification`, `unknown`, `mawquf`), long scholarly annotations (hundreds of unique Arabic sentences), and outright garbage (`[[{`, `1819`, `42 (لم تتمّ دراسته`). This needs normalization to an enum + a free-text `grading_notes` field. A `NormalizesHadithGrading` concern already exists for the Site layer (`app/Http/Controllers/Site/Concerns/NormalizesHadithGrading.php`) — the underlying column is still raw.
- **Ungraded:** 5,035 hadiths have empty/null grading, concentrated in:
  ```
  Sunan al-Darimi   3,406  (100% of the collection — entirely ungraded)
  Musnad Ahmad        533
  Sunan al-Nasa'i     362
  Sunan Ibn Majah     161
  Mishkat             156   Tirmidhi 142   Bulugh 102   Abi Dawud 81   Adab 66   Riyad 26
  ```

### H-9. Orphans ✓
```
hadiths with missing book = 0
books with missing collection = 0
```
Every hadith chains to a valid book → collection. No orphans.

---

## 3. FIXES APPLIED (trivial, unambiguous, low-risk)

### Fix #1 — `bismillah_pre` data: At-Tawbah (Surah 9) → 0  [DATA]
**Problem:** `bismillah_pre = 1` for **all 114 surahs**, but Surah 9 (At-Tawbah) is the one surah with **no Basmala**. With the flag on, the public reader would render a Basmala header above At-Tawbah — a well-known display error.
**Change:**
```php
DB::table('quran_surahs')->where('id',9)->update(['bismillah_pre'=>0]);
```
**Verified:** `Surah 9 bismillah_pre: before=1 after=1→0`; now only surah 9 has `bismillah_pre=0`.
(Surah 1/Al-Fatiha left at `1`: it is already guarded by `$surah['number'] != 1` in the view and its flag is used differently in admin; not safe to flip blindly.)

### Fix #2 — Public surah view guard: exclude At-Tawbah  [CODE]
**File:** `resources/views/public/quran/surah.blade.php` (line ~237).
The Basmala block’s own comment said “except At-Tawbah #9” but the condition only excluded #1. Made the guard match the comment (defensive — protects against a future bad re-import):
```diff
- {{-- Bismillah (shown for all surahs except At-Tawbah #9) --}}
- @if($surah['bismillahPre'] && $surah['number'] != 1)
+ {{-- Bismillah (shown for all surahs except Al-Fatiha #1, where it is verse 1, and At-Tawbah #9, which has no Basmala) --}}
+ @if($surah['bismillahPre'] && $surah['number'] != 1 && $surah['number'] != 9)
```
**Verified:** `php -l` clean. (Blade file — not Pint-scoped; no `.php` source files were modified, so Pint has nothing to reformat from this change.)

---

## 4. PRIORITIZED FIX LIST (recommendations — NOT applied)

| # | Pri | Type | Issue | Action |
|---|---|---|---|---|
| FL-1 | **P1** | DATA | Basmala embedded in ayah-1 text of 110 surahs (Q-6); 95 & 97 inconsistent | Re-import canonical ayah-1 text (Basmala as separate header only). Source canonical Uthmani text per verse; **do not** hand-strip — verify against quran.com per surah. Affects `quran_verse_translations` (lang=ar) verse_number=1. |
| FL-2 | **P1** | DATA | Musnad Ahmad ~1,373 of ~28,000+ (H-6) | Largest content gap. Re-import full Musnad Ahmad: `php artisan hadith:import <path-to-ahmad> --collection-id=<id>` (auto-dedup protects against dupes). |
| FL-3 | **P1** | CODE | API leaks raw tajwid `<u>/<b>` in transliteration (Q-5) | In `app/Http/Resources/TranslationResource.php`, strip tags for `lang='en-tl'` (reuse `TextSanitizer::cleanTextForDisplay()` / the Site's `cleanTransliteration` logic). Optionally backfill clean `en-tl` text so data is clean at rest. |
| FL-4 | **P2** | DATA | `grading_status` = 436 messy free-text values (H-8) | Add a normalized `grading` enum column (Sahih/Hasan/Da'if/Mawdu'/Mawquf/Maqtu'/Ungraded/NeedsVerification) + keep original verbose text in `grading_notes`. Build a mapping table (English + Arabic + transliteration variants) and a backfill command. Quarantine garbage values (`[[{`, `1819`, …) for manual review. |
| FL-5 | **P2** | DATA | Sunan al-Darimi 100% ungraded; +1,629 others ungraded (H-8) | Source gradings (e.g. from sunnah.com grade field) and backfill `grading_status`/new enum. |
| FL-6 | **P2** | DATA | Mishkat ~70%, Bukhari ~500 short (H-6) | Re-run import for Mishkat & Bukhari to reach stored targets; verify counts after. |
| FL-7 | **P3** | DATA | 125 Muwatta Malik hadiths missing Arabic text (H-8) | Inspect those 125 (likely Balaghat without isnad). Either backfill Arabic or mark as structural; ensure UI handles empty-text gracefully. |
| FL-8 | **P3** | DATA | `ar_notashkeel` only 50.7% populated (H-8) | Backfill vowel-stripped Arabic for all hadiths (derive from `lang=ar` text) to make Arabic search reliable. |
| FL-9 | **P3** | INFO | 85 cross-collection duplicate groups (H-7) | Leave as-is (legitimate). Do NOT auto-merge across collections; `--merge` only within a collection after manual review. |

---

## 5. Queries / commands run (reproducibility)

```bash
# table discovery
/opt/homebrew/bin/php artisan tinker --execute="Schema::getColumnListing('quran_surahs') ..."

# Quran counts & per-surah verification (full canonical map compared to stored + actual)
/opt/homebrew/bin/php artisan tinker --execute="<canonical-map compare>"

# revelation_place split + spot-checks (1,2,9,76,110,112,114)
# translation coverage per lang (ar,en,fr,tr,ur,en-tl) + empty-text counts
# tafsir count + active flag + empty-text
# Basmala-in-verse-1 detection (verse_number=1, mb_strpos 'بِسْمِ')
# bismillah_pre distribution (was 114×1)

# Hadith
/opt/homebrew/bin/php artisan tinker --execute="<totals, per-collection join, orphans, grading dist, ar/en coverage>"
/opt/homebrew/bin/php artisan hadith:deduplicate --find-only      # 85 groups

# Fixes
/opt/homebrew/bin/php artisan tinker --execute="DB::table('quran_surahs')->where('id',9)->update(['bismillah_pre'=>0]);"
# edited resources/views/public/quran/surah.blade.php  (Bismillah guard +#9)
```
