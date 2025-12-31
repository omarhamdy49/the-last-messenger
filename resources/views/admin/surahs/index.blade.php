@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="book-open" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('quran.surahs.title') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            إدارة سور القرآن الكريم - 114 سورة
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

        <!-- Export Button -->
        <button class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2
                       border-2"
                :class="darkMode ?
                    'border-green-700 bg-green-900/20 text-green-400 hover:bg-green-900/40' :
                    'border-green-300 bg-green-50 text-green-700 hover:bg-green-100'">
            <i data-lucide="download" class="w-5 h-5"></i>
            <span class="hidden sm:inline">تصدير</span>
        </button>
    </div>
</div>
@endsection

@section('content')
<div x-data="{
    filtersOpen: false,
    revelationFilter: '{{ request('revelation_place', '') }}'
}"
     @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-admin.stats-card
            icon="book-open"
            :title="__('quran.stats.total_surahs')"
            :value="$stats['total'] ?? 114"
            :gradient="true"
        />

        <x-admin.stats-card
            icon="sunrise"
            :title="__('quran.stats.makkah_surahs')"
            :value="$stats['meccan'] ?? 86"
            delay="delay-100"
        />

        <x-admin.stats-card
            icon="sunset"
            :title="__('quran.stats.madinah_surahs')"
            :value="$stats['medinan'] ?? 28"
            delay="delay-200"
        />

        <x-admin.stats-card
            icon="text"
            :title="__('quran.stats.total_verses')"
            :value="number_format($stats['total_verses'] ?? 6236)"
            delay="delay-300"
        />
    </div>

    <!-- Filter Section -->
    <div x-show="filtersOpen"
         x-collapse
         class="mb-6 p-6 rounded-2xl border"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.surahs.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    بحث
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ابحث باسم السورة..."
                       class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <!-- Revelation Place Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('quran.surahs.revelation_place') }}
                </label>
                <select name="revelation_place"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    <option value="makkah" {{ request('revelation_place') == 'makkah' ? 'selected' : '' }}>
                        {{ __('quran.surahs.makkah') }}
                    </option>
                    <option value="madinah" {{ request('revelation_place') == 'madinah' ? 'selected' : '' }}>
                        {{ __('quran.surahs.madinah') }}
                    </option>
                </select>
            </div>

            <!-- Sort By -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('admin.sort_by') }}
                </label>
                <select name="sort_by"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="number" {{ request('sort_by') == 'number' ? 'selected' : '' }}>ترتيب المصحف</option>
                    <option value="revelation_order" {{ request('sort_by') == 'revelation_order' ? 'selected' : '' }}>ترتيب النزول</option>
                    <option value="verses_count" {{ request('sort_by') == 'verses_count' ? 'selected' : '' }}>عدد الآيات</option>
                </select>
            </div>

            <!-- Filter Actions -->
            <div class="md:col-span-3 flex items-center gap-3 justify-end">
                <a href="{{ route('admin.surahs.index') }}"
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

    <!-- Surahs Table -->
    <div class="rounded-2xl border overflow-hidden"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            #
                        </th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.surahs.name') }}
                        </th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.surahs.name_transliteration') }}
                        </th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.surahs.revelation_place') }}
                        </th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.surahs.revelation_order') }}
                        </th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.surahs.verses_count') }}
                        </th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-200'">
                    @forelse($surahs as $surah)
                    <tr class="hover:bg-opacity-50 transition-colors"
                        :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-50'">
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold text-sm">
                                {{ $surah->number }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ $surah->name_ar }}
                                </span>
                                <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                    {{ $surah->name_en }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ $surah->transliteration }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($surah->revelation_place === 'makkah')
                                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                    <i data-lucide="sunrise" class="w-3.5 h-3.5"></i>
                                    {{ __('quran.surahs.makkah') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                    <i data-lucide="sunset" class="w-3.5 h-3.5"></i>
                                    {{ __('quran.surahs.madinah') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ $surah->revelation_order }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-semibold"
                                  :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                                {{ $surah->verses_count }} {{ __('quran.verses.singular') }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.surahs.show', $surah->id) }}"
                                   class="p-2 rounded-lg transition-all hover:scale-110"
                                   :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-500 hover:text-gray-700'"
                                   title="{{ __('admin.view') }}">
                                    <i data-lucide="eye" class="w-5 h-5"></i>
                                </a>
                                <a href="{{ route('admin.surahs.edit', $surah->id) }}"
                                   class="p-2 rounded-lg transition-all hover:scale-110"
                                   :class="darkMode ? 'hover:bg-blue-900/30 text-blue-400' : 'hover:bg-blue-100 text-blue-600'"
                                   title="{{ __('admin.edit') }}">
                                    <i data-lucide="edit-2" class="w-5 h-5"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <i data-lucide="book-open" class="w-12 h-12" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                <p class="text-lg font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    {{ __('quran.empty.no_surahs') }}
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($surahs->hasPages())
        <div class="px-6 py-4 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            {{ $surahs->withQueryString()->links() }}
        </div>
        @endif
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
