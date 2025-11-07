<?php

declare(strict_types=1);

return [
    'title' => 'القرآن الكريم',
    'singular' => 'قرآن',
    'plural' => 'القرآن',

    // Page Titles
    'index' => 'إدارة القرآن الكريم',
    'surahs' => 'السور',
    'verses' => 'الآيات',
    'tafseer' => 'التفسير',
    'reciters' => 'القراء',
    'recitations' => 'التلاوات',

    // Actions
    'actions' => [
        'manage_surahs' => 'إدارة السور',
        'manage_verses' => 'إدارة الآيات',
        'manage_tafseer' => 'إدارة التفسير',
        'manage_reciters' => 'إدارة القراء',
        'add_recitation' => 'إضافة تلاوة',
        'add_tafseer' => 'إضافة تفسير',
        'import_verses' => 'استيراد الآيات',
        'export' => 'تصدير',
    ],

    // Surahs
    'surahs' => [
        'title' => 'السور',
        'singular' => 'سورة',
        'number' => 'رقم السورة',
        'name' => 'اسم السورة',
        'name_arabic' => 'الاسم بالعربية',
        'name_transliteration' => 'النطق بالإنجليزية',
        'name_translation' => 'الترجمة',
        'revelation_place' => 'مكان النزول',
        'revelation_order' => 'ترتيب النزول',
        'verses_count' => 'عدد الآيات',
        'pages' => 'الصفحات',
        'juz' => 'الجزء',
        'hizb' => 'الحزب',
        'ruku' => 'الركوع',
        'manzil' => 'المنزل',
        'description' => 'وصف السورة',
        'theme' => 'الموضوع الرئيسي',
        'occasion' => 'سبب النزول',
        'virtues' => 'فضائل السورة',
        'makkah' => 'مكية',
        'madinah' => 'مدنية',
    ],

    // Verses
    'verses' => [
        'title' => 'الآيات',
        'singular' => 'آية',
        'number' => 'رقم الآية',
        'text' => 'نص الآية',
        'text_simple' => 'النص المبسط',
        'text_uthmani' => 'النص العثماني',
        'text_imlaei' => 'النص الإملائي',
        'transliteration' => 'النطق بالحروف اللاتينية',
        'translation' => 'الترجمة',
        'translation_ar' => 'الترجمة بالعربية',
        'translation_en' => 'الترجمة بالإنجليزية',
        'translation_fr' => 'الترجمة بالفرنسية',
        'translation_tr' => 'الترجمة بالتركية',
        'translation_ur' => 'الترجمة بالأردية',
        'juz' => 'الجزء',
        'page' => 'الصفحة',
        'hizb' => 'الحزب',
        'ruku' => 'الركوع',
        'manzil' => 'المنزل',
        'sajda' => 'سجدة',
        'sajda_type' => 'نوع السجدة',
        'sajda_mandatory' => 'سجدة واجبة',
        'sajda_recommended' => 'سجدة مستحبة',
    ],

    // Tafseer
    'tafseer' => [
        'title' => 'التفسير',
        'singular' => 'تفسير',
        'name' => 'اسم التفسير',
        'author' => 'المؤلف',
        'language' => 'اللغة',
        'text' => 'نص التفسير',
        'short_text' => 'التفسير المختصر',
        'detailed_text' => 'التفسير المفصل',
        'word_by_word' => 'التفسير الكلمة بكلمة',
        'linguistic_analysis' => 'التحليل اللغوي',
        'legal_rulings' => 'الأحكام الفقهية',
        'occasions' => 'أسباب النزول',
        'benefits' => 'الفوائد',
        'references' => 'المراجع',

        // Famous Tafseer Books
        'tabari' => 'تفسير الطبري',
        'ibn_kathir' => 'تفسير ابن كثير',
        'qurtubi' => 'تفسير القرطبي',
        'baghawi' => 'تفسير البغوي',
        'jalalayn' => 'تفسير الجلالين',
        'saadi' => 'تفسير السعدي',
        'muyassar' => 'التفسير الميسر',
    ],

    // Reciters
    'reciters' => [
        'title' => 'القراء',
        'singular' => 'قارئ',
        'name' => 'اسم القارئ',
        'name_ar' => 'الاسم بالعربية',
        'name_en' => 'الاسم بالإنجليزية',
        'country' => 'البلد',
        'style' => 'الأسلوب',
        'narration' => 'الرواية',
        'biography' => 'السيرة الذاتية',
        'birth_year' => 'سنة الميلاد',
        'death_year' => 'سنة الوفاة',
        'photo' => 'الصورة',
        'is_featured' => 'مميز',
        'recitations_count' => 'عدد التلاوات',

        // Narrations (Qira'at)
        'hafs' => 'حفص عن عاصم',
        'warsh' => 'ورش عن نافع',
        'qalun' => 'قالون عن نافع',
        'douri' => 'الدوري عن أبي عمرو',
    ],

    // Recitations
    'recitations' => [
        'title' => 'التلاوات',
        'singular' => 'تلاوة',
        'reciter' => 'القارئ',
        'surah' => 'السورة',
        'audio_file' => 'ملف الصوت',
        'audio_url' => 'رابط الصوت',
        'quality' => 'الجودة',
        'bitrate' => 'معدل البت',
        'duration' => 'المدة',
        'file_size' => 'حجم الملف',
        'format' => 'الصيغة',
        'is_available' => 'متاح',

        // Quality
        'high' => 'جودة عالية',
        'medium' => 'جودة متوسطة',
        'low' => 'جودة منخفضة',
    ],

    // Topics & Themes
    'themes' => [
        'aqeedah' => 'العقيدة',
        'stories' => 'القصص',
        'laws' => 'الأحكام',
        'morals' => 'الأخلاق',
        'worship' => 'العبادات',
        'history' => 'التاريخ',
        'afterlife' => 'الآخرة',
        'prophets' => 'الأنبياء',
        'miracles' => 'المعجزات',
    ],

    // Features
    'features' => [
        'mushaf' => 'المصحف',
        'word_by_word' => 'كلمة بكلمة',
        'audio' => 'الصوت',
        'bookmarks' => 'الإشارات المرجعية',
        'notes' => 'الملاحظات',
        'search' => 'البحث',
        'share' => 'مشاركة',
        'copy' => 'نسخ',
        'listen' => 'استماع',
        'read' => 'قراءة',
    ],

    // Placeholders
    'placeholders' => [
        'search_surah' => 'ابحث عن سورة...',
        'search_verse' => 'ابحث في الآيات...',
        'search_word' => 'ابحث عن كلمة...',
        'select_surah' => 'اختر السورة',
        'select_juz' => 'اختر الجزء',
        'select_page' => 'اختر الصفحة',
        'select_reciter' => 'اختر القارئ',
        'select_tafseer' => 'اختر التفسير',
    ],

    // Messages
    'messages' => [
        'verse_added' => 'تمت إضافة الآية بنجاح',
        'verse_updated' => 'تم تحديث الآية بنجاح',
        'tafseer_added' => 'تمت إضافة التفسير بنجاح',
        'recitation_added' => 'تمت إضافة التلاوة بنجاح',
        'reciter_added' => 'تمت إضافة القارئ بنجاح',
        'imported' => 'تم الاستيراد بنجاح',
        'exported' => 'تم التصدير بنجاح',
        'not_found' => 'غير موجود',
        'error_occurred' => 'حدث خطأ',
    ],

    // Filters
    'filters' => [
        'all_surahs' => 'كل السور',
        'makkah' => 'المكية',
        'madinah' => 'المدنية',
        'by_juz' => 'حسب الجزء',
        'by_page' => 'حسب الصفحة',
        'with_sajda' => 'بها سجدة',
    ],

    // Navigation
    'navigation' => [
        'previous_surah' => 'السورة السابقة',
        'next_surah' => 'السورة التالية',
        'previous_verse' => 'الآية السابقة',
        'next_verse' => 'الآية التالية',
        'previous_page' => 'الصفحة السابقة',
        'next_page' => 'الصفحة التالية',
        'go_to_surah' => 'اذهب إلى السورة',
        'go_to_juz' => 'اذهب إلى الجزء',
        'go_to_page' => 'اذهب إلى الصفحة',
    ],

    // Statistics
    'stats' => [
        'total_surahs' => 'إجمالي السور',
        'total_verses' => 'إجمالي الآيات',
        'total_pages' => 'إجمالي الصفحات',
        'total_juz' => 'إجمالي الأجزاء',
        'total_reciters' => 'إجمالي القراء',
        'total_recitations' => 'إجمالي التلاوات',
        'makkah_surahs' => 'السور المكية',
        'madinah_surahs' => 'السور المدنية',
    ],

    // Empty States
    'empty' => [
        'no_surahs' => 'لا توجد سور',
        'no_verses' => 'لا توجد آيات',
        'no_tafseer' => 'لا يوجد تفسير',
        'no_reciters' => 'لا يوجد قراء',
        'no_recitations' => 'لا توجد تلاوات',
    ],

    // Validation
    'validation' => [
        'surah_required' => 'السورة مطلوبة',
        'verse_required' => 'الآية مطلوبة',
        'text_required' => 'النص مطلوب',
        'reciter_required' => 'القارئ مطلوب',
    ],

    // Help Text
    'help' => [
        'surah_number' => 'رقم السورة في ترتيب المصحف (1-114)',
        'revelation_order' => 'ترتيب نزول السورة',
        'juz' => 'الجزء الذي تقع فيه الآية (1-30)',
        'page' => 'رقم الصفحة في المصحف',
        'sajda' => 'الآيات التي بها سجدة تلاوة',
    ],
];
