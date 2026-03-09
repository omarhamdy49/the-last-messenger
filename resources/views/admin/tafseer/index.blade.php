@extends('layouts.admin')

@php
use Illuminate\Support\Str;
@endphp

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
    <div class="flex flex-wrap items-center gap-3">
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

    <!-- Tafseer Rows -->
    <div class="space-y-4">
        @forelse($tafsirs ?? [] as $tafsir)
            <div class="group rounded-xl overflow-hidden transition-all duration-300 hover:shadow-lg hover:scale-[1.01]"
                 :class="darkMode ? 'bg-gray-800/60 border border-gray-700 hover:bg-gray-800' : 'bg-white border border-gray-200 hover:shadow-xl'"
                 style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">

                <div class="p-5 sm:p-6 flex flex-col sm:flex-row gap-4 sm:gap-6 items-start sm:items-center">
                    <!-- Verse Key Badge -->
                    <div class="flex-shrink-0">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                            <span class="text-sm sm:text-base font-bold text-white">{{ $tafsir->verse_key }}</span>
                        </div>
                    </div>

                    <!-- Main Content: Tafseer Text (Clickable) -->
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('admin.tafseer.show', $tafsir->id) }}"
                           class="block group">
                            <p class="font-serif text-base leading-relaxed line-clamp-2 group-hover:text-[#64CCC5] transition-colors"
                               :class="darkMode ? 'text-gray-100' : 'text-gray-900'"
                               dir="rtl">
                                {{ Str::limit($tafsir->text, 250, '...') }}
                            </p>
                        </a>

                        <!-- Bottom Info: Surah, Language, Status -->
                        <div class="flex flex-wrap gap-2 mt-3 items-center">
                            <!-- Surah Info -->
                            <span class="text-xs sm:text-sm font-medium px-3 py-1 rounded-full"
                                  :class="darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-200 text-gray-700'">
                                📖 {{ $tafsir->verse?->surah?->name_ar ?? '-' }}
                            </span>

                            <!-- Language Badge -->
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs sm:text-sm font-semibold"
                                  :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                                🌐 {{ strtoupper($tafsir->lang) }}
                            </span>

                            <!-- Status Badge -->
                            @if($tafsir->active)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs sm:text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    نشط
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs sm:text-sm font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    غير نشط
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 self-end sm:self-auto">
                        <a href="{{ route('admin.tafseer.show', $tafsir->id) }}"
                           class="p-2.5 rounded-lg transition-all"
                           :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white hover:text-[#64CCC5]' : 'hover:bg-gray-100 text-gray-600 hover:text-[#0056b3]'"
                           title="{{ __('admin.view') }}">
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </a>
                        <a href="{{ route('admin.tafseer.edit', $tafsir->id) }}"
                           class="p-2.5 rounded-lg transition-all"
                           :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white hover:text-[#64CCC5]' : 'hover:bg-gray-100 text-gray-600 hover:text-[#0056b3]'"
                           title="{{ __('admin.edit') }}">
                            <i data-lucide="edit-2" class="w-5 h-5"></i>
                        </a>
                        <form action="{{ route('admin.tafseer.destroy', $tafsir->id) }}"
                              method="POST"
                              class="inline"
                              @submit.prevent="$dispatch('show-confirm', { title: '{{ __("admin.confirm_delete") }}', message: '{{ __("admin.delete_warning") }}', type: 'danger', onConfirm: () => $el.submit() })">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="p-2.5 rounded-lg transition-all text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-700"
                                    title="{{ __('admin.delete') }}">
                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12 px-6 rounded-2xl border"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <i data-lucide="message-square-text" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p class="text-lg font-medium mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('quran.empty.no_tafseer') }}
                </p>
                <a href="{{ route('admin.tafseer.create') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    {{ __('quran.actions.add_tafseer') }}
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if(isset($tafsirs) && $tafsirs->hasPages())
    <div class="mt-8">
        {{ $tafsirs->withQueryString()->links() }}
    </div>
    @endif

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
