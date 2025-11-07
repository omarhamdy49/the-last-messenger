<?php

declare(strict_types=1);

return [
    'title' => 'شجرة الأنساب',
    'singular' => 'شخص',
    'plural' => 'الأشخاص',

    // Page Titles
    'index' => 'إدارة شجرة الأنساب',
    'sahaba' => 'الصحابة',
    'family_tree' => 'شجرة عائلة النبي ﷺ',
    'create' => 'إضافة شخص جديد',
    'edit' => 'تعديل الشخص',
    'show' => 'عرض الشخص',

    // Actions
    'actions' => [
        'create_person' => 'إضافة شخص',
        'edit_person' => 'تعديل الشخص',
        'delete_person' => 'حذف الشخص',
        'view_tree' => 'عرض الشجرة',
        'add_relationship' => 'إضافة علاقة',
        'add_child' => 'إضافة ابن/ابنة',
        'add_spouse' => 'إضافة زوج/زوجة',
        'add_parent' => 'إضافة والد/والدة',
        'export_tree' => 'تصدير الشجرة',
    ],

    // Fields
    'fields' => [
        'name' => 'الاسم',
        'name_ar' => 'الاسم بالعربية',
        'name_en' => 'الاسم بالإنجليزية',
        'full_name' => 'الاسم الكامل',
        'kunya' => 'الكنية',
        'laqab' => 'اللقب',
        'nisba' => 'النسبة',
        'title' => 'اللقب الشرفي',
        'gender' => 'الجنس',
        'birth_date' => 'تاريخ الميلاد',
        'birth_place' => 'مكان الميلاد',
        'death_date' => 'تاريخ الوفاة',
        'death_place' => 'مكان الوفاة',
        'age' => 'العمر',
        'biography' => 'السيرة الذاتية',
        'description' => 'الوصف',
        'virtues' => 'الفضائل',
        'status' => 'الصفة',
        'tribe' => 'القبيلة',
        'clan' => 'البطن',
        'lineage' => 'النسب',
        'father' => 'الأب',
        'mother' => 'الأم',
        'spouse' => 'الزوج/الزوجة',
        'spouses' => 'الأزواج',
        'children' => 'الأبناء',
        'sons' => 'الأبناء الذكور',
        'daughters' => 'الأبناء الإناث',
        'siblings' => 'الإخوة والأخوات',
        'generation' => 'الجيل',
        'is_prophet' => 'نبي',
        'is_companion' => 'صحابي',
        'is_family_member' => 'من آل البيت',
        'conversion_date' => 'تاريخ الإسلام',
        'image' => 'الصورة',
        'created_at' => 'تاريخ الإضافة',
        'updated_at' => 'تاريخ التحديث',
    ],

    // Gender
    'gender' => [
        'male' => 'ذكر',
        'female' => 'أنثى',
    ],

    // Status
    'status' => [
        'prophet' => 'نبي',
        'companion' => 'صحابي',
        'family' => 'من آل البيت',
        'wife' => 'زوجة',
        'child' => 'ابن/ابنة',
        'parent' => 'والد/والدة',
        'grandparent' => 'جد/جدة',
        'uncle' => 'عم',
        'aunt' => 'عمة',
    ],

    // Relationships
    'relationships' => [
        'title' => 'العلاقات',
        'father' => 'الأب',
        'mother' => 'الأم',
        'son' => 'ابن',
        'daughter' => 'ابنة',
        'husband' => 'زوج',
        'wife' => 'زوجة',
        'brother' => 'أخ',
        'sister' => 'أخت',
        'grandfather' => 'جد',
        'grandmother' => 'جدة',
        'grandson' => 'حفيد',
        'granddaughter' => 'حفيدة',
        'uncle_paternal' => 'عم',
        'aunt_paternal' => 'عمة',
        'uncle_maternal' => 'خال',
        'aunt_maternal' => 'خالة',
        'cousin' => 'ابن عم/ابن خال',
    ],

    // Companion Categories
    'companion_categories' => [
        'muhajireen' => 'المهاجرون',
        'ansar' => 'الأنصار',
        'badr' => 'أهل بدر',
        'uhud' => 'أهل أحد',
        'ashara' => 'العشرة المبشرون بالجنة',
        'ahl_bayt' => 'أهل البيت',
        'khulafa_rashidun' => 'الخلفاء الراشدون',
        'wives' => 'أمهات المؤمنين',
        'early_converts' => 'السابقون الأولون',
    ],

    // Tribes
    'tribes' => [
        'quraysh' => 'قريش',
        'hashim' => 'بنو هاشم',
        'umayya' => 'بنو أمية',
        'aws' => 'الأوس',
        'khazraj' => 'الخزرج',
        'taym' => 'بنو تيم',
        'adi' => 'بنو عدي',
        'makhzum' => 'بنو مخزوم',
    ],

    // Placeholders
    'placeholders' => [
        'name' => 'أدخل الاسم...',
        'full_name' => 'أدخل الاسم الكامل...',
        'kunya' => 'مثال: أبو القاسم',
        'laqab' => 'مثال: الصديق',
        'search' => 'ابحث عن شخص...',
        'select_father' => 'اختر الأب',
        'select_mother' => 'اختر الأم',
        'select_spouse' => 'اختر الزوج/الزوجة',
        'select_tribe' => 'اختر القبيلة',
    ],

    // Messages
    'messages' => [
        'created' => 'تمت إضافة الشخص بنجاح',
        'updated' => 'تم تحديث الشخص بنجاح',
        'deleted' => 'تم حذف الشخص بنجاح',
        'relationship_added' => 'تمت إضافة العلاقة بنجاح',
        'relationship_removed' => 'تم حذف العلاقة بنجاح',
        'not_found' => 'الشخص غير موجود',
        'cannot_delete' => 'لا يمكن حذف هذا الشخص',
        'has_descendants' => 'لا يمكن حذف شخص له ذرية',
        'error_creating' => 'حدث خطأ أثناء إضافة الشخص',
        'error_updating' => 'حدث خطأ أثناء تحديث الشخص',
    ],

    // Filters
    'filters' => [
        'all' => 'كل الأشخاص',
        'prophets' => 'الأنبياء',
        'companions' => 'الصحابة',
        'family' => 'آل البيت',
        'male' => 'الذكور',
        'female' => 'الإناث',
        'by_tribe' => 'حسب القبيلة',
        'by_generation' => 'حسب الجيل',
        'muhajireen' => 'المهاجرون',
        'ansar' => 'الأنصار',
    ],

    // Sorting
    'sort' => [
        'name_asc' => 'الاسم (أ-ي)',
        'name_desc' => 'الاسم (ي-أ)',
        'birth_date' => 'تاريخ الميلاد',
        'death_date' => 'تاريخ الوفاة',
        'generation' => 'الجيل',
    ],

    // Tree View
    'tree' => [
        'title' => 'شجرة الأنساب',
        'view_tree' => 'عرض الشجرة',
        'view_list' => 'عرض القائمة',
        'expand_all' => 'توسيع الكل',
        'collapse_all' => 'طي الكل',
        'zoom_in' => 'تكبير',
        'zoom_out' => 'تصغير',
        'reset' => 'إعادة تعيين',
        'center' => 'توسيط',
        'export_image' => 'تصدير كصورة',
        'export_pdf' => 'تصدير كملف PDF',
        'show_photos' => 'إظهار الصور',
        'hide_photos' => 'إخفاء الصور',
        'show_dates' => 'إظهار التواريخ',
        'hide_dates' => 'إخفاء التواريخ',
    ],

    // Statistics
    'stats' => [
        'total_people' => 'إجمالي الأشخاص',
        'total_companions' => 'إجمالي الصحابة',
        'total_family' => 'إجمالي آل البيت',
        'male_count' => 'عدد الذكور',
        'female_count' => 'عدد الإناث',
        'generations' => 'عدد الأجيال',
    ],

    // Empty States
    'empty' => [
        'title' => 'لا توجد بيانات في الشجرة بعد',
        'description' => 'ابدأ بإضافة أول شخص',
        'no_results' => 'لا توجد نتائج مطابقة',
        'no_children' => 'لا يوجد أبناء',
        'no_spouses' => 'لا يوجد أزواج',
        'no_relationships' => 'لا توجد علاقات',
    ],

    // Tabs
    'tabs' => [
        'biography' => 'السيرة الذاتية',
        'family' => 'العائلة',
        'relationships' => 'العلاقات',
        'descendants' => 'الذرية',
        'ancestors' => 'الأجداد',
        'siblings' => 'الإخوة',
        'virtues' => 'الفضائل',
    ],

    // Validation
    'validation' => [
        'name_required' => 'الاسم مطلوب',
        'gender_required' => 'الجنس مطلوب',
        'birth_before_death' => 'تاريخ الميلاد يجب أن يكون قبل تاريخ الوفاة',
        'cannot_be_own_parent' => 'لا يمكن أن يكون الشخص والداً لنفسه',
        'cannot_be_own_child' => 'لا يمكن أن يكون الشخص ابناً لنفسه',
    ],

    // Help Text
    'help' => [
        'kunya' => 'الكنية: أبو فلان أو أم فلان',
        'laqab' => 'اللقب: صفة أو لقب اشتهر به الشخص',
        'nisba' => 'النسبة: نسبته إلى قبيلة أو بلد أو مهنة',
        'generation' => 'الجيل: رقم الجيل من النبي ﷺ',
        'is_companion' => 'من رأى النبي ﷺ مؤمناً به ومات على الإسلام',
    ],
];
