@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.scheduler.index') }}" class="hover:text-accent transition-colors">جدولة المحتوى</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <a href="{{ route('admin.scheduler.show', $schedule->id) }}" class="hover:text-accent transition-colors">جدولة #{{ $schedule->id }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>تعديل</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="edit-2" class="w-7 h-7 text-white"></i>
            </div>
            تعديل الجدولة
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.scheduler.show', $schedule->id) }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="x" class="w-5 h-5"></i>
            <span>{{ __('admin.cancel') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.scheduler.update', $schedule->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Content Info (read-only) -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="file-text" class="w-5 h-5 text-accent"></i>
                    المحتوى المجدول
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">نوع المحتوى</label>
                        <p class="text-lg font-semibold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ class_basename($schedule->schedulable_type) }}
                        </p>
                    </div>
                    <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">معرف المحتوى</label>
                        <p class="text-lg font-semibold font-mono mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            #{{ $schedule->schedulable_id }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Schedule Details -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="settings" class="w-5 h-5 text-accent"></i>
                    تعديل تفاصيل الجدولة
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Scheduled At -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            موعد التنفيذ <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local"
                               name="scheduled_at"
                               value="{{ old('scheduled_at', $schedule->scheduled_at?->format('Y-m-d\TH:i')) }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               required>
                        @error('scheduled_at')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Timezone -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            المنطقة الزمنية
                        </label>
                        <select name="timezone"
                                class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            @foreach($timezones as $tz)
                                <option value="{{ $tz }}" {{ old('timezone', $schedule->timezone ?? 'Asia/Riyadh') == $tz ? 'selected' : '' }}>{{ $tz }}</option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Notes -->
                <div class="mt-6">
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        ملاحظات
                    </label>
                    <textarea name="notes"
                              rows="3"
                              class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all resize-none"
                              :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                              placeholder="أضف ملاحظات اختيارية...">{{ old('notes', $schedule->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Submit Button -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <button type="submit"
                        class="w-full px-6 py-3 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    حفظ التغييرات
                </button>

                <a href="{{ route('admin.scheduler.show', $schedule->id) }}"
                   class="mt-4 w-full px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border flex items-center justify-center gap-2"
                   :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                    {{ __('admin.cancel') }}
                </a>
            </div>

            <!-- Current Status -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                    الحالة الحالية
                </h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الحالة</span>
                        @php
                            $statusLabels = ['pending' => 'في الانتظار', 'executed' => 'تم التنفيذ', 'cancelled' => 'ملغية', 'failed' => 'فشلت'];
                        @endphp
                        <span class="font-bold text-yellow-500">{{ $statusLabels[$schedule->status] ?? $schedule->status }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الإجراء</span>
                        @php $actionLabels = ['publish' => 'نشر', 'unpublish' => 'إلغاء نشر', 'update' => 'تحديث']; @endphp
                        <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $actionLabels[$schedule->action] ?? $schedule->action }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تاريخ الإنشاء</span>
                        <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $schedule->created_at?->format('Y/m/d') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
