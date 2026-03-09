@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.quran.index') }}" class="hover:text-accent transition-colors">القرآن الكريم</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ $verse->verse_key }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="book-open" class="w-7 h-7 text-white"></i>
            </div>
            الآية: {{ $verse->verse_key }}
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.quran.edit', $verse->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="edit-2" class="w-5 h-5"></i>
            <span>{{ __('admin.edit') }}</span>
        </a>
        <a href="{{ route('admin.quran.index') }}"
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

        <!-- Verse Display Card -->
        <div class="rounded-2xl border overflow-hidden"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="bg-gradient-to-br from-[#0056b3] to-[#218838] p-8 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/20 text-white text-sm font-semibold mb-6">
                    <i data-lucide="bookmark" class="w-4 h-4"></i>
                    {{ $verse->verse_key }}
                </div>

                @if($verse->getTranslationText('ar'))
                <p class="text-3xl leading-loose text-white font-bold" dir="rtl" style="font-family: 'Amiri', 'Cairo', serif;">
                    {{ $verse->getTranslationText('ar') }}
                </p>
                @endif
            </div>

            <!-- Translations -->
            <div class="p-6" x-data="{ activeTab: 'ar' }">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="languages" class="w-5 h-5 text-accent"></i>
                    الترجمات
                </h2>

                <!-- Language Tabs -->
                <div class="flex flex-wrap gap-2 border-b pb-4 mb-4" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                    <button @click="activeTab = '{{ $lang }}'"
                            class="px-4 py-2 rounded-lg font-medium transition-all"
                            :class="activeTab === '{{ $lang }}' ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' :
                                (darkMode ? 'bg-gray-800 text-gray-400 hover:text-white' : 'bg-gray-100 text-gray-600 hover:text-gray-900')">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>

                @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                <div x-show="activeTab === '{{ $lang }}'"
                     class="p-4 rounded-xl"
                     :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    @if($verse->getTranslationText($lang))
                    <p class="leading-relaxed text-lg {{ in_array($lang, ['ar', 'ur']) ? 'text-right' : 'text-left' }}"
                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                       dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                        {{ $verse->getTranslationText($lang) }}
                    </p>
                    @else
                    <p class="text-center py-4" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                        لا توجد ترجمة متاحة لهذه اللغة
                    </p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Tafseer -->
        @if($verse->getTafsir('ar'))
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="file-text" class="w-5 h-5 text-accent"></i>
                التفسير
            </h2>

            <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                <p class="leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" dir="rtl">
                    {{ $verse->getTafsir('ar')->text ?? '' }}
                </p>
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
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">مفتاح الآية</span>
                    <span class="font-bold text-accent">{{ $verse->verse_key }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">رقم السورة</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $verse->surah_id }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">رقم الآية</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $verse->verse_number }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الجزء</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $verse->juz_number }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الحزب</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $verse->hizb_number }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">ربع الحزب</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $verse->rub_el_hizb_number }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الركوع</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $verse->ruku_number }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الصفحة</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $verse->page_number }}</span>
                </div>
                @if($verse->sajdah_number)
                <div class="flex items-center justify-between p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20">
                    <span class="text-amber-700 dark:text-amber-400">سجدة</span>
                    <span class="font-bold text-amber-600 dark:text-amber-400">{{ $verse->sajdah_number }}</span>
                </div>
                @endif
                @if($verse->manzil_number)
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">المنزل</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $verse->manzil_number }}</span>
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
                <a href="{{ route('admin.quran.edit', $verse->id) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="edit-2" class="w-5 h-5 text-accent"></i>
                    <span>تعديل الآية</span>
                </a>

                <a href="{{ route('admin.quran.create') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="plus" class="w-5 h-5 text-accent"></i>
                    <span>إضافة آية جديدة</span>
                </a>
            </div>
        </div>

        <!-- Delete -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-red-800/50' : 'bg-white border-red-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-red-500">
                <i data-lucide="trash-2" class="w-5 h-5"></i>
                حذف الآية
            </h3>

            <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                سيتم حذف هذه الآية نهائياً ولا يمكن استرجاعها.
            </p>

            <form action="{{ route('admin.quran.destroy', $verse->id) }}"
                  method="POST"
                  onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 bg-red-500 hover:bg-red-600 text-white flex items-center justify-center gap-2">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    <span>حذف الآية</span>
                </button>
            </form>
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
