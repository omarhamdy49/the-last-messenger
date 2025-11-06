@extends('layouts.admin')

@section('title', 'إنشاء مقال جديد')

@push('styles')
<style>
    :root {
        --primary-blue: #0056b3;
        --primary-green: #218838;
        --accent-teal: #64CCC5;
    }

    /* Clean Input Styling */
    .input-clean {
        transition: all 0.2s ease;
    }

    .input-clean:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
    }

    .input-error {
        border-color: #ef4444;
    }

    .input-error:focus {
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    .input-success {
        border-color: #10b981;
    }

    /* Tab Styling */
    .tab-button {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        font-weight: 500;
        color: #6b7280;
        transition: all 0.2s ease;
        border-bottom: 2px solid transparent;
        white-space: nowrap;
    }

    .tab-button:hover {
        color: #111827;
    }

    .tab-button.active {
        color: var(--primary-blue);
        border-bottom-color: var(--primary-blue);
    }

    .dark .tab-button {
        color: #9ca3af;
    }

    .dark .tab-button:hover {
        color: #e5e7eb;
    }

    .dark .tab-button.active {
        color: var(--primary-blue);
    }

    /* Progress Bar */
    .progress-bar {
        height: 2px;
        background: #e5e7eb;
        border-radius: 9999px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-blue), var(--primary-green));
        transition: width 0.5s ease;
    }

    /* Character Counter */
    .char-counter {
        font-size: 0.75rem;
        color: #9ca3af;
        transition: color 0.2s ease;
    }

    .char-counter.over-limit {
        color: #ef4444;
    }

    /* Validation Message */
    .validation-message {
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    /* Sticky Sidebar */
    .sidebar-sticky {
        position: sticky;
        top: 24px;
        max-height: calc(100vh - 48px);
        overflow-y: auto;
    }

    /* Gradient Button */
    .gradient-button {
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
        transition: all 0.2s ease;
    }

    .gradient-button:hover {
        box-shadow: 0 4px 12px rgba(0, 86, 179, 0.3);
        transform: translateY(-1px);
    }

    /* Card Styling */
    .sidebar-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .dark .sidebar-card {
        background: #1f2937;
        border-color: #374151;
    }

    .dark .input-clean {
        background: #111827;
        border-color: #374151;
        color: white;
    }

    /* Toggle Switch */
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: 0.2s;
        border-radius: 34px;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.2s;
        border-radius: 50%;
    }

    input:checked + .toggle-slider {
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
    }

    input:checked + .toggle-slider:before {
        transform: translateX(20px);
    }

    /* Fade In Animation */
    .fade-in {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Image Preview */
    .image-preview {
        position: relative;
        aspect-ratio: 16/9;
        border-radius: 0.5rem;
        overflow: hidden;
        background: #f3f4f6;
    }

    .dark .image-preview {
        background: #374151;
    }

    /* Custom Scrollbar */
    .sidebar-sticky::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar-sticky::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar-sticky::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .dark .sidebar-sticky::-webkit-scrollbar-thumb {
        background: #4b5563;
    }

    /* Hide scrollbar for tabs */
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }

    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    /* Responsive Tab Button */
    @media (max-width: 640px) {
        .tab-button {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }

        .tab-button svg {
            width: 1rem;
            height: 1rem;
        }
    }
</style>
@endpush

