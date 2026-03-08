@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.articles.index') }}" class="hover:text-accent transition-colors">المقالات</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>سلة المحذوفات</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="trash" class="w-7 h-7 text-white"></i>
            </div>
            سلة المحذوفات
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            المقالات المحذوفة - يمكن استعادتها أو حذفها نهائياً
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.articles.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="arrow-right" class="w-5 h-5"></i>
            <span>جميع المقالات</span>
        </a>
    </div>
</div>
@endsection

@section('content')

    <!-- Trashed Articles Table -->
    <div class="rounded-2xl border overflow-hidden"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            المقال
                        </th>
                        <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الكاتب
                        </th>
                        <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            التصنيف
                        </th>
                        <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            تاريخ الحذف
                        </th>
                        <th class="p-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الإجراءات
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-200'">
                    @forelse($articles as $article)
                    <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                @if($article->thumb)
                                    <img src="{{ asset('storage/' . $article->thumb) }}"
                                         alt=""
                                         class="w-12 h-12 rounded-lg object-cover opacity-60">
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center opacity-60">
                                        <i data-lucide="file-text" class="w-6 h-6 text-white/50"></i>
                                    </div>
                                @endif
                                <div>
                                    <h3 class="font-semibold line-through opacity-75" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ $article->locales->where('locale', 'ar')->first()?->title ?? $article->locales->first()?->title ?? 'بدون عنوان' }}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        @foreach($article->locales as $locale)
                                        <span class="text-xs px-1.5 py-0.5 rounded"
                                              :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'">
                                            {{ strtoupper($locale->locale) }}
                                        </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="p-4">
                            <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $article->writer?->name ?? 'غير محدد' }}
                            </span>
                        </td>
                        <td class="p-4">
                            @if($article->category)
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs"
                                  :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                                {{ $article->category->getTranslation('name', 'ar') }}
                            </span>
                            @else
                            <span class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">-</span>
                            @endif
                        </td>
                        <td class="p-4">
                            <div>
                                <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    {{ $article->deleted_at?->format('Y/m/d') }}
                                </span>
                                <span class="text-xs block" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                    {{ $article->deleted_at?->diffForHumans() }}
                                </span>
                            </div>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <!-- Restore -->
                                <form action="{{ route('admin.articles.restore', $article->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="p-2 rounded-lg transition-colors flex items-center gap-1 text-green-500 hover:bg-green-100 dark:hover:bg-green-900/20"
                                            title="استعادة">
                                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                    </button>
                                </form>

                                <!-- Force Delete -->
                                <form action="{{ route('admin.articles.force-delete', $article->id) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('هل أنت متأكد؟ سيتم حذف المقال نهائياً ولا يمكن استعادته.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 rounded-lg transition-colors text-red-500 hover:bg-red-100 dark:hover:bg-red-900/20"
                                            title="حذف نهائي">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center">
                            <i data-lucide="trash" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                            <p class="text-lg font-medium mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                سلة المحذوفات فارغة
                            </p>
                            <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                لا توجد مقالات محذوفة حالياً
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($articles->hasPages())
    <div class="mt-8">
        {{ $articles->links() }}
    </div>
    @endif

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
