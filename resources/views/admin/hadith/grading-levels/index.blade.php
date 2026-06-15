@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="shield" class="w-7 h-7 text-white"></i>
            </div>
            درجات التصنيف
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            عرض مستويات تصنيف الأحاديث والرواة المحددة في النظام
        </p>
    </div>

    <div class="flex items-center gap-3">
        <span class="px-6 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg flex items-center gap-2">
            <i data-lucide="scroll-text" class="w-5 h-5"></i>
            <span>درجات الحديث</span>
        </span>
    </div>
</div>
@endsection

@section('content')
<div>
    <!-- Info Banner -->
    <div class="mb-8 p-4 rounded-2xl border flex items-start gap-3"
         :class="darkMode ? 'bg-blue-900/20 border-blue-800/50' : 'bg-blue-50 border-blue-200'">
        <i data-lucide="info" class="w-5 h-5 mt-0.5 text-blue-500 shrink-0"></i>
        <div>
            <p class="text-sm font-medium" :class="darkMode ? 'text-blue-300' : 'text-blue-800'">
                درجات التصنيف محددة في النظام
            </p>
            <p class="text-sm mt-1" :class="darkMode ? 'text-blue-400' : 'text-blue-600'">
                هذه الدرجات محددة مسبقاً في كود النظام (Enums) ولا يمكن تعديلها أو حذفها من واجهة الإدارة.
            </p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <x-admin.stats-card
            icon="shield"
            title="إجمالي الدرجات"
            :value="count($grades ?? [])"
            :gradient="true"
        />

        <x-admin.stats-card
            icon="check-circle"
            :title="$type === 'hadith' ? 'درجات موثوقة' : 'إجمالي الدرجات'"
            :value="$type === 'hadith' ? collect($grades)->where('is_reliable', true)->count() : count($grades ?? [])"
            delay="delay-100"
        />

        <x-admin.stats-card
            icon="tag"
            :title="$type === 'hadith' ? 'تصنيفات الحديث' : 'تصنيفات الرواة'"
            :value="$type === 'hadith' ? 'حديث' : 'راوي'"
            delay="delay-200"
        />
    </div>

    <!-- Grades Table -->
    <div class="rounded-2xl border overflow-hidden"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">#</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">القيمة</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الاسم بالعربية</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الاسم بالإنجليزية</th>
                        @if($type === 'hadith')
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">موثوق</th>
                        @endif
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                    @forelse($grades ?? [] as $index => $grade)
                    @php
                        $badgeColors = [
                            'sahih' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                            'hasan' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                            'daif' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'mawdu' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                            'unknown' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                            'thiqah' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                            'saduq' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                            'matruk' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                        ];
                        $colorClass = $badgeColors[$grade['value']] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                    @endphp
                    <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ $index + 1 }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $colorClass }}">
                                {{ $grade['value'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $grade['label_ar'] }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $grade['label_en'] }}</span>
                        </td>
                        @if($type === 'hadith')
                        <td class="px-6 py-4 text-center">
                            @if($grade['is_reliable'] ?? false)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    <i data-lucide="check" class="w-3 h-3"></i> نعم
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                    <i data-lucide="x" class="w-3 h-3"></i> لا
                                </span>
                            @endif
                        </td>
                        @endif
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.hadith.grading-levels.show', $grade['value']) }}"
                                   class="p-2 rounded-lg transition-colors"
                                   :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'"
                                   title="عرض">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $type === 'hadith' ? 6 : 5 }}" class="px-6 py-12 text-center">
                            <i data-lucide="shield" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                            <p class="text-lg font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">لا توجد درجات تصنيف</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => { lucide.createIcons(); });
</script>
@endpush
