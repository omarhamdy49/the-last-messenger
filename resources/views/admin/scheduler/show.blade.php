@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.scheduler.index') }}" class="hover:text-accent transition-colors">جدولة المحتوى</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>تفاصيل الجدولة #{{ $schedule->id }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="calendar-clock" class="w-7 h-7 text-white"></i>
            </div>
            تفاصيل الجدولة
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        @if($schedule->status === 'pending')
        <a href="{{ route('admin.scheduler.edit', $schedule->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="edit-2" class="w-5 h-5"></i>
            <span>{{ __('admin.edit') }}</span>
        </a>
        @endif
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
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Schedule Details -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                تفاصيل العملية
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Content Type -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">نوع المحتوى</label>
                    <p class="text-lg font-semibold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ class_basename($schedule->schedulable_type) }}
                    </p>
                </div>

                <!-- Content ID -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">معرف المحتوى</label>
                    <p class="text-lg font-semibold font-mono mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        #{{ $schedule->schedulable_id }}
                    </p>
                </div>

                <!-- Action -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">الإجراء</label>
                    <div class="mt-1">
                        @php
                            $actionLabels = ['publish' => 'نشر', 'unpublish' => 'إلغاء نشر', 'update' => 'تحديث'];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold"
                              :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                            {{ $actionLabels[$schedule->action] ?? $schedule->action }}
                        </span>
                    </div>
                </div>

                <!-- Status -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">الحالة</label>
                    <div class="mt-1">
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                'executed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                            ];
                            $statusLabels = ['pending' => 'في الانتظار', 'executed' => 'تم التنفيذ', 'cancelled' => 'ملغية', 'failed' => 'فشلت'];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold {{ $statusColors[$schedule->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $statusLabels[$schedule->status] ?? $schedule->status }}
                        </span>
                    </div>
                </div>

                <!-- Scheduled At -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">موعد التنفيذ</label>
                    <p class="text-lg font-semibold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $schedule->scheduled_at?->format('Y/m/d H:i') }}
                    </p>
                    <p class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                        {{ $schedule->scheduled_at?->diffForHumans() }}
                    </p>
                </div>

                <!-- Timezone -->
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">المنطقة الزمنية</label>
                    <p class="text-lg font-semibold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $schedule->timezone ?? 'UTC' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Notes -->
        @if($schedule->notes)
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="message-square" class="w-5 h-5 text-accent"></i>
                ملاحظات
            </h2>
            <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                <p class="leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ $schedule->notes }}
                </p>
            </div>
        </div>
        @endif

        <!-- Scheduled Data (for update action) -->
        @if($schedule->scheduled_data)
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="code" class="w-5 h-5 text-accent"></i>
                البيانات المجدولة
            </h2>
            <div class="p-4 rounded-xl overflow-x-auto" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                <pre class="text-sm font-mono whitespace-pre-wrap" :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                     dir="ltr">{{ json_encode($schedule->scheduled_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">

        <!-- Meta Info -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="clock" class="w-5 h-5 text-accent"></i>
                معلومات إضافية
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">أنشئ بواسطة</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $schedule->creator?->name ?? 'غير معروف' }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تاريخ الإنشاء</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $schedule->created_at?->format('Y/m/d H:i') }}</span>
                </div>
                @if($schedule->executed_at)
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تاريخ التنفيذ</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $schedule->executed_at->format('Y/m/d H:i') }}</span>
                </div>
                @endif
                @if($schedule->recurrence)
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">التكرار</span>
                    @php
                        $recurrenceLabels = ['daily' => 'يومي', 'weekly' => 'أسبوعي', 'monthly' => 'شهري'];
                    @endphp
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $recurrenceLabels[$schedule->recurrence] ?? $schedule->recurrence }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="zap" class="w-5 h-5 text-accent"></i>
                إجراءات سريعة
            </h3>

            <div class="space-y-3">
                @if($schedule->status === 'pending')
                <a href="{{ route('admin.scheduler.edit', $schedule->id) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="edit-2" class="w-5 h-5 text-accent"></i>
                    <span>تعديل الجدولة</span>
                </a>
                @endif

                <a href="{{ route('admin.scheduler.create') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="plus" class="w-5 h-5 text-accent"></i>
                    <span>جدولة جديدة</span>
                </a>
            </div>
        </div>

        <!-- Cancel Schedule -->
        @if($schedule->status === 'pending')
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-red-800/50' : 'bg-white border-red-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-red-500">
                <i data-lucide="x-circle" class="w-5 h-5"></i>
                إلغاء الجدولة
            </h3>

            <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                سيتم إلغاء هذه الجدولة ولن يتم تنفيذها.
            </p>

            <form action="{{ route('admin.scheduler.destroy', $schedule->id) }}"
                  method="POST"
                  onsubmit="return confirm('هل أنت متأكد من إلغاء هذه الجدولة؟')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 bg-red-500 hover:bg-red-600 text-white flex items-center justify-center gap-2">
                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                    <span>إلغاء الجدولة</span>
                </button>
            </form>
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
