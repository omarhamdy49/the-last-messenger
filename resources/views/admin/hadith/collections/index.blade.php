@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="library" class="w-7 h-7 text-white"></i>
            </div>
            مجموعات الأحاديث
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            إدارة مجموعات الأحاديث النبوية الشريفة
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
        <a href="{{ route('admin.hadith.collections.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>إضافة مجموعة</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="{
    filtersOpen: {{ request()->hasAny(['active', 'featured', 'search']) ? 'true' : 'false' }}
}"
     @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-admin.stats-card
            icon="library"
            title="إجمالي المجموعات"
            :value="$collections->total() ?? 0"
            :gradient="true"
        />

        <x-admin.stats-card
            icon="star"
            title="المجموعات المميزة"
            :value="$collections->where('featured', true)->count() ?? 0"
            delay="delay-100"
        />

        <x-admin.stats-card
            icon="check-circle"
            title="المجموعات النشطة"
            :value="$collections->where('active', true)->count() ?? 0"
            delay="delay-200"
        />

        <x-admin.stats-card
            icon="book-open"
            title="إجمالي الكتب"
            :value="$collections->sum(fn($c) => $c->books_count ?? $c->books->count()) ?? 0"
            delay="delay-300"
        />
    </div>

    <!-- Filter Section -->
    <div x-show="filtersOpen"
         x-collapse
         class="mb-6 p-6 rounded-2xl border"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.hadith.collections.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    بحث
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ابحث باسم المجموعة..."
                       class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <!-- Active Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    الحالة
                </label>
                <select name="active"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>نشط</option>
                    <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>غير نشط</option>
                </select>
            </div>

            <!-- Featured Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    مميز
                </label>
                <select name="featured"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    <option value="1" {{ request('featured') === '1' ? 'selected' : '' }}>مميز</option>
                    <option value="0" {{ request('featured') === '0' ? 'selected' : '' }}>غير مميز</option>
                </select>
            </div>

            <!-- Filter Actions -->
            <div class="flex items-end gap-3">
                <a href="{{ route('admin.hadith.collections.index') }}"
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

    <!-- Collections Table -->
    <div class="rounded-2xl border overflow-hidden"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">#</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الاسم</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الاختصار</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الكتب</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الأحاديث</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الحالة</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الترتيب</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                    @forelse($collections ?? [] as $collection)
                    <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ $collection->id }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <h4 class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ $collection->name_ar ?? ($collection->name['ar'] ?? '') }}
                                </h4>
                                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    {{ $collection->name_en ?? ($collection->name['en'] ?? '') }}
                                </p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                  :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                                {{ $collection->abbreviation ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold"
                                  :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                                <i data-lucide="book-open" class="w-3 h-3"></i>
                                {{ $collection->books_count ?? $collection->books->count() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ number_format($collection->total_hadiths ?? 0) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if($collection->active ?? true)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        <i data-lucide="check" class="w-3 h-3"></i>
                                        نشط
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                        <i data-lucide="x" class="w-3 h-3"></i>
                                        غير نشط
                                    </span>
                                @endif
                                @if($collection->featured ?? false)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                        <i data-lucide="star" class="w-3 h-3"></i>
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $collection->display_order ?? 0 }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.hadith.collections.show', $collection->id) }}"
                                   class="p-2 rounded-lg transition-colors"
                                   :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'"
                                   title="عرض">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="{{ route('admin.hadith.collections.edit', $collection->id) }}"
                                   class="p-2 rounded-lg transition-colors"
                                   :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'"
                                   title="تعديل">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </a>
                                <form action="{{ route('admin.hadith.collections.destroy', $collection->id) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 rounded-lg transition-colors text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20"
                                            title="حذف">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <i data-lucide="library" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                            <p class="text-lg font-medium mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                لا توجد مجموعات أحاديث
                            </p>
                            <a href="{{ route('admin.hadith.collections.create') }}"
                               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                                <i data-lucide="plus" class="w-5 h-5"></i>
                                إضافة مجموعة جديدة
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if(isset($collections) && $collections->hasPages())
    <div class="mt-8">
        {{ $collections->withQueryString()->links() }}
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
