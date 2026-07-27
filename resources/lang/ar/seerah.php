<?php

declare(strict_types=1);

return [
    'title' => 'السيرة النبوية',
    'singular' => 'حدث',
    'plural' => 'الأحداث',

    // Page Titles
    'index' => 'إدارة السيرة النبوية',
    'events' => 'أحداث السيرة',
    'timeline' => 'الخط الزمني',
    'create' => 'إضافة حدث جديد',
    'edit' => 'تعديل الحدث',
    'show' => 'عرض الحدث',

    // Actions
    'actions' => [
        'create_event' => 'حدث جديد',
        'edit_event' => 'تعديل الحدث',
        'delete_event' => 'حذف الحدث',
        'view_timeline' => 'عرض الخط الزمني',
        'add_to_timeline' => 'إضافة إلى الخط الزمني',
        'link_events' => 'ربط الأحداث',
        'export_timeline' => 'تصدير الخط الزمني',
    ],

    // Fields
    'fields' => [
        'title' => 'عنوان الحدث',
        'title_ar' => 'العنوان بالعربية',
        'title_en' => 'العنوان بالإنجليزية',
        'title_fr' => 'العنوان بالفرنسية',
        'title_tr' => 'العنوان بالتركية',
        'title_ur' => 'العنوان بالأردية',
        'description' => 'وصف الحدث',
        'content' => 'تفاصيل الحدث',
        'summary' => 'ملخص',
        'category' => 'التصنيف',
        'type' => 'نوع الحدث',
        'date_hijri' => 'التاريخ الهجري',
        'date_gregorian' => 'التاريخ الميلادي',
        'year_hijri' => 'السنة الهجرية',
        'month_hijri' => 'الشهر الهجري',
        'day_hijri' => 'اليوم الهجري',
        'date_precision' => 'دقة التاريخ',
        'duration' => 'المدة',
        'location' => 'المكان',
        'location_ar' => 'المكان بالعربية',
        'location_en' => 'المكان بالإنجليزية',
        'coordinates' => 'الإحداثيات',
        'latitude' => 'خط العرض',
        'longitude' => 'خط الطول',
        'significance' => 'الأهمية',
        'lessons' => 'الدروس المستفادة',
        'related_verses' => 'الآيات ذات الصلة',
        'related_hadiths' => 'الأحاديث ذات الصلة',
        'related_events' => 'الأحداث ذات الصلة',
        'participants' => 'المشاركون',
        'companions' => 'الصحابة',
        'references' => 'المراجع',
        'sources' => 'المصادر',
        'images' => 'الصور',
        'timeline_order' => 'الترتيب في الخط الزمني',
        'is_major_event' => 'حدث رئيسي',
        'is_featured' => 'مميز',
        'created_at' => 'تاريخ الإضافة',
        'updated_at' => 'تاريخ التحديث',
    ],

    // Event Categories
    'categories' => [
        'birth' => 'الميلاد والنشأة',
        'prophethood' => 'النبوة والدعوة',
        'persecution' => 'الاضطهاد',
        'migration' => 'الهجرة',
        'battles' => 'الغزوات والسرايا',
        'treaties' => 'المعاهدات',
        'conquest' => 'الفتوحات',
        'delegations' => 'الوفود',
        'miracles' => 'المعجزات',
        'family' => 'الأسرة',
        'companions' => 'الصحابة',
        'death' => 'الوفاة',
    ],

    // Event Types
    'types' => [
        'birth' => 'ميلاد',
        'battle' => 'غزوة',
        'sariyya' => 'سرية',
        'treaty' => 'معاهدة',
        'miracle' => 'معجزة',
        'revelation' => 'نزول وحي',
        'journey' => 'رحلة',
        'marriage' => 'زواج',
        'death' => 'وفاة',
        'conquest' => 'فتح',
        'delegation' => 'وفد',
        'meeting' => 'لقاء',
        'speech' => 'خطبة',
    ],

    // Date Precision
    'date_precision' => [
        'exact' => 'تاريخ دقيق',
        'month_year' => 'شهر وسنة',
        'year_only' => 'سنة فقط',
        'approximate' => 'تقريبي',
        'unknown' => 'غير معروف',
    ],

    // Periods
    'periods' => [
        'pre_prophethood' => 'ما قبل النبوة',
        'makkah' => 'الفترة المكية',
        'early_makkah' => 'مكة المبكرة',
        'middle_makkah' => 'مكة المتوسطة',
        'late_makkah' => 'مكة المتأخرة',
        'madinah' => 'الفترة المدنية',
        'early_madinah' => 'المدينة المبكرة',
        'middle_madinah' => 'المدينة المتوسطة',
        'late_madinah' => 'المدينة المتأخرة',
    ],

    // Locations
    'locations' => [
        'makkah' => 'مكة المكرمة',
        'madinah' => 'المدينة المنورة',
        'taif' => 'الطائف',
        'badr' => 'بدر',
        'uhud' => 'أحد',
        'khandaq' => 'الخندق',
        'khaybar' => 'خيبر',
        'hudaybiyyah' => 'الحديبية',
        'tabuk' => 'تبوك',
        'hunain' => 'حنين',
    ],

    // Famous Battles
    'battles' => [
        'badr' => 'غزوة بدر',
        'uhud' => 'غزوة أحد',
        'khandaq' => 'غزوة الخندق',
        'khaybar' => 'غزوة خيبر',
        'makkah_conquest' => 'فتح مكة',
        'hunain' => 'غزوة حنين',
        'taif' => 'غزوة الطائف',
        'tabuk' => 'غزوة تبوك',
    ],

    // Placeholders
    'placeholders' => [
        'title' => 'أدخل عنوان الحدث...',
        'description' => 'أدخل وصف الحدث...',
        'content' => 'أدخل تفاصيل الحدث...',
        'search' => 'ابحث في أحداث السيرة...',
        'select_category' => 'اختر التصنيف',
        'select_type' => 'اختر نوع الحدث',
        'select_location' => 'اختر المكان',
        'select_date' => 'اختر التاريخ',
    ],

    // Messages
    'messages' => [
        'created' => 'تمت إضافة الحدث بنجاح',
        'updated' => 'تم تحديث الحدث بنجاح',
        'deleted' => 'تم حذف الحدث بنجاح',
        'linked' => 'تم ربط الأحداث بنجاح',
        'not_found' => 'الحدث غير موجود',
        'cannot_delete' => 'لا يمكن حذف هذا الحدث',
        'error_creating' => 'حدث خطأ أثناء إضافة الحدث',
        'error_updating' => 'حدث خطأ أثناء تحديث الحدث',
        'error_deleting' => 'حدث خطأ أثناء حذف الحدث',
    ],

    // Filters
    'filters' => [
        'all' => 'كل الأحداث',
        'by_category' => 'حسب التصنيف',
        'by_type' => 'حسب النوع',
        'by_period' => 'حسب الفترة',
        'by_location' => 'حسب المكان',
        'makkah_period' => 'الفترة المكية',
        'madinah_period' => 'الفترة المدنية',
        'major_events' => 'الأحداث الرئيسية',
        'featured' => 'المميزة',
    ],

    // Sorting
    'sort' => [
        'chronological' => 'زمنياً',
        'chronological_desc' => 'زمنياً (عكسي)',
        'title_asc' => 'العنوان (أ-ي)',
        'title_desc' => 'العنوان (ي-أ)',
        'newest' => 'الأحدث إضافة',
        'oldest' => 'الأقدم إضافة',
    ],

    // Timeline
    'timeline' => [
        'title' => 'الخط الزمني',
        'view_timeline' => 'عرض الخط الزمني',
        'interactive_timeline' => 'الخط الزمني التفاعلي',
        'year' => 'السنة',
        'event_count' => 'عدد الأحداث',
        'before_hijrah' => 'قبل الهجرة',
        'after_hijrah' => 'بعد الهجرة',
        'show_all_events' => 'عرض كل الأحداث',
        'show_major_events' => 'عرض الأحداث الرئيسية',
        'zoom_in' => 'تكبير',
        'zoom_out' => 'تصغير',
        'reset_zoom' => 'إعادة تعيين التكبير',
    ],

    // Statistics
    'stats' => [
        'total_events' => 'إجمالي الأحداث',
        'makkah_events' => 'أحداث مكة',
        'madinah_events' => 'أحداث المدينة',
        'battles_count' => 'عدد الغزوات',
        'years_covered' => 'السنوات المغطاة',
        'locations_count' => 'عدد الأماكن',
    ],

    // Empty States
    'empty' => [
        'title' => 'لا توجد أحداث بعد',
        'description' => 'ابدأ بإضافة أول حدث من السيرة',
        'no_results' => 'لا توجد نتائج مطابقة',
        'no_timeline' => 'لا توجد أحداث في الخط الزمني',
    ],

    // Tabs
    'tabs' => [
        'details' => 'التفاصيل',
        'location' => 'المكان',
        'participants' => 'المشاركون',
        'significance' => 'الأهمية والدروس',
        'related' => 'محتوى ذو صلة',
        'references' => 'المراجع',
        'media' => 'الوسائط',
    ],

    // Validation
    'validation' => [
        'title_required' => 'عنوان الحدث مطلوب',
        'category_required' => 'التصنيف مطلوب',
        'date_required' => 'التاريخ مطلوب',
        'year_required' => 'السنة مطلوبة',
        'invalid_date' => 'التاريخ غير صحيح',
    ],

    // Help Text
    'help' => [
        'hijri_date' => 'أدخل التاريخ بالتقويم الهجري',
        'date_precision' => 'حدد دقة التاريخ (دقيق، تقريبي، غير معروف)',
        'coordinates' => 'إحداثيات GPS للموقع (اختياري)',
        'timeline_order' => 'الترتيب الذي سيظهر به الحدث في الخط الزمني',
        'major_event' => 'الأحداث الرئيسية تظهر بشكل بارز في الخط الزمني',
    ],

    // Public site (frontend) — Seerah timeline, events directory, single event
    'public' => [
        // Index / timeline
        'page_title' => 'السيرة النبوية الشريفة',
        'page_description' => 'رحلة في حياة خاتَم المرسلين ﷺ عبر خط زمني تفاعلي يوثق أبرز محطات السيرة النبوية.',
        'hero_badge' => 'الخط الزمني التفاعلي',
        'hero_title' => 'رحلة سيد الخلق ﷺ',
        'hero_subtitle' => 'تتبّع أحداث السيرة النبوية من المولد الشريف إلى الرفيق الأعلى، مرتبة زمنياً بالتاريخين الهجري والميلادي.',
        'stat_events' => 'حدث موثّق',
        'stat_pivotal' => 'حدث مفصلي',
        'stat_phases' => 'مراحل رئيسية',
        'browse_all_events' => 'تصفّح جميع الأحداث',
        'pivotal_event' => 'حدث مفصلي',
        'jump_to_phase' => 'الانتقال إلى المرحلة',

        // Events directory
        'events_title' => 'أحداث السيرة النبوية',
        'events_description' => 'استكشف أحداث السيرة النبوية مع البحث والتصفية حسب النوع.',
        'events_heading' => 'دليل أحداث السيرة',
        'events_subheading' => 'ابحث وصفِّ أحداث السيرة النبوية بالتفصيل.',
        'search_placeholder' => 'ابحث عن حدث أو موقع...',
        'filter_all' => 'الكل',
        'results_count' => 'حدث',
        'read_more' => 'اقرأ المزيد',

        // Single event
        'breadcrumb_seerah' => 'السيرة النبوية',
        'section_intro' => 'المقدمة',
        'section_context' => 'السياق التاريخي',
        'section_details' => 'تفاصيل الحدث',
        'section_lessons' => 'الدروس والعبر',
        'section_sources' => 'المصادر والمراجع',
        'section_characters' => 'الشخصيات الرئيسية',
        'on_this_page' => 'في هذه الصفحة',
        'related_events' => 'أحداث ذات صلة',
        'share' => 'مشاركة',
        'views' => 'مشاهدة',
        'location_label' => 'المكان',
        'phase_label' => 'المرحلة',
        'era_label' => 'العهد',
        'untitled_event' => 'حدث بدون عنوان',
        'no_content' => 'لا يتوفر محتوى تفصيلي لهذا الحدث بعد.',
        'back_to_timeline' => 'العودة إلى الخط الزمني',
        'scroll_to_top' => 'العودة إلى الأعلى',

        // Index / timeline — hero & stats
        'hero_tagline' => 'رحلة النور والهداية',
        'hero_heading' => 'السيرة النبوية',
        'hero_heading_accent' => 'الخط الزمني التفاعلي',
        'stat_years_of_life' => 'عاماً من الحياة',
        'stat_years_of_dawah' => 'عاماً من الدعوة',
        'stat_historical_events' => 'حدثاً تاريخياً',
        'map_title' => 'خريطة الأحداث التفاعلية',
        'map_region' => 'خريطة شبه الجزيرة العربية',
        'map_hint' => 'انقر على العلامات لعرض تفاصيل الحدث',
        'gregorian_suffix' => 'م',
        'hijri_suffix' => 'هـ',
        'before_hijra_suffix' => 'ق.هـ',
        'hijri_year_label' => 'سنة',
        'no_results_title' => 'لا توجد نتائج',
        'no_results_hint' => 'جرب البحث بكلمات مختلفة أو اختر فئة أخرى',
        'cta_title' => 'استكشف المزيد من السيرة النبوية',
        'cta_subtitle' => 'تعمق في حياة النبي ﷺ من خلال مقالات تفصيلية، روايات موثقة، ومحتوى تفاعلي غني',
        'cta_watch_media' => 'شاهد المرئيات',

        // Events directory
        'events_span' => 'من عام الفيل حتى العهد الراشدي',
        'categories_label' => 'فئة',
        'sort_oldest_first' => 'الأقدم أولاً',
        'sort_newest_first' => 'الأحدث أولاً',
        'showing_results' => 'عرض :shown من :total حدث',
        'page_of' => 'الصفحة :current من :total',
        'reset_filters' => 'إعادة تعيين',
        'reset_search' => 'إعادة تعيين البحث',
        'previous' => 'السابق',
        'next' => 'التالي',

        // Single event — article & timeline styles
        'start_reading' => 'ابدأ القراءة',
        'save_article' => 'حفظ المقال',
        'saved' => 'محفوظ',
        'bookmark_added' => 'تمت الإضافة إلى المفضلة',
        'bookmark_removed' => 'تمت الإزالة من المفضلة',
        'link_copied' => 'تم نسخ الرابط',
        'event_location' => 'موقع الحدث',
        'event_location_map' => 'خريطة موقع الحدث',
        'event_gallery' => 'معرض صور الحدث',
        'related_article' => 'مقال ذو صلة',
        'event_navigation' => 'أحداث السيرة المجاورة',
        'previous_event' => 'الحدث السابق',
        'next_event' => 'الحدث التالي',
        'event_stats_heading' => 'أرقام الحدث',
        'event_timeline_heading' => 'الخط الزمني للأحداث',
        'explore_story' => 'استكشف القصة',
        'read_as_article' => 'قراءة كمقال',
        'read_as_timeline' => 'عرض كخط زمني',
        'like' => 'إعجاب',
        'print' => 'طباعة',
        'close' => 'إغلاق',

        // Hero-journey timeline (milestones / الحِقَب)
        'journey_badge' => 'رحلة النور.. ملحمة سيّد المرسلين ﷺ',
        'journey_subtitle' => 'تتبّع السيرة النبوية كرحلةٍ ملحمية عبر حِقَبها الكبرى، من المولد الشريف إلى الرفيق الأعلى.',
        'mode_journey' => 'الرحلة',
        'mode_articles' => 'المقالات',
        'stat_milestones' => 'حقبة',
        'stat_stations' => 'محطة',
        'stat_span' => 'النطاق الزمني',
        'milestone_chapter' => 'الحقبة',
        'jump_to_milestone' => 'الحِقَب',
        'stations_count' => 'محطات',
        'articles_mode_hint' => 'كل محطات السيرة مرتّبة زمنياً',
        'empty_journey' => 'لا توجد حِقَب منشورة بعد',
        'start_label' => 'بداية الرحلة',
        'finish_label' => 'اكتملت الرحلة',
        'finish_hint' => 'من المولد الشريف إلى الرفيق الأعلى',
        'you_are_here' => 'أنت هنا',
    ],
];
