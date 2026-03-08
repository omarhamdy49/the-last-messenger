@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="calendar-clock" class="w-7 h-7 text-white"></i>
            </div>
            جدولة المحتوى
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            إدارة المحتوى المجدول للنشر والتحديث
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
        <button @click="$dispatch('toggle-filters')"
                x-data
                class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
                :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="filter" class="w-5 h-5"></i>
            <span class="hidden sm:inline">{{ __('admin.filters') }}</span>
        </button>

        <a href="{{ route('admin.scheduler.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>جدولة جديدة</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="{
    filtersOpen: {{ request()->hasAny(['status', 'action', 'type', 'date_from', 'date_to']) ? 'true' : 'false' }}
}"
     @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-admin.stats-card
            icon="calendar-clock"
            title="إجمالي الجدولات"
            :value="$stats['total'] ?? 0"
            :gradient="true"
        />

        <x-admin.stats-card
            icon="clock"
            title="في الانتظار"
            :value="$stats['pending'] ?? 0"
            delay="delay-100"
        />

        <x-admin.stats-card
            icon="check-circle"
            title="تم التنفيذ"
            :value="$stats['executed'] ?? 0"
            delay="delay-200"
        />

        <x-admin.stats-card
            icon="x-circle"
            title="ملغية"
            :value="$stats['cancelled'] ?? 0"
            delay="delay-300"
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        <!-- Main Content -->
        <div class="lg:col-span-3 space-y-6">

            <!-- Filter Section -->
            <div x-show="filtersOpen"
                 x-collapse
                 class="p-6 rounded-2xl border"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <form method="GET" action="{{ route('admin.scheduler.index') }}" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الحالة</label>
                        <select name="status"
                                class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">{{ __('admin.all') }}</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>في الانتظار</option>
                            <option value="executed" {{ request('status') == 'executed' ? 'selected' : '' }}>تم التنفيذ</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغية</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>فشلت</option>
                        </select>
                    </div>

                    <!-- Content Type -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">نوع المحتوى</label>
                        <select name="type"
                                class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">{{ __('admin.all') }}</option>
                            @foreach($contentTypes as $type => $label)
                                <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">من تاريخ</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                               class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">إلى تاريخ</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                               class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    </div>

                    <!-- Filter Actions -->
                    <div class="flex items-end gap-3">
                        <a href="{{ route('admin.scheduler.index') }}"
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

            <!-- Schedules Table -->
            <div class="rounded-2xl border overflow-hidden"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                                <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">المحتوى</th>
                                <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الإجراء</th>
                                <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">موعد التنفيذ</th>
                                <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الحالة</th>
                                <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-200'">
                            @forelse($schedules as $schedule)
                            <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                                <!-- Content -->
                                <td class="p-4">
                                    <div>
                                        <span class="font-semibold text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                            {{ class_basename($schedule->schedulable_type) }}
                                        </span>
                                        <span class="text-xs block font-mono" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                            #{{ $schedule->schedulable_id }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Action -->
                                <td class="p-4">
                                    @php
                                        $actionLabels = ['publish' => 'نشر', 'unpublish' => 'إلغاء نشر', 'update' => 'تحديث'];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                                          :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                                        {{ $actionLabels[$schedule->action] ?? $schedule->action }}
                                    </span>
                                </td>

                                <!-- Scheduled At -->
                                <td class="p-4">
                                    <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                        {{ $schedule->scheduled_at?->format('Y/m/d H:i') }}
                                    </span>
                                    <span class="text-xs block" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                        {{ $schedule->scheduled_at?->diffForHumans() }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="p-4">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                            'executed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                            'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        ];
                                        $statusLabels = ['pending' => 'في الانتظار', 'executed' => 'تم التنفيذ', 'cancelled' => 'ملغية', 'failed' => 'فشلت'];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusColors[$schedule->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $statusLabels[$schedule->status] ?? $schedule->status }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.scheduler.show', $schedule->id) }}"
                                           class="p-2 rounded-lg transition-colors"
                                           :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-200 text-gray-600'"
                                           title="عرض">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        @if($schedule->status === 'pending')
                                        <a href="{{ route('admin.scheduler.edit', $schedule->id) }}"
                                           class="p-2 rounded-lg transition-colors"
                                           :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-200 text-gray-600'"
                                           title="تعديل">
                                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                                        </a>
                                        <form action="{{ route('admin.scheduler.destroy', $schedule->id) }}"
                                              method="POST" class="inline"
                                              onsubmit="return confirm('هل أنت متأكد من إلغاء هذه الجدولة؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-2 rounded-lg transition-colors text-red-500 hover:bg-red-100 dark:hover:bg-red-900/20"
                                                    title="إلغاء">
                                                <i data-lucide="x" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="p-12 text-center">
                                    <i data-lucide="calendar-clock" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                    <p class="text-lg font-medium mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                        لا توجد جدولات
                                    </p>
                                    <a href="{{ route('admin.scheduler.create') }}"
                                       class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                                        <i data-lucide="plus" class="w-5 h-5"></i>
                                        إنشاء جدولة جديدة
                                    </a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            @if($schedules->hasPages())
            <div class="mt-6">
                {{ $schedules->withQueryString()->links() }}
            </div>
            @endif
        </div>

        <!-- Sidebar - Upcoming -->
        <div class="space-y-6">
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="clock" class="w-5 h-5 text-accent"></i>
                    القادمة
                </h3>

                <div class="space-y-3">
                    @forelse($upcoming as $item)
                    <a href="{{ route('admin.scheduler.show', $item->id) }}"
                       class="block p-3 rounded-xl transition-all hover:scale-[1.02]"
                       :class="darkMode ? 'bg-gray-800/50 hover:bg-gray-800' : 'bg-gray-50 hover:bg-gray-100'">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-2 h-2 rounded-full bg-yellow-500 animate-pulse"></div>
                            <span class="text-sm font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ class_basename($item->schedulable_type) }}
                            </span>
                        </div>
                        <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ $item->scheduled_at?->format('Y/m/d H:i') }}
                        </p>
                        <p class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                            {{ $item->scheduled_at?->diffForHumans() }}
                        </p>
                    </a>
                    @empty
                    <p class="text-sm text-center py-4" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                        لا توجد جدولات قادمة
                    </p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
