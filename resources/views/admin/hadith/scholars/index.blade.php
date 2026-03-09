@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="graduation-cap" class="w-7 h-7 text-white"></i>
            </div>
            علماء الحديث
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            إدارة علماء الحديث والفقهاء
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <button @click="$dispatch('toggle-filters')"
                x-data
                class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
                :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="filter" class="w-5 h-5"></i>
            <span class="hidden sm:inline">{{ __('admin.filters') }}</span>
        </button>

        <a href="{{ route('admin.hadith.scholars.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>إضافة عالم</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="{
    filtersOpen: {{ request()->hasAny(['era', 'country', 'search']) ? 'true' : 'false' }}
}"
     @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <x-admin.stats-card
            icon="graduation-cap"
            title="إجمالي العلماء"
            :value="$scholars->total() ?? 0"
            :gradient="true"
        />

        <x-admin.stats-card
            icon="check-circle"
            title="العلماء النشطون"
            :value="$scholars->where('status', 'active')->count() ?? 0"
            delay="delay-100"
        />

        <x-admin.stats-card
            icon="globe"
            title="المناطق"
            :value="$scholars->pluck('country')->filter()->unique()->count() ?? 0"
            delay="delay-200"
        />
    </div>

    <!-- Filter Section -->
    <div x-show="filtersOpen"
         x-collapse
         class="mb-6 p-6 rounded-2xl border"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.hadith.scholars.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">بحث</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث باسم العالم..."
                       class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">العصر</label>
                <input type="text" name="era" value="{{ request('era') }}" placeholder="مثال: القرن الثالث"
                       class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الحالة</label>
                <select name="status"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>نشط</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>غير نشط</option>
                </select>
            </div>

            <div class="flex items-end gap-3">
                <a href="{{ route('admin.hadith.scholars.index') }}"
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

    <!-- Scholars Table -->
    <div class="rounded-2xl border overflow-hidden"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">#</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الاسم</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">العصر</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">البلد</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الوفاة (هـ)</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الحالة</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                    @forelse($scholars ?? [] as $scholar)
                    <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ $scholar->id }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <h4 class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ $scholar->name_ar ?? ($scholar->name['ar'] ?? '') }}
                                </h4>
                                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    {{ $scholar->name_en ?? ($scholar->name['en'] ?? '') }}
                                </p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $scholar->era ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $scholar->country ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ $scholar->death_hijri ?? $scholar->death_year_hijri ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(($scholar->status ?? 'active') === 'active')
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    <i data-lucide="check" class="w-3 h-3"></i> نشط
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                    <i data-lucide="x" class="w-3 h-3"></i> غير نشط
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.hadith.scholars.show', $scholar->id) }}" class="p-2 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'" title="عرض">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="{{ route('admin.hadith.scholars.edit', $scholar->id) }}" class="p-2 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'" title="تعديل">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </a>
                                <form action="{{ route('admin.hadith.scholars.destroy', $scholar->id) }}" method="POST" class="inline" @submit.prevent="$dispatch('show-confirm', { title: '{{ __("admin.confirm_delete") }}', message: '{{ __("admin.delete_warning") }}', type: 'danger', onConfirm: () => $el.submit() })">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg transition-colors text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20" title="حذف">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i data-lucide="graduation-cap" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                            <p class="text-lg font-medium mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">لا يوجد علماء</p>
                            <a href="{{ route('admin.hadith.scholars.create') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                                <i data-lucide="plus" class="w-5 h-5"></i> إضافة عالم جديد
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(isset($scholars) && $scholars->hasPages())
    <div class="mt-8">{{ $scholars->withQueryString()->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => { lucide.createIcons(); });
</script>
@endpush
