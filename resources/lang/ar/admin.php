<?php

declare(strict_types=1);

return [
    // General
    'dashboard' => 'لوحة التحكم',
    'welcome' => 'مرحباً',
    'home' => 'الرئيسية',
    'logout' => 'تسجيل الخروج',
    'profile' => 'الملف الشخصي',
    'settings' => 'الإعدادات',
    'search' => 'بحث...',
    'save' => 'حفظ',
    'cancel' => 'إلغاء',
    'confirm' => 'تأكيد',
    'delete' => 'حذف',
    'edit' => 'تعديل',
    'view' => 'عرض',
    'details' => 'التفاصيل',
    'create' => 'إنشاء',
    'actions' => 'الإجراءات',
    'status' => 'الحالة',
    'date' => 'التاريخ',
    'loading' => 'جاري التحميل...',
    'no_data' => 'لا توجد بيانات',
    'confirm_delete' => 'هل أنت متأكد من الحذف؟',
    'delete_warning' => 'لا يمكن التراجع عن هذا الإجراء.',
    'copyright' => 'خاتم المرسلین. جميع الحقوق محفوظة لامة محمد ﷺ.',
    'documentation' => 'التوثيق',
    'support' => 'الدعم',
    'api_docs' => 'توثيق API',
    'notifications' => 'الإشعارات',
    'no_notifications' => 'لا توجد إشعارات',
    'view_all_notifications' => 'عرض كل الإشعارات',
    'mark_as_read' => 'تعليم كمقروء',
    'admin_panel' => 'لوحة الإدارة',
    'analytics' => 'التحليلات',
    'no_results' => 'لا توجد نتائج',
    'help' => 'المساعدة',

    // Navigation
    'nav' => [
        'main_menu' => 'القائمة الرئيسية',
        'content' => 'المحتوى',
        'media' => 'الوسائط',
        'users' => 'المستخدمون',
        'settings' => 'الإعدادات',
        'hadith' => 'الحديث الشريف',
    ],

    // Dashboard
    'dashboard_title' => 'لوحة التحكم الرئيسية',
    'dashboard_subtitle' => 'نظرة عامة على نظام الموسوعة الإسلامية',
    'statistics' => 'الإحصائيات',
    'recent_activity' => 'النشاط الأخير',
    'quick_actions' => 'إجراءات سريعة',
    'view_all' => 'عرض الكل',

    // Statistics Cards
    'total' => 'المجموع',
    'published' => 'منشور',
    'draft' => 'مسودة',
    'archived' => 'أرشيف',
    'verified' => 'مُفعّل',
    'unverified' => 'غير مُفعّل',

    // Modules
    'articles' => 'المقالات',
    'article' => 'مقال',
    'articles_count' => '{0} لا توجد مقالات|{1} مقال واحد|{2} مقالان|[3,10] :count مقالات|[11,*] :count مقالة',
    'new_article' => 'مقال جديد',
    'manage_articles' => 'إدارة المقالات',

    'hadith' => 'الأحاديث',
    'hadiths' => 'الأحاديث',
    'hadith_count' => '{0} لا توجد أحاديث|{1} حديث واحد|{2} حديثان|[3,10] :count أحاديث|[11,*] :count حديث',
    'new_hadith' => 'حديث جديد',
    'manage_hadith' => 'إدارة الأحاديث',
    'hadith_books' => 'كتب الحديث',

    'media_programs' => 'البرامج الإعلامية',
    'media_program' => 'برنامج إعلامي',
    'programs_count' => '{0} لا توجد برامج|{1} برنامج واحد|{2} برنامجان|[3,10] :count برامج|[11,*] :count برنامج',
    'new_program' => 'برنامج جديد',
    'manage_programs' => 'إدارة البرامج',
    'episodes' => 'الحلقات',

    'quran' => 'القرآن الكريم',
    'quran_surahs' => 'سور القرآن',
    'quran_verses' => 'آيات القرآن',
    'verses_count' => '{0} لا توجد آيات|{1} آية واحدة|{2} آيتان|[3,10] :count آيات|[11,*] :count آية',
    'manage_quran' => 'إدارة القرآن',

    'sahaba' => 'الصحابة',
    'sahabi' => 'صحابي',
    'sahaba_count' => '{0} لا يوجد صحابة|{1} صحابي واحد|{2} صحابيان|[3,10] :count صحابة|[11,*] :count صحابي',
    'new_sahabi' => 'صحابي جديد',
    'manage_sahaba' => 'إدارة الصحابة',
    'companions' => 'الصحابة الكرام',

    'seerah' => 'السيرة النبوية',
    'seerah_events' => 'أحداث السيرة',
    'seerah_event' => 'حدث من السيرة',
    'events_count' => '{0} لا توجد أحداث|{1} حدث واحد|{2} حدثان|[3,10] :count أحداث|[11,*] :count حدث',
    'new_event' => 'حدث جديد',
    'manage_seerah' => 'إدارة السيرة',
    'timeline' => 'الخط الزمني',

    'genealogy' => 'شجرة العائلة',
    'family_tree' => 'شجرة عائلة النبي ﷺ',
    'manage_genealogy' => 'إدارة شجرة العائلة',
    'people' => 'الأشخاص',
    'relationships' => 'العلاقات',

    'users' => 'المستخدمون',
    'user' => 'مستخدم',
    'users_count' => '{0} لا يوجد مستخدمون|{1} مستخدم واحد|{2} مستخدمان|[3,10] :count مستخدمون|[11,*] :count مستخدم',
    'new_user' => 'مستخدم جديد',
    'manage_users' => 'إدارة المستخدمين',
    'user_management' => 'إدارة المستخدمين',

    'roles' => 'الصلاحيات',
    'role' => 'صلاحية',
    'permissions' => 'الأذونات',
    'manage_roles' => 'إدارة الصلاحيات',
    'assign_roles' => 'تعيين الصلاحيات',

    // Categories & Tags
    'categories' => 'التصنيفات',
    'category' => 'تصنيف',
    'tags' => 'الوسوم',
    'tag' => 'وسم',

    // Forms
    'title' => 'العنوان',
    'title_ar' => 'العنوان بالعربية',
    'title_en' => 'العنوان بالإنجليزية',
    'title_fr' => 'العنوان بالفرنسية',
    'title_tr' => 'العنوان بالتركية',
    'title_ur' => 'العنوان بالأردية',
    'content' => 'المحتوى',
    'description' => 'الوصف',
    'excerpt' => 'مقتطف',
    'image' => 'الصورة',
    'video' => 'الفيديو',
    'author' => 'الكاتب',
    'created_at' => 'تاريخ الإنشاء',
    'updated_at' => 'تاريخ التحديث',

    // Messages
    'success' => 'نجح!',
    'error' => 'خطأ!',
    'created_successfully' => 'تم الإنشاء بنجاح',
    'updated_successfully' => 'تم التحديث بنجاح',
    'deleted_successfully' => 'تم الحذف بنجاح',
    'error_occurred' => 'حدث خطأ. يرجى المحاولة مرة أخرى.',
    'items_published' => 'عنصر تم نشره بنجاح',
    'operation_failed'          => 'فشلت العملية. يرجى المحاولة مرة أخرى.',
    'category_operation_failed' => 'فشلت عملية الفئة. يرجى المحاولة مرة أخرى.',
    'article_create_failed'     => 'فشل إنشاء المقالة. يرجى المحاولة مرة أخرى.',
    'article_update_failed'     => 'فشل تحديث المقالة. يرجى المحاولة مرة أخرى.',
    'article_delete_failed'     => 'فشل حذف المقالة. يرجى المحاولة مرة أخرى.',

    // Pagination
    'showing' => 'عرض',
    'to' => 'إلى',
    'of' => 'من',
    'results' => 'نتيجة',
    'previous' => 'السابق',
    'next' => 'التالي',

    // Authentication
    'login' => 'تسجيل الدخول',
    'email' => 'البريد الإلكتروني',
    'password' => 'كلمة المرور',
    'remember_me' => 'تذكرني',
    'forgot_password' => 'نسيت كلمة المرور؟',
    'login_subtitle' => 'أدخل بيانات الدخول للوصول إلى لوحة التحكم',
    'admin_access_only' => 'هذه البوابة مخصصة للمسؤولين فقط',
    'unauthorized' => 'غير مصرح',
    'no_admin_access' => 'ليس لديك صلاحيات الإدارة',

    // Time
    'just_now' => 'الآن',
    'minutes_ago' => 'منذ :count دقيقة|منذ :count دقائق',
    'hours_ago' => 'منذ :count ساعة|منذ :count ساعات',
    'days_ago' => 'منذ :count يوم|منذ :count أيام',
    'weeks_ago' => 'منذ :count أسبوع|منذ :count أسابيع',
    'months_ago' => 'منذ :count شهر|منذ :count أشهر',
    'years_ago' => 'منذ :count عام|منذ :count أعوام',

    // Charts
    'content_growth' => 'نمو المحتوى',
    'popular_content' => 'المحتوى الشائع',
    'views' => 'المشاهدات',
    'favorites' => 'المفضلة',

    // Activity Types
    'activity_types' => [
        'article' => 'مقال',
        'hadith' => 'حديث',
        'media_program' => 'برنامج إعلامي',
        'seerah_event' => 'حدث من السيرة',
        'sahabi' => 'صحابي',
    ],

    // Status Types
    'status_types' => [
        'published' => 'منشور',
        'draft' => 'مسودة',
        'archived' => 'أرشيف',
        'pending' => 'قيد المراجعة',
    ],

    // Bulk Actions
    'bulk_actions' => 'إجراءات جماعية',
    'select_all' => 'تحديد الكل',
    'bulk_delete' => 'حذف المحدد',
    'bulk_publish' => 'نشر المحدد',
    'bulk_archive' => 'أرشفة المحدد',
    'items_selected' => 'عنصر محدد|عناصر محددة',

    // Filters
    'filter' => 'تصفية',
    'filters' => 'التصفيات',
    'sort_by' => 'ترتيب حسب',
    'filter_by_status' => 'تصفية حسب الحالة',
    'filter_by_category' => 'تصفية حسب التصنيف',
    'all' => 'الكل',
    'reset_filters' => 'إعادة تعيين',

    // Dark/Light Mode
    'theme' => 'الثيم',
    'light_mode' => 'الوضع الفاتح',
    'dark_mode' => 'الوضع الداكن',
    'toggle_theme' => 'تبديل الوضع',

    // Breadcrumbs
    'breadcrumb' => [
        'home' => 'الرئيسية',
        'dashboard' => 'لوحة التحكم',
    ],

    // Hadith Sub-modules
    'hadith_collections' => 'المجموعات',
    'collection' => 'المجموعة',
    'hadith_narrators' => 'الرواة',
    'hadith_scholars' => 'العلماء',
    'hadith_themes' => 'الموضوعات',
    'hadith_grading' => 'درجات التصنيف',

    // Hadith Management
    'add_hadith' => 'إضافة حديث',
    'add_new_hadith' => 'إضافة حديث جديد',
    'create_new_hadith' => 'إنشاء حديث جديد في النظام',
    'hadith_information' => 'معلومات الحديث',
    'manage_hadiths' => 'إدارة الأحاديث الشريفة',
    'hadith_number' => 'رقم الحديث',
    'select_book' => 'اختر كتاب الحديث',
    'enter_hadith_number' => 'أدخل رقم الحديث',
    'narrator' => 'الراوي',
    'select_narrator' => 'اختر الراوي',
    'grading' => 'التقدير',
    'select_grading' => 'اختر مستوى التقدير',
    'companion_id' => 'معرّف الصحابي',
    'enter_companion_id' => 'أدخل معرّف الصحابي',
    'create_hadith' => 'إنشاء حديث',
    'back' => 'عودة',
    'edit_hadith' => 'تعديل الحديث',
    'save_changes' => 'حفظ التغييرات',
    'hadith_details' => 'تفاصيل الحديث',
    'basic_information' => 'المعلومات الأساسية',
    'book' => 'الكتاب',
    'metadata' => 'البيانات الوصفية',
    'id' => 'المعرّف',
    'total_hadiths' => 'إجمالي الأحاديث',
    'current_page' => 'الصفحة الحالية',
    'search_hadith_number' => 'ابحث برقم الحديث...',
    'no_hadiths_found' => 'لم يتم العثور على أحاديث',
    'translations' => 'الترجمات',
    'confirm_delete_hadith' => 'هل أنت متأكد من حذف هذا الحديث؟',
    'ungraded' => 'لم يتم التقديرللـــ بعد',
    'delete_hadith' => 'حذف الحديث',

    // Additional Modules
    'series' => 'السلاسل',
    'activity_logs' => 'سجل النشاطات',
    'scheduler' => 'جدولة المحتوى',

    // Data Table
    'data_table' => 'جدول البيانات',
    'records_found' => 'نتيجة',
    'export' => 'تصدير',
    'export_csv' => 'تصدير CSV',
    'export_excel' => 'تصدير Excel',
    'export_pdf' => 'تصدير PDF',
    'selected' => 'محدد',
    'rows_per_page' => 'الصفوف في الصفحة',
    'no_data_message' => 'لا توجد بيانات لعرضها',
    'clear' => 'مسح',
    'publish' => 'نشر',
    'trash' => 'المحذوفات',
    'view_details' => 'عرض التفاصيل',
    'search_placeholder' => 'ابدأ الكتابة للبحث...',

    // Filter Sidebar
    'advanced_filters' => 'فلاتر متقدمة',
    'filters_active'   => 'فلتر نشط',
    'clear_all'        => 'مسح الكل',
    'date_range'       => 'نطاق التاريخ',
    'from'             => 'من',
    'no_results'       => 'لا توجد نتائج',
    'special_filters'  => 'فلاتر خاصة',
    'featured_only'    => 'المميزة فقط',
    'pinned_only'      => 'المثبتة فقط',
    'reset_all'        => 'إعادة تعيين الكل',
    'apply_filters'    => 'تطبيق الفلاتر',

    // Category Not Found / Error Messages
    'category_not_found'     => 'الفئة المطلوبة غير موجودة.',
    'category_delete_failed' => 'فشل حذف الفئة. يرجى المحاولة مرة أخرى.',

    // Tag Not Found / Error Messages
    'tag_not_found'    => 'الوسم المطلوب غير موجود.',
    'tag_delete_failed' => 'فشل حذف الوسم. يرجى المحاولة مرة أخرى.',

    // Narrator Not Found / Error Messages
    'narrator_not_found'     => 'الراوي المطلوب غير موجود.',
    'narrator_delete_failed' => 'فشل حذف الراوي. يرجى المحاولة مرة أخرى.',
    'narrator_has_hadiths'   => 'لا يمكن حذف راوٍ مرتبط بأحاديث.',

    // Series Not Found / Error Messages
    'series_not_found'     => 'السلسلة المطلوبة غير موجودة.',
    'series_delete_failed' => 'فشل حذف السلسلة. يرجى المحاولة مرة أخرى.',

    // Category CRUD Messages
    'category_created'                => 'تم إنشاء الفئة بنجاح.',
    'category_updated'                => 'تم تحديث الفئة بنجاح.',
    'category_deleted'                => 'تم حذف الفئة بنجاح.',
    'category_restored'               => 'تم استعادة الفئة بنجاح.',
    'category_duplicated'             => 'تم تكرار الفئة بنجاح.',
    'category_duplicated_edit'        => 'تم تكرار الفئة بنجاح. يمكنك الآن تعديل النسخة.',
    'categories_reordered'            => 'تم إعادة ترتيب الفئات بنجاح.',
    'category_has_children'           => 'لا يمكن حذف الفئة التي تحتوي على فئات فرعية. يرجى حذف الفئات الفرعية أولاً.',
    'category_has_articles'           => 'لا يمكن حذف الفئة التي تحتوي على :count مقال. يرجى إعادة تعيين المقالات أولاً.',
    'categories_bulk_deleted'         => 'تم حذف :deleted فئة بنجاح.',
    'categories_bulk_deleted_partial' => 'تم حذف :deleted فئة بنجاح (:failed فشل).',

    // Tag CRUD Messages
    'tag_created'          => 'تم إنشاء الوسم بنجاح.',
    'tag_updated'          => 'تم تحديث الوسم بنجاح.',
    'tag_deleted'          => 'تم حذف الوسم بنجاح.',
    'tag_duplicate_exists' => 'وسم مشابه موجود بالفعل.',
    'tags_merged'          => 'تم دمج الوسوم بنجاح. تم نقل :count مقال.',
    'tags_bulk_deleted'    => 'تم حذف :count وسم بنجاح.',

    // Series CRUD Messages
    'series_created'            => 'تم إنشاء السلسلة بنجاح.',
    'series_updated'            => 'تم تحديث السلسلة بنجاح.',
    'series_deleted'            => 'تم حذف السلسلة بنجاح.',
    'series_articles_reordered' => 'تم إعادة ترتيب المقالات بنجاح.',
    'series_bulk_deleted'       => 'تم حذف :count سلسلة بنجاح.',

    // Reciter CRUD Messages
    'reciter_created'                => 'تم إنشاء القارئ بنجاح.',
    'reciter_updated'                => 'تم تحديث القارئ بنجاح.',
    'reciter_deleted'                => 'تم حذف القارئ بنجاح.',
    'reciter_create_failed'          => 'فشل إنشاء القارئ. يرجى المحاولة مرة أخرى.',
    'reciter_update_failed'          => 'فشل تحديث القارئ. يرجى المحاولة مرة أخرى.',
    'reciter_delete_failed'          => 'فشل حذف القارئ. يرجى المحاولة مرة أخرى.',
    'reciters_reordered'             => 'تم إعادة ترتيب القراء بنجاح.',
    'reciters_reorder_failed'        => 'فشل إعادة ترتيب القراء. يرجى المحاولة مرة أخرى.',
    'reciters_bulk_deleted'          => 'تم حذف :count قارئ بنجاح.',
    'reciters_bulk_delete_failed'    => 'فشل حذف القراء. يرجى المحاولة مرة أخرى.',
    'reciter_featured_toggled'       => 'تم تحديث حالة التمييز بنجاح.',
    'reciter_featured_toggle_failed' => 'فشل تحديث حالة التمييز. يرجى المحاولة مرة أخرى.',
    'reciter_search_failed'          => 'فشل البحث عن القراء. يرجى المحاولة مرة أخرى.',

    // Locale Switch Messages
    'locale_unsupported' => 'اللغة المحددة غير مدعومة.',
    'locale_changed'     => 'تم تغيير اللغة بنجاح.',

    // Narrator CRUD Messages
    'narrator_fetched'             => 'تم جلب الرواة بنجاح.',
    'narrator_created'             => 'تم إنشاء الراوي بنجاح.',
    'narrator_updated'             => 'تم تحديث الراوي بنجاح.',
    'narrator_deleted'             => 'تم حذف الراوي بنجاح.',
    'narrator_restored'            => 'تم استعادة الراوي بنجاح.',
    'narrator_bulk_deleted'        => 'تم حذف :count راوي بنجاح.',
    'narrator_statistics_fetched'  => 'تم جلب إحصائيات الراوي بنجاح.',

    // Scholar CRUD Messages
    'scholar_fetched'       => 'تم جلب العلماء بنجاح.',
    'scholar_created'       => 'تم إنشاء العالم بنجاح.',
    'scholar_create_failed' => 'فشل إنشاء العالم. يرجى المحاولة مرة أخرى.',
    'scholar_updated'       => 'تم تحديث العالم بنجاح.',
    'scholar_update_failed' => 'فشل تحديث العالم. يرجى المحاولة مرة أخرى.',
    'scholar_deleted'       => 'تم حذف العالم بنجاح.',
    'scholar_delete_failed' => 'فشل حذف العالم. يرجى المحاولة مرة أخرى.',
    'scholar_bulk_deleted'  => 'تم حذف :count عالم بنجاح.',

    // Grading Level Messages
    'grading_levels_fetched'           => 'تم جلب مستويات التصنيف بنجاح.',
    'grading_level_not_found'          => 'مستوى التصنيف غير موجود.',
    'grading_level_fetched'            => 'تم جلب مستوى التصنيف بنجاح.',
    'grading_level_statistics_fetched' => 'تم جلب إحصائيات مستوى التصنيف بنجاح.',

    // Theme CRUD Messages
    'theme_fetched'       => 'تم جلب المواضيع بنجاح.',
    'theme_created'       => 'تم إنشاء الموضوع بنجاح.',
    'theme_create_failed' => 'فشل إنشاء الموضوع. يرجى المحاولة مرة أخرى.',
    'theme_updated'       => 'تم تحديث الموضوع بنجاح.',
    'theme_update_failed' => 'فشل تحديث الموضوع. يرجى المحاولة مرة أخرى.',
    'theme_has_content'   => 'لا يمكن حذف موضوع مرتبط بمحتوى.',
    'theme_deleted'       => 'تم حذف الموضوع بنجاح.',
    'theme_delete_failed' => 'فشل حذف الموضوع. يرجى المحاولة مرة أخرى.',
    'theme_bulk_deleted'  => 'تم حذف :count موضوع بنجاح.',
];
