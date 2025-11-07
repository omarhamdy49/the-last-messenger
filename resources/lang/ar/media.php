<?php

declare(strict_types=1);

return [
    'title' => 'المكتبة الإعلامية',
    'singular' => 'ملف',
    'plural' => 'الملفات',

    // Page Titles
    'index' => 'المكتبة الإعلامية',
    'upload' => 'رفع ملفات',
    'library' => 'مكتبة الوسائط',
    'programs' => 'البرامج الإعلامية',
    'series' => 'المسلسلات',
    'episodes' => 'الحلقات',

    // Programs
    'programs' => [
        'title' => 'البرامج الإعلامية',
        'singular' => 'برنامج',
        'create' => 'إنشاء برنامج جديد',
        'edit' => 'تعديل البرنامج',
        'show' => 'عرض البرنامج',
        'name' => 'اسم البرنامج',
        'description' => 'وصف البرنامج',
        'presenter' => 'مقدم البرنامج',
        'category' => 'التصنيف',
        'type' => 'نوع البرنامج',
        'episodes_count' => 'عدد الحلقات',
        'duration' => 'المدة الإجمالية',
        'release_year' => 'سنة الإصدار',
        'status' => 'الحالة',
        'thumbnail' => 'الصورة المصغرة',
        'banner' => 'صورة الغلاف',
        'trailer' => 'الإعلان التشويقي',
        'rating' => 'التقييم',
        'views_count' => 'عدد المشاهدات',
        'is_featured' => 'مميز',
        'is_series' => 'مسلسل',
    ],

    // Series
    'series' => [
        'title' => 'المسلسلات',
        'singular' => 'مسلسل',
        'seasons' => 'المواسم',
        'season' => 'الموسم',
        'season_number' => 'رقم الموسم',
    ],

    // Episodes
    'episodes' => [
        'title' => 'الحلقات',
        'singular' => 'حلقة',
        'create' => 'إضافة حلقة جديدة',
        'edit' => 'تعديل الحلقة',
        'show' => 'عرض الحلقة',
        'number' => 'رقم الحلقة',
        'title' => 'عنوان الحلقة',
        'description' => 'وصف الحلقة',
        'duration' => 'المدة',
        'video_url' => 'رابط الفيديو',
        'video_file' => 'ملف الفيديو',
        'thumbnail' => 'الصورة المصغرة',
        'release_date' => 'تاريخ الإصدار',
        'views_count' => 'عدد المشاهدات',
        'is_published' => 'منشور',
        'order' => 'الترتيب',
    ],

    // Media Library
    'library' => [
        'title' => 'مكتبة الوسائط',
        'upload' => 'رفع ملفات',
        'upload_files' => 'رفع ملفات جديدة',
        'select_files' => 'اختر ملفات',
        'drag_drop' => 'اسحب وأفلت الملفات هنا',
        'or' => 'أو',
        'browse' => 'تصفح',
        'all_media' => 'كل الوسائط',
        'images' => 'الصور',
        'videos' => 'الفيديوهات',
        'audio' => 'الصوتيات',
        'documents' => 'المستندات',
        'others' => 'أخرى',
    ],

    // File Types
    'types' => [
        'image' => 'صورة',
        'video' => 'فيديو',
        'audio' => 'صوت',
        'document' => 'مستند',
        'pdf' => 'ملف PDF',
        'archive' => 'أرشيف مضغوط',
        'other' => 'أخرى',
    ],

    // Program Categories
    'categories' => [
        'seerah' => 'السيرة النبوية',
        'tafsir' => 'التفسير',
        'hadith' => 'شرح الحديث',
        'fiqh' => 'الفقه',
        'aqeedah' => 'العقيدة',
        'history' => 'التاريخ الإسلامي',
        'documentary' => 'وثائقي',
        'lecture' => 'محاضرات',
        'interview' => 'مقابلات',
        'educational' => 'تعليمي',
    ],

    // Actions
    'actions' => [
        'upload' => 'رفع',
        'download' => 'تحميل',
        'delete' => 'حذف',
        'edit' => 'تعديل',
        'view' => 'عرض',
        'select' => 'اختيار',
        'insert' => 'إدراج',
        'replace' => 'استبدال',
        'crop' => 'قص',
        'resize' => 'تغيير الحجم',
        'rotate' => 'تدوير',
        'copy_url' => 'نسخ الرابط',
        'preview' => 'معاينة',
        'play' => 'تشغيل',
        'pause' => 'إيقاف مؤقت',
        'stop' => 'إيقاف',
        'add_episode' => 'إضافة حلقة',
        'manage_episodes' => 'إدارة الحلقات',
        'publish' => 'نشر',
        'unpublish' => 'إلغاء النشر',
    ],

    // Fields
    'fields' => [
        'file_name' => 'اسم الملف',
        'file_type' => 'نوع الملف',
        'file_size' => 'حجم الملف',
        'dimensions' => 'الأبعاد',
        'duration' => 'المدة',
        'uploaded_by' => 'رفع بواسطة',
        'uploaded_at' => 'تاريخ الرفع',
        'title' => 'العنوان',
        'alt_text' => 'النص البديل',
        'caption' => 'التعليق',
        'description' => 'الوصف',
        'url' => 'الرابط',
        'thumbnail' => 'الصورة المصغرة',
        'mime_type' => 'نوع MIME',
        'extension' => 'الامتداد',
        'folder' => 'المجلد',
        'tags' => 'الوسوم',
    ],

    // Upload
    'upload' => [
        'title' => 'رفع ملفات',
        'select_files' => 'اختر ملفات',
        'drop_files' => 'اسحب وأفلت الملفات هنا',
        'uploading' => 'جاري الرفع...',
        'processing' => 'جاري المعالجة...',
        'success' => 'تم الرفع بنجاح',
        'failed' => 'فشل الرفع',
        'max_size' => 'الحد الأقصى لحجم الملف: :size',
        'allowed_types' => 'الأنواع المسموحة: :types',
        'file_too_large' => 'الملف كبير جداً',
        'invalid_type' => 'نوع الملف غير مسموح',
        'upload_limit' => 'يمكنك رفع :count ملف كحد أقصى',
    ],

    // Messages
    'messages' => [
        'uploaded' => 'تم رفع الملف بنجاح',
        'deleted' => 'تم حذف الملف بنجاح',
        'updated' => 'تم تحديث الملف بنجاح',
        'url_copied' => 'تم نسخ الرابط',
        'program_created' => 'تم إنشاء البرنامج بنجاح',
        'program_updated' => 'تم تحديث البرنامج بنجاح',
        'program_deleted' => 'تم حذف البرنامج بنجاح',
        'episode_created' => 'تمت إضافة الحلقة بنجاح',
        'episode_updated' => 'تم تحديث الحلقة بنجاح',
        'episode_deleted' => 'تم حذف الحلقة بنجاح',
        'cannot_delete' => 'لا يمكن حذف هذا الملف',
        'not_found' => 'الملف غير موجود',
        'upload_failed' => 'فشل رفع الملف',
    ],

    // Filters
    'filters' => [
        'all' => 'كل الملفات',
        'images' => 'الصور',
        'videos' => 'الفيديوهات',
        'audio' => 'الصوتيات',
        'documents' => 'المستندات',
        'recent' => 'الأحدث',
        'by_type' => 'حسب النوع',
        'by_date' => 'حسب التاريخ',
        'by_size' => 'حسب الحجم',
        'unused' => 'غير المستخدمة',
    ],

    // Sorting
    'sort' => [
        'name_asc' => 'الاسم (أ-ي)',
        'name_desc' => 'الاسم (ي-أ)',
        'date_asc' => 'الأقدم',
        'date_desc' => 'الأحدث',
        'size_asc' => 'الحجم (الأصغر)',
        'size_desc' => 'الحجم (الأكبر)',
    ],

    // Statistics
    'stats' => [
        'total_files' => 'إجمالي الملفات',
        'total_size' => 'الحجم الإجمالي',
        'images_count' => 'عدد الصور',
        'videos_count' => 'عدد الفيديوهات',
        'audio_count' => 'عدد الصوتيات',
        'storage_used' => 'المساحة المستخدمة',
        'storage_available' => 'المساحة المتاحة',
        'total_programs' => 'إجمالي البرامج',
        'total_episodes' => 'إجمالي الحلقات',
        'total_views' => 'إجمالي المشاهدات',
    ],

    // Empty States
    'empty' => [
        'title' => 'لا توجد ملفات بعد',
        'description' => 'ابدأ برفع أول ملف',
        'no_results' => 'لا توجد نتائج مطابقة',
        'no_programs' => 'لا توجد برامج',
        'no_episodes' => 'لا توجد حلقات',
        'drop_files_here' => 'اسحب الملفات هنا',
    ],

    // Video Player
    'player' => [
        'play' => 'تشغيل',
        'pause' => 'إيقاف مؤقت',
        'stop' => 'إيقاف',
        'mute' => 'كتم الصوت',
        'unmute' => 'إلغاء كتم الصوت',
        'volume' => 'مستوى الصوت',
        'fullscreen' => 'ملء الشاشة',
        'exit_fullscreen' => 'إنهاء ملء الشاشة',
        'speed' => 'السرعة',
        'quality' => 'الجودة',
        'subtitles' => 'الترجمات',
        'settings' => 'الإعدادات',
    ],

    // Validation
    'validation' => [
        'name_required' => 'اسم البرنامج مطلوب',
        'file_required' => 'الملف مطلوب',
        'video_required' => 'ملف الفيديو مطلوب',
        'episode_number_required' => 'رقم الحلقة مطلوب',
        'invalid_file_type' => 'نوع الملف غير مسموح',
        'file_too_large' => 'حجم الملف كبير جداً',
    ],

    // Help Text
    'help' => [
        'thumbnail' => 'الصورة المصغرة للبرنامج. الحجم الموصى به: 1280×720 بكسل.',
        'video_url' => 'رابط الفيديو من YouTube أو Vimeo أو خادم خارجي.',
        'alt_text' => 'نص بديل يصف الصورة للمستخدمين ذوي الإعاقة البصرية.',
        'episode_order' => 'ترتيب الحلقة في البرنامج.',
    ],
];
