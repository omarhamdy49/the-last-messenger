@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.scheduler.index') }}" class="hover:text-accent transition-colors">جدولة المحتوى</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>جدولة جديدة</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="plus" class="w-7 h-7 text-white"></i>
            </div>
            جدولة محتوى جديد
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.scheduler.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="x" class="w-5 h-5"></i>
            <span>{{ __('admin.cancel') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.scheduler.store') }}" method="POST" x-data="schedulerForm()">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Content Selection -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="file-text" class="w-5 h-5 text-accent"></i>
                    اختيار المحتوى
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Content Type -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            نوع المحتوى <span class="text-red-500">*</span>
                        </label>
                        <select name="schedulable_type"
                                x-model="contentType"
                                class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                required>
                            <option value="">اختر نوع المحتوى</option>
                            @foreach($contentTypes as $type => $label)
                                <option value="{{ $type }}" {{ ($contentType ?? '') == $type ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('schedulable_type')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Content ID -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            معرف المحتوى <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               name="schedulable_id"
                               value="{{ old('schedulable_id', $contentId) }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="أدخل معرف المحتوى (ID)"
                               min="1"
                               required>
                        @error('schedulable_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Schedule Details -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="settings" class="w-5 h-5 text-accent"></i>
                    تفاصيل الجدولة
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Action -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الإجراء <span class="text-red-500">*</span>
                        </label>
                        <select name="action"
                                class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                required>
                            <option value="">اختر الإجراء</option>
                            <option value="publish" {{ old('action') == 'publish' ? 'selected' : '' }}>نشر</option>
                            <option value="unpublish" {{ old('action') == 'unpublish' ? 'selected' : '' }}>إلغاء نشر</option>
                            <option value="update" {{ old('action') == 'update' ? 'selected' : '' }}>تحديث</option>
                        </select>
                        @error('action')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Scheduled At -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            موعد التنفيذ <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local"
                               name="scheduled_at"
                               value="{{ old('scheduled_at') }}"
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
                                <option value="{{ $tz }}" {{ old('timezone', 'Asia/Riyadh') == $tz ? 'selected' : '' }}>{{ $tz }}</option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Recurrence -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            التكرار
                        </label>
                        <select name="recurrence"
                                class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">بدون تكرار</option>
                            <option value="daily" {{ old('recurrence') == 'daily' ? 'selected' : '' }}>يومي</option>
                            <option value="weekly" {{ old('recurrence') == 'weekly' ? 'selected' : '' }}>أسبوعي</option>
                            <option value="monthly" {{ old('recurrence') == 'monthly' ? 'selected' : '' }}>شهري</option>
                        </select>
                        @error('recurrence')
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
                              placeholder="أضف ملاحظات اختيارية...">{{ old('notes') }}</textarea>
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
                    <i data-lucide="calendar-plus" class="w-5 h-5"></i>
                    إنشاء الجدولة
                </button>

                <a href="{{ route('admin.scheduler.index') }}"
                   class="mt-4 w-full px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border flex items-center justify-center gap-2"
                   :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                    {{ __('admin.cancel') }}
                </a>
            </div>

            <!-- Help -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="help-circle" class="w-5 h-5 text-accent"></i>
                    مساعدة
                </h3>
                <ul class="space-y-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-0.5 text-green-500 flex-shrink-0"></i>
                        <span><strong>نشر:</strong> يتم نشر المحتوى في الموعد المحدد</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-0.5 text-green-500 flex-shrink-0"></i>
                        <span><strong>إلغاء نشر:</strong> يتم إخفاء المحتوى في الموعد المحدد</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-0.5 text-green-500 flex-shrink-0"></i>
                        <span><strong>تحديث:</strong> يتم تحديث بيانات المحتوى في الموعد المحدد</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function schedulerForm() {
        return {
            contentType: '{{ old('schedulable_type', $contentType ?? '') }}',
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
