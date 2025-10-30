@extends('layouts.admin')

@section('title', 'سجل النشاط')

@section('content')
<div x-data="activityLogData()" x-init="init()">
    <!-- Page Header with Glassmorphism -->
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <!-- Animated Background Gradient -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 animate-pulse"></div>

        <div class="relative z-10">
            <h1 class="text-4xl font-black text-white mb-2 gradient-text">سجل النشاط</h1>
            <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                <i data-lucide="activity" class="w-4 h-4"></i>
                <span>تتبع ومراقبة جميع الأنشطة والعمليات في النظام</span>
            </p>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Today's Logs -->
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <i data-lucide="calendar" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white">{{ $stats['today'] }}</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">نشاطات اليوم</p>
            </div>
        </div>

        <!-- Week's Logs -->
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-green-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                        <i data-lucide="calendar-range" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white">{{ $stats['week'] }}</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">نشاطات الأسبوع</p>
            </div>
        </div>

        <!-- Unique Users Today -->
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                        <i data-lucide="users" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white">{{ $stats['users'] }}</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">مستخدمين نشطين اليوم</p>
            </div>
        </div>

        <!-- Top Action -->
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-yellow-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center">
                        <i data-lucide="zap" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-lg font-black text-white">{{ $actions[$stats['topAction']] ?? $stats['topAction'] }}</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">الإجراء الأكثر شيوعاً</p>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <form method="GET" action="{{ route('activity-logs.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <!-- User Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">المستخدم</label>
                    <select name="user_id" x-model="filters.user_id" class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="">جميع المستخدمين</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">الإجراء</label>
                    <select name="action" x-model="filters.action" class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="">جميع الإجراءات</option>
                        @foreach($actions as $actionKey => $actionName)
                            <option value="{{ $actionKey }}" {{ request('action') == $actionKey ? 'selected' : '' }}>
                                {{ $actionName }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Model Type Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">نوع النموذج</label>
                    <select name="model_type" x-model="filters.model_type" class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="">جميع الأنواع</option>
                        @foreach($modelTypes as $typeKey => $typeName)
                            <option value="{{ $typeKey }}" {{ request('model_type') == $typeKey ? 'selected' : '' }}>
                                {{ $typeName }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Advanced Filters Toggle -->
            <div x-show="showAdvancedFilters" x-collapse class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <!-- Date From -->
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">من تاريخ</label>
                    <input type="date" name="date_from" x-model="filters.date_from" value="{{ request('date_from') }}" class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">إلى تاريخ</label>
                    <input type="date" name="date_to" x-model="filters.date_to" value="{{ request('date_to') }}" class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                </div>

                <!-- IP Address -->
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">عنوان IP</label>
                    <input type="text" name="ip_address" x-model="filters.ip_address" value="{{ request('ip_address') }}" placeholder="192.168.1.1" class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="flex items-center justify-between">
                <button type="button" @click="showAdvancedFilters = !showAdvancedFilters" class="text-sm font-semibold text-brand-accent hover:text-blue-400 transition-colors flex items-center space-x-1 space-x-reverse">
                    <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                    <span x-text="showAdvancedFilters ? 'إخفاء الفلاتر المتقدمة' : 'إظهار الفلاتر المتقدمة'"></span>
                </button>

                <div class="flex items-center space-x-2 space-x-reverse">
                    <a href="{{ route('activity-logs.index') }}" class="px-5 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 space-x-reverse">
                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                        <span>إعادة تعيين</span>
                    </a>

                    <button type="submit" class="px-6 py-3 gradient-primary text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all duration-300 flex items-center space-x-2 space-x-reverse hover:scale-105">
                        <i data-lucide="filter" class="w-5 h-5"></i>
                        <span>تطبيق الفلاتر</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Activity Logs Table -->
    <div class="rounded-2xl overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <!-- Table Header -->
        <div class="bg-gradient-to-r from-blue-500/10 to-green-500/10 border-b border-white/10 p-4">
            <div class="flex items-center justify-between">
                <span class="text-white font-bold text-lg">سجل النشاطات</span>
                <div class="text-sm text-gray-400">
                    عرض <span class="text-white font-bold">{{ $logs->count() }}</span> من أصل <span class="text-white font-bold">{{ $logs->total() }}</span>
                </div>
            </div>
        </div>

        <!-- Table Body -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">المستخدم</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">الإجراء</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">النموذج</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">عنوان IP</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">التاريخ</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($logs as $log)
                        <tr class="hover:bg-white/5 transition-colors duration-200 group">
                            <!-- ID -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-gray-400 font-mono text-sm">#{{ $log->id }}</span>
                            </td>

                            <!-- User -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-green-500 flex items-center justify-center text-white font-bold text-sm">
                                        {{ substr($log->user->name ?? 'U', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="text-white font-semibold text-sm">{{ $log->user->name ?? 'غير محدد' }}</p>
                                        <p class="text-xs text-gray-500">{{ $log->user->email ?? '' }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Action -->
                            <td class="px-6 py-4 whitespace-nowrap">
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
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold border {{ $colorClass }}">
                                    {{ $actions[$log->action] ?? $log->action }}
                                </span>
                            </td>

                            <!-- Model -->
                            <td class="px-6 py-4">
                                @if($log->model_type && $log->model_id)
                                    @php
                                        $shortModelName = class_basename($log->model_type);
                                        $modelDisplayName = $modelTypes[$log->model_type] ?? $shortModelName;
                                    @endphp
                                    <div class="flex items-center space-x-2 space-x-reverse">
                                        <span class="text-white font-semibold">{{ $modelDisplayName }}</span>
                                        <span class="text-gray-500 text-sm">#{{ $log->model_id }}</span>
                                    </div>
                                @else
                                    <span class="text-gray-500 text-sm">-</span>
                                @endif
                            </td>

                            <!-- IP Address -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-gray-300 font-mono text-sm">{{ $log->ip_address ?? '-' }}</span>
                            </td>

                            <!-- Date -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm">
                                    <p class="text-white font-semibold">{{ $log->created_at->format('Y/m/d') }}</p>
                                    <p class="text-gray-500 text-xs" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                                        {{ $log->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('activity-logs.show', $log->id) }}" class="inline-flex items-center px-4 py-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 rounded-lg transition-all hover:scale-105 font-semibold">
                                    <i data-lucide="eye" class="w-4 h-4 ml-1"></i>
                                    <span>التفاصيل</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-24 h-24 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full flex items-center justify-center mb-4">
                                        <i data-lucide="activity" class="w-12 h-12 text-gray-500"></i>
                                    </div>
                                    <p class="text-white font-bold text-xl mb-2">لا توجد نشاطات</p>
                                    <p class="text-gray-400">لم يتم العثور على أي نشاطات تطابق الفلاتر المحددة</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($logs->hasPages())
            <div class="border-t border-white/10 p-6">
                <div class="flex items-center justify-between">
                    <!-- Page Info -->
                    <div class="text-sm text-gray-400">
                        عرض
                        <span class="font-bold text-white">{{ $logs->firstItem() }}</span>
                        إلى
                        <span class="font-bold text-white">{{ $logs->lastItem() }}</span>
                        من أصل
                        <span class="font-bold text-white">{{ $logs->total() }}</span>
                        نتيجة
                    </div>

                    <!-- Pagination Links -->
                    <div class="flex items-center space-x-2 space-x-reverse">
                        {{-- Previous Page Link --}}
                        @if ($logs->onFirstPage())
                            <span class="px-4 py-2 bg-white/5 text-gray-600 rounded-lg cursor-not-allowed">
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </span>
                        @else
                            <a href="{{ $logs->previousPageUrl() }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-all hover:scale-105">
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </a>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($logs->links()->elements[0] as $page => $url)
                            @if ($page == $logs->currentPage())
                                <span class="px-4 py-2 gradient-primary text-white rounded-lg font-bold shadow-lg">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-all hover:scale-105">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($logs->hasMorePages())
                            <a href="{{ $logs->nextPageUrl() }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-all hover:scale-105">
                                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                            </a>
                        @else
                            <span class="px-4 py-2 bg-white/5 text-gray-600 rounded-lg cursor-not-allowed">
                                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function activityLogData() {
    return {
        logs: @json($logs->items()),
        filters: {
            user_id: '{{ request('user_id') }}',
            action: '{{ request('action') }}',
            model_type: '{{ request('model_type') }}',
            date_from: '{{ request('date_from') }}',
            date_to: '{{ request('date_to') }}',
            ip_address: '{{ request('ip_address') }}'
        },
        stats: @json($stats),
        showAdvancedFilters: {{ request()->hasAny(['date_from', 'date_to', 'ip_address']) ? 'true' : 'false' }},
        loading: false,
        darkMode: localStorage.getItem('darkMode') !== 'false',

        init() {
            this.$nextTick(() => {
                lucide.createIcons();
            });
        }
    };
}
</script>
@endpush
