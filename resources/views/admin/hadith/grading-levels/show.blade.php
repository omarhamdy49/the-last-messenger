@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.hadith.grading-levels.index') }}" class="hover:text-accent transition-colors">درجات التصنيف</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ $gradeData['label_ar'] }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="shield" class="w-7 h-7 text-white"></i>
            </div>
            {{ $gradeData['label_ar'] }}
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
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">

        <!-- Grade Details Card -->
        <div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            @php
                $badgeColorsBg = [
                    'sahih' => 'from-green-500 to-green-600',
                    'hasan' => 'from-blue-500 to-blue-600',
                    'daif' => 'from-yellow-500 to-yellow-600',
                    'mawdu' => 'from-red-500 to-red-600',
                    'unknown' => 'from-gray-500 to-gray-600',
                    'thiqah' => 'from-green-500 to-green-600',
                    'saduq' => 'from-blue-500 to-blue-600',
                    'matruk' => 'from-red-500 to-red-600',
                ];
                $gradientClass = $badgeColorsBg[$gradeData['value']] ?? 'from-gray-500 to-gray-600';
            @endphp
            <div class="relative h-32 bg-gradient-to-br {{ $gradientClass }}">
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-white text-4xl font-bold opacity-30">{{ strtoupper($gradeData['value']) }}</span>
                </div>
            </div>

            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-2xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $gradeData['label_ar'] }}</h2>
                        <p class="text-lg mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $gradeData['label_en'] }}</p>
                    </div>

                    @if($type === 'hadith' && isset($gradeData['is_reliable']))
                    <div>
                        @if($gradeData['is_reliable'])
                            <span class="inline-flex items-center gap-1 px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                <i data-lucide="shield-check" class="w-4 h-4"></i>
                                موثوق
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-4 py-2 rounded-full text-sm font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                <i data-lucide="shield-x" class="w-4 h-4"></i>
                                غير موثوق
                            </span>
                        @endif
                    </div>
                    @endif
                </div>

                <div class="flex flex-wrap gap-3 mt-6">
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                        <i data-lucide="code" class="w-4 h-4 text-accent"></i>
                        القيمة: {{ $gradeData['value'] }}
                    </span>
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                        <i data-lucide="tag" class="w-4 h-4 text-accent"></i>
                        النوع: {{ $type === 'hadith' ? 'حديث' : 'راوي' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- System Info -->
        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="settings" class="w-5 h-5 text-accent"></i>
                معلومات النظام
            </h2>

            <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                <p class="text-sm leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    هذا المستوى محدد كـ PHP Enum في النظام ولا يمكن تعديله أو حذفه من واجهة الإدارة.
                    يتم استخدامه تلقائياً عند تصنيف {{ $type === 'hadith' ? 'الأحاديث' : 'الرواة' }}.
                </p>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">

        <!-- Grade Info -->
        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                تفاصيل الدرجة
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">القيمة</span>
                    <span class="font-mono text-sm font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $gradeData['value'] }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">النوع</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $type === 'hadith' ? 'حديث' : 'راوي' }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الاسم (عربي)</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $gradeData['label_ar'] }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الاسم (إنجليزي)</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $gradeData['label_en'] }}</span>
                </div>
                @if($type === 'hadith' && isset($gradeData['is_reliable']))
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الموثوقية</span>
                    <span class="font-medium {{ $gradeData['is_reliable'] ? 'text-green-500' : 'text-red-500' }}">
                        {{ $gradeData['is_reliable'] ? 'موثوق' : 'غير موثوق' }}
                    </span>
                </div>
                @endif
            </div>
        </div>

        <!-- Navigation -->
        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="zap" class="w-5 h-5 text-accent"></i>
                تنقل سريع
            </h3>

            <div class="space-y-3">
                <a href="{{ route('admin.hadith.grading-levels.index') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="scroll-text" class="w-5 h-5 text-accent"></i>
                    <span>درجات الحديث</span>
                </a>
                <a href="{{ route('admin.hadith.grading-levels.index') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="users" class="w-5 h-5 text-accent"></i>
                    <span>درجات الرواة</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => { lucide.createIcons(); });
</script>
@endpush
