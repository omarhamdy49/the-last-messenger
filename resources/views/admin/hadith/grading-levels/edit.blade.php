@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.hadith.grading-levels.index') }}" class="hover:text-accent transition-colors">درجات التصنيف</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ $gradeData['label_ar'] }}</span>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>عرض التفاصيل</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="eye" class="w-7 h-7 text-white"></i>
            </div>
            تفاصيل الدرجة: {{ $gradeData['label_ar'] }}
        </h1>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.hadith.grading-levels.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="arrow-right" class="w-5 h-5"></i>
            <span>رجوع</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">

    <!-- Notice -->
    <div class="mb-6 p-4 rounded-2xl border flex items-start gap-3"
         :class="darkMode ? 'bg-amber-900/20 border-amber-800/50' : 'bg-amber-50 border-amber-200'">
        <i data-lucide="alert-triangle" class="w-5 h-5 mt-0.5 text-amber-500 shrink-0"></i>
        <div>
            <p class="text-sm font-medium" :class="darkMode ? 'text-amber-300' : 'text-amber-800'">
                التعديل غير متاح
            </p>
            <p class="text-sm mt-1" :class="darkMode ? 'text-amber-400' : 'text-amber-600'">
                درجات التصنيف محددة في كود النظام (PHP Enums) ولا يمكن تعديلها من واجهة الإدارة. هذه الصفحة للعرض فقط.
            </p>
        </div>
    </div>

    <!-- Grade Details (Read-Only) -->
    <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="shield" class="w-5 h-5 text-accent"></i>
            بيانات الدرجة
        </h2>

        <div class="space-y-6">
            <!-- Value -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    القيمة (Value)
                </label>
                <input type="text" value="{{ $gradeData['value'] }}" readonly disabled
                       class="w-full px-4 py-3 rounded-xl border transition-all opacity-75 cursor-not-allowed"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-gray-100 border-gray-300 text-gray-900'">
            </div>

            <!-- Arabic Label -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    الاسم بالعربية
                </label>
                <input type="text" value="{{ $gradeData['label_ar'] }}" readonly disabled
                       class="w-full px-4 py-3 rounded-xl border transition-all opacity-75 cursor-not-allowed"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-gray-100 border-gray-300 text-gray-900'">
            </div>

            <!-- English Label -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    الاسم بالإنجليزية
                </label>
                <input type="text" value="{{ $gradeData['label_en'] }}" readonly disabled
                       class="w-full px-4 py-3 rounded-xl border transition-all opacity-75 cursor-not-allowed"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-gray-100 border-gray-300 text-gray-900'">
            </div>

            <!-- Type -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    النوع
                </label>
                <input type="text" value="{{ $type === 'hadith' ? 'درجات الحديث' : 'درجات الرواة' }}" readonly disabled
                       class="w-full px-4 py-3 rounded-xl border transition-all opacity-75 cursor-not-allowed"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-gray-100 border-gray-300 text-gray-900'">
            </div>

            @if($type === 'hadith' && isset($gradeData['is_reliable']))
            <!-- Reliability -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    الموثوقية
                </label>
                <div class="flex items-center gap-3 p-3 rounded-xl"
                     :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    @if($gradeData['is_reliable'])
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                            موثوق - يمكن الاحتجاج به
                        </span>
                    @else
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                            <i data-lucide="shield-x" class="w-4 h-4"></i>
                            غير موثوق - لا يحتج به
                        </span>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3 mt-8">
            <a href="{{ route('admin.hadith.grading-levels.show', $gradeData['value']) }}"
               class="flex-1 px-6 py-3 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg flex items-center justify-center gap-2">
                <i data-lucide="eye" class="w-5 h-5"></i>
                عرض التفاصيل
            </a>
            <a href="{{ route('admin.hadith.grading-levels.index') }}"
               class="flex-1 px-4 py-3 rounded-xl font-semibold text-center transition-all hover:scale-105 border"
               :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                العودة إلى القائمة
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => { lucide.createIcons(); });
</script>
@endpush
