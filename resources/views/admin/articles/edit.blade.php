@extends('layouts.admin')

@section('title', 'تحرير المقال')

@push('styles')
<style>
    /* Premium Gradient Color Scheme */
    .gradient-primary {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
    }

    .gradient-text {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Glassmorphism Effect */
    .glass {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    [x-cloak] { display: none !important; }

    /* Step Progress */
    .step-line {
        position: absolute;
        top: 20px;
        left: 50%;
        right: -50%;
        height: 2px;
        background: #e5e7eb;
        z-index: 0;
    }

    .step-line.active {
        background: linear-gradient(90deg, #0056b3 0%, #218838 100%);
    }

    /* Teal Accent for Interactive Elements */
    .accent-teal {
        color: #64CCC5;
    }

    .hover-teal:hover {
        color: #64CCC5 !important;
    }

    /* Premium Card Hover */
    .premium-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .premium-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    /* Language Tab Styles */
    .lang-tab {
        position: relative;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .lang-tab.active {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        color: white;
        box-shadow: 0 4px 6px -1px rgba(0, 86, 179, 0.2);
    }

    .lang-tab:not(.active):hover {
        background: rgba(100, 204, 197, 0.1);
        color: #64CCC5;
    }

    /* Thumbnail Preview */
    .thumb-preview {
        position: relative;
        width: 100%;
        aspect-ratio: 16/9;
        border-radius: 1rem;
        overflow: hidden;
        background: linear-gradient(135deg, rgba(0, 86, 179, 0.1) 0%, rgba(33, 136, 56, 0.1) 100%);
    }

    /* Gallery Grid */
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1rem;
    }

    .gallery-item {
        position: relative;
        aspect-ratio: 1;
        border-radius: 0.5rem;
        overflow: hidden;
        cursor: move;
    }

    /* Dark Mode Support */
    .dark .glass {
        background: rgba(0, 0, 0, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(0, 0, 0, 0.1);
    }

    /* Auto-save Indicator */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .saving {
        animation: pulse 1.5s ease-in-out infinite;
    }

    /* TinyMCE Container */
    .tox-tinymce {
        border-radius: 0.5rem !important;
        border-color: #e5e7eb !important;
    }

    /* Reference Item */
    .reference-item {
        position: relative;
        padding: 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        transition: all 0.2s;
    }

    .reference-item:hover {
        border-color: #64CCC5;
        background: rgba(100, 204, 197, 0.05);
    }

    /* Stats Badge */
    .stats-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, rgba(0, 86, 179, 0.1) 0%, rgba(33, 136, 56, 0.1) 100%);
        border-radius: 0.5rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl" x-data="articleWizard()">

    {{-- Header with Stats --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold gradient-text mb-2">تحرير المقال #{{ $article->id }}</h1>
                <p class="text-gray-600 dark:text-gray-400">آخر تحديث: {{ $article->updated_at->diffForHumans() }}</p>
            </div>

            {{-- Auto-save Status --}}
            <div class="flex items-center gap-3">
                <div x-show="autoSaveStatus === 'saving'" class="flex items-center gap-2 text-blue-600 saving">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span class="text-sm font-medium">جاري الحفظ...</span>
                </div>

                <div x-show="autoSaveStatus === 'saved'" class="flex items-center gap-2 text-green-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="text-sm font-medium">تم الحفظ تلقائياً</span>
                </div>
            </div>
        </div>

        {{-- Article Stats --}}
        <div class="flex gap-3 flex-wrap">
            <div class="stats-badge">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <span>{{ number_format($article->views) }} مشاهدة</span>
            </div>

            @if($article->is_featured)
                <div class="stats-badge text-yellow-600">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                    <span>مميز</span>
                </div>
            @endif

            @if($article->is_pinned)
                <div class="stats-badge text-red-600">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"></path>
                    </svg>
                    <span>مثبت</span>
                </div>
            @endif

            <div class="stats-badge">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path>
                </svg>
                <span>{{ $article->locales->where('is_ready', true)->count() }} لغة جاهزة</span>
            </div>
        </div>
    </div>

    {{-- Step Progress Indicator --}}
    <div class="mb-10 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 premium-card">
        <div class="flex items-center justify-between relative">
            @foreach(['التفاصيل الأساسية', 'المحتوى متعدد اللغات', 'الوسائط والمعرض', 'SEO والبيانات الوصفية', 'التصنيفات والمراجع'] as $index => $stepName)
                <div class="flex flex-col items-center relative z-10" style="flex: 1;">
                    {{-- Line to Next Step --}}
                    @if($index < 4)
                        <div class="step-line" :class="{'active': currentStep > {{ $index + 1 }}}"></div>
                    @endif

                    {{-- Step Circle --}}
                    <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg mb-3 transition-all duration-300"
                         :class="currentStep > {{ $index + 1 }} ? 'gradient-primary text-white shadow-lg' :
                                 currentStep === {{ $index + 1 }} ? 'ring-4 ring-[#64CCC5] ring-opacity-50 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-xl scale-110' :
                                 'bg-gray-200 dark:bg-gray-700 text-gray-500'">
                        <span x-show="currentStep <= {{ $index + 1 }}">{{ $index + 1 }}</span>
                        <svg x-show="currentStep > {{ $index + 1 }}" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>

                    {{-- Step Label --}}
                    <span class="text-sm font-semibold text-center transition-colors duration-300"
                          :class="currentStep === {{ $index + 1 }} ? 'accent-teal' : 'text-gray-600 dark:text-gray-400'">
                        {{ $stepName }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Main Form --}}
    <form method="POST" action="{{ route('admin.articles.update', $article) }}" enctype="multipart/form-data"
          @submit.prevent="validateAndSubmit" id="articleForm">
        @csrf
        @method('PUT')

        {{-- Step 1: Basic Details --}}
        <div x-show="currentStep === 1" x-cloak class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 premium-card">
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                    <span class="gradient-text">الخطوة 1:</span>
                    <span class="text-gray-800 dark:text-gray-200">التفاصيل الأساسية</span>
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Current & New Thumbnail --}}
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            الصورة المصغرة
                        </label>

                        {{-- Current Thumbnail --}}
                        @if($article->thumb)
                            <div class="mb-4">
                                <p class="text-xs text-gray-500 mb-2">الصورة الحالية:</p>
                                <img src="{{ asset('storage/' . $article->thumb) }}" alt="Current Thumbnail" class="h-32 w-auto rounded-lg shadow-md">
                            </div>
                        @endif

                        <div class="thumb-preview" x-ref="thumbPreview"
                             @click="$refs.thumbInput.click()"
                             @dragover.prevent="thumbDragOver = true"
                             @dragleave.prevent="thumbDragOver = false"
                             @drop.prevent="handleThumbDrop($event)"
                             :class="{'ring-4 ring-[#64CCC5] ring-opacity-50': thumbDragOver}">

                            <template x-if="!thumbPreviewUrl">
                                <div class="absolute inset-0 flex flex-col items-center justify-center cursor-pointer hover:bg-black hover:bg-opacity-5 transition-colors">
                                    <svg class="w-16 h-16 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="text-gray-600 dark:text-gray-400 font-medium">انقر لتغيير الصورة أو اسحب صورة جديدة</p>
                                    <p class="text-sm text-gray-500 mt-1">PNG, JPG, WebP (الحد الأقصى 5MB)</p>
                                </div>
                            </template>

                            <template x-if="thumbPreviewUrl">
                                <div class="relative w-full h-full group">
                                    <img :src="thumbPreviewUrl" alt="Preview" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                                        <button type="button" @click.stop="removeThumb()" class="px-4 py-2 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition-colors">
                                            حذف الصورة الجديدة
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <input type="file" x-ref="thumbInput" name="thumb" accept="image/*" class="hidden" @change="handleThumbChange($event)">
                        <p class="text-xs text-gray-500 mt-2">* اختياري - رفع صورة جديدة سيستبدل الصورة الحالية</p>
                    </div>

                    {{-- Writer Selection --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">الكاتب</label>
                        <select name="writer_id" x-model="formData.writer_id" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all dark:bg-gray-700 dark:text-white">
                            <option value="">اختر الكاتب</option>
                            @foreach($writers ?? [] as $writer)
                                <option value="{{ $writer->id }}" {{ $article->writer_id == $writer->id ? 'selected' : '' }}>
                                    {{ $writer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Category Selection --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">التصنيف الرئيسي</label>
                        <select name="category_id" x-model="formData.category_id" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all dark:bg-gray-700 dark:text-white">
                            <option value="">اختر التصنيف</option>
                            @foreach($categories ?? [] as $category)
                                <option value="{{ $category->id }}" {{ $article->category_id == $category->id ? 'selected' : '' }}>
                                    {{ $category->getLocale('ar')->title ?? $category->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Read Time with Auto Calculator --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <span>وقت القراءة (دقائق) <span class="text-red-500">*</span></span>
                            <button type="button" @click="calculateReadTime()" class="text-xs px-2 py-1 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded hover:shadow-lg transition-all">
                                إعادة الحساب
                            </button>
                        </label>
                        <input type="number" name="read_time" x-model="formData.read_time" min="0" required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all dark:bg-gray-700 dark:text-white"
                               placeholder="5">
                    </div>

                    {{-- Hijri Date Picker --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">التاريخ الهجري</label>
                        <input type="text" name="hijri_date" x-model="formData.hijri_date"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all dark:bg-gray-700 dark:text-white"
                               placeholder="1 محرم 1446">
                        <p class="text-xs text-gray-500 mt-1">مثال: 1 محرم 1446</p>
                    </div>

                    {{-- Flags --}}
                    <div class="lg:col-span-2 flex gap-8 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <div class="relative">
                                <input type="checkbox" name="is_featured" x-model="formData.is_featured" value="1" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#64CCC5] peer-focus:ring-opacity-30 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-gradient-to-r peer-checked:from-[#0056b3] peer-checked:to-[#218838]"></div>
                            </div>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-[#64CCC5] transition-colors">مقال مميز</span>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer group">
                            <div class="relative">
                                <input type="checkbox" name="is_pinned" x-model="formData.is_pinned" value="1" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#64CCC5] peer-focus:ring-opacity-30 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-gradient-to-r peer-checked:from-[#0056b3] peer-checked:to-[#218838]"></div>
                            </div>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-[#64CCC5] transition-colors">مقال مثبت</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: Multi-Language Content --}}
        <div x-show="currentStep === 2" x-cloak class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 premium-card">
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                    <span class="gradient-text">الخطوة 2:</span>
                    <span class="text-gray-800 dark:text-gray-200">المحتوى متعدد اللغات</span>
                </h2>

                {{-- Language Tabs --}}
                <div class="flex gap-3 mb-6 flex-wrap">
                    @foreach(['ar' => ['العربية', '🇸🇦'], 'en' => ['English', '🇬🇧'], 'fr' => ['Français', '🇫🇷'], 'tr' => ['Türkçe', '🇹🇷'], 'ur' => ['اردو', '🇵🇰']] as $code => $langInfo)
                        <button type="button" @click="activeLanguage = '{{ $code }}'"
                                class="lang-tab flex items-center gap-2"
                                :class="{'active': activeLanguage === '{{ $code }}'}">
                            <span class="text-2xl">{{ $langInfo[1] }}</span>
                            <span class="font-semibold">{{ $langInfo[0] }}</span>
                            <svg x-show="locales['{{ $code }}'].is_ready" class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    @endforeach
                </div>

                {{-- Language Content Forms --}}
                @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $locale)
                    @php
                        $existingLocale = $article->locales->where('locale', $locale)->first();
                    @endphp

                    <div x-show="activeLanguage === '{{ $locale }}'" x-cloak>
                        @if($existingLocale)
                            <input type="hidden" name="locales[{{ $locale }}][id]" value="{{ $existingLocale->id }}">
                        @endif
                        <input type="hidden" :name="'locales[{{ $locale }}][locale]'" value="{{ $locale }}">

                        <div class="space-y-6" :dir="['ar', 'ur'].includes('{{ $locale }}') ? 'rtl' : 'ltr'">
                            {{-- Title --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                    العنوان <span class="text-red-500" x-show="activeLanguage === 'ar'">*</span>
                                    <span class="text-xs text-gray-500 ml-2" x-text="'(' + (locales['{{ $locale }}'].title?.length || 0) + '/500)'"></span>
                                </label>
                                <input type="text"
                                       :name="'locales[{{ $locale }}][title]'"
                                       x-model="locales['{{ $locale }}'].title"
                                       @input="generateSlug('{{ $locale }}')"
                                       :required="activeLanguage === 'ar'"
                                       maxlength="500"
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all dark:bg-gray-700 dark:text-white"
                                       placeholder="أدخل عنوان المقال">
                            </div>

                            {{-- Auto-generated Slug --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                    <span>الرابط (Slug)</span>
                                    <span class="text-xs text-gray-500">(يتم إنشاؤه تلقائياً)</span>
                                </label>
                                <div class="flex gap-2">
                                    <input type="text"
                                           x-model="locales['{{ $locale }}'].slug"
                                           readonly
                                           class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-400">
                                    <button type="button" @click="generateSlug('{{ $locale }}')"
                                            class="px-4 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Excerpt --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                    المقتطف
                                    <span class="text-xs text-gray-500 ml-2" x-text="'(' + (locales['{{ $locale }}'].excerpt?.length || 0) + '/500)'"></span>
                                </label>
                                <textarea :name="'locales[{{ $locale }}][excerpt]'"
                                          x-model="locales['{{ $locale }}'].excerpt"
                                          rows="3"
                                          maxlength="500"
                                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all dark:bg-gray-700 dark:text-white resize-none"
                                          placeholder="ملخص قصير للمقال"></textarea>
                            </div>

                            {{-- Rich Text Editor --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center justify-between">
                                    <span>المحتوى</span>
                                    <span class="text-xs font-normal" x-text="getWordCount('{{ $locale }}')"></span>
                                </label>
                                <textarea :name="'locales[{{ $locale }}][body]'"
                                          :id="'editor_{{ $locale }}'"
                                          x-model="locales['{{ $locale }}'].body"
                                          class="tinymce-editor"></textarea>
                            </div>

                            {{-- Summary --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">الملخص</label>
                                <textarea :name="'locales[{{ $locale }}][summary]'"
                                          x-model="locales['{{ $locale }}'].summary"
                                          rows="3"
                                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all dark:bg-gray-700 dark:text-white resize-none"
                                          placeholder="ملخص شامل للمقال"></textarea>
                            </div>

                            {{-- Is Ready Checkbox --}}
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox"
                                           :name="'locales[{{ $locale }}][is_ready]'"
                                           x-model="locales['{{ $locale }}'].is_ready"
                                           value="1"
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#64CCC5] peer-focus:ring-opacity-30 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-gradient-to-r peer-checked:from-[#0056b3] peer-checked:to-[#218838]"></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-[#64CCC5] transition-colors">
                                    الترجمة جاهزة للنشر
                                </span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Step 3: Media & Gallery --}}
        <div x-show="currentStep === 3" x-cloak class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 premium-card">
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                    <span class="gradient-text">الخطوة 3:</span>
                    <span class="text-gray-800 dark:text-gray-200">الوسائط والمعرض</span>
                </h2>

                {{-- Existing Gallery --}}
                @if($article->gallery->count() > 0)
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">المعرض الحالي ({{ $article->gallery->count() }} صورة)</h3>
                        <div class="gallery-grid">
                            @foreach($article->gallery as $galleryImage)
                                <div class="gallery-item group relative">
                                    <img src="{{ asset('storage/' . $galleryImage->image_path) }}" alt="Gallery {{ $galleryImage->order }}" class="w-full h-full object-cover">
                                    <div class="absolute top-2 left-2 w-8 h-8 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-full flex items-center justify-center text-sm font-bold">
                                        <span>{{ $galleryImage->order }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500 mt-2">* لإدارة المعرض الحالي، استخدم صفحة إدارة الوسائط</p>
                    </div>
                @endif

                {{-- New Gallery Upload --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">إضافة صور جديدة للمعرض</label>

                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center cursor-pointer hover:border-[#64CCC5] transition-colors"
                         @click="$refs.galleryInput.click()"
                         @dragover.prevent="galleryDragOver = true"
                         @dragleave.prevent="galleryDragOver = false"
                         @drop.prevent="handleGalleryDrop($event)"
                         :class="{'border-[#64CCC5] bg-[#64CCC5] bg-opacity-5': galleryDragOver}">
                        <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 font-medium">انقر لرفع الصور أو اسحبها هنا</p>
                        <p class="text-sm text-gray-500 mt-1">يمكنك رفع عدة صور في وقت واحد</p>
                    </div>

                    <input type="file" x-ref="galleryInput" name="gallery[]" accept="image/*" multiple class="hidden" @change="handleGalleryChange($event)">

                    {{-- Gallery Preview Grid --}}
                    <div x-show="galleryImages.length > 0" class="gallery-grid mt-6">
                        <template x-for="(image, index) in galleryImages" :key="index">
                            <div class="gallery-item group relative" draggable="true">
                                <img :src="image.url" :alt="'Gallery ' + (index + 1)" class="w-full h-full object-cover">

                                {{-- Overlay with Actions --}}
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                                    <button type="button" @click="removeGalleryImage(index)"
                                            class="p-2 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>

                                {{-- Order Badge --}}
                                <div class="absolute top-2 left-2 w-8 h-8 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-full flex items-center justify-center text-sm font-bold">
                                    <span x-text="index + 1"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 4: SEO & Metadata (same as create) --}}
        <div x-show="currentStep === 4" x-cloak class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 premium-card">
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                    <span class="gradient-text">الخطوة 4:</span>
                    <span class="text-gray-800 dark:text-gray-200">SEO والبيانات الوصفية</span>
                </h2>

                {{-- SEO for Each Language --}}
                <div class="space-y-6">
                    <p class="text-sm text-gray-600 dark:text-gray-400">قم بتحسين المقال لمحركات البحث لكل لغة</p>

                    @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $locale)
                        <div class="p-6 border-2 border-gray-200 dark:border-gray-700 rounded-xl">
                            <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                                <span>{{ ['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'][$locale] }}</span>
                                <span class="text-2xl">{{ ['ar' => '🇸🇦', 'en' => '🇬🇧', 'fr' => '🇫🇷', 'tr' => '🇹🇷', 'ur' => '🇵🇰'][$locale] }}</span>
                            </h3>

                            <div class="space-y-4">
                                {{-- Meta Title --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        عنوان الميتا
                                        <span class="text-xs text-gray-500 ml-2" x-text="'(' + (locales['{{ $locale }}'].seo_title?.length || 0) + '/60)'"></span>
                                    </label>
                                    <input type="text"
                                           :name="'locales[{{ $locale }}][seo_title]'"
                                           x-model="locales['{{ $locale }}'].seo_title"
                                           maxlength="60"
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all dark:bg-gray-700 dark:text-white"
                                           placeholder="عنوان تحسين محركات البحث (60 حرف)">
                                </div>

                                {{-- Meta Description --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        وصف الميتا
                                        <span class="text-xs text-gray-500 ml-2" x-text="'(' + (locales['{{ $locale }}'].seo_description?.length || 0) + '/160)'"></span>
                                    </label>
                                    <textarea :name="'locales[{{ $locale }}][seo_description]'"
                                              x-model="locales['{{ $locale }}'].seo_description"
                                              maxlength="160"
                                              rows="2"
                                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all dark:bg-gray-700 dark:text-white resize-none"
                                              placeholder="وصف محركات البحث (160 حرف)"></textarea>
                                </div>

                                {{-- Keywords --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">الكلمات المفتاحية</label>
                                    <input type="text"
                                           :name="'locales[{{ $locale }}][seo_keywords]'"
                                           x-model="locales['{{ $locale }}'].seo_keywords"
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all dark:bg-gray-700 dark:text-white"
                                           placeholder="الكلمات المفتاحية مفصولة بفواصل">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Step 5: Categories & References --}}
        <div x-show="currentStep === 5" x-cloak class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 premium-card">
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                    <span class="gradient-text">الخطوة 5:</span>
                    <span class="text-gray-800 dark:text-gray-200">التصنيفات والمراجع</span>
                </h2>

                <div class="space-y-8">
                    {{-- Additional Categories --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">تصنيفات إضافية</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach($categories ?? [] as $category)
                                <label class="flex items-center gap-2 p-3 border-2 border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:border-[#64CCC5] transition-colors">
                                    <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                                           {{ $article->categories->contains($category->id) ? 'checked' : '' }}
                                           class="w-4 h-4 text-[#0056b3] rounded focus:ring-[#64CCC5]">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $category->getLocale('ar')->title ?? $category->id }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- References --}}
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">المراجع</label>
                            <button type="button" @click="addReference()"
                                    class="px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-all flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                إضافة مرجع
                            </button>
                        </div>

                        <div class="space-y-4">
                            <template x-for="(ref, index) in references" :key="index">
                                <div class="reference-item">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">العنوان</label>
                                            <input type="text"
                                                   :name="'references[' + index + '][title]'"
                                                   x-model="ref.title"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all dark:bg-gray-700 dark:text-white text-sm"
                                                   placeholder="عنوان المرجع">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">المؤلف</label>
                                            <input type="text"
                                                   :name="'references[' + index + '][author]'"
                                                   x-model="ref.author"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all dark:bg-gray-700 dark:text-white text-sm"
                                                   placeholder="اسم المؤلف">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">الرابط</label>
                                            <input type="url"
                                                   :name="'references[' + index + '][url]'"
                                                   x-model="ref.url"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all dark:bg-gray-700 dark:text-white text-sm"
                                                   placeholder="https://example.com">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">سنة النشر</label>
                                            <input type="text"
                                                   :name="'references[' + index + '][year]'"
                                                   x-model="ref.year"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all dark:bg-gray-700 dark:text-white text-sm"
                                                   placeholder="2024">
                                        </div>
                                    </div>

                                    <button type="button" @click="removeReference(index)"
                                            class="absolute top-3 left-3 p-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-900 rounded transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </template>

                            <p x-show="references.length === 0" class="text-center text-gray-500 py-8">لم تتم إضافة أي مراجع بعد</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Navigation Buttons --}}
        <div class="flex justify-between items-center mt-8 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <button type="button" @click="prevStep()" x-show="currentStep > 1"
                    class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-semibold transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                السابق
            </button>

            <div x-show="currentStep === 1"></div>

            <div class="flex gap-3">
                <a href="{{ route('admin.articles.show', $article) }}" target="_blank"
                   class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-[#64CCC5] hover:text-[#64CCC5] rounded-lg font-semibold transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    معاينة
                </a>

                <button type="button" @click="nextStep()" x-show="currentStep < 5"
                        class="px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] hover:shadow-xl text-white rounded-lg font-semibold transition-all flex items-center gap-2">
                    التالي
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>

                <button type="submit" x-show="currentStep === 5"
                        class="px-8 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] hover:shadow-xl text-white rounded-lg font-bold transition-all flex items-center gap-2 text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    حفظ التعديلات
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
function articleWizard() {
    return {
        currentStep: 1,
        activeLanguage: 'ar',
        autoSaveStatus: '',
        autoSaveTimer: null,
        thumbPreviewUrl: null,
        thumbDragOver: false,
        galleryDragOver: false,
        galleryImages: [],
        _initialized: false, // Flag to prevent double initialization

        formData: {
            writer_id: '{{ $article->writer_id ?? "" }}',
            category_id: '{{ $article->category_id ?? "" }}',
            read_time: {{ $article->read_time }},
            hijri_date: '{{ $article->hijri_date ?? "" }}',
            is_featured: {{ $article->is_featured ? 'true' : 'false' }},
            is_pinned: {{ $article->is_pinned ? 'true' : 'false' }}
        },

        locales: {
            @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $locale)
                @php $existingLocale = $article->locales->where('locale', $locale)->first(); @endphp
                '{{ $locale }}': {
                    locale: '{{ $locale }}',
                    title: '{{ $existingLocale->title ?? "" }}',
                    slug: '{{ $existingLocale->slug ?? "" }}',
                    excerpt: '{{ $existingLocale->excerpt ?? "" }}',
                    body: {!! json_encode($existingLocale->getBody() ?? '') !!},
                    summary: '{{ $existingLocale->getSummary() ?? "" }}',
                    is_ready: {{ $existingLocale && $existingLocale->is_ready ? 'true' : 'false' }},
                    seo_title: '{{ $existingLocale->seo_metadata['title'] ?? "" }}',
                    seo_description: '{{ $existingLocale->seo_metadata['description'] ?? "" }}',
                    seo_keywords: '{{ $existingLocale->seo_metadata['keywords'] ?? "" }}'
                }{{ $loop->last ? '' : ',' }}
            @endforeach
        },

        references: {!! json_encode($article->references ?? []) !!},

        init() {
            this.initTinyMCE();
            this.setupAutoSave();
            this.setupKeyboardShortcuts();
        },

        initTinyMCE() {
            const locales = ['ar', 'en', 'fr', 'tr', 'ur'];

            locales.forEach(locale => {
                const isRTL = ['ar', 'ur'].includes(locale);

                tinymce.init({
                    selector: `#editor_${locale}`,
                    height: 500,
                    directionality: isRTL ? 'rtl' : 'ltr',
                    language: locale,
                    menubar: false,
                    plugins: [
                        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                        'insertdatetime', 'media', 'table', 'help', 'wordcount'
                    ],
                    toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | removeformat code fullscreen | help',
                    content_style: 'body { font-family: Cairo, Arial, sans-serif; font-size: 16px; }',
                    setup: (editor) => {
                        editor.on('init', () => {
                            editor.setContent(this.locales[locale].body || '');
                        });
                        editor.on('change keyup', () => {
                            this.locales[locale].body = editor.getContent();
                            this.triggerAutoSave();
                        });
                    }
                });
            });
        },

        nextStep() {
            if (this.currentStep < 5) {
                this.currentStep++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        prevStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        generateSlug(locale) {
            const title = this.locales[locale].title;
            if (!title) return;

            const slug = title
                .toLowerCase()
                .trim()
                .replace(/[^\u0600-\u06FFa-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .substring(0, 100);

            this.locales[locale].slug = slug + '-' + Math.random().toString(36).substring(2, 8);
        },

        calculateReadTime() {
            let totalWords = 0;

            Object.keys(this.locales).forEach(locale => {
                if (this.locales[locale].body) {
                    const text = this.locales[locale].body.replace(/<[^>]*>/g, '');
                    const words = text.trim().split(/\s+/).length;
                    totalWords += words;
                }
            });

            const avgWordsPerMinute = 225;
            this.formData.read_time = Math.max(1, Math.ceil(totalWords / avgWordsPerMinute));
        },

        getWordCount(locale) {
            const body = this.locales[locale].body || '';
            const text = body.replace(/<[^>]*>/g, '');
            const words = text.trim().split(/\s+/).filter(w => w.length > 0).length;
            return `${words} كلمة`;
        },

        // Thumbnail handling
        handleThumbChange(event) {
            const file = event.target.files[0];
            if (file) {
                this.thumbPreviewUrl = URL.createObjectURL(file);
            }
        },

        handleThumbDrop(event) {
            this.thumbDragOver = false;
            const file = event.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                this.$refs.thumbInput.files = event.dataTransfer.files;
                this.thumbPreviewUrl = URL.createObjectURL(file);
            }
        },

        removeThumb() {
            this.thumbPreviewUrl = null;
            this.$refs.thumbInput.value = '';
        },

        // Gallery handling
        handleGalleryChange(event) {
            this.addGalleryFiles(event.target.files);
        },

        handleGalleryDrop(event) {
            this.galleryDragOver = false;
            this.addGalleryFiles(event.dataTransfer.files);
        },

        addGalleryFiles(files) {
            Array.from(files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    this.galleryImages.push({
                        file: file,
                        url: URL.createObjectURL(file)
                    });
                }
            });
        },

        removeGalleryImage(index) {
            this.galleryImages.splice(index, 1);
        },

        // References
        addReference() {
            this.references.push({
                title: '',
                author: '',
                url: '',
                year: ''
            });
        },

        removeReference(index) {
            this.references.splice(index, 1);
        },

        // Auto-save
        setupAutoSave() {
            setInterval(() => {
                this.autoSave();
            }, 30000);
        },

        triggerAutoSave() {
            clearTimeout(this.autoSaveTimer);
            this.autoSaveTimer = setTimeout(() => {
                this.autoSave();
            }, 2000);
        },

        autoSave() {
            this.autoSaveStatus = 'saving';

            const draft = {
                formData: this.formData,
                locales: this.locales,
                references: this.references,
                timestamp: new Date().toISOString()
            };

            localStorage.setItem('article_edit_{{ $article->id }}', JSON.stringify(draft));

            setTimeout(() => {
                this.autoSaveStatus = 'saved';
                setTimeout(() => {
                    this.autoSaveStatus = '';
                }, 2000);
            }, 500);
        },

        // Keyboard shortcuts
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    this.autoSave();
                }

                if ((e.ctrlKey || e.metaKey) && e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.nextStep();
                }

                if ((e.ctrlKey || e.metaKey) && e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.prevStep();
                }
            });
        },

        validateAndSubmit(event) {
            if (!this.locales.ar.title) {
                alert('العنوان العربي مطلوب!');
                this.currentStep = 2;
                this.activeLanguage = 'ar';
                return;
            }

            localStorage.removeItem('article_edit_{{ $article->id }}');
            event.target.submit();
        }
    }
}
</script>
@endpush
