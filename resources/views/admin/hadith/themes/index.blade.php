@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="tag" class="w-7 h-7 text-white"></i>
            </div>
            موضوعات الحديث
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            إدارة موضوعات وتصنيفات الأحاديث النبوية
        </p>
    </div>
    <div class="flex items-center gap-3">
        <button @click="$dispatch('toggle-filters')" x-data
                class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
                :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="filter" class="w-5 h-5"></i>
            <span class="hidden sm:inline">{{ __('admin.filters') }}</span>
        </button>
        <a href="{{ route('admin.hadith.themes.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i> <span>إضافة موضوع</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="{ filtersOpen: {{ request()->hasAny(['search']) ? 'true' : 'false' }} }" @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <x-admin.stats-card icon="tag" title="إجمالي الموضوعات" :value="$themes->total() ?? 0" :gradient="true" />
        <x-admin.stats-card icon="file-text" title="إجمالي المقالات المرتبطة" :value="$themes->sum('articles_count') ?? 0" delay="delay-100" />
        <x-admin.stats-card icon="palette" title="بألوان مخصصة" :value="$themes->filter(fn($t) => $t->color && $t->color !== '#0056b3')->count() ?? 0" delay="delay-200" />
    </div>

    <!-- Filter -->
    <div x-show="filtersOpen" x-collapse class="mb-6 p-6 rounded-2xl border" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.hadith.themes.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">بحث</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث باسم الموضوع..."
                       class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>
            <div></div>
            <div class="flex items-end gap-3">
                <a href="{{ route('admin.hadith.themes.index') }}" class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 border-2"
                   :class="darkMode ? 'border-gray-700 text-gray-400 hover:bg-gray-800' : 'border-gray-300 text-gray-600 hover:bg-gray-100'">
                    {{ __('admin.reset_filters') }}
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg">
                    {{ __('admin.filter') }}
                </button>
            </div>
        </form>
    </div>

    <!-- Themes Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($themes ?? [] as $theme)
        <div class="rounded-2xl border overflow-hidden transition-all hover:scale-105 hover:shadow-xl"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <!-- Color Header -->
            <div class="h-3" style="background-color: {{ $theme->color ?? '#0056b3' }};"></div>

            <div class="p-5">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: {{ $theme->color ?? '#0056b3' }}20;">
                        <i data-lucide="{{ $theme->icon ?? 'tag' }}" class="w-6 h-6" style="color: {{ $theme->color ?? '#0056b3' }};"></i>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold"
                          :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                        <i data-lucide="file-text" class="w-3 h-3"></i>
                        {{ $theme->articles_count ?? 0 }}
                    </span>
                </div>

                <h3 class="text-lg font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ $theme->getTranslation('name', 'ar') }}
                </h3>
                <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ $theme->getTranslation('name', 'en') }}
                </p>

                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs"
                          :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'">
                        {{ $theme->slug }}
                    </span>

                    <div class="flex items-center gap-1">
                        <a href="{{ route('admin.hadith.themes.show', $theme->id) }}" class="p-1.5 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'" title="عرض">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </a>
                        <a href="{{ route('admin.hadith.themes.edit', $theme->id) }}" class="p-1.5 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'" title="تعديل">
                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                        </a>
                        <form action="{{ route('admin.hadith.themes.destroy', $theme->id) }}" method="POST" class="inline" @submit.prevent="$dispatch('show-confirm', { title: '{{ __("admin.confirm_delete") }}', message: '{{ __("admin.delete_warning") }}', type: 'danger', onConfirm: () => $el.submit() })">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 rounded-lg transition-colors text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20" title="حذف">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="rounded-2xl border p-12 text-center" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <i data-lucide="tag" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p class="text-lg font-medium mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">لا توجد موضوعات</p>
                <a href="{{ route('admin.hadith.themes.create') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                    <i data-lucide="plus" class="w-5 h-5"></i> إضافة موضوع جديد
                </a>
            </div>
        </div>
        @endforelse
    </div>

    @if(isset($themes) && $themes->hasPages())
    <div class="mt-8">{{ $themes->withQueryString()->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => { lucide.createIcons(); });
</script>
@endpush
