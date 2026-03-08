@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.hadith.grading-levels.index') }}" class="hover:text-accent transition-colors">درجات التصنيف</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>معلومات</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="info" class="w-7 h-7 text-white"></i>
            </div>
            درجات التصنيف - معلومات
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
    <!-- Info Notice -->
    <div class="rounded-2xl border p-8 text-center"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">

        <div class="w-20 h-20 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center mx-auto mb-6">
            <i data-lucide="shield-alert" class="w-10 h-10 text-white"></i>
        </div>

        <h2 class="text-2xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
            لا يمكن إضافة درجات تصنيف جديدة
        </h2>

        <p class="text-lg mb-6 max-w-lg mx-auto" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            درجات التصنيف محددة مسبقاً في كود النظام (PHP Enums) ولا يمكن إضافة درجات جديدة من واجهة الإدارة.
        </p>

        <div class="p-4 rounded-xl mb-6" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
            <h3 class="font-bold mb-3" :class="darkMode ? 'text-white' : 'text-gray-900'">الدرجات المتاحة حالياً:</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-start">
                <div>
                    <h4 class="font-semibold text-sm mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">درجات الحديث:</h4>
                    <ul class="space-y-1 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        <li class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-green-500"></span> صحيح (Sahih)</li>
                        <li class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-blue-500"></span> حسن (Hasan)</li>
                        <li class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-yellow-500"></span> ضعيف (Daif)</li>
                        <li class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-red-500"></span> موضوع (Mawdu)</li>
                        <li class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-gray-500"></span> غير محدد (Unknown)</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-sm mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">درجات الرواة:</h4>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        يتم تعريف درجات الرواة في النظام عبر NarratorGrade Enum.
                    </p>
                </div>
            </div>
        </div>

        <p class="text-sm mb-6" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
            لإضافة درجات جديدة، يرجى التواصل مع فريق التطوير لتحديث الكود المصدري.
        </p>

        <a href="{{ route('admin.hadith.grading-levels.index') }}"
           class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all shadow-lg">
            <i data-lucide="arrow-right" class="w-5 h-5"></i>
            العودة إلى القائمة
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => { lucide.createIcons(); });
</script>
@endpush
