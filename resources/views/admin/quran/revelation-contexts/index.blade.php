@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="scroll-text" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('quran.asbab.title') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ __('quran.asbab.list_subtitle') }}
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <button @click="$dispatch('toggle-filters')"
                x-data
                class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
                :class="darkMode ?
                    'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' :
                    'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="filter" class="w-5 h-5"></i>
            <span class="hidden sm:inline">{{ __('admin.filters') }}</span>
        </button>

        <a href="{{ route('admin.revelation-contexts.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>{{ __('quran.asbab.add') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="{
    filtersOpen: {{ request()->hasAny(['period', 'surah_id', 'search', 'sort']) ? 'true' : 'false' }}
}"
     @toggle-filters.window="filtersOpen = !filtersOpen">

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filter Section -->
    <div x-show="filtersOpen"
         x-collapse
         class="mb-6 p-6 rounded-2xl border"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.revelation-contexts.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('admin.search') }}
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="{{ __('quran.asbab.search_placeholder') }}"
                       class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <!-- Surah Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('quran.asbab.surah') }}
                </label>
                <select name="surah_id"
                        class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('quran.asbab.all_surahs') }}</option>
                    @foreach($surahs ?? [] as $surah)
                        <option value="{{ $surah->id }}" {{ request('surah_id') == $surah->id ? 'selected' : '' }}>
                            {{ $surah->number }}. {{ $surah->name_ar }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Period Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('quran.asbab.period') }}
                </label>
                <select name="period"
                        class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    <option value="makki" {{ request('period') === 'makki' ? 'selected' : '' }}>{{ __('quran.asbab.period_makki') }}</option>
                    <option value="madani" {{ request('period') === 'madani' ? 'selected' : '' }}>{{ __('quran.asbab.period_madani') }}</option>
                </select>
            </div>

            <!-- Filter Actions -->
            <div class="flex flex-wrap items-end gap-3">
                <a href="{{ route('admin.revelation-contexts.index') }}"
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

    <!-- Context Rows -->
    <div class="space-y-4">
        @forelse($contexts ?? [] as $context)
            <div class="group rounded-xl overflow-hidden transition-all duration-300 hover:shadow-lg hover:scale-[1.01]"
                 :class="darkMode ? 'bg-gray-800/60 border border-gray-700 hover:bg-gray-800' : 'bg-white border border-gray-200 hover:shadow-xl'">
                <div class="p-5 sm:p-6 flex flex-col sm:flex-row gap-4 sm:gap-6 items-start sm:items-center">
                    <!-- Surah / Verse Badge -->
                    <div class="flex-shrink-0">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                            <span class="text-xs sm:text-sm font-bold text-white">{{ $context->surahNumber }}:{{ $context->verseRange }}</span>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('admin.revelation-contexts.show', $context->id) }}" class="block group">
                            <h3 class="text-lg font-bold truncate group-hover:text-[#64CCC5] transition-colors"
                                :class="darkMode ? 'text-gray-100' : 'text-gray-900'">
                                {{ $context->title ?: '—' }}
                            </h3>
                            <p class="text-sm leading-relaxed line-clamp-2 mt-1"
                               :class="darkMode ? 'text-gray-300' : 'text-gray-600'"
                               style="word-break: break-word;">
                                {{ \Illuminate\Support\Str::limit((string) $context->narrative, 200, '...') }}
                            </p>
                        </a>

                        <div class="flex flex-wrap gap-2 mt-3 items-center">
                            <span class="text-xs sm:text-sm font-medium px-3 py-1 rounded-full"
                                  :class="darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-200 text-gray-700'">
                                {{ $context->surahNameAr }} • {{ __('quran.asbab.verses_label') }} {{ $context->verseRange }}
                            </span>

                            @if($context->period === 'makki')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs sm:text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    {{ __('quran.asbab.period_makki') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs sm:text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    {{ __('quran.asbab.period_madani') }}
                                </span>
                            @endif

                            @if($context->isActive)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs sm:text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    {{ __('quran.asbab.active') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs sm:text-sm font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    {{ __('quran.asbab.inactive') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 self-end sm:self-auto">
                        <a href="{{ route('admin.revelation-contexts.show', $context->id) }}"
                           class="p-2.5 rounded-lg transition-all"
                           :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-[#64CCC5]' : 'hover:bg-gray-100 text-gray-600 hover:text-[#0056b3]'"
                           title="{{ __('admin.view') }}">
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </a>
                        <a href="{{ route('admin.revelation-contexts.edit', $context->id) }}"
                           class="p-2.5 rounded-lg transition-all"
                           :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-[#64CCC5]' : 'hover:bg-gray-100 text-gray-600 hover:text-[#0056b3]'"
                           title="{{ __('admin.edit') }}">
                            <i data-lucide="edit-2" class="w-5 h-5"></i>
                        </a>
                        <form action="{{ route('admin.revelation-contexts.destroy', $context->id) }}"
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
                <i data-lucide="scroll-text" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p class="text-lg font-medium mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('quran.asbab.empty') }}
                </p>
                <a href="{{ route('admin.revelation-contexts.create') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    {{ __('quran.asbab.add') }}
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if(isset($contexts) && $contexts->hasPages())
    <div class="mt-8">
        {{ $contexts->withQueryString()->links() }}
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
