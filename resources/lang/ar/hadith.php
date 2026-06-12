<?php

declare(strict_types=1);

return [
    'public' => [
        'page_title' => 'مكتبة الحديث الشريف',
        'page_description' => 'تصفح كتب السنة النبوية المطهرة وأمهات كتب الحديث الشريف.',
        'hero_title' => 'مكتبة الحديث الشريف',
        'hero_subtitle' => 'رحلة إلى كنوز السنة النبوية المطهرة',
        'breadcrumb' => 'الحديث',

        'stat_collections' => 'كتاب',
        'stat_hadiths' => 'حديث',
        'stat_books' => 'باب',

        'search_placeholder' => 'ابحث عن كتاب أو مؤلف...',
        'search_label' => 'البحث في مكتبة الحديث',

        'category_all' => 'جميع الكتب',
        'category_sahih' => 'الصحاح',
        'category_sunan' => 'كتب السنن',
        'category_masanid' => 'المسانيد',
        'category_arbaeen' => 'الأربعينات',
        'category_other' => 'كتب أخرى',

        'collection_unit' => 'كتاب',
        'hadith_unit' => 'حديث',
        'book_unit' => 'باب',
        'chapter_unit' => 'فصل',

        'explore' => 'استكشف الكتاب',
        'by_author' => 'تأليف',

        'empty_title' => 'لا توجد كتب',
        'empty_message' => 'لم نعثر على كتب مطابقة لبحثك. جرّب تعديل البحث أو الفئة.',

        'coming_soon_title' => 'قريباً',
        'coming_soon_message' => 'هذه الصفحة قيد الإنشاء وستتوفر قريباً بإذن الله.',

        // Grading buckets (normalized from free-text scholarly verdicts).
        'grading_sahih' => 'صحيح',
        'grading_hasan' => 'حسن',
        'grading_daif' => 'ضعيف',
        'grading_unknown' => 'لم يُحكم عليه',
        'grading_agreement' => 'متفق عليه',

        // Book (collection reader) page.
        'book_search_placeholder' => 'ابحث في أبواب الكتاب...',
        'book_chapters_heading' => 'أبواب الكتاب',
        'book_empty_title' => 'لا توجد أبواب',
        'book_empty_message' => 'لم نعثر على أبواب مطابقة. جرّب تعديل بحثك.',
        'book_open' => 'تصفح الباب',
        'book_chapter_count' => 'باب',
        'book_hadith_count' => 'حديث',

        // Single hadith page.
        'single_breadcrumb' => 'الحديث',
        'single_number' => 'حديث رقم',
        'single_text_heading' => 'نص الحديث',
        'single_translations_heading' => 'الترجمات',
        'single_themes_heading' => 'الموضوعات',
        'single_significance_heading' => 'الأهمية والفوائد',
        'single_context_heading' => 'السياق التاريخي',
        'single_related_heading' => 'أحاديث ذات صلة',
        'single_no_translation' => 'لا تتوفر ترجمة بهذه اللغة.',
        'single_font_size' => 'حجم الخط',
        'single_font_decrease' => 'تصغير الخط',
        'single_font_increase' => 'تكبير الخط',
        'single_copy' => 'نسخ',
        'single_copied' => 'تم النسخ',
        'single_share' => 'مشاركة',
        'single_print' => 'طباعة',
        'single_view' => 'عرض الحديث',

        // Chapter (kitab) reader page.
        'chapter_breadcrumb' => 'الباب',
        'chapter_hadith_unit' => 'حديث',
        'chapter_search_placeholder' => 'ابحث في أحاديث هذا الباب...',
        'chapter_search_label' => 'البحث داخل الباب',
        'chapter_filter_all' => 'كل الأحاديث',
        'chapter_view_density' => 'كثافة العرض',
        'chapter_view_compact' => 'مضغوط',
        'chapter_view_normal' => 'عادي',
        'chapter_view_expanded' => 'موسّع',
        'chapter_prev' => 'الباب السابق',
        'chapter_next' => 'الباب التالي',
        'chapter_index' => 'فهرس الكتاب',
        'chapter_showing' => 'عرض :from–:to من :total حديث',
        'chapter_per_page' => 'لكل صفحة',
        'chapter_apply' => 'تطبيق',
        'chapter_empty_title' => 'لا توجد أحاديث مطابقة',
        'chapter_empty_message' => 'لم نعثر على أحاديث مطابقة لبحثك في هذا الباب. جرّب كلمات أخرى أو أزل عوامل التصفية.',
        'chapter_reset_filters' => 'إعادة تعيين التصفية',
        'chapter_loading' => 'جارٍ تحميل الأحاديث...',
        'chapter_read' => 'عرض الحديث',

        // Parallel narrations (comparison) page.
        'narrations_breadcrumb' => 'مقارنة الروايات',
        'narrations_title' => 'مقارنة روايات الحديث',
        'narrations_subtitle' => 'عرض مقارن لنفس الحديث من مصادر متعددة لتوضيح الفروقات في اللفظ والسند.',
        'narrations_base_badge' => 'الرواية الأساسية',
        'narrations_parallel_badge' => 'رواية موازية',
        'narrations_isnad' => 'السند',
        'narrations_grading' => 'الدرجة',
        'narrations_back' => 'العودة إلى الحديث',
        'narrations_unit' => 'رواية',
        'narrations_empty_title' => 'هذه الرواية فريدة',
        'narrations_empty_message' => 'لم نعثر على روايات موازية لهذا الحديث في كتب أخرى.',
        'narrations_compare_link' => 'مقارنة الروايات (:count)',

        // Single page — isnad, commentaries and scholar gradings.
        'single_isnad_heading' => 'سلسلة الإسناد',
        'single_commentaries_heading' => 'شروح العلماء',
        'single_commentaries_subtitle' => 'أقوال أهل العلم في الحديث',
        'single_commentary_unknown_author' => 'شرح غير منسوب',
        'single_gradings_heading' => 'أحكام العلماء',
        'single_narrator_reliability' => 'درجة الثقة',
        'single_narrator_death' => 'سنة الوفاة',
        'single_narrator_kunya' => 'الكنية',
        'single_narrator_bio' => 'نبذة مختصرة',
        'single_hijri_suffix' => 'هـ',
        'single_verified' => 'موثق',

        // Explorer (advanced search) page.
        'explorer_breadcrumb' => 'مستكشف الحديث',
        'explorer_title' => 'مستكشف الحديث',
        'explorer_subtitle' => 'ابحث في آلاف الأحاديث عبر أمهات كتب السنة',
        'explorer_search_placeholder' => 'ابحث في نصوص الأحاديث أو برقم الحديث...',
        'explorer_filters' => 'عوامل التصفية',
        'explorer_collection' => 'الكتاب',
        'explorer_all_collections' => 'جميع الكتب',
        'explorer_grading' => 'درجة الحديث',
        'explorer_all_gradings' => 'جميع الدرجات',
        'explorer_sort' => 'الترتيب',
        'explorer_sort_asc' => 'تصاعدي حسب الرقم',
        'explorer_sort_desc' => 'تنازلي حسب الرقم',
        'explorer_per_page' => 'لكل صفحة',
        'explorer_apply' => 'تطبيق',
        'explorer_reset' => 'إعادة تعيين',
        'explorer_results_count' => 'نتيجة',
        'explorer_prompt_title' => 'ابدأ البحث',
        'explorer_prompt_message' => 'اكتب كلمة في صندوق البحث أو اختر كتاباً ودرجة للحديث لعرض النتائج.',
        'explorer_empty_title' => 'لا توجد نتائج',
        'explorer_empty_message' => 'لم نعثر على أحاديث مطابقة لبحثك. جرّب كلمات أخرى أو وسّع نطاق التصفية.',
        'explorer_jump_to' => 'انتقل إلى صفحة',
        'explorer_go' => 'انتقال',
    ],

    'explorer' => [
        'page_title' => 'مستكشف الحديث',
        'heading' => 'مستكشف الحديث',
    ],

    'book' => [
        'page_title' => 'تصفح الكتاب',
        'heading' => 'صفحة الكتاب',
    ],

    'single' => [
        'page_title' => 'الحديث',
        'heading' => 'صفحة الحديث',
    ],
];
