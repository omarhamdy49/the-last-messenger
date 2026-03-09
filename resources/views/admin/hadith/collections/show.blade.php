@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.hadith.collections.index') }}" class="hover:text-accent transition-colors">مجموعات الأحاديث</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ $collection->name_ar ?? ($collection->name['ar'] ?? '') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="library" class="w-7 h-7 text-white"></i>
            </div>
            {{ $collection->name_ar ?? ($collection->name['ar'] ?? '') }}
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.hadith.collections.edit', $collection->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="edit-2" class="w-5 h-5"></i>
            <span>{{ __('admin.edit') }}</span>
        </a>
        <a href="{{ route('admin.hadith.collections.index') }}"
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

        <!-- Collection Header Card -->
        <div class="rounded-2xl border overflow-hidden"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="relative h-32 bg-gradient-to-br from-[#0056b3] to-[#218838]">
                @if($collection->featured ?? false)
                <div class="absolute top-4 start-4">
                    <span class="inline-flex items-center gap-1 px-4 py-2 rounded-full text-sm font-semibold bg-amber-500 text-white shadow-lg">
                        <i data-lucide="star" class="w-4 h-4"></i>
                        مجموعة مميزة
                    </span>
                </div>
                @endif
            </div>

            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-2xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $collection->name_ar ?? ($collection->name['ar'] ?? '') }}
                        </h2>
                        <p class="text-lg mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ $collection->name_en ?? ($collection->name['en'] ?? '') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($collection->active ?? true)
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                نشط
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                <i data-lucide="x" class="w-4 h-4"></i>
                                غير نشط
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="flex flex-wrap gap-4 mt-6">
                    @if($collection->abbreviation)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl"
                          :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                        <i data-lucide="hash" class="w-4 h-4 text-accent"></i>
                        {{ $collection->abbreviation }}
                    </span>
                    @endif
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl"
                          :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                        <i data-lucide="book-open" class="w-4 h-4"></i>
                        {{ $collection->books->count() }} كتاب
                    </span>
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl"
                          :class="darkMode ? 'bg-green-900/30 text-green-400' : 'bg-green-100 text-green-800'">
                        <i data-lucide="scroll-text" class="w-4 h-4"></i>
                        {{ number_format($collection->total_hadiths ?? 0) }} حديث
                    </span>
                </div>
            </div>
        </div>

        <!-- Description -->
        @if(($collection->description_ar ?? ($collection->description['ar'] ?? null)) || ($collection->description_en ?? ($collection->description['en'] ?? null)))
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="file-text" class="w-5 h-5 text-accent"></i>
                الوصف
            </h2>

            <div x-data="{ activeTab: 'ar' }" class="space-y-4">
                <!-- Language Tabs -->
                <div class="flex flex-wrap gap-2 border-b pb-4" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                        @php
                            $desc = $collection->{"description_{$lang}"} ?? ($collection->description[$lang] ?? null);
                        @endphp
                        @if($desc)
                        <button @click="activeTab = '{{ $lang }}'"
                                class="px-4 py-2 rounded-lg font-medium transition-all"
                                :class="activeTab === '{{ $lang }}' ?
                                    'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' :
                                    (darkMode ? 'bg-gray-800 text-gray-400 hover:text-white' : 'bg-gray-100 text-gray-600 hover:text-gray-900')">
                            {{ $label }}
                        </button>
                        @endif
                    @endforeach
                </div>

                <!-- Content -->
                @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                    @php
                        $desc = $collection->{"description_{$lang}"} ?? ($collection->description[$lang] ?? null);
                    @endphp
                    @if($desc)
                    <div x-show="activeTab === '{{ $lang }}'"
                         class="p-4 rounded-xl"
                         :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <p class="leading-relaxed {{ in_array($lang, ['ar', 'ur']) ? 'text-right' : 'text-left' }}"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                           dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                            {{ $desc }}
                        </p>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        <!-- Books List -->
        @if($collection->books && $collection->books->count() > 0)
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="book-open" class="w-5 h-5 text-accent"></i>
                الكتب ({{ $collection->books->count() }})
            </h2>

            <div class="space-y-3">
                @foreach($collection->books as $book)
                <div class="flex items-center justify-between p-4 rounded-xl transition-all"
                     :class="darkMode ? 'bg-gray-800/50 hover:bg-gray-800' : 'bg-gray-50 hover:bg-gray-100'">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center">
                            <i data-lucide="book" class="w-5 h-5 text-white"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $book->name_ar ?? ($book->name['ar'] ?? "كتاب #{$book->id}") }}
                            </h4>
                            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $book->name_en ?? ($book->name['en'] ?? '') }}
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Names in Different Languages -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="languages" class="w-5 h-5 text-accent"></i>
                الاسم بلغات مختلفة
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                    @php
                        $name = $collection->{"name_{$lang}"} ?? ($collection->name[$lang] ?? null);
                    @endphp
                    @if($name)
                    <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                            {{ $label }}
                        </label>
                        <p class="text-lg font-semibold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $name }}
                        </p>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">

        <!-- Collection Info -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                معلومات
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الاختصار</span>
                    <span class="font-mono text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $collection->abbreviation ?? '-' }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">المؤلف</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $collection->author_name ?? '-' }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">ترتيب العرض</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $collection->display_order ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تاريخ الإضافة</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $collection->created_at?->format('Y/m/d') }}</span>
                </div>
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
                <a href="{{ route('admin.hadith.collections.edit', $collection->id) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="edit-2" class="w-5 h-5 text-accent"></i>
                    <span>تعديل المجموعة</span>
                </a>

                <a href="{{ route('admin.hadith.collections.create') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="plus" class="w-5 h-5 text-accent"></i>
                    <span>إضافة مجموعة جديدة</span>
                </a>
            </div>
        </div>

        <!-- Delete -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-red-800/50' : 'bg-white border-red-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-red-500">
                <i data-lucide="trash-2" class="w-5 h-5"></i>
                حذف المجموعة
            </h3>

            <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                سيتم حذف هذه المجموعة نهائياً ولا يمكن استرجاعها.
            </p>

            <form action="{{ route('admin.hadith.collections.destroy', $collection->id) }}"
                  method="POST"
                  @submit.prevent="$dispatch('show-confirm', { title: '{{ __("admin.confirm_delete") }}', message: '{{ __("admin.delete_warning") }}', type: 'danger', onConfirm: () => $el.submit() })">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 bg-red-500 hover:bg-red-600 text-white flex items-center justify-center gap-2">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    <span>حذف المجموعة</span>
                </button>
            </form>
        </div>

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
