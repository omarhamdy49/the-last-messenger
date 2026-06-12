<?php

declare(strict_types=1);

/*
 * Source locations for the islamic-data:* import commands.
 * The prophet-data-generator workspace lives beside this Laravel app,
 * so defaults resolve relative to the repo's parent directory.
 */
return [
    'quran_json' => env(
        'ISLAMIC_DATA_QURAN_JSON',
        dirname(base_path()).'/prophet-data-generator/quran/holy_quran_utf8.json',
    ),

    'hadith_production_dir' => env(
        'ISLAMIC_DATA_HADITH_DIR',
        dirname(base_path()).'/prophet-data-generator/hadith/production',
    ),

    // Tanzil-format translation/tafsir datasets bundled inside this repo.
    'quran_datasets_path' => env('ISLAMIC_DATA_QURAN_DATASETS', 'database/data/quran'),

    /*
     * Production directory name => hadith_collections.source_slug.
     * Used by islamic-data:verify to compare source file counts with DB rows.
     */
    'hadith_collection_map' => [
        'abudawud' => 'abudawud',
        'ahmed' => 'musnad-ahmad-ibn-hanbal',
        'aladab_almufrad' => 'adab',
        'bukhari' => 'bukhari',
        'bulugh_almaram' => 'bulugh',
        'darimi' => 'darimi',
        'ibnmajah' => 'ibnmajah',
        'malik' => 'malik',
        'mishkat_almasabih' => 'mishkat',
        'muslim' => 'muslim',
        'nasai' => 'nasai',
        'nawawi40' => 'the-forty-hadith-of-imam-nawawi',
        'qudsi40' => 'the-forty-hadith-qudsi',
        'riyad_assalihin' => 'riyadussalihin',
        'shahwaliullah40' => 'the-forty-hadith-of-shah-waliullah',
        'shamail_muhammadiyah' => 'shamail',
        'tirmidhi' => 'tirmidhi',
    ],

    // Canonical Quran totals used as verification floor.
    'expected' => [
        'surahs' => 114,
        'verses' => 6236,
    ],

    /*
     * Verified import floors per production directory (audited 2026-06-11).
     * File counts overstate content: collections contain in-source duplicate files
     * (same hadith split across files, e.g. shamail has 402 files / 400 hadiths) and
     * derivative collections (bulugh, nawawi40, ...) whose files cite primary sources.
     * Floors equal the converged import result verified against distinct source
     * (book_number, hadith_number) pairs. A DB count below its floor = data loss.
     */
    'expected_hadiths' => [
        'abudawud' => 5269,
        'ahmed' => 1373,
        'aladab_almufrad' => 1315,
        'bukhari' => 7149,
        'bulugh_almaram' => 1767,
        'darimi' => 3406,
        'ibnmajah' => 4342,
        'malik' => 1985,
        'mishkat_almasabih' => 4427,
        'muslim' => 7459,
        'nasai' => 5751,
        'nawawi40' => 42,
        'qudsi40' => 40,
        'riyad_assalihin' => 1896,
        'shahwaliullah40' => 40,
        'shamail_muhammadiyah' => 400,
        'tirmidhi' => 4052,
    ],

    /*
     * 125 Muwatta Malik source files ship English-only (hadith_text.ar is null).
     * Documented source gap — do not invent Arabic text. Verify tolerates exactly this many.
     */
    'known_missing_arabic' => 125,

    /*
     * Hadiths with a non-null chapter_id after the 2026-06 chapter linkage backfill.
     * Not every source file carries chapter info (qudsi40 has none; nawawi40 and
     * shahwaliullah40 are partial), so this floor is the achieved coverage —
     * a DB count below it means the hadith → chapter linkage regressed.
     */
    'expected_chapter_linked' => 49008,
];
