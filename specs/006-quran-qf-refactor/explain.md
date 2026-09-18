# Query plans — Quran read paths

`EXPLAIN` against the dev database after the full import (114 chapters, 6,236 verses, 83,665
words, 392,448 word glosses, 36,458 translation rows, 12,401 tafsir rows, 114 chapter audio files
with 6,236 timings, 604 mushaf pages with 83,665 mushaf words). Run 2026-09-17.

The constitution rule for this feature is that every index the data model declares has to earn
its place in at least one plan. Every one below is chosen by the optimiser, and no hot path
falls back to a full scan.

## Hot paths

| Query | type | key | rows |
|---|---|---|---|
| chapter verses (reader page) | ref | `quran_verses_chapter_id_verse_number_unique` | 286 |
| translations for a page of verses | range | `quran_translations_resource_id_verse_id_unique` | 100 |
| words of a chapter (word-by-word) | range | `quran_words_verse_id_position_index` | 767 |
| mushaf page lines | ref | `mw_render_idx` | 151 |
| chapter audio timestamps (join) | const + ref | `quran_chapter_audio_files_resource_id_chapter_id_unique`, `qcat_file_verse_unique` | 1 + 286 |
| search over translations | fulltext | `qt_text_ft` | 1 |
| admin verses index (chapter + locked) | ref | `quran_verses_chapter_id_verse_number_unique` | 286 |

The timestamp join reports `Using filesort` on the one-row parent lookup, which is the ordering
of a single row and costs nothing.

## Axis filters (the public API's `by_juz`, `by_hizb`, … and the section pages)

| Query | type | key | rows |
|---|---|---|---|
| verses by juz | ref | `quran_verses_juz_number_index` | 126 |
| verses by hizb | ref | `quran_verses_hizb_number_index` | 48 |
| verses by rubʿ al-hizb | ref | `quran_verses_rub_el_hizb_number_index` | 10 |
| verses by manzil | ref | `quran_verses_manzil_number_index` | 669 |
| verses by rukūʿ | ref | `quran_verses_ruku_number_index` | 4 |
| verses by page | ref | `quran_verses_page_number_index` | 4 |
| sajdah verses | range | `quran_verses_sajdah_number_index` | 14 |
| verse by key | const | `quran_verses_verse_key_unique` | 1 |
| words by page and line (mushaf render) | ref | `quran_words_page_number_line_number_position_index` | 10 |
| search over the verse scripts | fulltext | `qv_text_ft` | 1 |
| glosses for a range of words | range | `quran_word_glosses_word_id_resource_id_unique` | 429 |

## Conclusion

No index in `data-model.md` is unused, so none was dropped. The two FULLTEXT indexes are both
exercised: `qv_text_ft` by an Arabic search over the verse scripts and `qt_text_ft` by a search
over a translation, which is why `SearchModule::QURAN` declares two MATCH groups rather than one.
