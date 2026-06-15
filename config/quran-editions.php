<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Quran Editions Catalog
|--------------------------------------------------------------------------
|
| Canonical catalog of the translation and tafsir editions imported into the
| Quran module. The catalog mirrors the quran.ai MCP edition list, restricted
| to the five languages the app renders (ar, en, fr, tr, ur). Each entry maps
| an edition slug to its numeric Quran.com API v4 resource id, which the
| `quran:import-translation` / `quran:import-tafsir` commands use to pull the
| canonical text from https://api.quran.com/api/v4 (the MCP's own upstream).
|
| `default` marks the one edition per language used for the legacy single
| translation read path (Verse::getTranslation, VerseDTO lang keys). Exactly
| one translation per language must be default. The `ar` source-text resource
| is the Arabic Quran text itself (not a translation) and is always default ar.
|
| Editions present in the MCP catalog but NOT served by Quran.com API v4 are
| listed under `unavailable_via_api` for transparency — importing them needs
| the per-verse MCP fetch path or an alternate dataset (see TODO_quran-data.md).
|
*/

return [

    // The Arabic Quran text lives in quran_verse_translations under lang `ar`.
    // It is modelled as a translation resource so the reader's edition switcher
    // and the (verse_id, resource_id) uniqueness stay consistent, but it is
    // never targeted by a translation import (Arabic has no "translation").
    'arabic_source' => [
        'slug' => 'ar-uthmani',
        'lang' => 'ar',
        'name' => ['ar' => 'القرآن الكريم (رسم عثماني)', 'en' => 'The Noble Qur’an (Uthmani)'],
        'author' => 'مجمع الملك فهد لطباعة المصحف الشريف',
    ],

    // Optional Latin transliteration of the Arabic (stored under lang `en-tl`).
    'transliteration' => [
        'slug' => 'en-transliteration',
        'lang' => 'en-tl',
        'quran_com_id' => 57,
        'name' => ['ar' => 'النقحرة اللاتينية', 'en' => 'Transliteration'],
        'author' => 'Transliteration',
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation editions (Quran.com API v4 resource ids)
    |--------------------------------------------------------------------------
    | text source: GET /quran/translations/{id}?chapter_number={1..114}
    */
    'translations' => [
        // English (7)
        ['slug' => 'en-sahih-international', 'lang' => 'en', 'quran_com_id' => 20, 'default' => true,
            'name' => ['ar' => 'صحيح إنترناشونال', 'en' => 'Saheeh International'], 'author' => 'Saheeh International'],
        ['slug' => 'en-pickthall', 'lang' => 'en', 'quran_com_id' => 19,
            'name' => ['ar' => 'بيكتال', 'en' => 'M. Pickthall'], 'author' => 'Marmaduke Pickthall'],
        ['slug' => 'en-yusufali', 'lang' => 'en', 'quran_com_id' => 22,
            'name' => ['ar' => 'يوسف علي', 'en' => 'A. Yusuf Ali'], 'author' => 'Abdullah Yusuf Ali'],
        ['slug' => 'en-abdel-haleem', 'lang' => 'en', 'quran_com_id' => 85,
            'name' => ['ar' => 'عبد الحليم', 'en' => 'M.A.S. Abdel Haleem'], 'author' => 'M.A.S. Abdel Haleem'],
        ['slug' => 'en-al-hilali-khan', 'lang' => 'en', 'quran_com_id' => 203,
            'name' => ['ar' => 'الهلالي وخان', 'en' => 'Al-Hilali & Khan'], 'author' => 'Al-Hilali & Khan'],
        ['slug' => 'en-taqi-usmani', 'lang' => 'en', 'quran_com_id' => 84,
            'name' => ['ar' => 'تقي عثماني', 'en' => 'T. Usmani'], 'author' => 'Mufti Taqi Usmani'],
        ['slug' => 'en-al-maududi', 'lang' => 'en', 'quran_com_id' => 95,
            'name' => ['ar' => 'المودودي (تفهيم القرآن)', 'en' => 'A. Maududi (Tafhim)'], 'author' => 'Sayyid Abul Ala Maududi'],

        // French (3)
        ['slug' => 'fr-hamidullah', 'lang' => 'fr', 'quran_com_id' => 31, 'default' => true,
            'name' => ['ar' => 'حميد الله', 'en' => 'Muhammad Hamidullah'], 'author' => 'Muhammad Hamidullah'],
        ['slug' => 'fr-montada-islamic-foundation', 'lang' => 'fr', 'quran_com_id' => 136,
            'name' => ['ar' => 'مؤسسة منتدى الإسلامية', 'en' => 'Montada Islamic Foundation'], 'author' => 'Montada Islamic Foundation'],
        ['slug' => 'fr-rashid-maash', 'lang' => 'fr', 'quran_com_id' => 779,
            'name' => ['ar' => 'رشيد معاش', 'en' => 'Rashid Maash'], 'author' => 'Rashid Maash'],

        // Turkish (2)
        ['slug' => 'tr-diyanet', 'lang' => 'tr', 'quran_com_id' => 77, 'default' => true,
            'name' => ['ar' => 'ديانت', 'en' => 'Diyanet İşleri'], 'author' => 'Diyanet İşleri'],
        ['slug' => 'tr-hamdi', 'lang' => 'tr', 'quran_com_id' => 52,
            'name' => ['ar' => 'المللي حمدي يازر', 'en' => 'Elmalılı Hamdi Yazır'], 'author' => 'Elmalılı Hamdi Yazır'],

        // Urdu (4)
        ['slug' => 'ur-junagarri', 'lang' => 'ur', 'quran_com_id' => 54, 'default' => true,
            'name' => ['ar' => 'محمد جوناگڑھی', 'en' => 'Muhammad Junagarhi'], 'author' => 'Maulana Muhammad Junagarhi'],
        ['slug' => 'ur-fatah-muhammad-jalandhari', 'lang' => 'ur', 'quran_com_id' => 234,
            'name' => ['ar' => 'فتح محمد جالندھری', 'en' => 'Fatah Muhammad Jalandhari'], 'author' => 'Fatah Muhammad Jalandhari'],
        ['slug' => 'ur-bayan-ul-quran', 'lang' => 'ur', 'quran_com_id' => 158,
            'name' => ['ar' => 'بیان القرآن (إسرار أحمد)', 'en' => 'Bayan-ul-Quran (Israr Ahmad)'], 'author' => 'Dr. Israr Ahmad'],
        ['slug' => 'ur-maulana-wahid-uddin-khan-urdu', 'lang' => 'ur', 'quran_com_id' => 819,
            'name' => ['ar' => 'وحید الدین خان', 'en' => 'Maulana Wahiduddin Khan'], 'author' => 'Maulana Wahiduddin Khan'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tafsir editions (Quran.com API v4 resource ids)
    |--------------------------------------------------------------------------
    | text source: GET /tafsirs/{id}/by_chapter/{1..114}
    */
    'tafsirs' => [
        // Arabic (7 of the 10 MCP Arabic tafsirs are on Quran.com API v4)
        ['slug' => 'ar-muyassar', 'lang' => 'ar', 'quran_com_id' => 16, 'default' => true,
            'name' => ['ar' => 'التفسير الميسر', 'en' => 'Al-Muyassar'], 'author' => 'مجمع الملك فهد'],
        ['slug' => 'ar-ibn-kathir', 'lang' => 'ar', 'quran_com_id' => 14,
            'name' => ['ar' => 'تفسير ابن كثير', 'en' => 'Tafsir Ibn Kathir'], 'author' => 'إسماعيل بن كثير'],
        ['slug' => 'ar-tabari', 'lang' => 'ar', 'quran_com_id' => 15,
            'name' => ['ar' => 'تفسير الطبري', 'en' => 'Tafsir al-Tabari'], 'author' => 'محمد بن جرير الطبري'],
        ['slug' => 'ar-qurtubi', 'lang' => 'ar', 'quran_com_id' => 90,
            'name' => ['ar' => 'تفسير القرطبي', 'en' => 'Al-Qurtubi'], 'author' => 'محمد بن أحمد القرطبي'],
        ['slug' => 'ar-baghawi', 'lang' => 'ar', 'quran_com_id' => 94,
            'name' => ['ar' => 'معالم التنزيل (البغوي)', 'en' => 'Tafseer Al-Baghawi'], 'author' => 'الحسين بن مسعود البغوي'],
        ['slug' => 'ar-saadi', 'lang' => 'ar', 'quran_com_id' => 91,
            'name' => ['ar' => 'تيسير الكريم الرحمن (السعدي)', 'en' => 'Al-Sa’di'], 'author' => 'عبد الرحمن بن ناصر السعدي'],
        ['slug' => 'ar-al-wasit', 'lang' => 'ar', 'quran_com_id' => 93,
            'name' => ['ar' => 'التفسير الوسيط (طنطاوي)', 'en' => 'Al-Wasit (Tantawi)'], 'author' => 'محمد سيد طنطاوي'],

        // English (3)
        ['slug' => 'en-ibn-kathir', 'lang' => 'en', 'quran_com_id' => 169, 'default' => true,
            'name' => ['ar' => 'ابن كثير (مختصر)', 'en' => 'Ibn Kathir (Abridged)'], 'author' => 'Hafiz Ibn Kathir'],
        ['slug' => 'en-maarif-ul-quran', 'lang' => 'en', 'quran_com_id' => 168,
            'name' => ['ar' => 'معارف القرآن', 'en' => 'Ma’arif al-Qur’an'], 'author' => 'Mufti Muhammad Shafi'],
        ['slug' => 'en-tazkirul-quran', 'lang' => 'en', 'quran_com_id' => 817,
            'name' => ['ar' => 'تذكير القرآن', 'en' => 'Tazkirul Quran'], 'author' => 'Maulana Wahiduddin Khan'],
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP editions not available on Quran.com API v4
    |--------------------------------------------------------------------------
    | These exist in the quran.ai MCP corpus but are not served by the v4 REST
    | API used for bulk import. Importing them requires the per-verse MCP fetch
    | path (6,236 calls each) or an alternate dataset — tracked as a follow-up.
    */
    'unavailable_via_api' => [
        'tafsirs' => [
            ['slug' => 'ar-jalalayn', 'lang' => 'ar', 'name' => 'تفسير الجلالين'],
            ['slug' => 'ar-kashaf', 'lang' => 'ar', 'name' => 'الكشاف (الزمخشري)'],
            ['slug' => 'ar-nathm-aldurar', 'lang' => 'ar', 'name' => 'نظم الدرر (البقاعي)'],
            ['slug' => 'ar-tahrir-wa-tanwir', 'lang' => 'ar', 'name' => 'التحرير والتنوير (ابن عاشور)'],
        ],
    ],
];
