@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.activity-logs.index') }}" class="hover:text-accent transition-colors">سجل النشاطات</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>نشاطات {{ $user->name }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="user" class="w-7 h-7 text-white"></i>
            </div>
            نشاطات المستخدم
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            جميع العمليات التي قام بها {{ $user->name }}
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.activity-logs.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="arrow-right" class="w-5 h-5"></i>
            <span>جميع السجلات</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    <!-- Sidebar - User Info -->
    <div class="space-y-6">
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="text-center">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center mx-auto mb-4">
                    <span class="text-white text-2xl font-bold">{{ mb_substr($user->name, 0, 1) }}</span>
                </div>
                <h3 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ $user->name }}
                </h3>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ $user->email }}
                </p>
            </div>

            <div class="mt-6 space-y-3">
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">إجمالي النشاطات</span>
                    <span class="font-bold text-accent">{{ $logs->total() }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تاريخ التسجيل</span>
                    <span class="font-medium text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $user->created_at?->format('Y/m/d') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content - Logs List -->
    <div class="lg:col-span-3">
        <div class="rounded-2xl border overflow-hidden"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
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
                            <td class="p-4">
                                @php
                                    $actionColors = [
                                        'created' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'updated' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'deleted' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        'login' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                        'logout' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    ];
                                    $actionLabels = [
                                        'created' => 'إنشاء', 'updated' => 'تحديث', 'deleted' => 'حذف',
                                        'login' => 'تسجيل دخول', 'logout' => 'تسجيل خروج',
                                        'bulk_delete' => 'حذف جماعي', 'bulk_publish' => 'نشر جماعي',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $actionColors[$log->action] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $actionLabels[$log->action] ?? $log->action }}
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    {{ class_basename($log->model_type ?? '') }}
                                </span>
                                @if($log->model_id)
                                <span class="text-xs block font-mono" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">#{{ $log->model_id }}</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <span class="text-sm truncate max-w-xs block" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    {{ is_array($log->details) ? json_encode($log->details, JSON_UNESCAPED_UNICODE) : ($log->details ?? '-') }}
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    {{ $log->created_at?->format('Y/m/d H:i') }}
                                </span>
                                <span class="text-xs block" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                    {{ $log->created_at?->diffForHumans() }}
                                </span>
                            </td>
                            <td class="p-4">
                                <a href="{{ route('admin.activity-logs.show', $log->id) }}"
                                   class="p-2 rounded-lg transition-colors inline-flex"
                                   :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-200 text-gray-600'">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center">
                                <i data-lucide="scroll-text" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                <p class="text-lg font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    لا توجد نشاطات لهذا المستخدم
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
            {{ $logs->links() }}
        </div>
        @endif
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
