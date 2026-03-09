@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="mic" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('quran.reciters.title') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            إدارة قراء القرآن الكريم
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
        <a href="{{ route('admin.reciters.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>إضافة قارئ</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="{
    filtersOpen: {{ request()->hasAny(['country', 'style', 'search']) ? 'true' : 'false' }}
}"
     @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-admin.stats-card
            icon="mic"
            :title="__('quran.stats.total_reciters')"
            :value="$stats['total'] ?? 0"
            :gradient="true"
        />

        <x-admin.stats-card
            icon="star"
            title="القراء المميزون"
            :value="$stats['featured'] ?? 0"
            delay="delay-100"
        />

        <x-admin.stats-card
            icon="globe"
            title="الدول"
            :value="$stats['countries'] ?? 0"
            delay="delay-200"
        />

        <x-admin.stats-card
            icon="music"
            title="أساليب القراءة"
            :value="count($styles ?? [])"
            delay="delay-300"
        />
    </div>

    <!-- Filter Section -->
    <div x-show="filtersOpen"
         x-collapse
         class="mb-6 p-6 rounded-2xl border"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.reciters.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    بحث
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ابحث باسم القارئ..."
                       class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <!-- Country Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('quran.reciters.country') }}
                </label>
                <select name="country"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach($countries ?? [] as $country)
                        <option value="{{ $country }}" {{ request('country') == $country ? 'selected' : '' }}>
                            {{ $country }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Style Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('quran.reciters.style') }}
                </label>
                <select name="style"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach($styles ?? [] as $style)
                        <option value="{{ $style }}" {{ request('style') == $style ? 'selected' : '' }}>
                            {{ $style }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Actions -->
            <div class="flex items-end gap-3">
                <a href="{{ route('admin.reciters.index') }}"
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

    <!-- Reciters Rows -->
    <div class="space-y-4">
        @forelse($reciters ?? [] as $reciter)
            <div class="group rounded-xl overflow-hidden transition-all duration-300 hover:shadow-lg hover:scale-[1.01]"
                 :class="darkMode ? 'bg-gray-800/60 border border-gray-700 hover:bg-gray-800' : 'bg-white border border-gray-200 hover:shadow-xl'"
                 style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">

                <div class="p-5 sm:p-6 flex flex-col sm:flex-row gap-4 sm:gap-6 items-start sm:items-center">
                    <!-- Reciter Image/Avatar -->
                    <div class="flex-shrink-0">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full overflow-hidden border-2"
                             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                            @if($reciter->profile_image)
                                <img src="{{ asset('storage/' . $reciter->profile_image) }}"
                                     alt="{{ $reciter->getTranslation('name', 'ar') }}"
                                     class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                                    <i data-lucide="mic" class="w-8 h-8 text-white/50"></i>
                                </div>
                            @endif
                        </div>

                        <!-- Status Badge -->
                        @if($reciter->is_active)
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-500 text-white border-2 border-white"
                                  :class="darkMode ? 'dark:border-gray-800' : ''"
                                  style="margin-top: -10px; margin-start: 35px; position: relative;">
                                <i data-lucide="check" class="w-3 h-3"></i>
                            </span>
                        @endif
                    </div>

                    <!-- Main Content: Reciter Info -->
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('admin.reciters.show', $reciter->id) }}"
                           class="block group">
                            <h3 class="text-lg sm:text-xl font-bold group-hover:text-[#64CCC5] transition-colors"
                                :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $reciter->getTranslation('name', 'ar') }}
                            </h3>
                            <p class="text-sm sm:text-base mt-0.5" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $reciter->getTranslation('name', 'en') }}
                            </p>
                        </a>

                        <!-- Bottom Info: Country, Style, Featured -->
                        <div class="flex flex-wrap gap-2 mt-3 items-center">
                            @if($reciter->country)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs sm:text-sm font-medium"
                                      :class="darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-200 text-gray-700'">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                    {{ $reciter->country }}
                                </span>
                            @endif

                            @if($reciter->style)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs sm:text-sm font-medium"
                                      :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                                    <i data-lucide="music" class="w-3.5 h-3.5"></i>
                                    {{ $reciter->style }}
                                </span>
                            @endif

                            @if($reciter->is_featured)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs sm:text-sm font-semibold bg-amber-500 text-white">
                                    <i data-lucide="star" class="w-3.5 h-3.5"></i>
                                    مميز
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 self-end sm:self-auto">
                        <a href="{{ route('admin.reciters.show', $reciter->id) }}"
                           class="p-2.5 rounded-lg transition-all"
                           :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white hover:text-[#64CCC5]' : 'hover:bg-gray-100 text-gray-600 hover:text-[#0056b3]'"
                           title="{{ __('admin.view') }}">
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </a>
                        <a href="{{ route('admin.reciters.edit', $reciter->id) }}"
                           class="p-2.5 rounded-lg transition-all"
                           :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white hover:text-[#64CCC5]' : 'hover:bg-gray-100 text-gray-600 hover:text-[#0056b3]'"
                           title="{{ __('admin.edit') }}">
                            <i data-lucide="edit-2" class="w-5 h-5"></i>
                        </a>
                        <form action="{{ route('admin.reciters.destroy', $reciter->id) }}"
                              method="POST"
                              class="inline"
                              onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
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
                <i data-lucide="mic" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p class="text-lg font-medium mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('quran.empty.no_reciters') }}
                </p>
                <a href="{{ route('admin.reciters.create') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    إضافة قارئ جديد
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if(isset($reciters) && $reciters->hasPages())
    <div class="mt-8">
        {{ $reciters->withQueryString()->links() }}
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
