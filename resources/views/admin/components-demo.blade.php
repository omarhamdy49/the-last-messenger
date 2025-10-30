@extends('layouts.admin')

@section('title', 'Components Demo')

@section('content')
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2 gradient-text">معرض المكونات</h1>
            <p class="text-gray-600 dark:text-gray-400">مجموعة من المكونات القابلة لإعادة الاستخدام في لوحة التحكم</p>
        </div>
    </div>

    <!-- Toast Notification Demo -->
    <div class="rounded-xl p-6 shadow-sm" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
        <h2 class="text-2xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
            1. Toast Notifications
        </h2>
        <p class="mb-6" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            إشعارات منبثقة تظهر تلقائياً وتختفي بعد 5 ثوانٍ
        </p>

        <div class="flex flex-wrap gap-3">
            <button
                @click="window.showToast('تم الحفظ بنجاح!', 'success')"
                class="px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-lg transition-colors shadow-md hover:shadow-lg"
            >
                <i data-lucide="check-circle" class="w-5 h-5 inline-block ml-2"></i>
                نجاح
            </button>

            <button
                @click="window.showToast('حدث خطأ أثناء العملية', 'error')"
                class="px-6 py-3 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition-colors shadow-md hover:shadow-lg"
            >
                <i data-lucide="alert-circle" class="w-5 h-5 inline-block ml-2"></i>
                خطأ
            </button>

            <button
                @click="window.showToast('يرجى التحقق من البيانات المدخلة', 'warning')"
                class="px-6 py-3 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold rounded-lg transition-colors shadow-md hover:shadow-lg"
            >
                <i data-lucide="alert-triangle" class="w-5 h-5 inline-block ml-2"></i>
                تحذير
            </button>

            <button
                @click="window.showToast('معلومة: تم تحديث النظام', 'info')"
                class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg transition-colors shadow-md hover:shadow-lg"
            >
                <i data-lucide="info" class="w-5 h-5 inline-block ml-2"></i>
                معلومة
            </button>
        </div>

        <div class="mt-6 p-4 rounded-lg" :class="darkMode ? 'bg-gray-700/50' : 'bg-gray-100'">
            <p class="font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">كيفية الاستخدام:</p>
            <pre class="text-sm overflow-x-auto" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"><code>// في JavaScript
window.showToast('رسالة النجاح', 'success');
window.showToast('رسالة الخطأ', 'error');

// في Laravel Controller
return redirect()->back()->with('success', 'تم الحفظ بنجاح');
return redirect()->back()->with('error', 'حدث خطأ');</code></pre>
        </div>
    </div>

    <!-- File Uploader Demo -->
    <div class="rounded-xl p-6 shadow-sm" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
        <h2 class="text-2xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
            2. File Uploader
        </h2>
        <p class="mb-6" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            مكون رفع الملفات مع السحب والإفلات ومعاينة الصور
        </p>

        <div class="grid md:grid-cols-2 gap-6">
            <!-- Image Uploader -->
            <div>
                <h3 class="font-bold mb-3" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">رفع الصور</h3>
                @include('admin.components.file-uploader', [
                    'name' => 'images',
                    'accept' => 'image/jpeg,image/png,image/webp,image/gif',
                    'multiple' => true,
                    'maxSize' => 5,
                    'label' => 'اختر الصور أو اسحبها هنا'
                ])
            </div>

            <!-- Video Uploader -->
            <div>
                <h3 class="font-bold mb-3" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">رفع الفيديو</h3>
                @include('admin.components.file-uploader', [
                    'name' => 'video',
                    'accept' => 'video/mp4,video/webm',
                    'multiple' => false,
                    'maxSize' => 50,
                    'label' => 'اختر ملف الفيديو'
                ])
            </div>
        </div>
    </div>

    <!-- Skeleton Loaders Demo -->
    <div class="rounded-xl p-6 shadow-sm" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
        <h2 class="text-2xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
            3. Skeleton Loaders
        </h2>
        <p class="mb-6" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            مكونات التحميل الهيكلي لتحسين تجربة المستخدم
        </p>

        <div class="space-y-6">
            <!-- Table Skeleton -->
            <div>
                <h3 class="font-bold mb-3" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">جدول التحميل</h3>
                @include('admin.components.skeleton-table', ['rows' => 5])
            </div>

            <!-- Card Skeleton -->
            <div>
                <h3 class="font-bold mb-3" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">بطاقة التحميل</h3>
                <div class="grid md:grid-cols-3 gap-6">
                    @include('admin.components.skeleton-card')
                    @include('admin.components.skeleton-card')
                    @include('admin.components.skeleton-card')
                </div>
            </div>

            <!-- Form Skeleton -->
            <div>
                <h3 class="font-bold mb-3" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">نموذج التحميل</h3>
                @include('admin.components.skeleton-form', ['fields' => 5])
            </div>
        </div>
    </div>

    <!-- Usage Examples -->
    <div class="rounded-xl p-6 shadow-sm" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
        <h2 class="text-2xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
            4. أمثلة الاستخدام في Blade
        </h2>

        <div class="space-y-4">
            <!-- Toast Usage -->
            <div>
                <h3 class="font-semibold mb-2 text-brand-primary">Toast Component:</h3>
                <div class="p-4 rounded-lg font-mono text-sm overflow-x-auto" :class="darkMode ? 'bg-gray-900 text-gray-300' : 'bg-gray-100 text-gray-800'">
                    <code>{{-- في Layout الرئيسي --}}
