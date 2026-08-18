<?php

declare(strict_types=1);

return [
    'title' => 'الأماكن',
    'singular' => 'مكان',
    'create' => 'إضافة مكان',
    'edit' => 'تعديل المكان',
    'show' => 'تفاصيل المكان',
    'index_description' => 'المرجع الموحّد لأسماء الأماكن المستخدمة في السيرة والصحابة والقرآن.',

    'fields' => [
        'slug' => 'المعرّف',
        'type' => 'النوع',
        'parent' => 'يتبع',
        'no_parent' => 'بدون',
        'depth' => 'المستوى',
        'latitude' => 'خط العرض',
        'longitude' => 'خط الطول',
        'coordinates' => 'الإحداثيات',
        'aliases' => 'التسميات البديلة',
        'name' => 'الاسم',
        'description' => 'الوصف',
        'is_active' => 'مفعّل',
        'display_order' => 'الترتيب',
        'references' => 'الارتباطات',
    ],

    'hints' => [
        'slug' => 'حروف إنجليزية صغيرة وأرقام وشرطات فقط، مثل: makkah',
        'aliases' => 'كل تسمية في سطر — تُستخدم لمطابقة النصوص القديمة وللبحث.',
        'coordinates' => 'اتركها فارغة إذا لم تكن معروفة؛ عندها لا تظهر الخريطة.',
        'parent' => 'المكان الأكبر الذي يقع بداخله، مثل: مكة المكرمة تتبع الحجاز.',
    ],

    'filters' => [
        'all_types' => 'كل الأنواع',
        'search' => 'ابحث بالاسم أو التسمية البديلة',
        'active_only' => 'المفعّلة فقط',
        'with_coordinates' => 'التي لها إحداثيات',
        'unmapped' => 'غير مرتبطة بمكان',
    ],

    'actions' => [
        'save' => 'حفظ',
        'cancel' => 'إلغاء',
        'delete' => 'حذف',
        'activate' => 'تفعيل',
        'deactivate' => 'إلغاء التفعيل',
        'add_alias' => 'إضافة تسمية',
        'remove_alias' => 'حذف التسمية',
    ],

    'messages' => [
        'created' => 'تم إنشاء المكان.',
        'updated' => 'تم تحديث المكان.',
        'deleted' => 'تم حذف المكان.',
        'activated' => 'تم تفعيل المكان.',
        'deactivated' => 'تم إلغاء تفعيل المكان.',
        'empty' => 'لا توجد أماكن بعد.',
        'unmapped_count' => 'سجلات بحاجة إلى ربط: :count',
    ],

    'errors' => [
        'in_use' => 'لا يمكن حذف هذا المكان لأنه مستخدم في محتوى قائم.',
        'in_use_detail' => 'مرتبط بـ :details.',
        'cycle' => 'لا يمكن أن يتبع المكان نفسه أو أحد الأماكن التابعة له.',
        'self_parent' => 'لا يمكن أن يتبع المكان نفسه.',
        'alias_taken' => 'التسمية «:alias» مستخدمة بالفعل للمكان «:location».',
        'name_required_ar' => 'الاسم بالعربية مطلوب.',
    ],

    'usage' => [
        'seerah_events' => ':count حدثًا',
        'muslims' => ':count شخصية',
        'quran_surahs' => ':count سورة',
    ],

    'types' => [
        'city' => 'مدينة',
        'region' => 'إقليم',
        'country' => 'بلد',
        'mountain' => 'جبل',
        'valley' => 'وادٍ',
        'mosque' => 'مسجد',
        'well' => 'بئر',
        'battlefield' => 'موقع غزوة',
        'landmark' => 'معلم',
    ],

    'public' => [
        'place' => 'المكان',
        'place_note' => 'وصف المكان',
        'birth_place' => 'مكان الميلاد',
        'revelation_place' => 'مكان النزول',
        'view_on_map' => 'عرض على الخريطة',
    ],
];
