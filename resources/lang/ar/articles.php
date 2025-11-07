<?php

declare(strict_types=1);

return [
    'title' => 'المقالات',
    'singular' => 'مقال',
    'plural' => 'المقالات',

    // Page Titles
    'index' => 'إدارة المقالات',
    'create' => 'إنشاء مقال جديد',
    'edit' => 'تعديل المقال',
    'show' => 'عرض المقال',
    'trash' => 'سلة مهملات المقالات',
    'description' => 'إدارة المقالات والمنشورات ومحتوى السيرة',
    'list_title' => 'قائمة المقالات',

    // Actions
    'actions' => [
        'create_new' => 'مقال جديد',
        'edit' => 'تعديل المقال',
        'delete' => 'حذف المقال',
        'restore' => 'استعادة المقال',
        'publish' => 'نشر المقال',
        'unpublish' => 'إلغاء نشر المقال',
        'schedule' => 'جدولة المقال',
        'duplicate' => 'تكرار المقال',
        'preview' => 'معاينة المقال',
        'view_on_site' => 'عرض في الموقع',
        'save_draft' => 'حفظ كمسودة',
        'save_publish' => 'حفظ ونشر',
        'save_schedule' => 'حفظ وجدولة',
        'move_to_trash' => 'نقل إلى سلة المهملات',
        'delete_permanently' => 'حذف نهائياً',
        'bulk_delete' => 'حذف المحدد',
        'bulk_publish' => 'نشر المحدد',
        'bulk_archive' => 'أرشفة المحدد',
        'export' => 'تصدير المقالات',
        'import' => 'استيراد المقالات',
    ],

    // Fields
    'fields' => [
        'id' => 'المعرّف',
        'image' => 'الصورة',
        'views' => 'المشاهدات',
        'title' => 'عنوان المقال',
        'title_ar' => 'العنوان بالعربية',
        'title_en' => 'العنوان بالإنجليزية',
        'title_fr' => 'العنوان بالفرنسية',
        'title_tr' => 'العنوان بالتركية',
        'title_ur' => 'العنوان بالأردية',
        'slug' => 'الرابط المختصر',
        'content' => 'محتوى المقال',
        'content_ar' => 'المحتوى بالعربية',
        'content_en' => 'المحتوى بالإنجليزية',
        'content_fr' => 'المحتوى بالفرنسية',
        'content_tr' => 'المحتوى بالتركية',
        'content_ur' => 'المحتوى بالأردية',
        'excerpt' => 'المقتطف',
        'excerpt_ar' => 'المقتطف بالعربية',
        'excerpt_en' => 'المقتطف بالإنجليزية',
        'excerpt_fr' => 'المقتطف بالفرنسية',
        'excerpt_tr' => 'المقتطف بالتركية',
        'excerpt_ur' => 'المقتطف بالأردية',
        'status' => 'حالة المقال',
        'category' => 'التصنيف',
        'categories' => 'التصنيفات',
        'tags' => 'الوسوم',
        'featured_image' => 'الصورة البارزة',
        'featured_image_alt' => 'نص بديل للصورة',
        'featured_image_caption' => 'تعليق الصورة',
        'author' => 'الكاتب',
        'published_at' => 'تاريخ النشر',
        'scheduled_at' => 'تاريخ الجدولة',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ آخر تحديث',
        'views_count' => 'عدد المشاهدات',
        'favorites_count' => 'عدد المفضلة',
        'shares_count' => 'عدد المشاركات',
        'is_featured' => 'مميز',
        'is_premium' => 'محتوى مميز',
        'allow_comments' => 'السماح بالتعليقات',
        'reading_time' => 'وقت القراءة',
        'word_count' => 'عدد الكلمات',
        'series' => 'السلسلة',
        'series_order' => 'ترتيب السلسلة',
        'related_articles' => 'مقالات ذات صلة',
    ],

    // Status Types
    'status' => [
        'all' => 'كل الحالات',
        'draft' => 'مسودة',
        'published' => 'منشور',
        'scheduled' => 'مجدول',
        'pending' => 'قيد المراجعة',
        'archived' => 'مؤرشف',
        'trashed' => 'في سلة المهملات',
    ],

    // Placeholders
    'placeholders' => [
        'title' => 'أدخل عنوان المقال...',
        'slug' => 'الرابط-المختصر-للمقال',
        'excerpt' => 'أدخل ملخصاً مختصراً للمقال...',
        'content' => 'ابدأ بكتابة المحتوى...',
        'search' => 'ابحث عن المقالات...',
        'select_category' => 'اختر التصنيف',
        'select_tags' => 'اختر الوسوم',
        'select_author' => 'اختر الكاتب',
        'select_series' => 'اختر السلسلة',
    ],

    // Messages
    'messages' => [
        'created' => 'تم إنشاء المقال بنجاح',
        'updated' => 'تم تحديث المقال بنجاح',
        'deleted' => 'تم حذف المقال بنجاح',
        'restored' => 'تم استعادة المقال بنجاح',
        'published' => 'تم نشر المقال بنجاح',
        'unpublished' => 'تم إلغاء نشر المقال بنجاح',
        'scheduled' => 'تم جدولة المقال بنجاح',
        'duplicated' => 'تم تكرار المقال بنجاح',
        'bulk_deleted' => 'تم حذف المقالات المحددة بنجاح',
        'bulk_published' => 'تم نشر المقالات المحددة بنجاح',
        'bulk_archived' => 'تم أرشفة المقالات المحددة بنجاح',
        'not_found' => 'المقال غير موجود',
        'cannot_delete' => 'لا يمكن حذف هذا المقال',
        'error_creating' => 'حدث خطأ أثناء إنشاء المقال',
        'error_updating' => 'حدث خطأ أثناء تحديث المقال',
        'error_deleting' => 'حدث خطأ أثناء حذف المقال',
    ],

    // Filters
    'filters' => [
        'all' => 'كل المقالات',
        'mine' => 'مقالاتي',
        'published' => 'المنشورة',
        'drafts' => 'المسودات',
        'scheduled' => 'المجدولة',
        'pending' => 'قيد المراجعة',
        'archived' => 'المؤرشفة',
        'trashed' => 'سلة المهملات',
        'featured' => 'المميزة',
        'recent' => 'الأحدث',
        'popular' => 'الأكثر شعبية',
        'by_category' => 'حسب التصنيف',
        'by_tag' => 'حسب الوسم',
        'by_author' => 'حسب الكاتب',
        'by_date' => 'حسب التاريخ',
    ],

    // Sorting
    'sort' => [
        'newest' => 'الأحدث',
        'oldest' => 'الأقدم',
        'title_asc' => 'العنوان (أ-ي)',
        'title_desc' => 'العنوان (ي-أ)',
        'most_viewed' => 'الأكثر مشاهدة',
        'most_favorited' => 'الأكثر حفظاً',
        'most_shared' => 'الأكثر مشاركة',
        'updated_recently' => 'المحدثة مؤخراً',
    ],

    // Tabs
    'tabs' => [
        'all' => 'الكل',
        'content' => 'المحتوى',
        'settings' => 'الإعدادات',
        'seo' => 'تحسين محركات البحث',
        'translations' => 'الترجمات',
        'media' => 'الوسائط',
        'related' => 'محتوى ذو صلة',
        'analytics' => 'التحليلات',
        'revisions' => 'الإصدارات',
    ],

    // SEO Section
    'seo' => [
        'title' => 'عنوان محرك البحث',
        'description' => 'وصف محرك البحث',
        'keywords' => 'الكلمات المفتاحية',
        'og_title' => 'عنوان Facebook',
        'og_description' => 'وصف Facebook',
        'og_image' => 'صورة Facebook',
        'twitter_title' => 'عنوان Twitter',
        'twitter_description' => 'وصف Twitter',
        'twitter_image' => 'صورة Twitter',
        'canonical_url' => 'الرابط الأساسي',
        'robots' => 'توجيهات Robots',
        'index' => 'فهرسة',
        'noindex' => 'عدم الفهرسة',
        'follow' => 'متابعة الروابط',
        'nofollow' => 'عدم متابعة الروابط',
    ],

    // Statistics
    'stats' => [
        'total' => 'إجمالي المقالات',
        'published' => 'منشور',
        'draft' => 'مسودة',
        'drafts' => 'مسودات',
        'scheduled' => 'مجدول',
        'pending' => 'قيد المراجعة',
        'archived' => 'مؤرشف',
        'total_views' => 'إجمالي المشاهدات',
        'total_favorites' => 'إجمالي المفضلة',
        'average_reading_time' => 'متوسط وقت القراءة',
    ],

    // Confirmation Messages
    'confirm' => [
        'delete' => 'هل أنت متأكد من حذف هذا المقال؟',
        'duplicate' => 'هل أنت متأكد من تكرار هذا المقال؟',
        'publish' => 'هل أنت متأكد من نشر هذا المقال؟',
        'archive' => 'هل أنت متأكد من أرشفة هذا المقال؟',
        'bulk_delete' => 'هل أنت متأكد من حذف المقالات المحددة؟',
    ],

    // Empty States
    'empty' => [
        'title' => 'لا توجد مقالات بعد',
        'description' => 'ابدأ بإنشاء أول مقال لك',
        'no_results' => 'لا توجد نتائج مطابقة',
        'no_drafts' => 'لا توجد مسودات',
        'no_published' => 'لا توجد مقالات منشورة',
        'no_scheduled' => 'لا توجد مقالات مجدولة',
        'no_trash' => 'سلة المهملات فارغة',
    ],

    // Validation
    'validation' => [
        'title_required' => 'عنوان المقال مطلوب',
        'content_required' => 'محتوى المقال مطلوب',
        'category_required' => 'التصنيف مطلوب',
        'slug_unique' => 'الرابط المختصر مستخدم بالفعل',
        'published_date_future' => 'تاريخ النشر يجب أن يكون في المستقبل',
    ],

    // Help Text
    'help' => [
        'slug' => 'الرابط المختصر للمقال. سيتم إنشاؤه تلقائياً من العنوان إذا تُرك فارغاً.',
        'excerpt' => 'ملخص مختصر للمقال. سيظهر في قوائم المقالات ونتائج البحث.',
        'featured_image' => 'الصورة الرئيسية للمقال. الحجم الموصى به: 1200×630 بكسل.',
        'featured' => 'المقالات المميزة تظهر في الصفحة الرئيسية والأقسام المميزة.',
        'allow_comments' => 'السماح للمستخدمين بالتعليق على هذا المقال.',
        'scheduled_at' => 'اختر تاريخاً ووقتاً لنشر المقال تلقائياً.',
        'series' => 'ربط هذا المقال بسلسلة مقالات.',
    ],
];
