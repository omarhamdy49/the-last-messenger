<?php

declare(strict_types=1);

return [
    'title' => 'الأحاديث',
    'singular' => 'حديث',
    'plural' => 'الأحاديث',

    // Page Titles
    'index' => 'إدارة الأحاديث',
    'create' => 'إضافة حديث جديد',
    'edit' => 'تعديل الحديث',
    'show' => 'عرض الحديث',
    'books' => 'كتب الحديث',
    'narrators' => 'الرواة',
    'chains' => 'سلاسل الإسناد',

    // Actions
    'actions' => [
        'create_new' => 'حديث جديد',
        'edit' => 'تعديل الحديث',
        'delete' => 'حذف الحديث',
        'view' => 'عرض الحديث',
        'verify' => 'التحقق من الحديث',
        'mark_verified' => 'تعليم كمحقق',
        'add_narration' => 'إضافة رواية',
        'add_explanation' => 'إضافة شرح',
        'manage_books' => 'إدارة الكتب',
        'manage_narrators' => 'إدارة الرواة',
        'bulk_import' => 'استيراد جماعي',
        'export' => 'تصدير',
    ],

    // Fields
    'fields' => [
        'number' => 'رقم الحديث',
        'text' => 'نص الحديث',
        'text_ar' => 'النص بالعربية',
        'text_en' => 'النص بالإنجليزية',
        'text_fr' => 'النص بالفرنسية',
        'text_tr' => 'النص بالتركية',
        'text_ur' => 'النص بالأردية',
        'book' => 'الكتاب',
        'chapter' => 'الباب',
        'chapter_number' => 'رقم الباب',
        'hadith_number' => 'رقم الحديث',
        'volume' => 'المجلد',
        'page' => 'الصفحة',
        'grade' => 'درجة الحديث',
        'authenticity' => 'درجة الصحة',
        'narrator_chain' => 'سلسلة الرواة',
        'first_narrator' => 'الراوي الأول',
        'last_narrator' => 'الراوي الأخير',
        'explanation' => 'الشرح',
        'explanation_ar' => 'الشرح بالعربية',
        'explanation_en' => 'الشرح بالإنجليزية',
        'explanation_fr' => 'الشرح بالفرنسية',
        'explanation_tr' => 'الشرح بالتركية',
        'explanation_ur' => 'الشرح بالأردية',
        'benefits' => 'الفوائد',
        'keywords' => 'الكلمات المفتاحية',
        'topics' => 'الموضوعات',
        'related_verses' => 'الآيات ذات الصلة',
        'related_hadiths' => 'الأحاديث ذات الصلة',
        'source' => 'المصدر',
        'reference' => 'المرجع',
        'is_verified' => 'محقق',
        'is_featured' => 'مميز',
        'views_count' => 'عدد المشاهدات',
        'favorites_count' => 'عدد المفضلة',
        'created_at' => 'تاريخ الإضافة',
        'updated_at' => 'تاريخ التحديث',
    ],

    // Authenticity Grades
    'grades' => [
        'sahih' => 'صحيح',
        'hasan' => 'حسن',
        'daif' => 'ضعيف',
        'mawdu' => 'موضوع',
        'sahih_lighayrihi' => 'صحيح لغيره',
        'hasan_lighayrihi' => 'حسن لغيره',
        'very_weak' => 'ضعيف جداً',
        'fabricated' => 'موضوع',
        'unknown' => 'غير معروف',
    ],

    // Books
    'books' => [
        'title' => 'كتب الحديث',
        'name' => 'اسم الكتاب',
        'author' => 'المؤلف',
        'volumes' => 'عدد المجلدات',
        'hadiths_count' => 'عدد الأحاديث',
        'chapters_count' => 'عدد الأبواب',
        'description' => 'وصف الكتاب',
        'era' => 'الحقبة',
        'hijri_year' => 'السنة الهجرية',
        'popular_books' => 'الكتب الشهيرة',

        // Famous Books
        'sahih_bukhari' => 'صحيح البخاري',
        'sahih_muslim' => 'صحيح مسلم',
        'sunan_abu_dawud' => 'سنن أبي داود',
        'jami_tirmidhi' => 'جامع الترمذي',
        'sunan_nasai' => 'سنن النسائي',
        'sunan_ibn_majah' => 'سنن ابن ماجه',
        'muwatta_malik' => 'موطأ مالك',
        'musnad_ahmad' => 'مسند أحمد',
        'sahih_ibn_hibban' => 'صحيح ابن حبان',
        'sunan_darimi' => 'سنن الدارمي',
    ],

    // Narrators
    'narrators' => [
        'title' => 'الرواة',
        'name' => 'اسم الراوي',
        'full_name' => 'الاسم الكامل',
        'nickname' => 'الكنية',
        'birth_year' => 'سنة الميلاد',
        'death_year' => 'سنة الوفاة',
        'biography' => 'السيرة الذاتية',
        'reliability' => 'درجة الموثوقية',
        'teachers' => 'الشيوخ',
        'students' => 'التلاميذ',
        'narrated_hadiths' => 'الأحاديث المروية',
        'generation' => 'الطبقة',
        'city' => 'المدينة',

        // Reliability Grades
        'trustworthy' => 'ثقة',
        'very_trustworthy' => 'ثقة ثقة',
        'truthful' => 'صدوق',
        'weak' => 'ضعيف',
        'abandoned' => 'متروك',
        'liar' => 'كذاب',
    ],

    // Chapters
    'chapters' => [
        'title' => 'الأبواب',
        'name' => 'اسم الباب',
        'number' => 'رقم الباب',
        'book' => 'الكتاب',
        'hadiths_count' => 'عدد الأحاديث',
        'description' => 'وصف الباب',
    ],

    // Topics
    'topics' => [
        'title' => 'الموضوعات',
        'aqeedah' => 'العقيدة',
        'fiqh' => 'الفقه',
        'akhlaq' => 'الأخلاق',
        'adab' => 'الآداب',
        'tafsir' => 'التفسير',
        'seerah' => 'السيرة',
        'history' => 'التاريخ',
        'manners' => 'المعاملات',
        'worship' => 'العبادات',
        'jihad' => 'الجهاد',
        'knowledge' => 'العلم',
        'zuhd' => 'الزهد',
    ],

    // Placeholders
    'placeholders' => [
        'search' => 'ابحث في الأحاديث...',
        'search_text' => 'ابحث في نص الحديث...',
        'search_narrator' => 'ابحث عن راوٍ...',
        'select_book' => 'اختر الكتاب',
        'select_chapter' => 'اختر الباب',
        'select_grade' => 'اختر درجة الحديث',
        'select_topic' => 'اختر الموضوع',
        'hadith_text' => 'أدخل نص الحديث...',
        'explanation' => 'أدخل شرح الحديث...',
    ],

    // Messages
    'messages' => [
        'created' => 'تم إضافة الحديث بنجاح',
        'updated' => 'تم تحديث الحديث بنجاح',
        'deleted' => 'تم حذف الحديث بنجاح',
        'verified' => 'تم التحقق من الحديث بنجاح',
        'imported' => 'تم استيراد الأحاديث بنجاح',
        'exported' => 'تم تصدير الأحاديث بنجاح',
        'not_found' => 'الحديث غير موجود',
        'cannot_delete' => 'لا يمكن حذف هذا الحديث',
        'error_creating' => 'حدث خطأ أثناء إضافة الحديث',
        'error_updating' => 'حدث خطأ أثناء تحديث الحديث',
        'error_deleting' => 'حدث خطأ أثناء حذف الحديث',
    ],

    // Filters
    'filters' => [
        'all' => 'كل الأحاديث',
        'by_book' => 'حسب الكتاب',
        'by_grade' => 'حسب الدرجة',
        'by_narrator' => 'حسب الراوي',
        'by_topic' => 'حسب الموضوع',
        'verified' => 'المحققة',
        'unverified' => 'غير المحققة',
        'featured' => 'المميزة',
        'sahih_only' => 'الصحيحة فقط',
        'weak_only' => 'الضعيفة فقط',
    ],

    // Sorting
    'sort' => [
        'number_asc' => 'رقم الحديث (تصاعدي)',
        'number_desc' => 'رقم الحديث (تنازلي)',
        'book_name' => 'اسم الكتاب',
        'most_viewed' => 'الأكثر مشاهدة',
        'most_favorited' => 'الأكثر حفظاً',
        'newest' => 'الأحدث',
        'oldest' => 'الأقدم',
    ],

    // Statistics
    'stats' => [
        'total' => 'إجمالي الأحاديث',
        'sahih' => 'صحيح',
        'hasan' => 'حسن',
        'daif' => 'ضعيف',
        'verified' => 'محقق',
        'by_book' => 'حسب الكتاب',
        'by_narrator' => 'حسب الراوي',
        'total_books' => 'إجمالي الكتب',
        'total_narrators' => 'إجمالي الرواة',
        'total_chapters' => 'إجمالي الأبواب',
    ],

    // Empty States
    'empty' => [
        'title' => 'لا توجد أحاديث بعد',
        'description' => 'ابدأ بإضافة أول حديث',
        'no_results' => 'لا توجد نتائج مطابقة',
        'no_books' => 'لا توجد كتب',
        'no_narrators' => 'لا يوجد رواة',
        'no_chapters' => 'لا توجد أبواب',
    ],

    // Tabs
    'tabs' => [
        'hadith_text' => 'نص الحديث',
        'narration_chain' => 'سلسلة الإسناد',
        'explanation' => 'الشرح',
        'benefits' => 'الفوائد',
        'related' => 'ذات صلة',
        'translations' => 'الترجمات',
    ],

    // Validation
    'validation' => [
        'text_required' => 'نص الحديث مطلوب',
        'book_required' => 'الكتاب مطلوب',
        'number_required' => 'رقم الحديث مطلوب',
        'grade_required' => 'درجة الحديث مطلوبة',
    ],

    // Help Text
    'help' => [
        'hadith_number' => 'رقم الحديث في الكتاب المصدر',
        'grade' => 'درجة صحة الحديث حسب علماء الحديث',
        'narrator_chain' => 'سلسلة رواة الحديث من الصحابي إلى المحدث',
        'explanation' => 'شرح معاني الحديث وفوائده',
        'topics' => 'الموضوعات التي يتناولها الحديث',
    ],
];
