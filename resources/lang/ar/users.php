<?php

declare(strict_types=1);

return [
    'title' => 'المستخدمون',
    'singular' => 'مستخدم',
    'plural' => 'المستخدمون',

    // Page Titles
    'index' => 'إدارة المستخدمين',
    'create' => 'إضافة مستخدم جديد',
    'edit' => 'تعديل المستخدم',
    'show' => 'عرض المستخدم',
    'profile' => 'الملف الشخصي',

    // Actions
    'actions' => [
        'create_user' => 'مستخدم جديد',
        'edit_user' => 'تعديل المستخدم',
        'delete_user' => 'حذف المستخدم',
        'activate' => 'تفعيل الحساب',
        'deactivate' => 'تعطيل الحساب',
        'ban' => 'حظر المستخدم',
        'unban' => 'إلغاء الحظر',
        'verify_email' => 'تأكيد البريد',
        'reset_password' => 'إعادة تعيين كلمة المرور',
        'send_verification' => 'إرسال رابط التأكيد',
        'view_activity' => 'عرض النشاطات',
        'assign_role' => 'تعيين دور',
        'remove_role' => 'إزالة دور',
        'export_users' => 'تصدير المستخدمين',
    ],

    // Fields
    'fields' => [
        'name' => 'الاسم',
        'first_name' => 'الاسم الأول',
        'last_name' => 'اسم العائلة',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'current_password' => 'كلمة المرور الحالية',
        'new_password' => 'كلمة المرور الجديدة',
        'phone' => 'رقم الهاتف',
        'avatar' => 'الصورة الشخصية',
        'bio' => 'نبذة عني',
        'gender' => 'الجنس',
        'birth_date' => 'تاريخ الميلاد',
        'country' => 'البلد',
        'city' => 'المدينة',
        'address' => 'العنوان',
        'role' => 'الدور',
        'roles' => 'الأدوار',
        'permissions' => 'الصلاحيات',
        'status' => 'الحالة',
        'email_verified_at' => 'تاريخ تأكيد البريد',
        'last_login_at' => 'آخر تسجيل دخول',
        'last_activity_at' => 'آخر نشاط',
        'ip_address' => 'عنوان IP',
        'user_agent' => 'المتصفح',
        'timezone' => 'المنطقة الزمنية',
        'language' => 'اللغة',
        'created_at' => 'تاريخ التسجيل',
        'updated_at' => 'تاريخ آخر تحديث',
    ],

    // Gender
    'gender' => [
        'male' => 'ذكر',
        'female' => 'أنثى',
        'other' => 'آخر',
        'prefer_not_to_say' => 'أفضل عدم الإفصاح',
    ],

    // Status
    'status' => [
        'all' => 'كل الحالات',
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'pending' => 'قيد الانتظار',
        'suspended' => 'معلق',
        'banned' => 'محظور',
        'verified' => 'مؤكد',
        'unverified' => 'غير مؤكد',
    ],

    // Roles
    'roles' => [
        'title' => 'الأدوار',
        'singular' => 'دور',
        'admin' => 'مدير',
        'editor' => 'محرر',
        'author' => 'كاتب',
        'moderator' => 'مشرف',
        'contributor' => 'مساهم',
        'subscriber' => 'مشترك',
        'user' => 'مستخدم',
        'super_admin' => 'مدير عام',
        'manage_roles' => 'إدارة الأدوار',
        'create_role' => 'إنشاء دور جديد',
        'edit_role' => 'تعديل الدور',
        'delete_role' => 'حذف الدور',
        'assign_permissions' => 'تعيين الصلاحيات',
    ],

    // Permissions
    'permissions' => [
        'title' => 'الصلاحيات',
        'singular' => 'صلاحية',
        'view' => 'عرض',
        'create' => 'إنشاء',
        'edit' => 'تعديل',
        'delete' => 'حذف',
        'publish' => 'نشر',
        'manage' => 'إدارة',
        'view_any' => 'عرض الكل',
        'update_any' => 'تحديث الكل',
        'delete_any' => 'حذف الكل',
        'restore' => 'استعادة',
        'force_delete' => 'حذف نهائي',

        // Module Permissions
        'manage_articles' => 'إدارة المقالات',
        'manage_hadith' => 'إدارة الأحاديث',
        'manage_quran' => 'إدارة القرآن',
        'manage_seerah' => 'إدارة السيرة',
        'manage_genealogy' => 'إدارة الأنساب',
        'manage_media' => 'إدارة الوسائط',
        'manage_users' => 'إدارة المستخدمين',
        'manage_roles' => 'إدارة الأدوار',
        'manage_settings' => 'إدارة الإعدادات',
        'view_analytics' => 'عرض التحليلات',
        'view_logs' => 'عرض السجلات',
    ],

    // Placeholders
    'placeholders' => [
        'name' => 'أدخل الاسم الكامل...',
        'email' => 'أدخل البريد الإلكتروني...',
        'password' => 'أدخل كلمة المرور...',
        'phone' => 'أدخل رقم الهاتف...',
        'search' => 'ابحث عن المستخدمين...',
        'select_role' => 'اختر الدور',
        'select_status' => 'اختر الحالة',
        'select_country' => 'اختر البلد',
    ],

    // Messages
    'messages' => [
        'created' => 'تم إنشاء المستخدم بنجاح',
        'updated' => 'تم تحديث المستخدم بنجاح',
        'deleted' => 'تم حذف المستخدم بنجاح',
        'activated' => 'تم تفعيل الحساب بنجاح',
        'deactivated' => 'تم تعطيل الحساب بنجاح',
        'banned' => 'تم حظر المستخدم بنجاح',
        'unbanned' => 'تم إلغاء حظر المستخدم بنجاح',
        'email_verified' => 'تم تأكيد البريد الإلكتروني',
        'password_reset' => 'تم إعادة تعيين كلمة المرور',
        'verification_sent' => 'تم إرسال رابط التأكيد',
        'role_assigned' => 'تم تعيين الدور بنجاح',
        'role_removed' => 'تم إزالة الدور بنجاح',
        'profile_updated' => 'تم تحديث الملف الشخصي بنجاح',
        'password_changed' => 'تم تغيير كلمة المرور بنجاح',
        'avatar_updated' => 'تم تحديث الصورة الشخصية بنجاح',
        'not_found' => 'المستخدم غير موجود',
        'cannot_delete_self' => 'لا يمكنك حذف حسابك الخاص',
        'cannot_delete_admin' => 'لا يمكن حذف حساب المدير',
        'email_already_exists' => 'البريد الإلكتروني مستخدم بالفعل',
        'weak_password' => 'كلمة المرور ضعيفة',
        'error_creating' => 'حدث خطأ أثناء إنشاء المستخدم',
        'error_updating' => 'حدث خطأ أثناء تحديث المستخدم',
    ],

    // Filters
    'filters' => [
        'all' => 'كل المستخدمين',
        'active' => 'النشطون',
        'inactive' => 'غير النشطين',
        'verified' => 'المؤكدون',
        'unverified' => 'غير المؤكدين',
        'banned' => 'المحظورون',
        'by_role' => 'حسب الدور',
        'by_status' => 'حسب الحالة',
        'registered_today' => 'المسجلون اليوم',
        'registered_this_week' => 'المسجلون هذا الأسبوع',
        'registered_this_month' => 'المسجلون هذا الشهر',
        'online' => 'متصل الآن',
    ],

    // Sorting
    'sort' => [
        'name_asc' => 'الاسم (أ-ي)',
        'name_desc' => 'الاسم (ي-أ)',
        'email_asc' => 'البريد (أ-ي)',
        'email_desc' => 'البريد (ي-أ)',
        'newest' => 'الأحدث تسجيلاً',
        'oldest' => 'الأقدم تسجيلاً',
        'last_active' => 'آخر نشاط',
        'most_active' => 'الأكثر نشاطاً',
    ],

    // Statistics
    'stats' => [
        'total' => 'إجمالي المستخدمين',
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'verified' => 'مؤكد',
        'unverified' => 'غير مؤكد',
        'online' => 'متصل الآن',
        'new_today' => 'جديد اليوم',
        'new_this_week' => 'جديد هذا الأسبوع',
        'new_this_month' => 'جديد هذا الشهر',
        'by_role' => 'حسب الدور',
    ],

    // Activity
    'activity' => [
        'title' => 'نشاط المستخدم',
        'recent_activity' => 'النشاط الأخير',
        'login_history' => 'سجل تسجيل الدخول',
        'no_activity' => 'لا يوجد نشاط',
        'logged_in' => 'تسجيل دخول',
        'logged_out' => 'تسجيل خروج',
        'created' => 'إنشاء',
        'updated' => 'تحديث',
        'deleted' => 'حذف',
        'viewed' => 'مشاهدة',
    ],

    // Profile
    'profile' => [
        'title' => 'الملف الشخصي',
        'edit_profile' => 'تعديل الملف الشخصي',
        'change_password' => 'تغيير كلمة المرور',
        'change_avatar' => 'تغيير الصورة الشخصية',
        'account_settings' => 'إعدادات الحساب',
        'privacy_settings' => 'إعدادات الخصوصية',
        'notification_settings' => 'إعدادات الإشعارات',
        'delete_account' => 'حذف الحساب',
        'two_factor_auth' => 'المصادقة الثنائية',
        'enable_2fa' => 'تفعيل المصادقة الثنائية',
        'disable_2fa' => 'تعطيل المصادقة الثنائية',
    ],

    // Empty States
    'empty' => [
        'title' => 'لا يوجد مستخدمون بعد',
        'description' => 'ابدأ بإضافة أول مستخدم',
        'no_results' => 'لا توجد نتائج مطابقة',
        'no_activity' => 'لا يوجد نشاط',
        'no_roles' => 'لا توجد أدوار',
    ],

    // Tabs
    'tabs' => [
        'overview' => 'نظرة عامة',
        'profile' => 'الملف الشخصي',
        'activity' => 'النشاطات',
        'permissions' => 'الصلاحيات',
        'settings' => 'الإعدادات',
        'security' => 'الأمان',
    ],

    // Validation
    'validation' => [
        'name_required' => 'الاسم مطلوب',
        'email_required' => 'البريد الإلكتروني مطلوب',
        'email_invalid' => 'البريد الإلكتروني غير صحيح',
        'email_unique' => 'البريد الإلكتروني مستخدم بالفعل',
        'password_required' => 'كلمة المرور مطلوبة',
        'password_min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
        'password_confirmation' => 'كلمات المرور غير متطابقة',
        'role_required' => 'الدور مطلوب',
        'current_password_incorrect' => 'كلمة المرور الحالية غير صحيحة',
    ],

    // Help Text
    'help' => [
        'email' => 'البريد الإلكتروني سيُستخدم لتسجيل الدخول وإرسال الإشعارات',
        'password' => 'يجب أن تحتوي كلمة المرور على 8 أحرف على الأقل',
        'role' => 'الدور يحدد الصلاحيات التي سيحصل عليها المستخدم',
        'status' => 'حالة الحساب (نشط/غير نشط/محظور)',
        '2fa' => 'المصادقة الثنائية تضيف طبقة أمان إضافية لحسابك',
    ],
];
