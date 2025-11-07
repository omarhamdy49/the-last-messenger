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
];
