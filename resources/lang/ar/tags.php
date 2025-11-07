<?php

declare(strict_types=1);

return [
    'title' => 'الوسوم',
    'singular' => 'وسم',
    'plural' => 'الوسوم',

    // Page Titles
    'index' => 'إدارة الوسوم',
    'create' => 'إنشاء وسم جديد',
    'edit' => 'تعديل الوسم',
    'show' => 'عرض الوسم',

    // Actions
    'actions' => [
        'create_new' => 'وسم جديد',
        'edit' => 'تعديل الوسم',
        'delete' => 'حذف الوسم',
        'merge' => 'دمج الوسوم',
        'bulk_delete' => 'حذف المحدد',
        'export' => 'تصدير الوسوم',
        'import' => 'استيراد الوسوم',
    ],

    // Fields
    'fields' => [
        'name' => 'اسم الوسم',
        'name_ar' => 'الاسم بالعربية',
        'name_en' => 'الاسم بالإنجليزية',
        'name_fr' => 'الاسم بالفرنسية',
        'name_tr' => 'الاسم بالتركية',
        'name_ur' => 'الاسم بالأردية',
        'slug' => 'الرابط المختصر',
        'description' => 'الوصف',
        'color' => 'اللون',
        'articles_count' => 'عدد المقالات',
        'usage_count' => 'عدد مرات الاستخدام',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
    ],

    // Placeholders
    'placeholders' => [
        'name' => 'أدخل اسم الوسم...',
        'slug' => 'الرابط-المختصر',
        'description' => 'أدخل وصف الوسم...',
        'search' => 'ابحث عن الوسوم...',
        'select_tags' => 'اختر الوسوم',
        'add_tags' => 'أضف وسوماً',
    ],

    // Messages
    'messages' => [
        'created' => 'تم إنشاء الوسم بنجاح',
        'updated' => 'تم تحديث الوسم بنجاح',
        'deleted' => 'تم حذف الوسم بنجاح',
        'merged' => 'تم دمج الوسوم بنجاح',
        'bulk_deleted' => 'تم حذف الوسوم المحددة بنجاح',
        'not_found' => 'الوسم غير موجود',
        'cannot_delete' => 'لا يمكن حذف هذا الوسم',
        'has_articles' => 'لا يمكن حذف وسم يحتوي على مقالات',
        'error_creating' => 'حدث خطأ أثناء إنشاء الوسم',
        'error_updating' => 'حدث خطأ أثناء تحديث الوسم',
        'error_deleting' => 'حدث خطأ أثناء حذف الوسم',
    ],

    // Filters
    'filters' => [
        'all' => 'كل الوسوم',
        'popular' => 'الشائعة',
        'unused' => 'غير المستخدمة',
        'recent' => 'الأحدث',
    ],

    // Sorting
    'sort' => [
        'name_asc' => 'الاسم (أ-ي)',
        'name_desc' => 'الاسم (ي-أ)',
        'usage_desc' => 'الأكثر استخداماً',
        'usage_asc' => 'الأقل استخداماً',
        'newest' => 'الأحدث',
        'oldest' => 'الأقدم',
    ],

    // Empty States
    'empty' => [
        'title' => 'لا توجد وسوم بعد',
        'description' => 'ابدأ بإنشاء أول وسم لك',
        'no_results' => 'لا توجد نتائج مطابقة',
        'no_unused' => 'لا توجد وسوم غير مستخدمة',
    ],

    // Statistics
    'stats' => [
        'total' => 'إجمالي الوسوم',
        'used' => 'مستخدم',
        'unused' => 'غير مستخدم',
        'most_popular' => 'الأكثر شيوعاً',
    ],

    // Cloud
    'cloud' => [
        'title' => 'سحابة الوسوم',
        'show_cloud' => 'عرض السحابة',
        'hide_cloud' => 'إخفاء السحابة',
    ],

    // Validation
    'validation' => [
        'name_required' => 'اسم الوسم مطلوب',
        'slug_unique' => 'الرابط المختصر مستخدم بالفعل',
        'name_unique' => 'اسم الوسم مستخدم بالفعل',
    ],

    // Help Text
    'help' => [
        'name' => 'اسم الوسم كما سيظهر للمستخدمين',
        'slug' => 'الرابط المختصر للوسم. سيتم إنشاؤه تلقائياً من الاسم إذا تُرك فارغاً.',
        'description' => 'وصف مختصر للوسم (اختياري)',
        'merge' => 'دمج عدة وسوم في وسم واحد',
    ],
];
