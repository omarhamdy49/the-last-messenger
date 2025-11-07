<?php

declare(strict_types=1);

return [
    'title' => 'الإعدادات',
    'singular' => 'إعداد',
    'plural' => 'الإعدادات',

    // Page Titles
    'index' => 'إعدادات النظام',
    'general' => 'الإعدادات العامة',
    'system' => 'إعدادات النظام',
    'appearance' => 'المظهر',
    'email' => 'إعدادات البريد',
    'security' => 'الأمان',
    'backup' => 'النسخ الاحتياطي',
    'cache' => 'الذاكرة المؤقتة',
    'languages' => 'اللغات',
    'maintenance' => 'وضع الصيانة',

    // Actions
    'actions' => [
        'save_settings' => 'حفظ الإعدادات',
        'reset_settings' => 'إعادة تعيين الإعدادات',
        'clear_cache' => 'مسح الذاكرة المؤقتة',
        'optimize' => 'تحسين',
        'backup_now' => 'نسخ احتياطي الآن',
        'restore_backup' => 'استعادة النسخة الاحتياطية',
        'enable_maintenance' => 'تفعيل وضع الصيانة',
        'disable_maintenance' => 'تعطيل وضع الصيانة',
        'test_email' => 'اختبار البريد',
        'regenerate' => 'إعادة إنشاء',
    ],

    // General Settings
    'general' => [
        'site_name' => 'اسم الموقع',
        'site_description' => 'وصف الموقع',
        'site_keywords' => 'كلمات مفتاحية',
        'site_logo' => 'شعار الموقع',
        'site_favicon' => 'أيقونة الموقع',
        'site_url' => 'رابط الموقع',
        'admin_email' => 'بريد المدير',
        'timezone' => 'المنطقة الزمنية',
        'date_format' => 'تنسيق التاريخ',
        'time_format' => 'تنسيق الوقت',
        'default_language' => 'اللغة الافتراضية',
        'items_per_page' => 'العناصر لكل صفحة',
        'contact_email' => 'بريد التواصل',
        'contact_phone' => 'هاتف التواصل',
        'social_links' => 'روابط التواصل الاجتماعي',
    ],

    // Email Settings
    'email' => [
        'driver' => 'مزود البريد',
        'host' => 'خادم البريد',
        'port' => 'منفذ البريد',
        'username' => 'اسم المستخدم',
        'password' => 'كلمة المرور',
        'encryption' => 'التشفير',
        'from_address' => 'البريد المرسل',
        'from_name' => 'اسم المرسل',
        'test_email_address' => 'بريد الاختبار',
        'send_test_email' => 'إرسال بريد تجريبي',
    ],

    // Security Settings
    'security' => [
        'two_factor_auth' => 'المصادقة الثنائية',
        'force_https' => 'إجبار HTTPS',
        'password_expiry' => 'انتهاء صلاحية كلمة المرور',
        'password_expiry_days' => 'عدد الأيام حتى انتهاء الصلاحية',
        'min_password_length' => 'الحد الأدنى لطول كلمة المرور',
        'require_special_char' => 'طلب رمز خاص',
        'require_number' => 'طلب رقم',
        'require_uppercase' => 'طلب حرف كبير',
        'login_attempts' => 'محاولات تسجيل الدخول',
        'lockout_duration' => 'مدة القفل (دقائق)',
        'session_timeout' => 'مهلة الجلسة (دقائق)',
        'ip_whitelist' => 'القائمة البيضاء للـ IP',
        'enable_captcha' => 'تفعيل Captcha',
    ],

    // Cache Settings
    'cache' => [
        'driver' => 'مزود الذاكرة المؤقتة',
        'ttl' => 'مدة الذاكرة المؤقتة (ثواني)',
        'clear_all_cache' => 'مسح كل الذاكرة المؤقتة',
        'clear_config_cache' => 'مسح ذاكرة الإعدادات',
        'clear_route_cache' => 'مسح ذاكرة المسارات',
        'clear_view_cache' => 'مسح ذاكرة العروض',
        'cache_cleared' => 'تم مسح الذاكرة المؤقتة بنجاح',
    ],

    // Backup Settings
    'backup' => [
        'auto_backup' => 'نسخ احتياطي تلقائي',
        'backup_frequency' => 'تكرار النسخ الاحتياطي',
        'backup_location' => 'موقع النسخ الاحتياطي',
        'keep_backups' => 'عدد النسخ المحفوظة',
        'include_database' => 'تضمين قاعدة البيانات',
        'include_files' => 'تضمين الملفات',
        'last_backup' => 'آخر نسخة احتياطية',
        'backup_size' => 'حجم النسخة',
        'create_backup' => 'إنشاء نسخة احتياطية',
        'download_backup' => 'تحميل النسخة',
        'delete_backup' => 'حذف النسخة',
        'restore_backup' => 'استعادة النسخة',
    ],

    // Maintenance Settings
    'maintenance' => [
        'mode' => 'وضع الصيانة',
        'enabled' => 'مفعل',
        'disabled' => 'معطل',
        'message' => 'رسالة الصيانة',
        'allowed_ips' => 'عناوين IP المسموح بها',
        'retry_after' => 'إعادة المحاولة بعد',
        'end_time' => 'وقت الانتهاء المتوقع',
    ],

    // Language Settings
    'languages' => [
        'title' => 'اللغات',
        'available_languages' => 'اللغات المتاحة',
        'default_language' => 'اللغة الافتراضية',
        'rtl' => 'من اليمين إلى اليسار',
        'ltr' => 'من اليسار إلى اليمين',
        'direction' => 'اتجاه النص',
        'code' => 'رمز اللغة',
        'name' => 'اسم اللغة',
        'native_name' => 'الاسم الأصلي',
        'flag' => 'العلم',
        'enabled' => 'مفعل',
        'is_default' => 'افتراضي',
    ],

    // Appearance Settings
    'appearance' => [
        'theme' => 'الثيم',
        'dark_mode' => 'الوضع الداكن',
        'light_mode' => 'الوضع الفاتح',
        'primary_color' => 'اللون الأساسي',
        'secondary_color' => 'اللون الثانوي',
        'accent_color' => 'لون التمييز',
        'font_family' => 'نوع الخط',
        'font_size' => 'حجم الخط',
        'sidebar_position' => 'موضع الشريط الجانبي',
        'header_style' => 'نمط الرأس',
        'custom_css' => 'CSS مخصص',
        'custom_js' => 'JavaScript مخصص',
    ],

    // System Info
    'system' => [
        'info' => 'معلومات النظام',
        'version' => 'الإصدار',
        'php_version' => 'إصدار PHP',
        'laravel_version' => 'إصدار Laravel',
        'database_version' => 'إصدار قاعدة البيانات',
        'server' => 'الخادم',
        'os' => 'نظام التشغيل',
        'disk_space' => 'المساحة المتاحة',
        'memory_usage' => 'استخدام الذاكرة',
        'upload_max_size' => 'الحد الأقصى للرفع',
        'post_max_size' => 'الحد الأقصى للإرسال',
        'max_execution_time' => 'وقت التنفيذ الأقصى',
    ],

    // Messages
    'messages' => [
        'saved' => 'تم حفظ الإعدادات بنجاح',
        'reset' => 'تم إعادة تعيين الإعدادات بنجاح',
        'cache_cleared' => 'تم مسح الذاكرة المؤقتة بنجاح',
        'backup_created' => 'تم إنشاء النسخة الاحتياطية بنجاح',
        'backup_restored' => 'تم استعادة النسخة الاحتياطية بنجاح',
        'test_email_sent' => 'تم إرسال البريد التجريبي بنجاح',
        'maintenance_enabled' => 'تم تفعيل وضع الصيانة',
        'maintenance_disabled' => 'تم تعطيل وضع الصيانة',
        'error_saving' => 'حدث خطأ أثناء حفظ الإعدادات',
    ],

    // Tabs
    'tabs' => [
        'general' => 'عام',
        'email' => 'البريد',
        'security' => 'الأمان',
        'appearance' => 'المظهر',
        'cache' => 'الذاكرة المؤقتة',
        'backup' => 'النسخ الاحتياطي',
        'maintenance' => 'الصيانة',
        'languages' => 'اللغات',
        'system' => 'النظام',
        'advanced' => 'متقدم',
    ],

    // Help Text
    'help' => [
        'site_name' => 'اسم الموقع كما سيظهر في المتصفح',
        'timezone' => 'المنطقة الزمنية المستخدمة لعرض التواريخ والأوقات',
        'items_per_page' => 'عدد العناصر المعروضة في كل صفحة من القوائم',
        'cache_ttl' => 'مدة بقاء البيانات في الذاكرة المؤقتة بالثواني',
        'password_expiry' => 'عدد الأيام قبل انتهاء صلاحية كلمة المرور',
        'session_timeout' => 'المدة بالدقائق قبل انتهاء صلاحية جلسة المستخدم',
        'maintenance_mode' => 'عند التفعيل، سيتم عرض رسالة الصيانة لجميع الزوار',
    ],

    // Validation
    'validation' => [
        'site_name_required' => 'اسم الموقع مطلوب',
        'email_required' => 'البريد الإلكتروني مطلوب',
        'invalid_email' => 'البريد الإلكتروني غير صحيح',
        'url_required' => 'الرابط مطلوب',
        'invalid_url' => 'الرابط غير صحيح',
        'positive_number' => 'يجب أن يكون رقماً موجباً',
    ],
];
