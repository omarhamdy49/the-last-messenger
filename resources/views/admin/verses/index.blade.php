@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="text" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('quran.verses.title') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            إدارة آيات القرآن الكريم - 6,236 آية
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
        <a href="{{ route('admin.verses.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>إضافة آية</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="{
    filtersOpen: {{ request()->hasAny(['surah_id', 'juz_number', 'page_number', 'is_sajda']) ? 'true' : 'false' }}
}"
     @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-admin.stats-card
            icon="text"
            :title="__('quran.stats.total_verses')"
            :value="number_format($stats['total'] ?? 6236)"
            :gradient="true"
        />

        <x-admin.stats-card
            icon="bookmark"
            :title="'آيات السجدة'"
            :value="$stats['sajda'] ?? 15"
            delay="delay-100"
        />

        <x-admin.stats-card
            icon="layers"
            :title="__('quran.stats.total_juz')"
            :value="$stats['juz'] ?? 30"
            delay="delay-200"
        />

        <x-admin.stats-card
            icon="book-open"
            :title="__('quran.stats.total_surahs')"
            :value="114"
            delay="delay-300"
        />
    </div>

    <!-- Filter Section -->
    <div x-show="filtersOpen"
         x-collapse
         class="mb-6 p-6 rounded-2xl border"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.verses.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    بحث
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ابحث في نص الآية..."
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

            <!-- Juz Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('quran.verses.juz') }}
                </label>
                <select name="juz_number"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    @for($i = 1; $i <= 30; $i++)
                        <option value="{{ $i }}" {{ request('juz_number') == $i ? 'selected' : '' }}>
                            الجزء {{ $i }}
                        </option>
                    @endfor
                </select>
            </div>

            <!-- Page Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('quran.verses.page') }}
                </label>
                <input type="number"
                       name="page_number"
                       value="{{ request('page_number') }}"
                       min="1"
                       max="604"
                       placeholder="رقم الصفحة"
                       class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <!-- Sajda Filter -->
            <div class="flex items-end">
                <label class="flex items-center gap-2 cursor-pointer px-4 py-2.5 rounded-xl border transition-all"
                       :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700' : 'border-gray-300 bg-white hover:bg-gray-50'">
                    <input type="checkbox"
                           name="is_sajda"
                           value="1"
                           {{ request('is_sajda') ? 'checked' : '' }}
                           class="w-5 h-5 rounded text-accent border-gray-300 focus:ring-accent">
                    <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('quran.filters.with_sajda') }}</span>
                </label>
            </div>

            <!-- Filter Actions -->
            <div class="md:col-span-3 flex items-center gap-3 justify-end">
                <a href="{{ route('admin.verses.index') }}"
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

    <!-- Verses Table -->
    <div class="rounded-2xl border overflow-hidden"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.verses.number') }}
                        </th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            السورة
                        </th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" style="max-width: 400px;">
                            {{ __('quran.verses.text') }}
                        </th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.verses.juz') }}
                        </th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.verses.page') }}
                        </th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.verses.sajda') }}
                        </th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-200'">
                    @forelse($verses as $verse)
                    <tr class="hover:bg-opacity-50 transition-colors"
                        :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-50'">
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold text-sm">
                                {{ $verse->verse_key }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ $verse->surah->name_ar ?? '-' }}
                                </span>
                                <span class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                    رقم {{ $verse->verse_number }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4" style="max-width: 400px;">
                            <p class="text-lg leading-relaxed line-clamp-2 font-arabic" :class="darkMode ? 'text-white' : 'text-gray-900'" dir="rtl">
                                @if($verse->translations && $verse->translations->where('lang', 'ar')->first())
                                    {{ Str::limit($verse->translations->where('lang', 'ar')->first()->text, 150) }}
                                @else
                                    <span class="text-gray-400">لا يوجد نص</span>
                                @endif
                            </p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-semibold"
                                  :class="darkMode ? 'bg-purple-900/30 text-purple-400' : 'bg-purple-100 text-purple-800'">
                                {{ $verse->juz_number }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ $verse->page_number }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($verse->sajdah_number)
                                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                    <i data-lucide="bookmark" class="w-3.5 h-3.5"></i>
                                    سجدة
                                </span>
                            @else
                                <span :class="darkMode ? 'text-gray-600' : 'text-gray-400'">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.verses.show', $verse->id) }}"
                                   class="p-2 rounded-lg transition-all hover:scale-110"
                                   :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-500 hover:text-gray-700'"
                                   title="{{ __('admin.view') }}">
                                    <i data-lucide="eye" class="w-5 h-5"></i>
                                </a>
                                <a href="{{ route('admin.verses.edit', $verse->id) }}"
                                   class="p-2 rounded-lg transition-all hover:scale-110"
                                   :class="darkMode ? 'hover:bg-blue-900/30 text-blue-400' : 'hover:bg-blue-100 text-blue-600'"
                                   title="{{ __('admin.edit') }}">
                                    <i data-lucide="edit-2" class="w-5 h-5"></i>
                                </a>
                                <form action="{{ route('admin.verses.destroy', $verse->id) }}"
                                      method="POST"
                                      class="inline"
                                      @submit.prevent="$dispatch('show-confirm', { title: '{{ __("admin.confirm_delete") }}', message: '{{ __("admin.delete_warning") }}', type: 'danger', onConfirm: () => $el.submit() })">
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
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <i data-lucide="text" class="w-12 h-12" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                <p class="text-lg font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    {{ __('quran.empty.no_verses') }}
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($verses->hasPages())
        <div class="px-6 py-4 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            {{ $verses->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

@push('styles')
<style>
    .font-arabic {
        font-family: 'Amiri', 'Cairo', serif;
    }
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
