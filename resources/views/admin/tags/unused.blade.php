@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.tags.index') }}" class="hover:text-accent transition-colors">الوسوم</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>الوسوم غير المستخدمة</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="tag" class="w-7 h-7 text-white"></i>
            </div>
            الوسوم غير المستخدمة
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            وسوم لم يتم ربطها بأي محتوى
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.tags.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="arrow-right" class="w-5 h-5"></i>
            <span>جميع الوسوم</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="{
    selectedTags: [],
    selectAll: false,
    toggleSelectAll() {
        if (this.selectAll) {
            this.selectedTags = [];
        } else {
            this.selectedTags = Array.from(document.querySelectorAll('input[name=tag_ids]')).map(el => el.value);
        }
        this.selectAll = !this.selectAll;
    }
}">

    <!-- Bulk Actions Bar -->
    <div x-show="selectedTags.length > 0"
         x-transition
         class="mb-6 p-4 rounded-2xl border flex items-center justify-between"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="flex items-center gap-3">
            <span class="text-sm font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                تم تحديد <span x-text="selectedTags.length" class="font-bold text-accent"></span> وسم
            </span>
        </div>
        <button @click="if(confirm('هل أنت متأكد من حذف الوسوم المحددة؟')) { /* bulk delete via AJAX */ }"
                class="px-4 py-2 rounded-xl font-semibold transition-all hover:scale-105 bg-red-500 hover:bg-red-600 text-white flex items-center gap-2">
            <i data-lucide="trash-2" class="w-4 h-4"></i>
            <span>حذف المحدد</span>
        </button>
    </div>

    <!-- Tags Table -->
    <div class="rounded-2xl border overflow-hidden"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <th class="p-4 text-start">
                            <input type="checkbox" @click="toggleSelectAll()"
                                   class="w-4 h-4 rounded text-accent border-gray-300 focus:ring-accent">
                        </th>
                        <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الاسم
                        </th>
                        <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الرابط المختصر
                        </th>
                        <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            تاريخ الإنشاء
                        </th>
                        <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الإجراءات
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-200'">
                    @forelse($unusedTags as $tag)
                    <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                        <td class="p-4">
                            <input type="checkbox" name="tag_ids" value="{{ $tag->id }}"
                                   x-model="selectedTags"
                                   class="w-4 h-4 rounded text-accent border-gray-300 focus:ring-accent">
                        </td>
                        <td class="p-4">
                            <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $tag->getTranslation('name', 'ar') }}
                            </span>
                            @if($tag->getTranslation('name', 'en', false))
                            <span class="text-sm block" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                {{ $tag->getTranslation('name', 'en', false) }}
                            </span>
                            @endif
                        </td>
                        <td class="p-4">
                            <span class="font-mono text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $tag->slug }}
                            </span>
                        </td>
                        <td class="p-4">
                            <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $tag->created_at?->format('Y/m/d') }}
                            </span>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.tags.edit', $tag->id) }}"
                                   class="p-2 rounded-lg transition-colors"
                                   :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-200 text-gray-600'"
                                   title="{{ __('admin.edit') }}">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </a>
                                <form action="{{ route('admin.tags.destroy', $tag->id) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 rounded-lg transition-colors text-red-500 hover:bg-red-100 dark:hover:bg-red-900/20"
                                            title="{{ __('admin.delete') }}">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center">
                            <i data-lucide="check-circle" class="w-16 h-16 mx-auto mb-4 text-green-500"></i>
                            <p class="text-lg font-medium mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                لا توجد وسوم غير مستخدمة
                            </p>
                            <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                جميع الوسوم مرتبطة بمحتوى
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($unusedTags->hasPages())
    <div class="mt-8">
        {{ $unusedTags->links() }}
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