@section('content')
<div x-data="articleForm()" x-init="init()">

    <!-- Header Bar -->
    <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-4 sm:px-6 py-3 sm:py-4 mb-6">
        <div class="flex items-center justify-between gap-2 sm:gap-4">
            <!-- Back Button -->
            <a href="{{ route('admin.articles.index') }}" class="flex items-center gap-1 sm:gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors shrink-0">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span class="font-medium text-sm sm:text-base hidden sm:inline">العودة للمقالات</span>
                <span class="font-medium text-sm sm:hidden">رجوع</span>
            </a>

            <!-- Live Title Preview -->
            <h2 class="text-sm sm:text-lg font-bold text-gray-900 dark:text-white truncate max-w-[120px] sm:max-w-md text-center flex-1">
                <span x-text="form.title[currentLang] || 'مقال بدون عنوان'"></span>
            </h2>

            <!-- Auto-save Status -->
            <div class="flex items-center gap-1 sm:gap-2 text-xs sm:text-sm shrink-0">
                <span x-show="saving" x-cloak class="text-gray-500 dark:text-gray-400 flex items-center gap-1 sm:gap-2">
                    <svg class="animate-spin h-3 w-3 sm:h-4 sm:w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="hidden sm:inline">جاري الحفظ...</span>
                </span>
                <span x-show="!saving && lastSaved" x-cloak class="text-green-600 flex items-center gap-1 sm:gap-2">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="hidden sm:inline">تم الحفظ</span>
                </span>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 pb-12">
        <div class="grid grid-cols-1 lg:grid-cols-[1fr] xl:grid-cols-[2fr_1fr] gap-6">

            <!-- Main Content Area (2fr - LEFT) -->
            <div class="xl:col-span-1 order-2 xl:order-1">
                <div class="space-y-6" dir="rtl">

                    <!-- Progress Bar -->
                    <div class="progress-bar" dir="ltr">
                        <div class="progress-fill" :style="`width: ${progress}%`"></div>
                    </div>

                    <!-- Tab Navigation -->
                    <div class="flex flex-nowrap border-b border-gray-200 dark:border-gray-700 gap-2 sm:gap-4 overflow-x-auto pb-0 scrollbar-hide">
                        <button type="button" @click="activeTab = 'content'" :class="activeTab === 'content' ? 'active' : ''" class="tab-button shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>المحتوى</span>
                        </button>
                        <button type="button" @click="activeTab = 'media'" :class="activeTab === 'media' ? 'active' : ''" class="tab-button shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>الوسائط</span>
                        </button>
                        <button type="button" @click="activeTab = 'seo'" :class="activeTab === 'seo' ? 'active' : ''" class="tab-button shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span class="hidden sm:inline">تحسين محركات البحث</span>
                            <span class="sm:hidden">SEO</span>
                        </button>
                        <button type="button" @click="activeTab = 'historical'" :class="activeTab === 'historical' ? 'active' : ''" class="tab-button shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="hidden sm:inline">المعلومات التاريخية</span>
                            <span class="sm:hidden">تاريخي</span>
                        </button>
                        <button type="button" @click="activeTab = 'settings'" :class="activeTab === 'settings' ? 'active' : ''" class="tab-button shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>الإعدادات</span>
                        </button>
                        <button type="button" @click="activeTab = 'related'" :class="activeTab === 'related' ? 'active' : ''" class="tab-button shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            <span class="hidden sm:inline">المقالات المرتبطة</span>
                            <span class="sm:hidden">مرتبطة</span>
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <form @submit.prevent="submit">

                        <!-- Tab 1: Content -->
                        <div x-show="activeTab === 'content'" x-cloak class="space-y-6 fade-in">

                            <!-- Language Switcher -->
                            <div class="flex flex-wrap justify-end gap-2">
                                <button type="button" @click="currentLang = 'ar'" :class="currentLang === 'ar' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'" class="px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-medium transition-colors">
                                    العربية
                                </button>
                                <button type="button" @click="currentLang = 'en'" :class="currentLang === 'en' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'" class="px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-medium transition-colors">
                                    English
                                </button>
                                <button type="button" @click="currentLang = 'tr'" :class="currentLang === 'tr' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'" class="px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-medium transition-colors">
                                    Türkçe
                                </button>
                                <button type="button" @click="currentLang = 'ur'" :class="currentLang === 'ur' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'" class="px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-medium transition-colors">
                                    اردو
                                </button>
                            </div>

                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                    عنوان المقال <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    :dir="currentLang === 'ar' || currentLang === 'ur' ? 'rtl' : 'ltr'"
                                    x-model="form.title[currentLang]"
                                    @input="validateField('title'); updateProgress()"
                                    :class="errors.title ? 'input-error' : (form.title[currentLang] ? 'input-success' : '')"
                                    class="input-clean w-full px-4 py-3 text-lg border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none dark:bg-gray-800 dark:text-white text-right"
                                    placeholder="أدخل عنوان المقال..."
                                    required
                                >
                                <div class="flex items-center justify-between mt-2">
                                    <div x-show="errors.title" class="validation-message text-red-500 text-right" x-cloak>
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                                        </svg>
                                        <span x-text="errors.title"></span>
                                    </div>
                                    <div x-show="form.title[currentLang]" class="char-counter text-left" :class="form.title[currentLang]?.length > 60 ? 'over-limit' : ''" x-cloak dir="ltr">
                                        <span x-text="form.title[currentLang]?.length || 0"></span>/60 حرف
                                    </div>
                                </div>
                            </div>

                            <!-- Excerpt -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                    ملخص المقال <span class="text-red-500">*</span>
                                </label>
                                <div x-show="currentLang === 'ar'">
                                    <x-admin.wysiwyg-editor
                                        name="excerpt[ar]"
                                        :value="old('excerpt.ar', '')"
                                        language="ar"
                                        :height="200"
                                        :required="true"
                                        id="excerpt_ar"
                                    />
                                </div>
                                <div x-show="currentLang === 'en'" x-cloak>
                                    <x-admin.wysiwyg-editor
                                        name="excerpt[en]"
                                        :value="old('excerpt.en', '')"
                                        language="en"
                                        :height="200"
                                        id="excerpt_en"
                                    />
                                </div>
                                <div x-show="currentLang === 'tr'" x-cloak>
                                    <x-admin.wysiwyg-editor
                                        name="excerpt[tr]"
                                        :value="old('excerpt.tr', '')"
                                        language="tr"
                                        :height="200"
                                        id="excerpt_tr"
                                    />
                                </div>
                                <div x-show="currentLang === 'ur'" x-cloak>
                                    <x-admin.wysiwyg-editor
                                        name="excerpt[ur]"
                                        :value="old('excerpt.ur', '')"
                                        language="ur"
                                        :height="200"
                                        id="excerpt_ur"
                                    />
                                </div>
                                <div x-show="errors.excerpt" class="validation-message text-red-500 text-right mt-2" x-cloak>
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                                    </svg>
                                    <span x-text="errors.excerpt"></span>
                                </div>
                            </div>

                            <!-- Content Body -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                    نص المقال <span class="text-red-500">*</span>
                                </label>
                                <div x-show="currentLang === 'ar'">
                                    <x-admin.wysiwyg-editor
                                        name="content[ar][body]"
                                        :value="old('content.ar.body', '')"
                                        language="ar"
                                        :height="500"
                                        :required="true"
                                        id="content_body_ar"
                                    />
                                </div>
                                <div x-show="currentLang === 'en'" x-cloak>
                                    <x-admin.wysiwyg-editor
                                        name="content[en][body]"
                                        :value="old('content.en.body', '')"
                                        language="en"
                                        :height="500"
                                        id="content_body_en"
                                    />
                                </div>
                                <div x-show="currentLang === 'tr'" x-cloak>
                                    <x-admin.wysiwyg-editor
                                        name="content[tr][body]"
                                        :value="old('content.tr.body', '')"
                                        language="tr"
                                        :height="500"
                                        id="content_body_tr"
                                    />
                                </div>
                                <div x-show="currentLang === 'ur'" x-cloak>
                                    <x-admin.wysiwyg-editor
                                        name="content[ur][body]"
                                        :value="old('content.ur.body', '')"
                                        language="ur"
                                        :height="500"
                                        id="content_body_ur"
                                    />
                                </div>
                                <div x-show="errors.content" class="validation-message text-red-500 mt-2 text-right" x-cloak>
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                                    </svg>
                                    <span x-text="errors.content"></span>
                                </div>
                            </div>

                            <!-- Content Table of Contents (TOC) -->
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-800 p-6 rounded-xl border border-blue-100 dark:border-gray-700">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 text-white">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 text-right">
                                        <label class="block text-base font-bold text-gray-800 dark:text-white">
                                            جدول المحتويات
                                        </label>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">يمكن إنشاء تلقائي من العناوين أو إضافة يدويًا</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <template x-for="(item, index) in (form.content_toc[currentLang] || [])" :key="index">
                                        <div class="group relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:shadow-md transition-all duration-200">
                                            <div class="flex gap-3 items-start">
                                                <div class="flex-shrink-0 mt-2">
                                                    <div class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xs font-bold">
                                                        <span x-text="index + 1"></span>
                                                    </div>
                                                </div>
                                                <div class="flex-1 space-y-3">
                                                    <div class="relative">
                                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1 text-right">عنوان القسم</label>
                                                        <input
                                                            type="text"
                                                            x-model="form.content_toc[currentLang][index].title"
                                                            :dir="currentLang === 'ar' || currentLang === 'ur' ? 'rtl' : 'ltr'"
                                                            class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white transition-all"
                                                            :placeholder="currentLang === 'ar' ? 'مثال: المقدمة' : 'Example: Introduction'"
                                                        >
                                                    </div>
                                                    <div class="relative">
                                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1 text-right">رابط المرساة</label>
                                                        <div class="flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                                            </svg>
                                                            <input
                                                                type="text"
                                                                x-model="form.content_toc[currentLang][index].link"
                                                                dir="ltr"
                                                                class="flex-1 px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white font-mono transition-all"
                                                                placeholder="#section-introduction"
                                                            >
                                                        </div>
                                                    </div>
                                                </div>
                                                <button
                                                    type="button"
                                                    @click="form.content_toc[currentLang].splice(index, 1)"
                                                    class="flex-shrink-0 mt-2 p-2 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 opacity-0 group-hover:opacity-100 transition-all">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </template>

                                    <button
                                        type="button"
                                        @click="addTocItem()"
                                        class="w-full flex items-center justify-center gap-2 px-5 py-3.5 border-2 border-dashed border-blue-300 dark:border-blue-700 rounded-xl text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-400 dark:hover:border-blue-600 transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        <span>إضافة عنصر جديد</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Content Summarize Points -->
                            <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-gray-800 dark:to-gray-800 p-6 rounded-xl border border-emerald-100 dark:border-gray-700">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 text-white">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 text-right">
                                        <label class="block text-base font-bold text-gray-800 dark:text-white">
                                            نقاط التلخيص
                                        </label>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">النقاط الرئيسية والأفكار المهمة من المحتوى</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <template x-for="(point, index) in (form.content_summarize_points[currentLang] || [])" :key="index">
                                        <div class="group flex gap-3 items-center bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-3 hover:shadow-md transition-all duration-200">
                                            <div class="flex-shrink-0">
                                                <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-900 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xs font-bold">
                                                    <span x-text="index + 1"></span>
                                                </div>
                                            </div>
                                            <div class="flex-1 relative">
                                                <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" :class="currentLang === 'ar' || currentLang === 'ur' ? 'right-3' : 'left-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                                </svg>
                                                <input
                                                    type="text"
                                                    x-model="form.content_summarize_points[currentLang][index]"
                                                    :dir="currentLang === 'ar' || currentLang === 'ur' ? 'rtl' : 'ltr'"
                                                    class="w-full py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-800 dark:text-white transition-all"
                                                    :class="currentLang === 'ar' || currentLang === 'ur' ? 'pr-10 pl-4' : 'pl-10 pr-4'"
                                                    :placeholder="currentLang === 'ar' ? 'مثال: تعلم أهمية التخطيط المسبق' : 'Example: Learn the importance of planning'"
                                                >
                                            </div>
                                            <button
                                                type="button"
                                                @click="form.content_summarize_points[currentLang].splice(index, 1)"
                                                class="flex-shrink-0 p-2 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 opacity-0 group-hover:opacity-100 transition-all">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>

                                    <button
                                        type="button"
                                        @click="addSummarizePoint()"
                                        class="w-full flex items-center justify-center gap-2 px-5 py-3.5 border-2 border-dashed border-emerald-300 dark:border-emerald-700 rounded-xl text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 hover:border-emerald-400 dark:hover:border-emerald-600 transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        <span>إضافة نقطة جديدة</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Content Summary -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                    ملخص المحتوى
                                </label>
                                <p class="text-xs text-gray-500 mb-3 text-right">ملخص موجز للمحتوى الكامل (مختلف عن المقتطف)</p>
                                <div x-show="currentLang === 'ar'">
                                    <x-admin.wysiwyg-editor
                                        name="content_summary[ar]"
                                        :value="old('content_summary.ar', '')"
                                        language="ar"
                                        :height="250"
                                        id="content_summary_ar"
                                    />
                                </div>
                                <div x-show="currentLang === 'en'" x-cloak>
                                    <x-admin.wysiwyg-editor
                                        name="content_summary[en]"
                                        :value="old('content_summary.en', '')"
                                        language="en"
                                        :height="250"
                                        id="content_summary_en"
                                    />
                                </div>
                                <div x-show="currentLang === 'tr'" x-cloak>
                                    <x-admin.wysiwyg-editor
                                        name="content_summary[tr]"
                                        :value="old('content_summary.tr', '')"
                                        language="tr"
                                        :height="250"
                                        id="content_summary_tr"
                                    />
                                </div>
                                <div x-show="currentLang === 'ur'" x-cloak>
                                    <x-admin.wysiwyg-editor
                                        name="content_summary[ur]"
                                        :value="old('content_summary.ur', '')"
                                        language="ur"
                                        :height="250"
                                        id="content_summary_ur"
                                    />
                                </div>
                            </div>

                        </div>

                        <!-- Tab 2: Media & Gallery -->
                        <div x-show="activeTab === 'media'" x-cloak class="space-y-6 fade-in">

                            <!-- Gallery Images -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 text-right">
                                    معرض الصور
                                </label>
                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 mb-4" x-show="form.gallery.length > 0">
                                    <template x-for="(image, index) in form.gallery" :key="index">
                                        <div class="relative group">
                                            <img :src="image?.url || image" alt="Gallery" class="w-full h-40 object-cover rounded-lg">
                                            <button type="button" @click="removeGalleryImage(index)" class="absolute top-2 left-2 bg-red-500 text-white p-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                                <button type="button" @click="openMediaLibrary('gallery')" class="flex items-center justify-center gap-2 w-full px-6 py-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span class="text-gray-600 dark:text-gray-400 font-medium">إضافة صور من المكتبة</span>
                                </button>
                                <p class="text-xs text-gray-500 mt-2 text-right">اختر صور متعددة من مكتبة الوسائط</p>
                            </div>

                        </div>

                        <!-- Tab 3: SEO & Metadata -->
                        <div x-show="activeTab === 'seo'" x-cloak class="space-y-6 fade-in">

                            <!-- SEO Score -->
                            <div class="flex items-center justify-between p-4 rounded-lg border bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                <div class="text-right">
                                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">درجة الـ SEO</h3>
                                    <p class="text-xs text-gray-500 mt-1">بناءً على العنوان والوصف والكلمات المفتاحية</p>
                                </div>
                                <div class="px-4 py-2 rounded-lg text-sm font-bold" :class="seoScore >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : (seoScore >= 50 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300')">
                                    <span x-text="seoScore"></span>/100
                                    <div class="text-xs mt-1">
                                        <span x-show="seoScore >= 80">ممتاز</span>
                                        <span x-show="seoScore >= 50 && seoScore < 80">جيد</span>
                                        <span x-show="seoScore < 50">يحتاج تحسين</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Meta Title -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                    عنوان SEO
                                </label>
                                <input
                                    type="text"
                                    dir="rtl"
                                    x-model="form.meta_title[currentLang]"
                                    @input="updateProgress(); calculateSEO()"
                                    class="input-clean w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none dark:bg-gray-800 dark:text-white text-right"
                                    placeholder="عنوان محسّن لمحركات البحث (60 حرف)..."
                                >
                                <div class="flex justify-start mt-2" dir="ltr">
                                    <div x-show="form.meta_title[currentLang]" class="char-counter" :class="form.meta_title[currentLang]?.length > 60 ? 'over-limit' : ''" x-cloak>
                                        <span x-text="form.meta_title[currentLang]?.length || 0"></span>/60 حرف
                                    </div>
                                </div>
                            </div>

                            <!-- Meta Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                    وصف الميتا
                                </label>
                                <textarea
                                    dir="rtl"
                                    x-model="form.meta_description[currentLang]"
                                    @input="updateProgress(); calculateSEO()"
                                    class="input-clean w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none dark:bg-gray-800 dark:text-white text-right"
                                    rows="4"
                                    placeholder="وصف قصير للمقال (160 حرف)..."
                                ></textarea>
                                <div class="flex justify-start mt-2" dir="ltr">
                                    <div x-show="form.meta_description[currentLang]" class="char-counter" :class="form.meta_description[currentLang]?.length > 160 ? 'over-limit' : ''" x-cloak>
                                        <span x-text="form.meta_description[currentLang]?.length || 0"></span>/160 حرف
                                    </div>
                                </div>
                            </div>

                            <!-- Keywords -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                    الكلمات المفتاحية
                                </label>
                                <input
                                    type="text"
                                    dir="rtl"
                                    x-model="form.keywords[currentLang]"
                                    @input="updateProgress(); calculateSEO()"
                                    class="input-clean w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none dark:bg-gray-800 dark:text-white text-right"
                                    placeholder="كلمة1, كلمة2, كلمة3..."
                                >
                                <p class="text-xs text-gray-500 mt-2 text-right">افصل الكلمات بفواصل. الموصى به: 3-10 كلمات مفتاحية.</p>
                            </div>

                        </div>

                        <!-- Tab 4: Historical Info -->
                        <div x-show="activeTab === 'historical'" x-cloak class="space-y-6 fade-in">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">

                                <!-- Hijri Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                        التاريخ الهجري
                                    </label>
                                    <input
                                        type="text"
                                        dir="rtl"
                                        x-model="form.hijri_date"
                                        @input="updateProgress()"
                                        class="input-clean w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none dark:bg-gray-800 dark:text-white text-right"
                                        placeholder="مثال: 20 رمضان 8 هـ"
                                    >
                                </div>

                                <!-- Gregorian Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                        التاريخ الميلادي
                                    </label>
                                    <input
                                        type="date"
                                        x-model="form.gregorian_date"
                                        @input="updateProgress()"
                                        class="input-clean w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none dark:bg-gray-800 dark:text-white text-left"
                                        dir="ltr"
                                    >
                                </div>

                                <!-- Location -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                        الموقع الرئيسي
                                    </label>
                                    <input
                                        type="text"
                                        dir="rtl"
                                        x-model="form.location"
                                        @input="updateProgress()"
                                        class="input-clean w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none dark:bg-gray-800 dark:text-white text-right"
                                        placeholder="مثال: مكة المكرمة"
                                    >
                                </div>

                                <!-- Difficulty -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                        مستوى الصعوبة
                                    </label>
                                    <select dir="rtl" x-model="form.difficulty" @change="updateProgress()" class="input-clean w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none dark:bg-gray-800 dark:text-white text-right">
                                        <option value="">اختر المستوى</option>
                                        <option value="beginner">مبتدئ</option>
                                        <option value="intermediate">متوسط</option>
                                        <option value="advanced">متقدم</option>
                                    </select>
                                </div>

                            </div>

                            <!-- Audience -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 text-right">
                                    الجمهور المستهدف
                                </label>
                                <div class="flex flex-wrap gap-4" dir="rtl">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">الأطفال</span>
                                        <input type="checkbox" value="children" x-model="form.audience" @change="updateProgress()" class="w-4 h-4 rounded text-blue-600">
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">الشباب</span>
                                        <input type="checkbox" value="youth" x-model="form.audience" @change="updateProgress()" class="w-4 h-4 rounded text-blue-600">
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">البالغين</span>
                                        <input type="checkbox" value="adults" x-model="form.audience" @change="updateProgress()" class="w-4 h-4 rounded text-blue-600">
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">العلماء والباحثين</span>
                                        <input type="checkbox" value="scholars" x-model="form.audience" @change="updateProgress()" class="w-4 h-4 rounded text-blue-600">
                                    </label>
                                </div>
                            </div>

                        </div>

                        <!-- Tab 5: Settings -->
                        <div x-show="activeTab === 'settings'" x-cloak class="space-y-6 fade-in">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">

                                <!-- Author -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                        الكاتب <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        dir="rtl"
                                        x-model="form.author"
                                        @input="updateProgress()"
                                        class="input-clean w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none dark:bg-gray-800 dark:text-white text-right"
                                        placeholder="اسم الكاتب..."
                                    >
                                </div>

                                <!-- Show TOC -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                        عرض جدول المحتويات
                                    </label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" x-model="form.show_toc" @change="updateProgress()">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>

                            </div>

                            <!-- Comments -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 text-right">
                                    التعليقات
                                </label>
                                <div class="space-y-3" dir="rtl">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">تفعيل التعليقات</span>
                                        <input type="checkbox" x-model="form.comments_enabled" @change="updateProgress()" class="w-4 h-4 rounded text-blue-600">
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">تتطلب مراجعة يدوية</span>
                                        <input type="checkbox" x-model="form.comments_moderation" @change="updateProgress()" class="w-4 h-4 rounded text-blue-600">
                                    </label>
                                </div>
                            </div>

                            <!-- References -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                    المراجع والمصادر
                                </label>
                                <textarea
                                    dir="rtl"
                                    x-model="form.references"
                                    @input="updateProgress()"
                                    class="input-clean w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none dark:bg-gray-800 dark:text-white text-right"
                                    rows="6"
                                    placeholder="أضف المراجع هنا (سطر واحد لكل مرجع)..."
                                ></textarea>
                            </div>

                        </div>

                        <!-- Tab 6: Related Articles -->
                        <div x-show="activeTab === 'related'" x-cloak class="space-y-6 fade-in">

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                    ابحث عن مقال
                                </label>
                                <input
                                    type="text"
                                    dir="rtl"
                                    x-model="relatedSearch"
                                    class="input-clean w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none dark:bg-gray-800 dark:text-white text-right"
                                    placeholder="اكتب لبحث المقالات..."
                                >
                                <p class="text-sm text-gray-500 mt-2 text-right">اضغط لإضافة</p>
                            </div>

                            <div x-show="form.related_articles.length > 0" class="space-y-2">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 text-right">المقالات المختارة</h4>
                                <template x-for="(article, index) in form.related_articles" :key="index">
                                    <div class="flex items-center justify-between p-3 rounded-lg border bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700" dir="rtl">
                                        <button type="button" @click="form.related_articles.splice(index, 1)" class="text-red-500 hover:text-red-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                        <span class="text-sm" x-text="article.title"></span>
                                    </div>
                                </template>
                            </div>

                            <div x-show="form.related_articles.length === 0" class="text-center py-12 text-gray-500">
                                لم يتم اختيار أي مقالات بعد.
                            </div>

                        </div>

                    </form>

                </div>
            </div>

            <!-- Sidebar (1fr - RIGHT) -->
            <div class="xl:col-span-1 order-1 xl:order-2">
                <div class="xl:sidebar-sticky space-y-6" dir="rtl">

                    <!-- Publishing Options -->
                    <div class="sidebar-card">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 text-right">خيارات النشر</h3>

                        <!-- Status -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                الحالة <span class="text-red-500">*</span>
                            </label>
                            <select dir="rtl" x-model="form.status" @change="updateProgress()" class="input-clean w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none dark:bg-gray-800 dark:text-white text-sm text-right">
                                <option value="draft">مسودة</option>
                                <option value="published">منشور</option>
                                <option value="under_review">قيد المراجعة</option>
                                <option value="archived">مؤرشف</option>
                            </select>
                        </div>

                        <!-- Published Date -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                تاريخ النشر
                            </label>
                            <input
                                type="datetime-local"
                                x-model="form.published_at"
                                @input="updateProgress()"
                                class="input-clean w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none dark:bg-gray-800 dark:text-white text-sm text-left"
                                dir="ltr"
                            >
                        </div>

                        <!-- Featured Toggle -->
                        <div class="flex items-center justify-between py-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-right">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">مقال مميز</span>
                                <p class="text-xs text-gray-500">عرض في الصفحة الرئيسية</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" x-model="form.featured" @change="updateProgress()">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <!-- Pinned Toggle -->
                        <div class="flex items-center justify-between py-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-right">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">تثبيت في الأعلى</span>
                                <p class="text-xs text-gray-500">يبقى في أعلى القوائم</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" x-model="form.pinned" @change="updateProgress()">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                    </div>

                    <!-- Featured Image -->
                    <div class="sidebar-card">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 text-right">الصورة المميزة</h3>

                        <div x-show="!form.featuredImage" class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center">
                            <button type="button" @click="openMediaLibrary('featured')" class="w-full">
                                <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">اختر من مكتبة الوسائط</p>
                                <p class="text-xs text-gray-500 mt-1">JPEG, PNG, WebP</p>
                                <p class="text-xs text-gray-500">المقاس الموصى به: 1200×630 بكسل</p>
                            </button>
                        </div>

                        <div x-show="form.featuredImage" class="relative group">
                            <div class="image-preview">
                                <img :src="form.featuredImage?.url || form.featuredImage" alt="Featured" class="w-full h-full object-cover">
                            </div>
                            <div class="mt-3 flex gap-2">
                                <button type="button" @click="openMediaLibrary('featured')" class="flex-1 px-3 py-2 text-center text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    تغيير
                                </button>
                                <button type="button" @click="form.featuredImage = null; updateProgress()" class="flex-1 px-3 py-2 text-sm border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                    حذف
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- Category & Organization -->
                    <div class="sidebar-card">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 text-right">التصنيف والتنظيم</h3>

                        <!-- Category -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                التصنيف <span class="text-red-500">*</span>
                            </label>
                            <select dir="rtl" x-model="form.category_id" @change="validateField('category'); updateProgress()" :class="errors.category ? 'input-error' : ''" class="input-clean w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none dark:bg-gray-800 dark:text-white text-sm text-right" required>
                                <option value="">-- اختر التصنيف --</option>
                                @foreach($categories ?? [] as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tags -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                                الوسوم
                            </label>
                            <div class="max-h-64 overflow-y-auto space-y-2 p-2 border border-gray-200 dark:border-gray-700 rounded-lg">
                                @foreach($tags ?? [] as $tag)
                                <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 p-2 rounded transition-colors" dir="rtl">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $tag->name }}</span>
                                    <input type="checkbox" value="{{ $tag->id }}" x-model="form.tags" @change="updateProgress()" class="w-4 h-4 rounded text-blue-600">
                                </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-500 mt-2 text-right" x-show="form.tags.length > 0">
                                <span x-text="form.tags.length"></span> وسم محدد
                            </p>
                        </div>

                    </div>

                    <!-- Actions -->
                    <div class="sidebar-card">
                        <div class="space-y-3">
                            <button type="button" @click="openPreview" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                معاينة
                            </button>
                            <button type="button" @click="saveDraft" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                حفظ كمسودة
                            </button>
                            <button type="submit" @click="publish" class="w-full px-4 py-3 gradient-button text-white font-bold rounded-lg">
                                نشر المقال
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Media Library Modal -->
    <div x-show="mediaLibrary.isOpen"
         x-cloak
         @keydown.escape.window="mediaLibrary.isOpen = false"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">

        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
             @click="mediaLibrary.isOpen = false"></div>

        <!-- Modal -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden"
                 @click.stop>

                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">مكتبة الوسائط</h2>
                    <button @click="mediaLibrary.isOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Filters -->
                <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                        <!-- Search -->
                        <div class="sm:col-span-2">
                            <input type="text"
                                   x-model="mediaLibrary.search"
                                   @input="filterMedia()"
                                   placeholder="بحث بالاسم..."
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-right"
                                   dir="rtl">
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <select x-model="mediaLibrary.typeFilter"
                                    @change="filterMedia()"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-right"
                                    dir="rtl">
                                <option value="all">جميع الأنواع</option>
                                <option value="image">صور</option>
                                <option value="video">فيديو</option>
                                <option value="document">مستندات</option>
                            </select>
                        </div>

                        <!-- Date Filter -->
                        <div>
                            <select x-model="mediaLibrary.dateFilter"
                                    @change="filterMedia()"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-right"
                                    dir="rtl">
                                <option value="all">جميع التواريخ</option>
                                <option value="today">اليوم</option>
                                <option value="week">هذا الأسبوع</option>
                                <option value="month">هذا الشهر</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Media Grid -->
                <div class="p-4 sm:p-6 overflow-y-auto" style="max-height: 500px;">
                    <!-- Loading State -->
                    <div x-show="mediaLibrary.loading" class="flex items-center justify-center py-20">
                        <div class="text-center">
                            <svg class="animate-spin h-12 w-12 mx-auto text-blue-500 mb-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-gray-600 dark:text-gray-400">جاري التحميل...</p>
                        </div>
                    </div>

                    <!-- Media Grid -->
                    <div x-show="!mediaLibrary.loading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4">
                        <template x-for="(media, index) in mediaLibrary.filteredItems" :key="media.id">
                            <div @click="selectMedia(media)"
                                 :class="isMediaSelected(media) ? 'ring-4 ring-blue-500' : ''"
                                 class="relative group cursor-pointer rounded-lg overflow-hidden border-2 border-transparent hover:border-blue-300 transition-all">
                                <div class="aspect-square bg-gray-100 dark:bg-gray-800">
                                    <img :src="media.url"
                                         :alt="media.name"
                                         class="w-full h-full object-cover">
                                </div>

                                <!-- Selected Badge -->
                                <div x-show="isMediaSelected(media)"
                                     class="absolute top-2 right-2 bg-blue-500 text-white rounded-full p-1">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>

                                <!-- Info Overlay -->
                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <p class="text-white text-xs truncate" x-text="media.name"></p>
                                    <p class="text-white text-xs" x-text="formatFileSize(media.size)"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty State -->
                    <div x-show="!mediaLibrary.loading && mediaLibrary.filteredItems.length === 0" class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">لا توجد وسائط</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="p-4 sm:p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-0">
                        <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                            عرض <span x-text="mediaLibrary.pagination.from"></span> -
                            <span x-text="mediaLibrary.pagination.to"></span> من
                            <span x-text="mediaLibrary.pagination.total"></span>
                        </div>

                        <div class="flex flex-wrap gap-2 justify-center sm:justify-start">
                            <button @click="previousPage()"
                                    :disabled="mediaLibrary.pagination.currentPage === 1"
                                    :class="mediaLibrary.pagination.currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-700'"
                                    class="px-3 sm:px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 text-sm">
                                السابق
                            </button>

                            <div class="flex gap-1 flex-wrap justify-center">
                                <template x-for="page in pageNumbers()" :key="page">
                                    <button @click="goToPage(page)"
                                            :class="page === mediaLibrary.pagination.currentPage ? 'bg-blue-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                            class="px-2 sm:px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm"
                                            x-text="page">
                                    </button>
                                </template>
                            </div>

                            <button @click="nextPage()"
                                    :disabled="mediaLibrary.pagination.currentPage === mediaLibrary.pagination.lastPage"
                                    :class="mediaLibrary.pagination.currentPage === mediaLibrary.pagination.lastPage ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-700'"
                                    class="px-3 sm:px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 text-sm">
                                التالي
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-4 sm:p-6 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-3 sm:gap-0">
                    <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                        <span x-show="mediaLibrary.mode === 'gallery'">
                            <span x-text="mediaLibrary.selectedItems.length"></span> عنصر محدد
                        </span>
                    </div>
                    <div class="flex gap-3 w-full sm:w-auto">
                        <button @click="mediaLibrary.isOpen = false"
                                class="flex-1 sm:flex-none px-4 sm:px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 text-sm">
                            إلغاء
                        </button>
                        <button @click="confirmSelection()"
                                class="flex-1 sm:flex-none px-4 sm:px-6 py-2 gradient-button text-white rounded-lg text-sm">
                            تأكيد الاختيار
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function articleForm() {
    return {
        activeTab: 'content',
        currentLang: 'ar',
        progress: 0,
        saving: false,
        lastSaved: false,
        seoScore: 0,
        relatedSearch: '',
        darkMode: document.documentElement.classList.contains('dark'),

        form: {
            title: { ar: '', en: '', tr: '', ur: '' },
            excerpt: { ar: '', en: '', tr: '', ur: '' },
            content_body: { ar: '', en: '', tr: '', ur: '' },
            content_toc: { ar: [], en: [], tr: [], ur: [] },
            content_summarize_points: { ar: [], en: [], tr: [], ur: [] },
            content_summary: { ar: '', en: '', tr: '', ur: '' },
            meta_title: { ar: '', en: '', tr: '', ur: '' },
            meta_description: { ar: '', en: '', tr: '', ur: '' },
            keywords: { ar: '', en: '', tr: '', ur: '' },
            status: 'draft',
            category_id: '',
            author: '',
            published_at: '',
            tags: [],
            featuredImage: null,
            gallery: [],
            hijri_date: '',
            gregorian_date: '',
            location: '',
            difficulty: '',
            audience: [],
            references: '',
            featured: false,
            pinned: false,
            show_toc: true,
            comments_enabled: true,
            comments_moderation: false,
            related_articles: []
        },

        errors: {},

        // Media Library
        mediaLibrary: {
            isOpen: false,
            loading: false,
            mode: 'featured', // 'featured' or 'gallery'
            search: '',
            typeFilter: 'all',
            dateFilter: 'all',
            allItems: [],
            filteredItems: [],
            selectedItems: [],
            pagination: {
                currentPage: 1,
                perPage: 12,
                total: 0,
                from: 0,
                to: 0,
                lastPage: 1
            }
        },

        init() {
            this.updateProgress();
            this.setupAutoSave();
        },

        validateField(field) {
            delete this.errors[field];

            if (field === 'title' && !this.form.title[this.currentLang]) {
                this.errors.title = 'عنوان المقال مطلوب';
            }
            if (field === 'excerpt' && !this.form.excerpt[this.currentLang]) {
                this.errors.excerpt = 'ملخص المقال مطلوب';
            }
            if (field === 'content' && !this.form.content_body[this.currentLang]) {
                this.errors.content = 'محتوى المقال مطلوب';
            }
            if (field === 'category' && !this.form.category_id) {
                this.errors.category = 'التصنيف مطلوب';
            }
        },

        addTocItem() {
            if (!this.form.content_toc[this.currentLang]) {
                this.form.content_toc[this.currentLang] = [];
            }
            this.form.content_toc[this.currentLang].push({
                title: '',
                link: ''
            });
        },

        addSummarizePoint() {
            if (!this.form.content_summarize_points[this.currentLang]) {
                this.form.content_summarize_points[this.currentLang] = [];
            }
            this.form.content_summarize_points[this.currentLang].push('');
        },

        updateProgress() {
            let completed = 0;
            let total = 10;

            if (this.form.title.ar || this.form.title.en) completed++;
            if (this.form.excerpt.ar || this.form.excerpt.en) completed++;
            if (this.form.content_body.ar || this.form.content_body.en) completed++;
            if (this.form.category_id) completed++;
            if (this.form.featuredImage) completed++;
            if (this.form.tags.length > 0) completed++;
            if (this.form.meta_title.ar || this.form.meta_title.en) completed++;
            if (this.form.meta_description.ar || this.form.meta_description.en) completed++;
            if (this.form.author) completed++;
            if (this.form.published_at) completed++;

            this.progress = Math.round((completed / total) * 100);
        },

        calculateSEO() {
            let score = 0;

            if (this.form.meta_title[this.currentLang]) {
                const titleLen = this.form.meta_title[this.currentLang].length;
                if (titleLen >= 30 && titleLen <= 60) score += 30;
                else if (titleLen > 0) score += 15;
            }

            if (this.form.meta_description[this.currentLang]) {
                const descLen = this.form.meta_description[this.currentLang].length;
                if (descLen >= 120 && descLen <= 160) score += 30;
                else if (descLen > 0) score += 15;
            }

            if (this.form.keywords[this.currentLang]) {
                const keywordsCount = this.form.keywords[this.currentLang].split(',').filter(k => k.trim()).length;
                if (keywordsCount >= 3 && keywordsCount <= 10) score += 20;
                else if (keywordsCount > 0) score += 10;
            }

            if (this.form.content_body[this.currentLang]) {
                const contentLen = this.form.content_body[this.currentLang].length;
                if (contentLen >= 300) score += 20;
                else if (contentLen > 0) score += 10;
            }

            this.seoScore = score;
        },

        setupAutoSave() {
            let timeout;
            this.$watch('form', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.autoSave();
                }, 3000);
            }, { deep: true });
        },

        async autoSave() {
            this.saving = true;
            localStorage.setItem('article_draft', JSON.stringify(this.form));
            await new Promise(resolve => setTimeout(resolve, 500));
            this.saving = false;
            this.lastSaved = true;
            setTimeout(() => {
                this.lastSaved = false;
            }, 2000);
        },

        saveDraft() {
            this.form.status = 'draft';
            this.submit();
        },

        publish() {
            this.form.status = 'published';
            this.submit();
        },

        async submit() {
            this.validateField('title');
            this.validateField('excerpt');
            this.validateField('content');
            this.validateField('category');

            if (Object.keys(this.errors).length > 0) {
                alert('يرجى إصلاح الأخطاء قبل الحفظ');
                this.activeTab = 'content';
                return;
            }

            this.saving = true;

            try {
                // TODO: Implement actual form submission
                await new Promise(resolve => setTimeout(resolve, 1000));
                localStorage.removeItem('article_draft');
                window.location.href = '{{ route("admin.articles.index") }}';
            } catch (error) {
                alert('خطأ في حفظ المقال');
                this.saving = false;
            }
        },

        openPreview() {
            window.open('#', '_blank');
        },

        handleFeaturedImage(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('حجم الصورة يجب أن يكون أقل من 2 ميجابايت');
                    return;
                }
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.form.featuredImage = e.target.result;
                    this.updateProgress();
                };
                reader.readAsDataURL(file);
            }
        },

        handleGallery(event) {
            const files = Array.from(event.target.files);
            files.forEach(file => {
                if (file.size > 5 * 1024 * 1024) {
                    alert('حجم الصورة يجب أن يكون أقل من 5 ميجابايت');
                    return;
                }
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.form.gallery.push(e.target.result);
                    this.updateProgress();
                };
                reader.readAsDataURL(file);
            });
        },

        removeGalleryImage(index) {
            this.form.gallery.splice(index, 1);
            this.updateProgress();
        },

        // Media Library Methods
        async loadMediaLibrary() {
            this.mediaLibrary.loading = true;
            try {
                const response = await fetch('/su/portal/v1/admin/simple-media', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load media library');
                }

                const data = await response.json();

                // Transform Laravel pagination data to our format
                this.mediaLibrary.allItems = data.data.map(item => ({
                    id: item.id,
                    name: item.original_filename || item.filename,
                    url: item.url,
                    type: item.type || 'image',
                    size: item.size,
                    created_at: item.created_at
                }));

                this.filterMedia();
            } catch (error) {
                console.error('Error loading media library:', error);
                alert('فشل تحميل مكتبة الوسائط. يرجى المحاولة مرة أخرى.');
            } finally {
                this.mediaLibrary.loading = false;
            }
        },

        async openMediaLibrary(mode) {
            this.mediaLibrary.mode = mode;
            this.mediaLibrary.isOpen = true;
            this.mediaLibrary.selectedItems = [];
            this.mediaLibrary.search = '';
            this.mediaLibrary.typeFilter = 'all';
            this.mediaLibrary.dateFilter = 'all';
            this.mediaLibrary.pagination.currentPage = 1;

            // Reload media library data when opened
            await this.loadMediaLibrary();
        },

        filterMedia() {
            let filtered = [...this.mediaLibrary.allItems];

            // Search filter
            if (this.mediaLibrary.search) {
                const search = this.mediaLibrary.search.toLowerCase();
                filtered = filtered.filter(item =>
                    item.name.toLowerCase().includes(search)
                );
            }

            // Type filter
            if (this.mediaLibrary.typeFilter !== 'all') {
                filtered = filtered.filter(item =>
                    item.type === this.mediaLibrary.typeFilter
                );
            }

            // Date filter
            if (this.mediaLibrary.dateFilter !== 'all') {
                const now = new Date();
                const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                const monthAgo = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());

                filtered = filtered.filter(item => {
                    const itemDate = new Date(item.created_at);
                    if (this.mediaLibrary.dateFilter === 'today') {
                        return itemDate >= today;
                    } else if (this.mediaLibrary.dateFilter === 'week') {
                        return itemDate >= weekAgo;
                    } else if (this.mediaLibrary.dateFilter === 'month') {
                        return itemDate >= monthAgo;
                    }
                    return true;
                });
            }

            // Update pagination
            this.mediaLibrary.pagination.total = filtered.length;
            this.mediaLibrary.pagination.lastPage = Math.ceil(filtered.length / this.mediaLibrary.pagination.perPage);

            // Paginate
            const start = (this.mediaLibrary.pagination.currentPage - 1) * this.mediaLibrary.pagination.perPage;
            const end = start + this.mediaLibrary.pagination.perPage;

            this.mediaLibrary.pagination.from = filtered.length > 0 ? start + 1 : 0;
            this.mediaLibrary.pagination.to = Math.min(end, filtered.length);

            this.mediaLibrary.filteredItems = filtered.slice(start, end);
        },

        selectMedia(media) {
            if (this.mediaLibrary.mode === 'featured') {
                // Single selection for featured image
                this.mediaLibrary.selectedItems = [media];
            } else {
                // Multiple selection for gallery
                const index = this.mediaLibrary.selectedItems.findIndex(item => item.id === media.id);
                if (index > -1) {
                    this.mediaLibrary.selectedItems.splice(index, 1);
                } else {
                    this.mediaLibrary.selectedItems.push(media);
                }
            }
        },

        isMediaSelected(media) {
            return this.mediaLibrary.selectedItems.some(item => item.id === media.id);
        },

        confirmSelection() {
            if (this.mediaLibrary.mode === 'featured') {
                if (this.mediaLibrary.selectedItems.length > 0) {
                    this.form.featuredImage = this.mediaLibrary.selectedItems[0];
                    this.updateProgress();
                }
            } else {
                // Add to gallery (avoid duplicates)
                this.mediaLibrary.selectedItems.forEach(media => {
                    const exists = this.form.gallery.some(item =>
                        (item.id && item.id === media.id) || item === media.url
                    );
                    if (!exists) {
                        this.form.gallery.push(media);
                    }
                });
                this.updateProgress();
            }
            this.mediaLibrary.isOpen = false;
        },

        previousPage() {
            if (this.mediaLibrary.pagination.currentPage > 1) {
                this.mediaLibrary.pagination.currentPage--;
                this.filterMedia();
            }
        },

        nextPage() {
            if (this.mediaLibrary.pagination.currentPage < this.mediaLibrary.pagination.lastPage) {
                this.mediaLibrary.pagination.currentPage++;
                this.filterMedia();
            }
        },

        goToPage(page) {
            this.mediaLibrary.pagination.currentPage = page;
            this.filterMedia();
        },

        pageNumbers() {
            const pages = [];
            const current = this.mediaLibrary.pagination.currentPage;
            const last = this.mediaLibrary.pagination.lastPage;

            if (last <= 7) {
                for (let i = 1; i <= last; i++) {
                    pages.push(i);
                }
            } else {
                if (current <= 4) {
                    for (let i = 1; i <= 5; i++) {
                        pages.push(i);
                    }
                    pages.push('...');
                    pages.push(last);
                } else if (current >= last - 3) {
                    pages.push(1);
                    pages.push('...');
                    for (let i = last - 4; i <= last; i++) {
                        pages.push(i);
                    }
                } else {
                    pages.push(1);
                    pages.push('...');
                    for (let i = current - 1; i <= current + 1; i++) {
                        pages.push(i);
                    }
                    pages.push('...');
                    pages.push(last);
                }
            }

            return pages.filter(p => p !== '...' || pages.indexOf(p) === pages.lastIndexOf(p));
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
    };
}
</script>
@endpush

@endsection