@include('admin.components.toast')

{{-- في Controller --}}
return redirect()->back()->with('success', 'تم الحفظ بنجاح');

{{-- في JavaScript --}}
window.showToast('رسالة', 'success');</code>
                </div>
            </div>

            <!-- File Uploader Usage -->
            <div>
                <h3 class="font-semibold mb-2 text-brand-primary">File Uploader Component:</h3>
                <div class="p-4 rounded-lg font-mono text-sm overflow-x-auto" :class="darkMode ? 'bg-gray-900 text-gray-300' : 'bg-gray-100 text-gray-800'">
                    <code>@include('admin.components.file-uploader', [
    'name' => 'images',
    'accept' => 'image/*',
    'multiple' => true,
    'maxSize' => 5,
    'label' => 'رفع الصور'
])</code>
                </div>
            </div>

            <!-- Skeleton Usage -->
            <div>
                <h3 class="font-semibold mb-2 text-brand-primary">Skeleton Components:</h3>
                <div class="p-4 rounded-lg font-mono text-sm overflow-x-auto" :class="darkMode ? 'bg-gray-900 text-gray-300' : 'bg-gray-100 text-gray-800'">
                    <code>{{-- Table Skeleton --}}
@include('admin.components.skeleton-table', ['rows' => 5])

{{-- Card Skeleton --}}
@include('admin.components.skeleton-card')

{{-- Form Skeleton --}}
@include('admin.components.skeleton-form', ['fields' => 4])</code>
                </div>
            </div>
        </div>
    </div>

    <!-- Features List -->
    <div class="rounded-xl p-6 shadow-sm" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
        <h2 class="text-2xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
            5. المميزات
        </h2>

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <h3 class="font-semibold mb-3 text-brand-primary flex items-center gap-2">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                    Toast Notifications
                </h3>
                <ul class="space-y-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-1 text-green-500"></i>
                        4 أنواع: success, error, warning, info
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-1 text-green-500"></i>
                        إغلاق تلقائي بعد 5 ثوانٍ
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-1 text-green-500"></i>
                        تكامل مع Laravel Session Messages
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-1 text-green-500"></i>
                        دعم الوضع الليلي و RTL
                    </li>
                </ul>
            </div>

            <div>
                <h3 class="font-semibold mb-3 text-brand-primary flex items-center gap-2">
                    <i data-lucide="upload-cloud" class="w-5 h-5"></i>
                    File Uploader
                </h3>
                <ul class="space-y-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-1 text-green-500"></i>
                        السحب والإفلات (Drag & Drop)
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-1 text-green-500"></i>
                        معاينة الصور المرفوعة
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-1 text-green-500"></i>
                        التحقق من نوع وحجم الملف
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-1 text-green-500"></i>
                        شريط التقدم أثناء الرفع
                    </li>
                </ul>
            </div>

            <div>
                <h3 class="font-semibold mb-3 text-brand-primary flex items-center gap-2">
                    <i data-lucide="loader" class="w-5 h-5"></i>
                    Skeleton Loaders
                </h3>
                <ul class="space-y-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-1 text-green-500"></i>
                        3 أنواع: Table, Card, Form
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-1 text-green-500"></i>
                        تأثير Shimmer متحرك
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-1 text-green-500"></i>
                        قابل للتخصيص (عدد الصفوف/الحقول)
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-1 text-green-500"></i>
                        دعم الوضع الليلي
                    </li>
                </ul>
            </div>

            <div>
                <h3 class="font-semibold mb-3 text-brand-primary flex items-center gap-2">
                    <i data-lucide="palette" class="w-5 h-5"></i>
                    Design System
                </h3>
                <ul class="space-y-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-1 text-green-500"></i>
                        Blue-Green Gradient (#0056b3 → #218838)
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-1 text-green-500"></i>
                        Teal Accent (#64CCC5)
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-1 text-green-500"></i>
                        تصميم متجاوب مع جميع الأجهزة
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-1 text-green-500"></i>
                        خط Cairo محسّن للعربية
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Reinitialize Lucide icons after Alpine renders
    document.addEventListener('alpine:initialized', () => {
        lucide.createIcons();
    });

    // Also reinitialize on DOM content loaded
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            lucide.createIcons();
        }, 100);
    });
</script>
@endpush
