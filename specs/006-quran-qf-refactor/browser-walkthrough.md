# Browser walkthrough — Quran module (006)

Manual pass in Chrome against the dev server with the imported Quran Foundation data
(114 chapters, 6,236 verses, 83,665 words, 36,458 translation rows, 12,401 tafsir rows,
114 chapter audio files with timings, 6,236 ayah audio files).

Each item is ticked only after it was actually exercised in the browser. Anything that
fails gets a defect note under the item rather than a silent untick, so the list stays
the record of what was checked and what is left.

**Legend:** `[ ]` not run · `[X]` passed · `[!]` failed, defect noted · `[-]` blocked

---

## A. Public pages (no login)

### A1. Hub and navigation
- [X] `/quran` renders in Arabic, RTL, with the chapter grid
- [X] Chapter cards show Arabic name, English name, verse count and revelation place
- [X] Verse of the day block renders a real verse
- [X] Continue reading appears after visiting a chapter
- [ ] Revelation-order sort toggles the grid order
- [X] Divisions browser lists juz / hizb / rubʿ / manzil / rukūʿ
- [ ] Command bar opens and searches (chapter name, verse key, Arabic text)
- [X] Dark mode repaints the hub

### A2. Chapter reader `/quran/2`
- [X] Mushaf (reading) view renders continuous Arabic with verse markers
- [X] Verse-card view renders Arabic + translation + transliteration
- [X] Footnote from the translation renders under the verse
- [ ] Script switcher changes the rendered script (Uthmani → IndoPak → Tajweed)
- [ ] Tajweed script shows coloured markup, not raw tags
- [ ] Word-by-word popover shows gloss and transliteration
- [X] Chapter info panel shows text and its source line
- [X] Reciter picker lists chapter reciters and ayah recitations
- [ ] Audio plays and the verse highlight follows the timings
- [ ] Font size control changes the Arabic size
- [ ] Prev/next chapter navigation works
- [X] Dark mode repaints the reader

### A3. Other public pages
- [X] `/quran/page/42` renders the mushaf page
- [X] `/quran/juz/3` renders the juz
- [ ] `/quran/hizb/5`, `/quran/rub/17`, `/quran/manzil/1`, `/quran/ruku/35` render
- [X] `/quran/search?q=الرحمن` returns hits with highlighting
- [X] `/quran/reciters` lists both reciter kinds with filters
- [ ] `/quran/reciters/{slug}` shows the chapter list with durations
- [ ] `/quran/translation/{resource}` renders the translation resource page
- [ ] `/quran/tafsir/{resource}` renders the tafsir resource page
- [ ] `/quran/verse/2:255` renders a single verse
- [ ] `/quran/random` redirects to a verse
- [ ] `/quran/playlists` renders the gallery
- [ ] `/quran/ask` renders and the inquiry search returns local verses
- [X] `/quran/live` 301s to `/media/live`
- [ ] No console errors on any page above

## B. Admin — requires sign-in

### B1. Chapters
- [X] Index lists 114 chapters with filters and search
- [X] Show page: verses, chapter infos, audio coverage
- [X] Edit: change the localized name and save (read back after reload)
- [ ] Edit: toggle publish and confirm the public page follows
- [ ] Validation: submitting an invalid payload shows field errors, not a 500

### B2. Verses and words
- [X] Index filters by chapter, juz, page, sajdah, locked
- [X] Show page renders every script, words, translations, tafsirs, audio
- [ ] Edit a script field and save
- [ ] Lock a verse, then confirm sync reports it as skipped
- [ ] Bulk lock/unlock a selection
- [X] Word glosses screen saves a gloss

### B3. Resources (per kind)
- [ ] Index for translations, tafsirs, chapter infos, recitations, chapter reciters
- [ ] Create a manual resource
- [ ] Edit name, author, language, order
- [ ] Toggle active / default / sync-enabled
- [ ] Reorder
- [ ] Show page: coverage stats
- [ ] Catalogue screen lists what QF has that is not imported
- [ ] Delete a manual resource
- [ ] Reciter form: source + reference id with the Lookup button
- [ ] Reciter show: Fetch data / audio / timestamps queue a job and report status

### B4. Rows (translations, tafsirs, chapter infos)
- [ ] Rows screen lists per resource and chapter
- [ ] Create a row
- [ ] Edit a row
- [ ] Delete a row
- [ ] Import rows for the resource

### B5. Audio
- [ ] Ayah files screen paginates per recitation
- [ ] Add a file by URL
- [ ] Fill from a URL template
- [ ] Chapter audio screen lists the 114 files
- [ ] Timestamps editor renders and saves

### B6. Sections, mushafs, languages
- [ ] Sections index per axis, show page with the verse mapping
- [ ] Re-import sections
- [ ] Mushafs index and show (page viewer with lines)
- [ ] Mushaf: toggle active/default, download fonts, resync
- [ ] Languages: create, edit, toggle, delete

