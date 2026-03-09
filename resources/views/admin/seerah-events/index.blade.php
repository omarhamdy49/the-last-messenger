@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="calendar" class="w-7 h-7 text-white"></i>
            </div>
            أحداث السيرة النبوية
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            إدارة أحداث السيرة النبوية والتسلسل الزمني
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
        <a href="{{ route('admin.seerah-events.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>إضافة حدث</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="{
    filtersOpen: {{ request()->hasAny(['event_type', 'importance', 'search']) ? 'true' : 'false' }}
}"
     @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-admin.stats-card
            icon="calendar"
            title="إجمالي الأحداث"
            :value="$stats['total'] ?? 0"
            :gradient="true"
        />

        @foreach(($stats['categories'] ?? []) as $category => $count)
            @if($loop->index < 3)
            <x-admin.stats-card
                icon="tag"
                :title="$category"
                :value="$count"
                :delay="'delay-' . (($loop->index + 1) * 100)"
            />
            @endif
        @endforeach
    </div>

    <!-- Filter Section -->
    <div x-show="filtersOpen"
         x-collapse
         class="mb-6 p-6 rounded-2xl border"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.seerah-events.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    بحث
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ابحث باسم الحدث..."
                       class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <!-- Event Type Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    نوع الحدث
                </label>
                <select name="event_type"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach(\App\Domain\Seerah\Enums\EventType::cases() as $type)
                        <option value="{{ $type->value }}" {{ request('event_type') == $type->value ? 'selected' : '' }}>
                            {{ $type->labelArabic() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Importance Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    الأهمية
                </label>
                <select name="importance"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach(\App\Domain\Seerah\Enums\EventImportance::cases() as $imp)
                        <option value="{{ $imp->value }}" {{ request('importance') == $imp->value ? 'selected' : '' }}>
                            {{ $imp->labelArabic() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Actions -->
            <div class="flex items-end gap-3">
                <a href="{{ route('admin.seerah-events.index') }}"
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

    <!-- Events Table -->
    <div class="rounded-2xl border overflow-hidden"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">#</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">العنوان</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">النوع</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الأهمية</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">التاريخ الهجري</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الموقع</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                    @forelse($events ?? [] as $event)
                    <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                        <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ $event->id }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="{{ $event->event_type?->icon() ?? 'calendar' }}" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ $event->getTranslation('title', 'ar') }}
                                    </h4>
                                    <p class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                        {{ $event->getTranslation('title', 'en') }}
                                    </p>
                                </div>
                                @if($event->is_featured)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-amber-500 text-white">
                                    <i data-lucide="star" class="w-3 h-3"></i>
                                </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($event->event_type)
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold"
                                  :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                                <i data-lucide="{{ $event->event_type->icon() }}" class="w-3 h-3"></i>
                                {{ $event->event_type->labelArabic() }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($event->importance)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                {{ $event->importance->value === 'high' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                {{ $event->importance->value === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                {{ $event->importance->value === 'low' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}">
                                {{ $event->importance->labelArabic() }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            @if($event->hijri_year)
                                {{ $event->hijri_year }} هـ
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            @if($event->location)
                                <span class="inline-flex items-center gap-1">
                                    <i data-lucide="map-pin" class="w-3 h-3"></i>
                                    {{ $event->location }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.seerah-events.show', $event->id) }}"
                                   class="p-2 rounded-lg transition-colors"
                                   :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'"
                                   title="عرض">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="{{ route('admin.seerah-events.edit', $event->id) }}"
                                   class="p-2 rounded-lg transition-colors"
                                   :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'"
                                   title="تعديل">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </a>
                                <form action="{{ route('admin.seerah-events.destroy', $event->id) }}"
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
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i data-lucide="calendar" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                            <p class="text-lg font-medium mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                لا توجد أحداث حالياً
                            </p>
                            <a href="{{ route('admin.seerah-events.create') }}"
                               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                                <i data-lucide="plus" class="w-5 h-5"></i>
                                إضافة حدث جديد
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if(isset($events) && $events->hasPages())
    <div class="mt-8">
        {{ $events->withQueryString()->links() }}
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
