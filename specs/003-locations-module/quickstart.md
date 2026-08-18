# Quickstart: Locations Module

**Feature**: `003-locations-module` | **Date**: 2026-08-18

Runbook for applying the feature and verifying it did what it claims. Every check below is
something you run, not something you assume (constitution X).

---

## Before you start

Record the baseline. These are the numbers the success criteria are measured against, and you
cannot claim an improvement without them.

```bash
php artisan tinker --execute="
echo 'events total:            '.DB::table('seerah_events')->count().PHP_EOL;
echo 'events naming a place:   '.DB::table('seerah_events')->whereNotNull('location')->where('location','<>','')->count().PHP_EOL;
echo 'events with coordinates: '.DB::table('seerah_events')->whereNotNull('latitude')->count().PHP_EOL;
echo 'muslims with birthplace: '.DB::table('muslims')->whereNotNull('birth_place')->count().PHP_EOL;
echo 'surahs:                  '.DB::table('quran_surahs')->count().PHP_EOL;
"
```

Expected on the current dataset: `578 / 279 / 2 / 6677 / 114`. If yours differ, use yours — the
projections in `spec.md` scale with them.

---

## Apply

```bash
# 1. Schema — two new tables, three nullable foreign keys, nothing dropped
php artisan migrate

# 2. Vocabulary — ~55 canonical places. Idempotent, keyed on slug.
php artisan db:seed --class=LocationSeeder

# 3. Preview the resolution. Writes NOTHING. Read the report before proceeding.
php artisan locations:backfill --dry-run

# 4. Apply it
php artisan locations:backfill

# 5. Blade changed, and there is no Vite dev server here
npm run build
```

Step 3 is not optional. The report tells you what will be resolved and what will be left for an
editor; if the no-match count is far above expectation, the alias list is wrong and it is much
cheaper to find that out before the write.

---

## Verify

### 1. The vocabulary seeded cleanly

```bash
php artisan tinker --execute="
echo 'locations:    '.DB::table('locations')->count().PHP_EOL;
echo 'translations: '.DB::table('location_translations')->count().PHP_EOL;
echo 'with coords:  '.DB::table('locations')->whereNotNull('latitude')->count().PHP_EOL;
echo 'roots:        '.DB::table('locations')->whereNull('parent_id')->count().PHP_EOL;
echo 'max depth:    '.DB::table('locations')->max('depth').PHP_EOL;
"
```

Expect ~55 locations, ~275 translations, every location with coordinates, a handful of roots, and
max depth 3. **A location with no coordinates is a seeder defect** — it renders a place name with no
map, which is the thing this feature exists to fix.

### 2. The map gate actually opened (SC-001)

```bash
php artisan tinker --execute="
\$own = DB::table('seerah_events')->whereNotNull('latitude')->count();
\$viaLoc = DB::table('seerah_events as e')
    ->join('locations as l','l.id','=','e.location_id')
    ->whereNull('e.latitude')->whereNotNull('l.latitude')->count();
echo 'maps before: '.\$own.PHP_EOL;
echo 'maps after:  '.(\$own + \$viaLoc).PHP_EOL;
"
```

Target: at least **180**. Then open a real event page in the browser and confirm the map renders —
the query proves the data is there, not that the view uses it.

### 3. Spelling drift collapsed (SC-003)

```bash
php artisan tinker --execute="
\$id = DB::table('locations')->where('slug','al-madinah')->value('id');
echo 'resolved to Madinah: '.DB::table('muslims')->where('birth_place_id',\$id)->count().PHP_EOL;
foreach (['Medinah','Medina','Madinah'] as \$v) {
  echo str_pad(\$v,10).DB::table('muslims')
      ->where('birth_place', json_encode(['en'=>\$v], JSON_UNESCAPED_UNICODE))
      ->whereNull('birth_place_id')->count().' still unresolved'.PHP_EOL;
}
"
```

All three spellings must report **0 still unresolved**, and the resolved count must be at least
1,419.

### 4. Resolution is idempotent (SC-006, FR-012)

```bash
php artisan locations:backfill        # second run
```

Every `resolved` column must read **0** and every row must land in `already-assigned`. If the second
run resolves anything, the command is not idempotent and the write rule is wrong.

Then confirm a manual assignment survives:

```bash
php artisan tinker --execute="
\$e = DB::table('seerah_events')->whereNull('location_id')->where('location','<>','')->first();
DB::table('seerah_events')->where('id',\$e->id)->update(['location_id'=>1]);
echo 'set event '.\$e->id.' to location 1'.PHP_EOL;
"
php artisan locations:backfill
php artisan tinker --execute="echo DB::table('seerah_events')->where('id',<ID>)->value('location_id').PHP_EOL;"
```

Must still print `1`.

### 5. Nothing was lost (SC-007)

```bash
php artisan tinker --execute="
echo 'events with prose:  '.DB::table('seerah_events')->where('location','<>','')->count().PHP_EOL;
echo 'muslims with text:  '.DB::table('muslims')->whereNotNull('birth_place')->count().PHP_EOL;
echo 'surah classifications: '.DB::table('quran_surahs')->whereNotNull('revelation_place')->count().PHP_EOL;
"
```

Must match the baseline exactly: `279 / 6677 / 114`. Any drop is a defect.

### 6. Module isolation held

```bash
composer deptrac
```

Must pass **with no new baseline entry**. If `Shared` now imports a domain module, `Location`
acquired a relation it may not have — see `research.md` R-06.

### 7. Query counts did not regress (SC-009)

Load the Seerah event timeline and the companion directory with the query log on, and compare
against the baseline. Constitution II: eager loads must name their columns and the parent select
must include the foreign key. A place name rendering per row means the eager load is missing.

### 8. The gates

```bash
php artisan test
./vendor/bin/pint
composer deptrac
```

All three green. Then verify in the browser, **in Arabic**: the locations admin screens, a Seerah
event page, a companion profile, and a surah page — RTL layout, no physical `left-`/`right-`
positioning, both themes.

---

## Rollback

The migration is fully reversible and drops nothing that existed before:

```bash
php artisan migrate:rollback --path=database/migrations/<the three files>
```

Per constitution VIII, roll back by explicit migration name. **Do not use `--step=N`** — N counts
migration files, not tables, and silently reaches beyond the intended set.

Rolling back drops `location_id`, `birth_place_id` and `revelation_location_id`, then the two new
tables. `seerah_events.location`, `muslims.birth_place` and `quran_surahs.revelation_place` are
untouched throughout, so the application returns to exactly its prior behaviour — including the
2-of-578 map, which is the state being fixed.

---

## What is expected to remain unresolved

Not defects. Roughly 73 Seerah events name no resolvable place, because the text describes a
position rather than a location:

```
المنطقة المحايدة بين الجيشين
أدنى مياه بدر
الطريق إلى المدينة والمسجد النبوي
```

And ~19 companions have genuinely ambiguous birthplaces (`Makkah or Medinah`, `Yemen/Makkah`).
These surface in the `?unmapped=1` queue for an editor. They are the feature working as specified —
`spec.md` FR-011 requires them to be left alone rather than guessed.
