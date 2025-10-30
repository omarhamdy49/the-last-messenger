@extends('layouts.admin')

@section('title', 'تفاصيل سجل النشاط')

@section('content')
<div x-data="{ darkMode: localStorage.getItem('darkMode') !== 'false' }" x-init="$nextTick(() => lucide.createIcons())">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('activity-logs.index') }}" class="inline-flex items-center space-x-2 space-x-reverse text-gray-400 hover:text-white transition-colors group">
            <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
            <span class="font-semibold">العودة إلى سجل النشاط</span>
        </a>
    </div>

    <!-- Page Header -->
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 animate-pulse"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-4xl font-black text-white mb-2 gradient-text">تفاصيل سجل النشاط</h1>
                    <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                        <i data-lucide="info" class="w-4 h-4"></i>
                        <span>معلومات تفصيلية عن النشاط #{{ $log->id }}</span>
                    </p>
                </div>

                @php
                    $actionColors = [
                        'created' => 'bg-green-500/20 text-green-400 border-green-500/30',
                        'updated' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
                        'deleted' => 'bg-red-500/20 text-red-400 border-red-500/30',
                        'login' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
                        'logout' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
                        'bulk_delete' => 'bg-red-500/20 text-red-400 border-red-500/30',
                        'bulk_publish' => 'bg-green-500/20 text-green-400 border-green-500/30',
                        'bulk_archive' => 'bg-gray-500/20 text-gray-400 border-gray-500/30',
                    ];
                    $colorClass = $actionColors[$log->action] ?? 'bg-gray-500/20 text-gray-400 border-gray-500/30';

                    $actionNames = [
                        'created' => 'إنشاء',
                        'updated' => 'تحديث',
                        'deleted' => 'حذف',
                        'login' => 'تسجيل دخول',
                        'logout' => 'تسجيل خروج',
                        'bulk_delete' => 'حذف جماعي',
                        'bulk_publish' => 'نشر جماعي',
                        'bulk_archive' => 'أرشفة جماعية',
                    ];
                @endphp

                <div>
                    <span class="inline-flex items-center px-6 py-3 rounded-xl text-lg font-bold border {{ $colorClass }}">
                        {{ $actionNames[$log->action] ?? $log->action }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- User Information Card -->
        <div class="glass-dark rounded-2xl p-6 hover:scale-105 transition-transform duration-300">
            <div class="flex items-center space-x-3 space-x-reverse mb-4">
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <i data-lucide="user" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white">المستخدم</h3>
            </div>

            <div class="space-y-3">
                <div class="flex items-center space-x-3 space-x-reverse">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-green-500 flex items-center justify-center text-white font-bold text-xl">
                        {{ substr($log->user->name ?? 'U', 0, 1) }}
                    </div>
                    <div>
                        <p class="text-white font-bold text-lg">{{ $log->user->name ?? 'غير محدد' }}</p>
                        <p class="text-gray-400 text-sm">{{ $log->user->email ?? 'لا يوجد بريد' }}</p>
                    </div>
                </div>

                @if($log->user)
                    <a href="{{ route('activity-logs.user', $log->user_id) }}" class="block w-full px-4 py-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 rounded-lg transition-all text-center font-semibold">
                        <i data-lucide="history" class="w-4 h-4 inline ml-1"></i>
                        عرض جميع نشاطات المستخدم
                    </a>
                @endif
            </div>
        </div>

        <!-- Timestamp Card -->
        <div class="glass-dark rounded-2xl p-6 hover:scale-105 transition-transform duration-300">
            <div class="flex items-center space-x-3 space-x-reverse mb-4">
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white">الوقت والتاريخ</h3>
            </div>

            <div class="space-y-3">
                <div>
                    <p class="text-gray-400 text-sm mb-1">التاريخ</p>
                    <p class="text-white font-bold text-lg">{{ $log->created_at->format('Y/m/d') }}</p>
                </div>
                <div>
                    <p class="text-gray-400 text-sm mb-1">الوقت</p>
                    <p class="text-white font-bold text-lg">{{ $log->created_at->format('H:i:s') }}</p>
                </div>
                <div>
                    <p class="text-gray-400 text-sm mb-1">منذ</p>
                    <p class="text-brand-accent font-bold">{{ $log->created_at->diffForHumans() }}</p>
                </div>
            </div>
        </div>

        <!-- Technical Information Card -->
        <div class="glass-dark rounded-2xl p-6 hover:scale-105 transition-transform duration-300">
            <div class="flex items-center space-x-3 space-x-reverse mb-4">
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <i data-lucide="server" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white">معلومات تقنية</h3>
            </div>

            <div class="space-y-3">
                <div>
                    <p class="text-gray-400 text-sm mb-1">عنوان IP</p>
                    <p class="text-white font-mono font-bold">{{ $log->ip_address ?? 'غير متوفر' }}</p>
                </div>
                @if($log->user_agent)
                    <div>
                        <p class="text-gray-400 text-sm mb-1">User Agent</p>
                        <p class="text-white text-sm break-all">{{ Str::limit($log->user_agent, 60) }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Model Information -->
    @if($log->model_type && $log->model_id)
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3 space-x-reverse">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center">
                        <i data-lucide="box" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">النموذج المتأثر</h3>
                        <p class="text-gray-400 text-sm">المعلومات الخاصة بالعنصر الذي تم التعديل عليه</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2 space-x-reverse">
                    @if($modelExists && $modelUrl)
                        <a href="{{ $modelUrl }}" class="px-4 py-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 rounded-lg transition-all font-semibold flex items-center space-x-1 space-x-reverse">
                            <i data-lucide="external-link" class="w-4 h-4"></i>
                            <span>عرض العنصر</span>
                        </a>
                    @endif

                    <a href="{{ route('activity-logs.model', [urlencode($log->model_type), $log->model_id]) }}" class="px-4 py-2 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded-lg transition-all font-semibold flex items-center space-x-1 space-x-reverse">
                        <i data-lucide="history" class="w-4 h-4"></i>
                        <span>عرض التاريخ الكامل</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white/5 rounded-xl p-4">
                    <p class="text-gray-400 text-sm mb-1">نوع النموذج</p>
                    <p class="text-white font-bold">{{ class_basename($log->model_type) }}</p>
                </div>

                <div class="bg-white/5 rounded-xl p-4">
                    <p class="text-gray-400 text-sm mb-1">معرف النموذج</p>
                    <p class="text-white font-bold font-mono">#{{ $log->model_id }}</p>
                </div>

                <div class="bg-white/5 rounded-xl p-4">
                    <p class="text-gray-400 text-sm mb-1">الحالة</p>
                    @if($modelExists)
                        <span class="inline-flex items-center text-green-400 font-bold">
                            <i data-lucide="check-circle" class="w-4 h-4 ml-1"></i>
                            موجود
                        </span>
                    @else
                        <span class="inline-flex items-center text-red-400 font-bold">
                            <i data-lucide="x-circle" class="w-4 h-4 ml-1"></i>
                            محذوف
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Old Values vs New Values Comparison -->
    @if($log->old_values || $log->new_values)
        <div class="glass-dark rounded-2xl p-6">
            <div class="flex items-center space-x-3 space-x-reverse mb-6">
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-brand-primary to-brand-secondary flex items-center justify-center">
                    <i data-lucide="git-compare" class="w-6 h-6 text-white"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-white">مقارنة التغييرات</h3>
                    <p class="text-gray-400 text-sm">القيم القديمة مقابل القيم الجديدة</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Old Values -->
                <div class="bg-red-500/5 border border-red-500/20 rounded-xl p-6">
                    <div class="flex items-center space-x-2 space-x-reverse mb-4">
                        <i data-lucide="minus-circle" class="w-5 h-5 text-red-400"></i>
                        <h4 class="text-lg font-bold text-red-400">القيم القديمة</h4>
                    </div>

                    @if($log->old_values && count($log->old_values) > 0)
                        <div class="space-y-3">
                            @foreach($log->old_values as $key => $value)
                                <div class="bg-white/5 rounded-lg p-4">
                                    <p class="text-gray-400 text-sm font-semibold mb-2">{{ $key }}</p>
                                    <div class="text-white">
                                        @if(is_array($value))
                                            <pre class="text-xs overflow-x-auto custom-scrollbar bg-black/30 p-3 rounded-lg" dir="ltr">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @elseif(is_bool($value))
                                            <span class="font-mono">{{ $value ? 'true' : 'false' }}</span>
                                        @elseif(is_null($value))
                                            <span class="text-gray-500 italic">null</span>
                                        @else
                                            <span class="font-semibold">{{ $value }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i data-lucide="inbox" class="w-12 h-12 text-gray-500 mx-auto mb-2"></i>
                            <p class="text-gray-500">لا توجد قيم قديمة</p>
                        </div>
                    @endif
                </div>

                <!-- New Values -->
                <div class="bg-green-500/5 border border-green-500/20 rounded-xl p-6">
                    <div class="flex items-center space-x-2 space-x-reverse mb-4">
                        <i data-lucide="plus-circle" class="w-5 h-5 text-green-400"></i>
                        <h4 class="text-lg font-bold text-green-400">القيم الجديدة</h4>
                    </div>

                    @if($log->new_values && count($log->new_values) > 0)
                        <div class="space-y-3">
                            @foreach($log->new_values as $key => $value)
                                <div class="bg-white/5 rounded-lg p-4">
                                    <p class="text-gray-400 text-sm font-semibold mb-2">{{ $key }}</p>
                                    <div class="text-white">
                                        @if(is_array($value))
                                            <pre class="text-xs overflow-x-auto custom-scrollbar bg-black/30 p-3 rounded-lg" dir="ltr">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @elseif(is_bool($value))
                                            <span class="font-mono">{{ $value ? 'true' : 'false' }}</span>
                                        @elseif(is_null($value))
                                            <span class="text-gray-500 italic">null</span>
                                        @else
                                            <span class="font-semibold">{{ $value }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i data-lucide="inbox" class="w-12 h-12 text-gray-500 mx-auto mb-2"></i>
                            <p class="text-gray-500">لا توجد قيم جديدة</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="glass-dark rounded-2xl p-12 text-center">
            <div class="w-24 h-24 bg-gradient-to-br from-gray-500/20 to-gray-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="file-x" class="w-12 h-12 text-gray-500"></i>
            </div>
            <p class="text-white font-bold text-xl mb-2">لا توجد تفاصيل للتغييرات</p>
            <p class="text-gray-400">هذا النشاط لا يحتوي على تفاصيل قيم قديمة أو جديدة</p>
        </div>
    @endif

    <!-- Quick Actions -->
    <div class="mt-8 flex items-center justify-center space-x-4 space-x-reverse">
        <a href="{{ route('activity-logs.index') }}" class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-bold transition-all hover:scale-105 flex items-center space-x-2 space-x-reverse">
            <i data-lucide="arrow-right" class="w-5 h-5"></i>
            <span>العودة للقائمة</span>
        </a>

        @if($log->user)
            <a href="{{ route('activity-logs.user', $log->user_id) }}" class="px-6 py-3 gradient-primary text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all hover:scale-105 flex items-center space-x-2 space-x-reverse">
                <i data-lucide="user" class="w-5 h-5"></i>
                <span>نشاطات المستخدم</span>
            </a>
        @endif
    </div>
</div>
@endsection
