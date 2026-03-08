@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="layers" class="w-7 h-7 text-white"></i>
            </div>
            السلاسل
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            إدارة سلاسل المقالات
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
        <a href="{{ route('admin.series.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>إضافة سلسلة</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="{
    filtersOpen: {{ request()->hasAny(['search']) ? 'true' : 'false' }}
}"
     @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <x-admin.stats-card
            icon="layers"
            title="إجمالي السلاسل"
            :value="$stats['total'] ?? 0"
            :gradient="true"
        />

        <x-admin.stats-card
            icon="file-text"
            title="إجمالي المقالات في السلاسل"
            :value="$stats['total_articles_in_series'] ?? 0"
            delay="delay-100"
        />

        <x-admin.stats-card
            icon="bar-chart-2"
            title="أكبر سلسلة (مقالات)"
            :value="$stats['max_articles'] ?? 0"
            delay="delay-200"
        />
    </div>

    <!-- Filter Section -->
    <div x-show="filtersOpen"
         x-collapse
         class="mb-6 p-6 rounded-2xl border"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.series.index') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    بحث
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ابحث باسم السلسلة..."
                       class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <!-- Filter Actions -->
            <div class="flex items-end gap-3">
                <a href="{{ route('admin.series.index') }}"
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

    <!-- Series Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($series ?? [] as $item)
        <div class="rounded-2xl border overflow-hidden transition-all hover:scale-105 hover:shadow-xl"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <!-- Thumbnail -->
            <div class="relative aspect-video">
                @if($item->thumbnail)
                    <img src="{{ asset('storage/' . $item->thumbnail) }}"
                         alt="{{ $item->getTranslation('title', 'ar') }}"
                         class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                        <i data-lucide="layers" class="w-16 h-16 text-white/30"></i>
                    </div>
                @endif

                <!-- Articles Count Badge -->
                <div class="absolute top-3 start-3">
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-black/60 text-white backdrop-blur-sm">
                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                        {{ $item->total_articles ?? 0 }} مقال
                    </span>
                </div>

                <!-- Actions Overlay -->
                <div class="absolute inset-0 bg-black/50 opacity-0 hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                    <a href="{{ route('admin.series.show', $item->id) }}"
                       class="p-3 rounded-full bg-white/20 hover:bg-white/30 text-white transition-colors"
                       title="{{ __('admin.view') }}">
                        <i data-lucide="eye" class="w-5 h-5"></i>
                    </a>
                    <a href="{{ route('admin.series.edit', $item->id) }}"
                       class="p-3 rounded-full bg-white/20 hover:bg-white/30 text-white transition-colors"
                       title="{{ __('admin.edit') }}">
                        <i data-lucide="edit-2" class="w-5 h-5"></i>
                    </a>
                    <form action="{{ route('admin.series.destroy', $item->id) }}"
                          method="POST"
                          class="inline"
                          onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="p-3 rounded-full bg-white/20 hover:bg-red-500/50 text-white transition-colors"
                                title="{{ __('admin.delete') }}">
                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info -->
            <div class="p-4">
                <h3 class="text-lg font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ $item->getTranslation('title', 'ar') }}
                </h3>
                <p class="text-sm mb-3" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ $item->getTranslation('title', 'en') }}
                </p>

                @if($item->getTranslation('description', 'ar'))
                <p class="text-xs line-clamp-2 mb-3" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                    {{ Str::limit($item->getTranslation('description', 'ar'), 80) }}
                </p>
                @endif

                <div class="flex items-center justify-between text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                    <span>{{ $item->slug }}</span>
                    <span>{{ $item->created_at?->format('Y/m/d') }}</span>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="rounded-2xl border p-12 text-center"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <i data-lucide="layers" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p class="text-lg font-medium mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    لا توجد سلاسل
                </p>
                <a href="{{ route('admin.series.create') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    إضافة سلسلة جديدة
                </a>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if(isset($series) && $series->hasPages())
    <div class="mt-8">
        {{ $series->withQueryString()->links() }}
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
