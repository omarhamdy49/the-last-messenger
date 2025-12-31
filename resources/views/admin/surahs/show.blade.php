@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.surahs.index') }}" class="hover:text-accent transition-colors">{{ __('quran.surahs.title') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ $surah->name_ar }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <span class="text-white font-bold">{{ $surah->number }}</span>
            </div>
            سورة {{ $surah->name_ar }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ $surah->name_en }} - {{ $surah->transliteration }}
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.surahs.edit', $surah->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="edit-2" class="w-5 h-5"></i>
            <span>{{ __('admin.edit') }}</span>
        </a>
        <a href="{{ route('admin.surahs.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="arrow-right" class="w-5 h-5"></i>
            <span>{{ __('admin.back') ?? 'رجوع' }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Main Info Card -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Surah Details -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                معلومات السورة
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Surah Number -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('quran.surahs.number') }}
                    </label>
                    <p class="text-2xl font-bold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $surah->number }}
                    </p>
                </div>

                <!-- Revelation Place -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('quran.surahs.revelation_place') }}
                    </label>
                    <div class="mt-2">
                        @if($surah->revelation_place === 'makkah')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                <i data-lucide="sunrise" class="w-4 h-4"></i>
                                {{ __('quran.surahs.makkah') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                <i data-lucide="sunset" class="w-4 h-4"></i>
                                {{ __('quran.surahs.madinah') }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Revelation Order -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('quran.surahs.revelation_order') }}
                    </label>
                    <p class="text-2xl font-bold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $surah->revelation_order }}
                    </p>
                </div>

                <!-- Verses Count -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('quran.surahs.verses_count') }}
                    </label>
                    <p class="text-2xl font-bold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $surah->verses_count }} {{ __('quran.verses.singular') }}
                    </p>
                </div>

                <!-- Bismillah -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        البسملة
                    </label>
                    <div class="mt-2">
                        @if($surah->bismillah_pre)
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                نعم
                            </span>
                        @else
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                <i data-lucide="x" class="w-4 h-4"></i>
                                لا
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Pages -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('quran.surahs.pages') }}
                    </label>
                    <p class="text-lg font-bold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        @if($surah->pages && is_array($surah->pages))
                            {{ implode(' - ', $surah->pages) }}
                        @else
                            -
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Names Card -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="languages" class="w-5 h-5 text-accent"></i>
                أسماء السورة
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Arabic Name -->
                <div class="p-4 rounded-xl text-center" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('quran.surahs.name_arabic') }}
                    </label>
                    <p class="text-3xl font-bold mt-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $surah->name_ar }}
                    </p>
                </div>

                <!-- English Name -->
                <div class="p-4 rounded-xl text-center" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('quran.surahs.name_translation') }}
                    </label>
                    <p class="text-xl font-bold mt-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $surah->name_en }}
                    </p>
                </div>

                <!-- Transliteration -->
                <div class="p-4 rounded-xl text-center" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('quran.surahs.name_transliteration') }}
                    </label>
                    <p class="text-xl font-bold mt-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $surah->transliteration }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Surah Info (if exists) -->
        @if($surah->info && $surah->info->isNotEmpty())
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="file-text" class="w-5 h-5 text-accent"></i>
                وصف السورة
            </h2>

            <div x-data="{ activeTab: 'ar' }" class="space-y-4">
                <!-- Language Tabs -->
                <div class="flex flex-wrap gap-2 border-b pb-4" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    @foreach($surah->info as $info)
                    <button @click="activeTab = '{{ $info->lang }}'"
                            class="px-4 py-2 rounded-lg font-medium transition-all"
                            :class="activeTab === '{{ $info->lang }}' ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' :
                                (darkMode ? 'bg-gray-800 text-gray-400 hover:text-white' : 'bg-gray-100 text-gray-600 hover:text-gray-900')">
                        {{ strtoupper($info->lang) }}
                    </button>
                    @endforeach
                </div>

                <!-- Content -->
                @foreach($surah->info as $info)
                <div x-show="activeTab === '{{ $info->lang }}'" class="prose max-w-none" :class="darkMode ? 'prose-invert' : ''">
                    @if($info->desc)
                    <div class="p-4 rounded-xl mb-4" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <h3 class="text-lg font-semibold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">الوصف المختصر</h3>
                        <p :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $info->desc }}</p>
                    </div>
                    @endif

                    @if($info->content)
                    <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <h3 class="text-lg font-semibold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">المحتوى الكامل</h3>
                        <div :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{!! $info->content !!}</div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar Stats -->
    <div class="space-y-6">

        <!-- Quick Stats -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="bar-chart-2" class="w-5 h-5 text-accent"></i>
                إحصائيات
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">عدد الآيات</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $stats['verses_count'] ?? $surah->verses_count }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">عدد الكلمات</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ number_format($stats['words_count'] ?? 0) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">عدد الحروف</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ number_format($stats['letters_count'] ?? 0) }}</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="zap" class="w-5 h-5 text-accent"></i>
                إجراءات سريعة
            </h3>

            <div class="space-y-3">
                <a href="{{ route('admin.verses.index', ['surah_id' => $surah->id]) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="text" class="w-5 h-5 text-accent"></i>
                    <span>عرض آيات السورة</span>
                </a>
                <a href="{{ route('admin.tafseer.index', ['surah_id' => $surah->id]) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="message-square-text" class="w-5 h-5 text-accent"></i>
                    <span>عرض التفاسير</span>
                </a>
                <a href="{{ route('admin.surahs.edit', $surah->id) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="edit-2" class="w-5 h-5 text-accent"></i>
                    <span>تعديل معلومات السورة</span>
                </a>
            </div>
        </div>

        <!-- Navigation -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="navigation" class="w-5 h-5 text-accent"></i>
                التنقل
            </h3>

            <div class="flex gap-3">
                @if($surah->number > 1)
                <a href="{{ route('admin.surahs.show', $surah->number - 1) }}"
                   class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl transition-all hover:scale-105 border"
                   :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    <span>السابقة</span>
                </a>
                @endif

                @if($surah->number < 114)
                <a href="{{ route('admin.surahs.show', $surah->number + 1) }}"
                   class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl transition-all hover:scale-105 border"
                   :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                    <span>التالية</span>
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
