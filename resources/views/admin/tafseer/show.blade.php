@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.tafseer.index') }}" class="hover:text-accent transition-colors">{{ __('quran.tafseer.title') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ $tafsir->verse_key }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <span class="text-white font-bold">{{ $tafsir->verse_key }}</span>
            </div>
            تفسير الآية {{ $tafsir->verse_key }}
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.tafseer.edit', $tafsir->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="edit-2" class="w-5 h-5"></i>
            <span>{{ __('admin.edit') }}</span>
        </a>
        <a href="{{ route('admin.tafseer.index') }}"
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
        @if($tafsir->verse)
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="text" class="w-5 h-5 text-accent"></i>
                نص الآية
            </h2>

            <div class="p-6 rounded-xl text-center" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                @if($tafsir->verse->translations && $tafsir->verse->translations->where('lang', 'ar')->first())
                <p class="text-2xl leading-loose font-arabic" :class="darkMode ? 'text-white' : 'text-gray-900'" dir="rtl">
                    {{ $tafsir->verse->translations->where('lang', 'ar')->first()->text }}
                </p>
                @endif
            </div>
        </div>
        @endif

        <!-- Tafseer Text -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="message-square-text" class="w-5 h-5 text-accent"></i>
                    {{ __('quran.tafseer.text') }}
                </h2>
                <span class="px-3 py-1.5 rounded-full text-sm font-semibold"
                      :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                    {{ strtoupper($tafsir->lang) }}
                </span>
            </div>

            <div class="p-6 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                <p class="text-lg leading-relaxed whitespace-pre-wrap {{ in_array($tafsir->lang, ['ar', 'ur']) ? 'text-right' : 'text-left' }}"
                   :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                   dir="{{ in_array($tafsir->lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">{{ $tafsir->text }}</p>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">

        <!-- Tafseer Info -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                معلومات التفسير
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">السورة</span>
                    <a href="{{ route('admin.surahs.show', $tafsir->verse?->surah_id) }}"
                       class="font-bold hover:text-accent transition-colors"
                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $tafsir->verse?->surah?->name_ar ?? '-' }}
                    </a>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الآية</span>
                    <a href="{{ route('admin.verses.show', $tafsir->verse_id) }}"
                       class="font-bold hover:text-accent transition-colors"
                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $tafsir->verse_key }}
                    </a>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">اللغة</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ strtoupper($tafsir->lang) }}
                    </span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الحالة</span>
                    @if($tafsir->active)
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            نشط
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            غير نشط
                        </span>
                    @endif
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">عدد الكلمات</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ str_word_count($tafsir->text) }}
                    </span>
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
                <a href="{{ route('admin.tafseer.edit', $tafsir->id) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="edit-2" class="w-5 h-5 text-accent"></i>
                    <span>تعديل التفسير</span>
                </a>
                <a href="{{ route('admin.verses.show', $tafsir->verse_id) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="text" class="w-5 h-5 text-accent"></i>
                    <span>عرض الآية</span>
                </a>
                <a href="{{ route('admin.tafseer.create', ['verse_id' => $tafsir->verse_id]) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="plus" class="w-5 h-5 text-accent"></i>
                    <span>إضافة تفسير آخر</span>
                </a>
            </div>
        </div>

        <!-- Delete -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-red-800/50' : 'bg-white border-red-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-red-500">
                <i data-lucide="trash-2" class="w-5 h-5"></i>
                حذف التفسير
            </h3>

            <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                سيتم حذف هذا التفسير نهائياً ولا يمكن استرجاعه.
            </p>

            <form action="{{ route('admin.tafseer.destroy', $tafsir->id) }}"
                  method="POST"
                  onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 bg-red-500 hover:bg-red-600 text-white flex items-center justify-center gap-2">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    <span>حذف التفسير</span>
                </button>
            </form>
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
