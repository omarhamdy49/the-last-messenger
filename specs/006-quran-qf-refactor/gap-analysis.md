# Gap analysis — against quran.com and the Quran Foundation Content API

Checked 2026-09-17: quran.com in Chrome, and the endpoint catalogue in
`https://api-docs.quran.foundation/llms.txt` (96 documented Content API entries).

## 1. Against the Content API catalogue

### Covered
Chapters (list, single, info), verses on every axis (chapter, juz, hizb, rubʿ, manzil,
rukūʿ, page, range, key, random), all 11 scripts, the five section axes, pages and page
lookup, whole-resource translations and tafsirs, ayah recitation files, chapter
recitations and their timestamps, timestamp and lookup by time, resource catalogues per
kind with their info blurbs, languages, recitation styles, footnotes, and search.

Sync and snapshot endpoints are consumed by the importer rather than re-exposed, which is
the point of the refactor: our API serves our tables.

### Different shape, same data
Quran Foundation publishes a sub-resource URL per axis — `/translations/{id}/by_juz/{n}`,
`/tafsirs/{id}/by_page/{n}`, `/recitations/{id}/by_ruku/{n}` and so on, about 24 routes.
We expose one route per resource kind and take the axis as a query filter, so
`/quran/translations/{id}?juz_number=3` returns what their `by_juz/3` does. Worth adding
the aliases only if a client is written against their exact paths.

### Not implemented
| Area | Endpoints | Why |
|---|---|---|
| Hadith for an ayah | references, bodies, count-within-range | `quran:qf:doctor` reports `hadith: not_found` for our credentials |
| Quran Reflect | feed, post by id, comments, user posts | `reflect: insufficient_scope` |
| **Answers** | answers for an ayah, answer thread by question id, count within a range | **Never in our plan.** This is the Q&A that quran.com shows as its "Answers" tab. |

The Answers family is the one genuine omission from the design rather than a scope block.
It needs its own tables, importer and panel, in the same shape as the deferred hadith and
reflections work.

## 2. Against quran.com

### We match
Reading and verse-by-verse modes, script switching (Uthmani, IndoPak, Tajweed and six
more), font size, word-by-word with per-word audio, multiple translations, tafsir panel,
chapter info, reciter selection with verse highlighting, night mode, search, juz/hizb/page
navigation, copy and share per ayah, bookmarks.

### They have, we do not
| Feature | Notes |
|---|---|
| **Lessons** tab per ayah | Quran Reflect lessons — blocked on the `post.read` scope |
| **Reflections** tab per ayah | same block |
| **Answers** tab per ayah | the omission above; not scope-blocked, simply unplanned |
| **Related Content** per ayah | related verses and hadith for the ayah |
| **Notes** | private per-ayah notes; quran.com backs these with the User API, we have favourites only |
| **Collections** | user-made bookmark groups, also User API |
| Reading goals and streaks | product feature on their account system |
| Learning plans | course-style guided reading |
| Font-family picker | they offer a font per script (e.g. King Fahad Complex); we ship the QCF glyph mode and one family per script |
| "Copy verse as glyphs" | a copy option that preserves the glyph encoding |

### We have, they do not
Visitor playlists of whole-surah recordings, the Ask-Quran AI answer with grounded verse
citations, and an admin panel over every resource, row and audio file.

## 3. Suggested order, if these are wanted

1. **Answers** — the only unplanned API family, and the most visible missing tab.
2. **Related content per ayah** — pairs with the hadith work already specified.
3. **Notes and collections** — needs the Quran Foundation User API and signed-in users,
   which is a larger piece than it looks.
4. Font-family picker and glyph copy — small, self-contained reader settings.
5. Per-axis URL aliases — only for byte-level API compatibility.


---

# Feature backlog from the quran.com sweep (2026-09-18)

Enumerated in Chrome on quran.com: the ayah action menu, the ayah tabs, the single-ayah
page and the settings drawer. Each row says what it needs from us and whether the Quran
Foundation API can supply it today.

## Already done in this session
- [X] Reciter list opens straight to the list — the player popover was two nested selects
      (person, then recording); it is now one scrollable list of every recording.
- [X] Reciter list is searchable — a filter box over both names, the style and the kind.
      Typing "husary" narrows 33 rows to the 4 Husary recordings.
- [X] No duplicate reciters — a repeated name is qualified by style, then by recording kind,
      in both the public directory and the player list.
- [X] "Play from here" uses the whole-surah recording — verse-by-verse files are now only a
      fallback for a reciter that has no whole-surah file, never a mode the follow toggle
      switches into. The follow toggle now controls auto-scroll, which is what it looked like
      it did.
- [X] Switching reciter keeps the verse — it used to carry the wall-clock offset across, so
      3:41 into one reciter landed on a different ayah in another. It now resumes at the same
      ayah using the new recording's timings.
- [X] The default reciter prefers one with a whole-surah recording, so a first-time listener
      gets seek-in-one-file behaviour rather than chained verse files.

## Missing, with a source
| Feature (as seen on quran.com) | What it needs | Source status |
|---|---|---|
| **Answers** tab under an ayah ("Read Questions and Answers") | tables + importer + panel | QF documents three endpoints; **no scope block** — this was simply never in our plan |
| **Related hadith** for an ayah, linked into our own hadith pages | `quran_verse_hadith_references` + the `HadithReferenceLookup` contract, both already specified in T131–T133 | QF `hadith` capability returns `not_found` for our credentials |
| **Reflections** and **Lessons** tabs | tables + importer + panels (T134) | QF `reflect` returns `insufficient_scope` |
| **Related Content** (related verses) | link table | no dedicated endpoint found in the catalogue; would be our own editorial mapping |
| **Notes** per ayah | user-owned notes | QF User API, needs signed-in users |
| **Collections** (bookmark groups) | user-owned collections | QF User API |
| Reading goals / streaks, Learning Plans | product features on their account system | out of the Content API |

## Missing, buildable now from what we already hold
| Feature | Note |
|---|---|
| **Ayah modal**: one click on a verse opens translation + tafsir + word-by-word together | We have all three as separate panels; this is a composition, not new data |
| **Word pronunciation**: tap a word to hear it read properly | `quran_words.audio_url` is already imported for all 83,665 words |
| **Advanced copy** (choose translations/footnotes, copy as text or glyphs) | all fields are local |
| **Repeat verse / repeat range** with a play count | timings are local |
| **Pin & compare** two translations side by side | translations are local |
| **Font-family picker** per script | needs the font files we already downloaded |
| **Embed widget** for an ayah | a small public route |
| **Translation feedback** link | editorial |

## Suggested order
1. Ayah modal + word pronunciation + advanced copy — all local data, highest visible return.
2. Answers — the one API family we never planned; same shape as the deferred hadith work.
3. Repeat verse/range and pin-and-compare — local, reader-level polish.
4. Hadith references and reflections — the moment the scopes are granted.
5. Notes and collections — needs the User API and sign-in, the largest piece.
