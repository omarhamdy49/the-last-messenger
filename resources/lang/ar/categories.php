<?php

declare(strict_types=1);

return [
    'title' => 'التصنيفات',
    'singular' => 'تصنيف',
    'plural' => 'التصنيفات',

    // Page Titles
    'index' => 'إدارة التصنيفات',
    'create' => 'إنشاء تصنيف جديد',
    'edit' => 'تعديل التصنيف',
    'show' => 'عرض التصنيف',

    // Actions
    'actions' => [
        'create_new' => 'تصنيف جديد',
        'edit' => 'تعديل التصنيف',
        'delete' => 'حذف التصنيف',
        'restore' => 'استعادة التصنيف',
        'duplicate' => 'تكرار التصنيف',
        'merge' => 'دمج التصنيفات',
        'reorder' => 'إعادة ترتيب',
        'bulk_delete' => 'حذف المحدد',
        'export' => 'تصدير التصنيفات',
        'import' => 'استيراد التصنيفات',
    ],

    // Fields
    'fields' => [
        'name' => 'اسم التصنيف',
        'name_ar' => 'الاسم بالعربية',
        'name_en' => 'الاسم بالإنجليزية',
        'name_fr' => 'الاسم بالفرنسية',
        'name_tr' => 'الاسم بالتركية',
        'name_ur' => 'الاسم بالأردية',
        'slug' => 'الرابط المختصر',
        'description' => 'الوصف',
        'description_ar' => 'الوصف بالعربية',
        'description_en' => 'الوصف بالإنجليزية',
        'description_fr' => 'الوصف بالفرنسية',
        'description_tr' => 'الوصف بالتركية',
        'description_ur' => 'الوصف بالأردية',
        'parent' => 'التصنيف الأب',
        'parent_category' => 'التصنيف الرئيسي',
        'icon' => 'الأيقونة',
        'color' => 'اللون',
        'image' => 'الصورة',
        'order' => 'الترتيب',
        'is_active' => 'نشط',
        'is_featured' => 'مميز',
        'articles_count' => 'عدد المقالات',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
    ],

    // Placeholders
    'placeholders' => [
        'name' => 'أدخل اسم التصنيف...',
        'slug' => 'الرابط-المختصر',
        'description' => 'أدخل وصف التصنيف...',
        'search' => 'ابحث عن التصنيفات...',
        'select_parent' => 'اختر التصنيف الرئيسي',
        'select_icon' => 'اختر أيقونة',
        'select_color' => 'اختر لون',
    ],

    // Messages
    'messages' => [
        'created' => 'تم إنشاء التصنيف بنجاح',
        'updated' => 'تم تحديث التصنيف بنجاح',
        'deleted' => 'تم حذف التصنيف بنجاح',
        'restored' => 'تم استعادة التصنيف بنجاح',
        'duplicated' => 'تم تكرار التصنيف بنجاح',
        'merged' => 'تم دمج التصنيفات بنجاح',
        'reordered' => 'تم إعادة ترتيب التصنيفات بنجاح',
        'bulk_deleted' => 'تم حذف التصنيفات المحددة بنجاح',
        'not_found' => 'التصنيف غير موجود',
        'cannot_delete' => 'لا يمكن حذف هذا التصنيف',
        'has_children' => 'لا يمكن حذف تصنيف يحتوي على تصنيفات فرعية',
        'has_articles' => 'لا يمكن حذف تصنيف يحتوي على مقالات',
        'error_creating' => 'حدث خطأ أثناء إنشاء التصنيف',
        'error_updating' => 'حدث خطأ أثناء تحديث التصنيف',
        'error_deleting' => 'حدث خطأ أثناء حذف التصنيف',
    ],

    // Filters
    'filters' => [
        'all' => 'كل التصنيفات',
        'active' => 'النشطة',
        'inactive' => 'غير النشطة',
        'featured' => 'المميزة',
        'parent_only' => 'الرئيسية فقط',
        'with_articles' => 'التي تحتوي على مقالات',
        'empty' => 'الفارغة',
    ],

    // Sorting
    'sort' => [
        'name_asc' => 'الاسم (أ-ي)',
        'name_desc' => 'الاسم (ي-أ)',
        'order_asc' => 'الترتيب (تصاعدي)',
        'order_desc' => 'الترتيب (تنازلي)',
        'articles_count_desc' => 'عدد المقالات (الأكثر)',
        'articles_count_asc' => 'عدد المقالات (الأقل)',
        'newest' => 'الأحدث',
        'oldest' => 'الأقدم',
    ],

    // Empty States
    'empty' => [
        'title' => 'لا توجد تصنيفات بعد',
        'description' => 'ابدأ بإنشاء أول تصنيف لك',
        'no_results' => 'لا توجد نتائج مطابقة',
        'no_subcategories' => 'لا توجد تصنيفات فرعية',
    ],

    // Tree View
    'tree' => [
        'expand_all' => 'توسيع الكل',
        'collapse_all' => 'طي الكل',
        'show_tree' => 'عرض الشجرة',
        'show_list' => 'عرض القائمة',
        'level' => 'المستوى',
        'subcategories' => 'تصنيفات فرعية',
    ],

    // Statistics
    'stats' => [
        'total' => 'إجمالي التصنيفات',
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'with_articles' => 'تحتوي على مقالات',
        'empty' => 'فارغة',
    ],

    // Validation
    'validation' => [
        'name_required' => 'اسم التصنيف مطلوب',
        'slug_unique' => 'الرابط المختصر مستخدم بالفعل',
        'parent_not_self' => 'لا يمكن أن يكون التصنيف أباً لنفسه',
        'parent_not_child' => 'لا يمكن اختيار تصنيف فرعي كأب',
    ],

    // Help Text
    'help' => [
        'name' => 'اسم التصنيف كما سيظهر للمستخدمين',
        'slug' => 'الرابط المختصر للتصنيف. سيتم إنشاؤه تلقائياً من الاسم إذا تُرك فارغاً.',
        'parent' => 'اختر تصنيفاً رئيسياً لإنشاء تصنيف فرعي',
        'order' => 'الترتيب الذي سيظهر به التصنيف في القوائم',
        'icon' => 'أيقونة لتمثيل التصنيف',
        'color' => 'لون مميز للتصنيف',
    ],
];
