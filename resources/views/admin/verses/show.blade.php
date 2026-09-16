@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.verses.index') }}" class="hover:text-accent transition-colors">{{ __('quran.verses.title') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ $verse->verse_key }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <span class="text-white font-bold">{{ $verse->verse_key }}</span>
            </div>
            سورة {{ $verse->surah->name_ar ?? '-' }} - {{ __('quran.verses.singular') }} {{ $verse->verse_number }}
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.verses.edit', $verse->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="edit-2" class="w-5 h-5"></i>
            <span>{{ __('admin.edit') }}</span>
        </a>
        <a href="{{ route('admin.verses.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="arrow-right" class="w-5 h-5"></i>
            <span>رجوع</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Verse Text -->
        <div class="rounded-2xl border p-8"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="text" class="w-5 h-5 text-accent"></i>
                {{ __('quran.verses.text') }}
            </h2>

            <div class="p-6 rounded-xl text-center" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                @if($verse->translations && $verse->translations->where('lang', 'ar')->first())
                <p class="text-3xl leading-loose font-arabic" :class="darkMode ? 'text-white' : 'text-gray-900'" dir="rtl">
                    {{ $verse->translations->where('lang', 'ar')->first()->text }}
                </p>
                @else
                <p class="text-gray-400">لا يوجد نص للآية</p>
                @endif
            </div>
        </div>

        <!-- Translations -->
        @if($verse->translations && $verse->translations->count() > 0)
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="languages" class="w-5 h-5 text-accent"></i>
                الترجمات
            </h2>

            <div x-data="{ activeTab: 'ar' }" class="space-y-4">
                <!-- Language Tabs -->
                <div class="flex flex-wrap gap-2 border-b pb-4" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    @foreach($verse->translations->whereIn('lang', enabled_content_locales()) as $translation)
                    <button @click="activeTab = '{{ $translation->lang }}'"
                            class="px-4 py-2 rounded-lg font-medium transition-all"
                            :class="activeTab === '{{ $translation->lang }}' ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' :
                                (darkMode ? 'bg-gray-800 text-gray-400 hover:text-white' : 'bg-gray-100 text-gray-600 hover:text-gray-900')">
                        {{ strtoupper($translation->lang) }}
                    </button>
                    @endforeach
                </div>

                <!-- Content -->
                @foreach($verse->translations->whereIn('lang', enabled_content_locales()) as $translation)
                <div x-show="activeTab === '{{ $translation->lang }}'"
                     class="p-6 rounded-xl"
                     :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <p class="text-lg leading-relaxed {{ in_array($translation->lang, ['ar', 'ur']) ? 'text-right' : 'text-left' }}"
                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                       dir="{{ in_array($translation->lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                        {{ $translation->text }}
                    </p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Tafseer -->
        @if($verse->tafsirs && $verse->tafsirs->count() > 0)
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="message-square-text" class="w-5 h-5 text-accent"></i>
                التفسير
            </h2>

            <div class="space-y-4">
                @foreach($verse->tafsirs as $tafsir)
                <div class="p-6 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium px-3 py-1 rounded-full"
                              :class="darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-200 text-gray-700'">
                            {{ strtoupper($tafsir->lang) }}
                        </span>
                    </div>
                    <p class="leading-relaxed {{ in_array($tafsir->lang, ['ar', 'ur']) ? 'text-right' : 'text-left' }}"
                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                       dir="{{ in_array($tafsir->lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                        {{ Str::limit($tafsir->text, 500) }}
                    </p>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">

        <!-- Verse Info -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                معلومات الآية
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">السورة</span>
                    <a href="{{ route('admin.surahs.show', $verse->surah_id) }}"
                       class="font-bold hover:text-accent transition-colors"
                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $verse->surah->name_ar ?? '-' }}
                    </a>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">رقم الآية</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $verse->verse_number }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.verses.juz') }}</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $verse->juz_number }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.verses.hizb') }}</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $verse->hizb_number }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.verses.page') }}</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $verse->page_number }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.verses.ruku') }}</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $verse->ruku_number ?? '-' }}</span>
                </div>
                @if($verse->sajdah_number)
                <div class="flex items-center justify-between p-3 rounded-xl bg-amber-100 dark:bg-amber-900/30">
                    <span class="text-amber-800 dark:text-amber-400">{{ __('quran.verses.sajda') }}</span>
                    <span class="font-bold text-amber-800 dark:text-amber-400">
                        <i data-lucide="bookmark" class="w-5 h-5 inline"></i>
                        {{ $verse->sajdah_number }}
                    </span>
                </div>
                @endif
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
                <a href="{{ route('admin.tafseer.create', ['verse_id' => $verse->id]) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="message-square-plus" class="w-5 h-5 text-accent"></i>
                    <span>إضافة تفسير</span>
                </a>
                <a href="{{ route('admin.verses.edit', $verse->id) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="edit-2" class="w-5 h-5 text-accent"></i>
                    <span>تعديل الآية</span>
                </a>
                <a href="{{ route('admin.surahs.show', $verse->surah_id) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="book-open" class="w-5 h-5 text-accent"></i>
                    <span>عرض السورة</span>
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
                @if($verse->verse_number > 1 || $verse->surah_id > 1)
                <a href="{{ route('admin.verses.show', $verse->id - 1) }}"
                   class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl transition-all hover:scale-105 border"
                   :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    <span>السابقة</span>
                </a>
                @endif

                <a href="{{ route('admin.verses.show', $verse->id + 1) }}"
                   class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl transition-all hover:scale-105 border"
                   :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                    <span>التالية</span>
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
            </div>
        </div>

    </div>
</div>
@endsection

@push('styles')
<style>
    .font-arabic {
        font-family: 'Amiri', 'Cairo', serif;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
