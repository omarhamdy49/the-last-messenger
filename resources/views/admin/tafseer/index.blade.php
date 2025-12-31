@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="message-square-text" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('quran.tafseer.title') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            إدارة تفاسير القرآن الكريم
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
        <!-- Filter Toggle Button -->
        <button @click="$dispatch('toggle-filters')"
                x-data
                class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2
                       border-2"
                :class="darkMode ?
                    'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' :
                    'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="filter" class="w-5 h-5"></i>
            <span class="hidden sm:inline">{{ __('admin.filters') }}</span>
        </button>

        <!-- Create New Button -->
        <a href="{{ route('admin.tafseer.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>{{ __('quran.actions.add_tafseer') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="{
    filtersOpen: {{ request()->hasAny(['surah_id', 'source', 'lang']) ? 'true' : 'false' }}
}"
     @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-admin.stats-card
            icon="message-square-text"
            title="إجمالي التفاسير"
            :value="number_format($stats['total'] ?? 0)"
            :gradient="true"
        />

        <x-admin.stats-card
            icon="book"
            title="المصادر"
            :value="$stats['sources'] ?? 0"
            delay="delay-100"
        />

        <x-admin.stats-card
            icon="book-open"
            title="السور المغطاة"
            :value="$stats['surahs'] ?? 0"
            delay="delay-200"
        />

        <x-admin.stats-card
            icon="languages"
            title="اللغات"
            :value="5"
            delay="delay-300"
        />
    </div>

    <!-- Filter Section -->
    <div x-show="filtersOpen"
         x-collapse
         class="mb-6 p-6 rounded-2xl border"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.tafseer.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    بحث
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ابحث في نص التفسير..."
                       class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <!-- Surah Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    السورة
                </label>
                <select name="surah_id"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('quran.filters.all_surahs') }}</option>
                    @foreach($surahs ?? [] as $surah)
                        <option value="{{ $surah->id }}" {{ request('surah_id') == $surah->id ? 'selected' : '' }}>
                            {{ $surah->number }}. {{ $surah->name_ar }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Language Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    اللغة
                </label>
                <select name="lang"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    <option value="ar" {{ request('lang') == 'ar' ? 'selected' : '' }}>العربية</option>
                    <option value="en" {{ request('lang') == 'en' ? 'selected' : '' }}>English</option>
                    <option value="fr" {{ request('lang') == 'fr' ? 'selected' : '' }}>Français</option>
                    <option value="tr" {{ request('lang') == 'tr' ? 'selected' : '' }}>Türkçe</option>
                    <option value="ur" {{ request('lang') == 'ur' ? 'selected' : '' }}>اردو</option>
                </select>
            </div>

            <!-- Filter Actions -->
            <div class="flex items-end gap-3">
                <a href="{{ route('admin.tafseer.index') }}"
                   class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 border-2"
                   :class="darkMode ? 'border-gray-700 text-gray-400 hover:bg-gray-800' : 'border-gray-300 text-gray-600 hover:bg-gray-100'">
                    {{ __('admin.reset_filters') }}
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg">
                    {{ __('admin.filter') }}
                </button>
            </div>
        </form>
    </div>

    <!-- Tafseer Table -->
    <div class="rounded-2xl border overflow-hidden"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الآية
                        </th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            السورة
                        </th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" style="max-width: 400px;">
                            {{ __('quran.tafseer.text') }}
                        </th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.tafseer.language') }}
                        </th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الحالة
                        </th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-200'">
                    @forelse($tafsirs ?? [] as $tafsir)
                    <tr class="hover:bg-opacity-50 transition-colors"
                        :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-50'">
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold text-sm">
                                {{ $tafsir->verse_key }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $tafsir->verse?->surah?->name_ar ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4" style="max-width: 400px;">
                            <p class="leading-relaxed line-clamp-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" dir="rtl">
                                {{ Str::limit($tafsir->text, 150) }}
                            </p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold"
                                  :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                                {{ strtoupper($tafsir->lang) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
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
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.tafseer.show', $tafsir->id) }}"
                                   class="p-2 rounded-lg transition-all hover:scale-110"
                                   :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-500 hover:text-gray-700'"
                                   title="{{ __('admin.view') }}">
                                    <i data-lucide="eye" class="w-5 h-5"></i>
                                </a>
                                <a href="{{ route('admin.tafseer.edit', $tafsir->id) }}"
                                   class="p-2 rounded-lg transition-all hover:scale-110"
                                   :class="darkMode ? 'hover:bg-blue-900/30 text-blue-400' : 'hover:bg-blue-100 text-blue-600'"
                                   title="{{ __('admin.edit') }}">
                                    <i data-lucide="edit-2" class="w-5 h-5"></i>
                                </a>
                                <form action="{{ route('admin.tafseer.destroy', $tafsir->id) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 rounded-lg transition-all hover:scale-110"
                                            :class="darkMode ? 'hover:bg-red-900/30 text-red-400' : 'hover:bg-red-100 text-red-600'"
                                            title="{{ __('admin.delete') }}">
                                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <i data-lucide="message-square-text" class="w-12 h-12" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                <p class="text-lg font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    {{ __('quran.empty.no_tafseer') }}
                                </p>
                                <a href="{{ route('admin.tafseer.create') }}"
                                   class="mt-2 px-4 py-2 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                                    {{ __('quran.actions.add_tafseer') }}
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if(isset($tafsirs) && $tafsirs->hasPages())
        <div class="px-6 py-4 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            {{ $tafsirs->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

@push('styles')
<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
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
