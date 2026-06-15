@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="scroll-text" class="w-7 h-7 text-white"></i>
            </div>
            سجل النشاطات
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            متابعة جميع العمليات والإجراءات في النظام
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
    </div>
</div>
@endsection

@section('content')
<div x-data="{
    filtersOpen: {{ request()->hasAny(['user_id', 'action', 'model_type', 'date_from', 'date_to']) ? 'true' : 'false' }}
}"
     @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-admin.stats-card
            icon="activity"
            title="نشاطات اليوم"
            :value="$stats['today'] ?? 0"
            :gradient="true"
        />

        <x-admin.stats-card
            icon="calendar"
            title="نشاطات الأسبوع"
            :value="$stats['week'] ?? 0"
            delay="delay-100"
        />

        <x-admin.stats-card
            icon="users"
            title="مستخدمون نشطون اليوم"
            :value="$stats['users'] ?? 0"
            delay="delay-200"
        />

        <x-admin.stats-card
            icon="zap"
            title="أكثر إجراء"
            :value="$stats['topAction'] ?? 'لا يوجد'"
            delay="delay-300"
        />
    </div>

    <!-- Filter Section -->
    <div x-show="filtersOpen"
         x-collapse
         class="mb-6 p-6 rounded-2xl border"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <!-- User Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    المستخدم
                </label>
                <select name="user_id"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Action Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    الإجراء
                </label>
                <select name="action"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach($actions as $key => $label)
                        <option value="{{ $key }}" {{ request('action') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Model Type Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    نوع المحتوى
                </label>
                <select name="model_type"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach($modelTypes as $type => $label)
                        <option value="{{ $type }}" {{ request('model_type') == $type ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    من تاريخ
                </label>
                <input type="date"
                       name="date_from"
                       value="{{ request('date_from') }}"
                       class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    إلى تاريخ
                </label>
                <input type="date"
                       name="date_to"
                       value="{{ request('date_to') }}"
                       class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <!-- Filter Actions -->
            <div class="flex items-end gap-3">
                <a href="{{ route('admin.activity-logs.index') }}"
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

    <!-- Logs Table -->
    <div class="rounded-2xl border overflow-hidden"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">المستخدم</th>
                        <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الإجراء</th>
                        <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">المحتوى</th>
                        <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">التفاصيل</th>
                        <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">التاريخ</th>
                        <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">عرض</th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-200'">
                    @forelse($logs as $log)
                    <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                        <!-- User -->
                        <td class="p-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                                    <span class="text-white text-xs font-bold">{{ mb_substr($log->user?->name ?? '?', 0, 1) }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ $log->user?->name ?? 'غير معروف' }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Action -->
                        <td class="p-4">
                            @php
                                $actionColors = [
                                    'created' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                    'updated' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                    'deleted' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                    'login' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                    'logout' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                ];
                                $actionLabels = $actions ?? [];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $actionColors[$log->action] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $actionLabels[$log->action] ?? $log->action }}
                            </span>
                        </td>

                        <!-- Model Type -->
                        <td class="p-4">
                            <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $modelTypes[$log->model_type] ?? class_basename($log->model_type ?? '') }}
                            </span>
                            @if($log->model_id)
                            <span class="text-xs block font-mono" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                #{{ $log->model_id }}
                            </span>
                            @endif
                        </td>

                        <!-- Details -->
                        <td class="p-4">
                            <span class="text-sm truncate max-w-xs block" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ is_array($log->details) ? json_encode($log->details, JSON_UNESCAPED_UNICODE) : ($log->details ?? '-') }}
                            </span>
                        </td>

                        <!-- Date -->
                        <td class="p-4">
                            <div>
                                <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    {{ $log->created_at?->format('Y/m/d H:i') }}
                                </span>
                                <span class="text-xs block" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                    {{ $log->created_at?->diffForHumans() }}
                                </span>
                            </div>
                        </td>

                        <!-- View -->
                        <td class="p-4">
                            <a href="{{ route('admin.activity-logs.show', $log->id) }}"
                               class="p-2 rounded-lg transition-colors inline-flex"
                               :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-200 text-gray-600'"
                               title="عرض التفاصيل">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-12 text-center">
                            <i data-lucide="scroll-text" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                            <p class="text-lg font-medium mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                لا توجد سجلات نشاطات
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($logs->hasPages())
    <div class="mt-8">
        {{ $logs->withQueryString()->links() }}
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
