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
                <i data-lucide="book-open" class="w-7 h-7 text-white"></i>
            </div>
            القرآن الكريم
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            إدارة آيات القرآن الكريم
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
        <a href="{{ route('admin.quran.create') }}"
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
    filtersOpen: {{ request()->hasAny(['search', 'surah']) ? 'true' : 'false' }}
}"
     @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <x-admin.stats-card
            icon="book-open"
            title="إجمالي الآيات"
            :value="$stats['total'] ?? 0"
            :gradient="true"
        />

        <x-admin.stats-card
            icon="bookmark"
            title="عدد السور"
            :value="$stats['surahs'] ?? 114"
            delay="delay-100"
        />
    </div>

    <!-- Filter Section -->
    <div x-show="filtersOpen"
         x-collapse
         class="mb-6 p-6 rounded-2xl border"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.quran.index') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    بحث
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ابحث بمفتاح الآية (مثال: 2:255)..."
                       class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <!-- Filter Actions -->
            <div class="flex items-end gap-3">
                <a href="{{ route('admin.quran.index') }}"
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

    <!-- Verses Rows -->
    <div class="space-y-4">
        @forelse($verses ?? [] as $verse)
            <div class="group rounded-xl overflow-hidden transition-all duration-300 hover:shadow-lg hover:scale-[1.01]"
                 :class="darkMode ? 'bg-gray-800/60 border border-gray-700 hover:bg-gray-800' : 'bg-white border border-gray-200 hover:shadow-xl'"
                 style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">

                <div class="p-5 sm:p-6 flex flex-col sm:flex-row gap-4 sm:gap-6 items-start sm:items-center">
                    <!-- Verse Key Badge -->
                    <div class="flex-shrink-0">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                            <span class="text-sm sm:text-base font-bold text-white">{{ $verse->verse_key }}</span>
                        </div>
                    </div>

                    <!-- Main Content: Verse Text (Clickable) -->
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('admin.quran.show', $verse->id) }}"
                           class="block group">
                            @php
                                $translation = $verse->translations->where('lang', 'ar')->first();
                                $text = $translation?->text ?? '';
                                $textPreview = Illuminate\Support\Str::limit($text, 250, '...');
                            @endphp
                            <p class="font-serif text-lg leading-relaxed line-clamp-2 group-hover:text-[#64CCC5] transition-colors"
                               :class="darkMode ? 'text-gray-100' : 'text-gray-900'">
                                {{ $textPreview }}
                            </p>
                        </a>

                        <!-- Bottom Info: Surah & Metadata -->
                        <div class="flex flex-wrap gap-4 mt-3 items-center">
                            <span class="text-xs sm:text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                📖 {{ $verse->surah?->name_ar ?? "Surah {$verse->surah_id}" }}
                            </span>
                            <span class="text-xs sm:text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                📄 صفحة {{ $verse->page_number }}
                            </span>
                            <span class="text-xs sm:text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                📕 جزء {{ $verse->juz_number }}
                            </span>
                            <span class="text-xs sm:text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                🔖 حزب {{ $verse->hizb_number }}
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 self-end sm:self-auto">
                        <a href="{{ route('admin.quran.show', $verse->id) }}"
                           class="p-2.5 rounded-lg transition-all"
                           :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white hover:text-[#64CCC5]' : 'hover:bg-gray-100 text-gray-600 hover:text-[#0056b3]'"
                           title="{{ __('admin.view') }}">
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </a>
                        <a href="{{ route('admin.quran.edit', $verse->id) }}"
                           class="p-2.5 rounded-lg transition-all"
                           :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white hover:text-[#64CCC5]' : 'hover:bg-gray-100 text-gray-600 hover:text-[#0056b3]'"
                           title="{{ __('admin.edit') }}">
                            <i data-lucide="edit-2" class="w-5 h-5"></i>
                        </a>
                        <form action="{{ route('admin.quran.destroy', $verse->id) }}"
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
                <i data-lucide="book-open" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p class="text-lg font-medium mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    لا توجد آيات
                </p>
                <a href="{{ route('admin.quran.create') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    إضافة آية جديدة
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if(isset($verses) && $verses->hasPages())
    <div class="mt-8">
        {{ $verses->withQueryString()->links() }}
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
