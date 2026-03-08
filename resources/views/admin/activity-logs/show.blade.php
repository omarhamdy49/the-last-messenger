@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.activity-logs.index') }}" class="hover:text-accent transition-colors">سجل النشاطات</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>تفاصيل النشاط #{{ $log->id }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="scroll-text" class="w-7 h-7 text-white"></i>
            </div>
            تفاصيل النشاط
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
        @if($modelExists && $modelUrl)
        <a href="{{ $modelUrl }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="external-link" class="w-5 h-5"></i>
            <span>عرض المحتوى</span>
        </a>
        @endif
        <a href="{{ route('admin.activity-logs.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="arrow-right" class="w-5 h-5"></i>
            <span>رجوع</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Activity Details -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                تفاصيل العملية
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Action -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">الإجراء</label>
                    <div class="mt-1">
                        @php
                            $actionColors = [
                                'created' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                'updated' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                'deleted' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                'login' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                'logout' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                            ];
                            $actionLabels = [
                                'created' => 'إنشاء',
                                'updated' => 'تحديث',
                                'deleted' => 'حذف',
                                'login' => 'تسجيل دخول',
                                'logout' => 'تسجيل خروج',
                                'bulk_delete' => 'حذف جماعي',
                                'bulk_publish' => 'نشر جماعي',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold {{ $actionColors[$log->action] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $actionLabels[$log->action] ?? $log->action }}
                        </span>
                    </div>
                </div>

                <!-- Model Type -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">نوع المحتوى</label>
                    <p class="text-lg font-semibold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ class_basename($log->model_type ?? 'غير محدد') }}
                    </p>
                </div>

                <!-- Model ID -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">معرف المحتوى</label>
                    <p class="text-lg font-semibold font-mono mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        #{{ $log->model_id ?? '-' }}
                    </p>
                </div>

                <!-- Model Status -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">حالة المحتوى</label>
                    <p class="mt-1">
                        @if($modelExists)
                        <span class="inline-flex items-center gap-1 text-green-500 font-semibold">
                            <i data-lucide="check-circle" class="w-4 h-4"></i> موجود
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 text-red-500 font-semibold">
                            <i data-lucide="x-circle" class="w-4 h-4"></i> محذوف
                        </span>
                        @endif
                    </p>
                </div>

                <!-- IP Address -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">عنوان IP</label>
                    <p class="text-lg font-mono mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $log->ip_address ?? '-' }}
                    </p>
                </div>

                <!-- User Agent -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">المتصفح</label>
                    <p class="text-sm mt-1 truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" title="{{ $log->user_agent ?? '' }}">
                        {{ Str::limit($log->user_agent ?? '-', 50) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Details / Changes Data -->
        @if($log->details)
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="code" class="w-5 h-5 text-accent"></i>
                البيانات المسجلة
            </h2>

            <div class="p-4 rounded-xl overflow-x-auto" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                <pre class="text-sm font-mono whitespace-pre-wrap" :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                     dir="ltr">{{ is_array($log->details) ? json_encode($log->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $log->details }}</pre>
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">

        <!-- User Info -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="user" class="w-5 h-5 text-accent"></i>
                المستخدم
            </h3>

            <div class="text-center mb-4">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center mx-auto mb-3">
                    <span class="text-white text-xl font-bold">{{ mb_substr($log->user?->name ?? '?', 0, 1) }}</span>
                </div>
                <h4 class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ $log->user?->name ?? 'غير معروف' }}
                </h4>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ $log->user?->email ?? '' }}
                </p>
            </div>

            @if($log->user)
            <a href="{{ route('admin.activity-logs.user', $log->user_id) }}"
               class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                <i data-lucide="activity" class="w-4 h-4"></i>
                <span>عرض نشاطات المستخدم</span>
            </a>
            @endif
        </div>

        <!-- Timestamp Info -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="clock" class="w-5 h-5 text-accent"></i>
                التوقيت
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">التاريخ</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $log->created_at?->format('Y/m/d') }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الوقت</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $log->created_at?->format('H:i:s') }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">منذ</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $log->created_at?->diffForHumans() }}</span>
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
