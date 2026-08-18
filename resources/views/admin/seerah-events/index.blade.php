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
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
            إدارة أحداث السيرة النبوية والتسلسل الزمني
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

    {{-- Review queue: rows that name a place in prose but carry no canonical
         reference. The resolver leaves ambiguous and place-less text alone by
         design, so this is the editor's worklist, not an error count. --}}
    @if(($unmappedCount ?? 0) > 0)
        <a href="{{ request()->fullUrlWithQuery(['unmapped' => 1]) }}"
           class="mb-6 flex items-center gap-2 px-4 py-3 rounded-xl border text-sm transition-colors
                  bg-amber-50 border-amber-200 text-amber-900 hover:bg-amber-100
                  dark:bg-amber-900/25 dark:border-amber-800 dark:text-amber-200 dark:hover:bg-amber-900/40">
            <i data-lucide="map-pin-off" class="w-4 h-4 flex-shrink-0"></i>
            <span>{{ __('locations.messages.unmapped_count', ['count' => $unmappedCount]) }}</span>
            <i data-lucide="arrow-left" class="w-4 h-4 ms-auto rtl:rotate-180"></i>
        </a>
    @endif
<div x-data="seerahEventsIndex()"
     @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-admin.stats-card
            icon="calendar"
            title="إجمالي الأحداث"
            :value="$stats['total'] ?? 0"
            :gradient="true"
        />

        {{--
            The stats row used to print the raw type slug ("battle", "death"), which
            is neither Arabic nor meaningful to an editor. Map the slug back to the
            localized type name, and lead with the counts an editor actually acts on.
        --}}
        @php
            $typeNames = $eventTypes->mapWithKeys(fn ($t) => [$t->slug => $t->getTranslation('name', 'ar') ?: $t->slug]);
            $topCategories = collect($stats['categories'] ?? [])->sortDesc()->take(2);
        @endphp

        <x-admin.stats-card
            icon="check-circle-2"
            title="منشور"
            :value="$stats['published'] ?? 0"
            delay="delay-100"
        />

        @foreach($topCategories as $category => $count)
            <x-admin.stats-card
                icon="tag"
                :title="$typeNames[$category] ?? $category"
                :value="$count"
                :delay="'delay-' . (($loop->index + 2) * 100)"
            />
        @endforeach
    </div>

    <!-- Filter Section -->
    <div x-show="filtersOpen"
         x-collapse
         class="mb-6 p-6 rounded-2xl border"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.seerah-events.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    بحث
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ابحث باسم الحدث..."
                       class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <!-- Event Type Filter (DB-driven) -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    نوع الحدث
                </label>
                <select name="event_type"
                        class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach($eventTypes as $type)
                        <option value="{{ $type->slug }}" {{ request('event_type') == $type->slug ? 'selected' : '' }}>
                            {{ $type->getTranslation('name', 'ar') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Milestone Filter (DB-driven) -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    الحقبة
                </label>
                <select name="milestone"
                        class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach($milestones as $milestone)
                        <option value="{{ $milestone->id }}" {{ (int) request('milestone') === $milestone->id ? 'selected' : '' }}>
                            {{ $milestone->getTranslation('title', 'ar') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('admin.status') }}
                </label>
                <select name="status"
                        class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>
                            {{ $status->labelArabic() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Actions -->
            <div class="flex items-end gap-3">
                <a href="{{ route('admin.seerah-events.index') }}"
                   class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 border-2"
                   :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-600 hover:bg-gray-100'">
                    {{ __('admin.reset_filters') }}
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg">
                    {{ __('admin.filter') }}
                </button>
            </div>
        </form>
    </div>

    <!-- Bulk Actions Toolbar -->
    <div x-show="selected.length > 0" x-cloak x-transition
         class="mb-4 flex flex-wrap items-center gap-3 px-4 py-3 rounded-xl border"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <span class="text-sm font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
            <span x-text="selected.length"></span> {{ __('admin.selected') }}
        </span>
        <button type="button" @click="setStatus('published')"
                class="px-3 py-1.5 rounded-lg text-sm font-medium bg-green-500 text-white hover:bg-green-600 flex items-center gap-1">
            <i data-lucide="check-circle" class="w-4 h-4"></i> {{ __('admin.set_published') }}
        </button>
        <button type="button" @click="setStatus('draft')"
                class="px-3 py-1.5 rounded-lg text-sm font-medium bg-gray-500 text-white hover:bg-gray-600 flex items-center gap-1">
            <i data-lucide="file-edit" class="w-4 h-4"></i> {{ __('admin.set_draft') }}
        </button>
        <button type="button" @click="bulkDelete()"
                class="px-3 py-1.5 rounded-lg text-sm font-medium bg-red-500 text-white hover:bg-red-600 flex items-center gap-1">
            <i data-lucide="trash-2" class="w-4 h-4"></i> {{ __('admin.delete_selected') }}
        </button>
        <button type="button" @click="selected = []" class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
            {{ __('admin.clear') }}
        </button>
    </div>

    {{--
        Below `md` the seven-column table turns into a sideways scroll to reach
        the actions, so the card stack below replaces it there.
    --}}
    <div class="hidden md:block rounded-2xl border overflow-hidden"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <th class="px-4 py-4 text-start">
                            <input type="checkbox" @change="toggleAll($event)"
                                   class="w-4 h-4 rounded text-accent border-gray-300 focus:ring-accent">
                        </th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الحدث</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold hidden xl:table-cell" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الحقبة</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">النوع</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold hidden lg:table-cell" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">النقاط</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الأهمية</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.status') }}</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                    @forelse($events ?? [] as $event)
                    <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                        <td class="px-4 py-4">
                            <input type="checkbox" value="{{ $event->id }}" x-model.number="selected"
                                   class="w-4 h-4 rounded text-accent border-gray-300 focus:ring-accent">
                        </td>
                        {{--
                            The second line used to print title[en]; with no English
                            translation the locale fallback returned Arabic, so every
                            row showed its own title twice. Show the place and date
                            instead — what an editor scans a chronology by.
                        --}}
                        <td class="px-6 py-4 max-w-md">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br {{ $event->type?->gradient ?: 'from-[#0056b3] to-[#218838]' }} flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="{{ $event->type?->icon ?? 'calendar' }}" class="w-5 h-5 text-white"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <h4 class="font-semibold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                            {{ $event->getTranslation('title', 'ar') }}
                                        </h4>
                                        @if($event->is_pivotal)
                                        <span title="حدث مفصلي" class="inline-flex flex-shrink-0 w-5 h-5 items-center justify-center rounded-full bg-amber-500 text-white">
                                            <i data-lucide="star" class="w-3 h-3"></i>
                                            <span class="sr-only">حدث مفصلي</span>
                                        </span>
                                        @endif
                                    </div>
                                    <p class="text-xs truncate flex items-center gap-2 mt-0.5" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                        @if($event->location)
                                            <span class="inline-flex items-center gap-1">
                                                <i data-lucide="map-pin" class="w-3 h-3"></i>{{ $event->location }}
                                            </span>
                                        @endif
                                        @if($event->hijri_date)
                                            <span class="inline-flex items-center gap-1">
                                                <i data-lucide="calendar" class="w-3 h-3"></i>{{ $event->hijri_date }}
                                            </span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 hidden xl:table-cell">
                            @if($event->milestone)
                                <a href="{{ route('admin.seerah-milestones.show', $event->milestone->id) }}"
                                   title="عرض الحقبة"
                                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold max-w-[16rem] bg-gradient-to-r {{ $event->milestone->gradient ?: 'from-[#0056b3] to-[#218838]' }} text-white transition-all hover:scale-105 hover:shadow-md">
                                    <i data-lucide="{{ $event->milestone->icon ?: 'milestone' }}" class="w-3 h-3 flex-shrink-0"></i>
                                    <span class="truncate">{{ $event->milestone->getTranslation('title', 'ar') }}</span>
                                </a>
                            @else
                                <span class="text-xs" :class="darkMode ? 'text-gray-600' : 'text-gray-400'">بدون حقبة</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($event->type)
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold"
                                  :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                                <i data-lucide="{{ $event->type->icon ?? 'tag' }}" class="w-3 h-3"></i>
                                {{ $event->type->getTranslation('name', 'ar') }}
                            </span>
                            @else
                            <span class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">—</span>
                            @endif
                        </td>
                        {{-- Timeline points: an event with none renders no "الخط الزمني"
                             section on the public page, so surface it as a warning. --}}
                        <td class="px-6 py-4 text-center hidden lg:table-cell">
                            @php $pointCount = $event->timeline_items_count ?? 0; @endphp
                            <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-1 rounded-lg text-xs font-bold tabular-nums
                                {{ $pointCount === 0
                                    ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                    : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}"
                                  title="{{ $pointCount === 0 ? 'لا توجد نقاط — قسم الخط الزمني لن يظهر' : $pointCount.' نقطة' }}">
                                {{ $pointCount }}
                            </span>
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
                        <td class="px-6 py-4">
                            @if($event->status)
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold
                                {{ $event->status->value === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400' }}">
                                <i data-lucide="{{ $event->status->icon() }}" class="w-3 h-3"></i>
                                {{ $event->status->labelArabic() }}
                            </span>
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
                                      @submit.prevent="$dispatch('show-confirm', { title: '{{ __("admin.confirm_delete") }}', message: '{{ __("admin.delete_warning") }}', type: 'danger', onConfirm: () => $el.submit() })">
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
                            <i data-lucide="calendar" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                            <p class="text-lg font-medium mb-4" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
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

    <!-- Mobile card stack (< md) -->
    <div class="md:hidden space-y-3">
        @forelse($events ?? [] as $event)
            @php $pointCount = $event->timeline_items_count ?? 0; @endphp
            <x-admin.list-card
                :value="$event->id"
                :icon="$event->type?->icon ?? 'calendar'"
                :gradient="$event->type?->gradient ?: 'from-[#0056b3] to-[#218838]'"
                :title="$event->getTranslation('title', 'ar')"
                :subtitle="collect([$event->location, $event->hijri_date])->filter()->implode(' · ') ?: null">

                @if($event->is_pivotal)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500 text-white">
                        <i data-lucide="star" class="w-3 h-3"></i> مفصلي
                    </span>
                @endif

                @if($event->milestone)
                    <a href="{{ route('admin.seerah-milestones.show', $event->milestone->id) }}"
                       title="عرض الحقبة"
                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold max-w-full bg-gradient-to-r {{ $event->milestone->gradient ?: 'from-[#0056b3] to-[#218838]' }} text-white transition-all hover:shadow-md">
                        <i data-lucide="{{ $event->milestone->icon ?: 'milestone' }}" class="w-3 h-3 flex-shrink-0"></i>
                        <span class="truncate">{{ $event->milestone->getTranslation('title', 'ar') }}</span>
                    </a>
                @endif

                @if($event->type)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
                          :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                        <i data-lucide="{{ $event->type->icon ?? 'tag' }}" class="w-3 h-3"></i>
                        {{ $event->type->getTranslation('name', 'ar') }}
                    </span>
                @endif

                {{-- Same warning the table carries: no points means no public timeline section. --}}
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold tabular-nums
                    {{ $pointCount === 0
                        ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                        : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}"
                      title="{{ $pointCount === 0 ? 'لا توجد نقاط — قسم الخط الزمني لن يظهر' : $pointCount.' نقطة' }}">
                    <i data-lucide="list" class="w-3 h-3"></i> {{ $pointCount }}
                </span>

                @if($event->importance)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                        {{ $event->importance->value === 'high' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                        {{ $event->importance->value === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                        {{ $event->importance->value === 'low' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}">
                        {{ $event->importance->labelArabic() }}
                    </span>
                @endif

                @if($event->status)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold
                        {{ $event->status->value === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400' }}">
                        <i data-lucide="{{ $event->status->icon() }}" class="w-3 h-3"></i>
                        {{ $event->status->labelArabic() }}
                    </span>
                @endif

                <x-slot:actions>
                    <a href="{{ route('admin.seerah-events.show', $event->id) }}"
                       class="p-2.5 rounded-lg transition-colors"
                       :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'"
                       title="عرض">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </a>
                    <a href="{{ route('admin.seerah-events.edit', $event->id) }}"
                       class="p-2.5 rounded-lg transition-colors"
                       :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'"
                       title="تعديل">
                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                    </a>
                    <form action="{{ route('admin.seerah-events.destroy', $event->id) }}"
                          method="POST"
                          class="inline"
                          @submit.prevent="$dispatch('show-confirm', { title: '{{ __("admin.confirm_delete") }}', message: '{{ __("admin.delete_warning") }}', type: 'danger', onConfirm: () => $el.submit() })">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="p-2.5 rounded-lg transition-colors text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20"
                                title="حذف">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </form>
                </x-slot:actions>
            </x-admin.list-card>
        @empty
            <div class="p-8 rounded-2xl border text-center"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <i data-lucide="calendar" class="w-12 h-12 mx-auto mb-3" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p class="text-base font-medium mb-4" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">لا توجد أحداث حالياً</p>
                <a href="{{ route('admin.seerah-events.create') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    إضافة حدث جديد
                </a>
            </div>
        @endforelse
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
    function seerahEventsIndex() {
        return {
            filtersOpen: {{ request()->hasAny(['event_type', 'importance', 'status', 'milestone', 'search']) ? 'true' : 'false' }},
            selected: [],
            // The row checkboxes are x-model bound, so Alpine mirrors `selected`
            // into them on its own. Reading the DOM here instead would miss the
            // mobile card stack, which renders the same rows outside <tbody>.
            pageIds: {{ Js::from($events->pluck('id')->all()) }},
            toggleAll(e) {
                this.selected = e.target.checked ? [...this.pageIds] : [];
            },
            setStatus(status) {
                if (this.selected.length === 0) return;
                this.postBulk('{{ route('admin.seerah-events.bulk-status') }}', { status });
            },
            bulkDelete() {
                if (this.selected.length === 0) return;
                this.$dispatch('show-confirm', {
                    title: '{{ __('admin.confirm_delete') }}',
                    message: '{{ __('admin.delete_warning') }}',
                    type: 'danger',
                    onConfirm: () => this.postBulk('{{ route('admin.seerah-events.bulk-delete') }}'),
                });
            },
            postBulk(url, extra = {}) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">';
                this.selected.forEach(id => {
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'ids[]'; i.value = id;
                    form.appendChild(i);
                });
                Object.entries(extra).forEach(([k, v]) => {
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = k; i.value = v;
                    form.appendChild(i);
                });
                document.body.appendChild(form);
                form.submit();
            },
        };
    }
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
