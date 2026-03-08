@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.scheduler.index') }}" class="hover:text-accent transition-colors">جدولة المحتوى</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>سجل جدولة المحتوى #{{ $id }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="history" class="w-7 h-7 text-white"></i>
            </div>
            سجل الجدولة
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            جميع الجدولات الخاصة بـ {{ class_basename($contentType) }} رقم #{{ $id }}
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.scheduler.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="arrow-right" class="w-5 h-5"></i>
            <span>رجوع</span>
        </a>
    </div>
</div>
@endsection

@section('content')

    <!-- Content Info -->
    <div class="rounded-2xl border p-6 mb-6"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="flex items-center gap-4">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838]">
                <i data-lucide="database" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ class_basename($contentType) }}
                </h2>
                <div class="flex items-center gap-3 mt-1">
                    <span class="text-sm font-mono" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        المعرف: #{{ $id }}
                    </span>
                    <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        إجمالي الجدولات: {{ $history->total() }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- History Timeline -->
    <div class="space-y-4">
        @forelse($history as $schedule)
        <div class="rounded-2xl border p-6 transition-all hover:scale-[1.01]"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800 hover:border-gray-700' : 'bg-white border-gray-200 hover:border-gray-300'">
            <div class="flex items-start gap-4">
                <!-- Status Icon -->
                <div class="flex-shrink-0">
                    @php
                        $statusIcons = [
                            'pending' => ['icon' => 'clock', 'color' => 'text-yellow-500 bg-yellow-100 dark:bg-yellow-900/30'],
                            'executed' => ['icon' => 'check-circle', 'color' => 'text-green-500 bg-green-100 dark:bg-green-900/30'],
                            'cancelled' => ['icon' => 'x-circle', 'color' => 'text-gray-500 bg-gray-100 dark:bg-gray-700'],
                            'failed' => ['icon' => 'alert-circle', 'color' => 'text-red-500 bg-red-100 dark:bg-red-900/30'],
                        ];
                        $iconData = $statusIcons[$schedule->status] ?? ['icon' => 'circle', 'color' => 'text-gray-500 bg-gray-100 dark:bg-gray-700'];
                    @endphp
                    <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $iconData['color'] }}">
                        <i data-lucide="{{ $iconData['icon'] }}" class="w-5 h-5"></i>
                    </div>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            @php
                                $actionLabels = ['publish' => 'نشر', 'unpublish' => 'إلغاء نشر', 'update' => 'تحديث'];
                                $statusLabels = ['pending' => 'في الانتظار', 'executed' => 'تم التنفيذ', 'cancelled' => 'ملغية', 'failed' => 'فشلت'];
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                    'executed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                    'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                ];
                            @endphp
                            <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $actionLabels[$schedule->action] ?? $schedule->action }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusColors[$schedule->status] ?? '' }}">
                                {{ $statusLabels[$schedule->status] ?? $schedule->status }}
                            </span>
                        </div>
                        <a href="{{ route('admin.scheduler.show', $schedule->id) }}"
                           class="p-1.5 rounded-lg transition-colors"
                           :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-200 text-gray-600'">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        <span class="flex items-center gap-1">
                            <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                            مجدول: {{ $schedule->scheduled_at?->format('Y/m/d H:i') }}
                        </span>
                        @if($schedule->executed_at)
                        <span class="flex items-center gap-1">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            نفذ: {{ $schedule->executed_at->format('Y/m/d H:i') }}
                        </span>
                        @endif
                        <span class="flex items-center gap-1">
                            <i data-lucide="user" class="w-3.5 h-3.5"></i>
                            {{ $schedule->creator?->name ?? 'غير معروف' }}
                        </span>
                    </div>

                    @if($schedule->notes)
                    <div class="mt-3 p-3 rounded-xl text-sm" :class="darkMode ? 'bg-gray-800/50 text-gray-400' : 'bg-gray-50 text-gray-600'">
                        {{ $schedule->notes }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-2xl border p-12 text-center"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <i data-lucide="history" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
            <p class="text-lg font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                لا يوجد سجل جدولة لهذا المحتوى
            </p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($history->hasPages())
    <div class="mt-8">
        {{ $history->links() }}
    </div>
    @endif

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
