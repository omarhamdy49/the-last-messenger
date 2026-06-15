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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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

    <!-- Surahs Rows -->
    <div class="space-y-4">
        @forelse($surahs as $surah)
            <div class="group rounded-xl overflow-hidden transition-all duration-300 hover:shadow-lg hover:scale-[1.01]"
                 :class="darkMode ? 'bg-gray-800/60 border border-gray-700 hover:bg-gray-800' : 'bg-white border border-gray-200 hover:shadow-xl'"
                 style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">

                <div class="p-5 sm:p-6 flex flex-col sm:flex-row gap-4 sm:gap-6 items-start sm:items-center">
                    <!-- Surah Number Badge -->
                    <div class="flex-shrink-0">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                            <span class="text-base sm:text-lg font-bold text-white">{{ $surah->number }}</span>
                        </div>
                    </div>

                    <!-- Main Content: Surah Name -->
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('admin.surahs.show', $surah->id) }}"
                           class="block group">
                            <h3 class="text-lg sm:text-xl font-bold group-hover:text-[#64CCC5] transition-colors"
                                :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $surah->name_ar }}
                            </h3>
                            <p class="text-sm sm:text-base mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $surah->name_en }}
                            </p>
                        </a>

                        <!-- Bottom Info: Revelation & Metadata -->
                        <div class="flex flex-wrap gap-3 mt-3 items-center">
                            <!-- Revelation Type -->
                            @if($surah->revelation_place === 'Mecca')
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs sm:text-sm font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                    <i data-lucide="sunrise" class="w-3.5 h-3.5"></i>
                                    {{ __('quran.surahs.makkah') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs sm:text-sm font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                    <i data-lucide="sunset" class="w-3.5 h-3.5"></i>
                                    {{ __('quran.surahs.madinah') }}
                                </span>
                            @endif

                            <!-- Revelation Order -->
                            <span class="text-xs sm:text-sm font-medium px-3 py-1 rounded-full"
                                  :class="darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-200 text-gray-700'">
                                ترتيب النزول: {{ $surah->revelation_order }}
                            </span>

                            <!-- Verses Count -->
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs sm:text-sm font-semibold"
                                  :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                                📖 {{ $surah->verses_count }} {{ __('quran.verses.singular') }}
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 self-end sm:self-auto">
                        <a href="{{ route('admin.surahs.show', $surah->id) }}"
                           class="p-2.5 rounded-lg transition-all"
                           :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white hover:text-[#64CCC5]' : 'hover:bg-gray-100 text-gray-600 hover:text-[#0056b3]'"
                           title="{{ __('admin.view') }}">
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </a>
                        <a href="{{ route('admin.surahs.edit', $surah->id) }}"
                           class="p-2.5 rounded-lg transition-all"
                           :class="darkMode ? 'hover:bg-blue-900/30 text-blue-400' : 'hover:bg-blue-100 text-blue-600'"
                           title="{{ __('admin.edit') }}">
                            <i data-lucide="edit-2" class="w-5 h-5"></i>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12 px-6 rounded-2xl border"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <i data-lucide="book-open" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p class="text-lg font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('quran.empty.no_surahs') }}
                </p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($surahs->hasPages())
    <div class="mt-8">
        {{ $surahs->withQueryString()->links() }}
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