### B7. Sync dashboard
- [X] Index shows the state row, last run and per-resource freshness
- [ ] Run detail shows mutations, skipped locked rows and errors
- [!] Trigger an incremental sync from the dashboard — queues correctly (run #2), but the
      job is consumed by the queue worker of the **main checkout**, which has no
      `quran:qf:sync` command and fails it with "Command is no longer allowed". See F6.
- [ ] Trigger a bootstrap
- [ ] Trigger a download-assets run

### B8. Playlists and AI inquiries
- [ ] Playlists index, edit, toggle public/featured, coverage
- [ ] AI inquiries index, show, answer, publish, delete

### B9. Command runner (Settings → Commands)
- [X] The Quran command group lists the `quran:qf:*` commands
- [X] Running a safe command (`quran:qf:doctor`) records a run and shows its output
- [ ] A queued command reports its run status
- [ ] A missing required argument is rejected before execution (422)
- [ ] A command outside the whitelist is refused (403)

### B10. Permissions
- [ ] An editor without `sync quran` cannot reach the sync actions
- [ ] A viewer cannot reach the write actions

---

## Findings — round 1 (2026-09-17)

### F1. The hub had no top tabs, and the surah list was not one of them — FIXED
Reported by the owner. The axes (juz, hizb, rubʿ, manzil, rukūʿ) were a section at the
bottom of the page with their own tab row, and the surah grid was simply always on, so
there was no single place that said "you are browsing surahs, here are the other axes".

Rebuilt as one tab bar directly above the listing: **السور** (selected by default) plus a
tab per axis. The surah toolbar, counter, grid/list views and empty state now live in the
surah panel; each axis panel is its own tab panel. The choice is deep-linkable with
`?browse=juz`, and the URL updates without a reload. New key `quran.public.surahs_tab` in
all five locales.

### F2. The reciters directory looked duplicated and nearly empty — FIXED
Reported by the owner. Two separate causes:

1. **Nearly empty.** The catalogue held 21 chapter reciters and 12 ayah recitations, but
   only three had been activated during the rollout, so the public directory listed three
   entries. All 33 are now active and their audio is fetched: 2,394 chapter files, and the
   ayah recitations are filling in at 6,236 files each.
2. **Looked duplicated.** Quran Foundation lists the same reciter once per recording —
   Abdul Basit as Mujawwad and as Murattal, al-Husary as Murattal and as Muallim, and the
   same reciter both verse-by-verse and as whole surahs. Those rows carry identical names,
   so the cards read as duplicates. A repeated name is now qualified with its style, and if
   that still does not separate two rows, with its recording kind. The card meta line also
   shows the kind, which is useful in its own right. Verified: zero duplicate display names
   across the 33 reciters.

### F3. "New features not applied on the frontend" — partly a visibility problem
The features from the plan are implemented, but several sat behind the tools rail or below
the fold, which is why the page read as unchanged. Confirmed present in the browser: nine
scripts in the display panel (Uthmani, simple, tajweed, Imlaei, IndoPak, Nastaleeq, QPC
Hafs, QPC Nastaleeq), word-by-word toggle, translation picker (5 editions), tafsir picker
(2), reciter picker (2 kinds), chapter info with its source, footnotes, transliteration,
verse image link, verse of the day, continue reading, revelation-order sort, command bar.

Still genuinely missing, and tracked: the hadith-references and reflections panels, which
are blocked on Quran Foundation scopes (`hadith: not_found`, `reflect: insufficient_scope`).

### F4. Reciters have no photos — no source, not a bug
Reported by the owner. Quran Foundation's reciter catalogue carries no image at all: a
`/resources/chapter_reciters` row is `id`, `name`, `style`, `qirat` and `translated_name`,
nothing more. Every `quran_resources.image` is therefore null (0 of 233), and the card
falls back to the initial-letter avatar.

The photos the old site had came from the reciter-profile model that was scraped from
Wikidata, and that whole subsystem was dropped from scope by the refactor decision. No old
image files survive on disk either.

Two ways forward, neither of them started:
1. **Editorial.** The admin resource form already accepts an image per resource
   (`image`, max 5 MB), so a photo can be uploaded reciter by reciter today.
2. **A new importer.** Pulling portraits in bulk needs a source that actually has them —
   Wikidata/Wikimedia Commons — which means reviving a dropped importer, with the licence
   and attribution questions that come with it.

## Findings — round 2 (2026-09-17, admin)

### F5. صورة الآية never appears — FIXED (feature now hidden, source is dead)
Reported by the owner. Every verse carries an `image_url` from Quran Foundation pointing at
`c22506.r6.cf1.rackcdn.com`. That host resolves but refuses connections, so the image never
loads; nothing local exists because the image download was never run, and it would fail
against the same dead host. The reader now exposes an image only when we hold a local copy,
so the dead "صورة الآية" control is gone. Re-running `quran:qf:download-assets --images`
against a working source would light it back up.

### F6. A queued admin command is executed by the wrong checkout — environment, not code
Triggering the incremental sync from the dashboard created run #2 and queued it correctly.
It failed three seconds later with "Command is no longer allowed: quran:qf:sync". The cause
is outside this branch: a `queue:listen` started from the **main checkout** (`composer dev`,
12:57) shares this database, picked the job up, and that checkout has none of the new Quran
commands. Inline commands such as the doctor run in the web process and are unaffected.
To exercise queued commands here, run the worker from this worktree.

### F7. Feature and API gaps against quran.com and the QF catalogue
Written up in `gap-analysis.md`. The headline: the **Answers** family (answers per ayah,
answer threads, counts) is in the Quran Foundation catalogue and on quran.com's ayah tabs,
and was never in our plan — unlike hadith and reflections, it is not scope-blocked. Also
missing: related content per ayah, private notes and collections (User API), reading goals
and learning plans, a font-family picker, and "copy verse as glyphs".

### F8. Words rendered out of order in the mushaf view — FIXED (critical)
Reported by the owner as "surah 11 is missing words". Nothing was missing: chapter 11 holds
all 2,040 words, matching what Quran Foundation returns for it, and every verse of every
chapter has its words and glosses. The words were **out of order**. The opening الٓر of
11:1 rendered ninth, between حَكِيمٍ and خَبِيرٍ, which reads as a missing word.

**Cause.** The renderer ordered words inside a line by Quran Foundation's
`position_in_line`. That index contradicts the page order on **8,191 of the 8,820 lines** —
the first word of 11:1 carries 9 while the rest of its line run 1..10. `position_in_page` is
complete (no nulls, no zeros) and every line's values form one contiguous block, so it is
the reliable reading order for both the lines and the words within them.

**Fix.** Order by `position_in_page` in the three places that rendered layout:
`EloquentMushafRepository::chapterLines()`, `::pageLines()` and `MushafPage::wordsQuery()`.

**Proof it is gone everywhere.** Every chapter was rendered through the same repository the
reader uses and its word sequence compared against the canonical `chapter:verse:position`
order: **114 chapters, 77,429 words, 0 out of order.** A regression test
(`ReadWriteRepositoriesTest::test_chapter_lines_follow_the_page_order_not_the_line_index`)
feeds exactly the 11:1 shape — a first word carrying `position_in_line` 9 — and fails if the
per-line index is ever used for ordering again.

### F9. Only a fraction of the catalogue was switched on — FIXED
Reported by the owner as "not all tafsir resources exist like in quran", and it was the same
root cause as the reciters: the import brought the catalogue in, but the rollout only enabled
a handful. 23 tafsirs and 144 translations were sitting inactive with no content.

All tafsir, translation and chapter-info resources are now active and sync-enabled, and their
content is being synced. The reader's translation picker already groups editions by language,
so 143 of them stay navigable.

### F10. `quran_translations.text` was too small for some translations — FIXED (schema)
Surfaced by the full sync: `SQLSTATE[22001] Data too long for column 'text'`. The column was
TEXT — 65,535 *bytes*, roughly 21k characters once the text is Arabic or accented Latin —
while several Quran Foundation translations carry inline commentary past that. Tafsirs were
already LONGTEXT for exactly this reason.

Widened to LONGTEXT, in the create migration for fresh installs and in
`2026_09_18_000011_widen_quran_translation_text` for databases already migrated. The `down()`
refuses to narrow the column while any row would be truncated, rather than silently losing an
ayah. The sync reported the failure rather than writing a truncated verse, which is how the
defect was caught at all.

### F11. Sync state columns capped how much of the catalogue could be enabled — FIXED (schema)
The run after F10 applied its changes and then died writing its own bookkeeping:
`Data too long for column 'sync_token'`. Quran Foundation's token encodes the canonical
resource filter, so with 176 resources enabled it passed 1,400 characters against a
VARCHAR(500). `resources_filter` was VARCHAR(1000) and already stood at 769.

Both are now TEXT, in the create migration and in
`2026_09_18_000012_widen_quran_sync_state_columns`. Uniqueness lives on `filter_hash`, so
neither column is indexed and neither needs a length. This is the worst of the three width
bugs: the failure landed *after* the data was written, leaving no token to resume from, so
each retry started over.

Three schema ceilings in one session — translation text, sync token, resources filter — all
of the same shape: a fixed width on a value that grows with how much of the catalogue is
switched on.

## Blocked

- **Section B (admin) needs a signed-in session.** I do not enter passwords, so the admin
  walkthrough starts once someone signs in to the admin panel in the Chrome tab.

## Defects found

_(none outstanding; F1 and F2 are fixed, F3 is answered above)_
