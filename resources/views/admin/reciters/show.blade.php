@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.reciters.index') }}" class="hover:text-accent transition-colors">{{ __('quran.reciters.title') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ $reciter->getTranslation('name', 'ar') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="mic" class="w-7 h-7 text-white"></i>
            </div>
            {{ $reciter->getTranslation('name', 'ar') }}
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.reciters.edit', $reciter->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="edit-2" class="w-5 h-5"></i>
            <span>{{ __('admin.edit') }}</span>
        </a>
        <a href="{{ route('admin.reciters.index') }}"
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

        <!-- Profile Card -->
        <div class="rounded-2xl border overflow-hidden"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="relative h-48 bg-gradient-to-br from-[#0056b3] to-[#218838]">
                @if($reciter->is_featured)
                <div class="absolute top-4 start-4">
                    <span class="inline-flex items-center gap-1 px-4 py-2 rounded-full text-sm font-semibold bg-amber-500 text-white shadow-lg">
                        <i data-lucide="star" class="w-4 h-4"></i>
                        قارئ مميز
                    </span>
                </div>
                @endif
            </div>

            <div class="relative px-6 pb-6">
                <!-- Profile Image -->
                <div class="absolute -top-16 start-6">
                    <div class="w-32 h-32 rounded-2xl overflow-hidden border-4 shadow-xl"
                         :class="darkMode ? 'border-gray-900' : 'border-white'">
                        @if($reciter->profile_image)
                            <img src="{{ asset('storage/' . $reciter->profile_image) }}"
                                 alt="{{ $reciter->getTranslation('name', 'ar') }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                                <i data-lucide="mic" class="w-12 h-12 text-white/50"></i>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Info -->
                <div class="pt-20">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-2xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $reciter->getTranslation('name', 'ar') }}
                            </h2>
                            <p class="text-lg" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $reciter->getTranslation('name', 'en') }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($reciter->is_active)
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

                    <div class="flex flex-wrap gap-3 mt-4">
                        @if($reciter->country)
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl"
                              :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                            <i data-lucide="map-pin" class="w-4 h-4 text-accent"></i>
                            {{ $reciter->country }}
                        </span>
                        @endif
                        @if($reciter->style)
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl"
                              :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                            <i data-lucide="music" class="w-4 h-4"></i>
                            {{ $reciter->style }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Biography -->
        @if($reciter->getTranslation('description', 'ar') || $reciter->getTranslation('description', 'en'))
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="file-text" class="w-5 h-5 text-accent"></i>
                السيرة الذاتية
            </h2>

            <div x-data="{ activeTab: 'ar' }" class="space-y-4">
                <!-- Language Tabs -->
                <div class="flex flex-wrap gap-2 border-b pb-4" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                        @if($reciter->getTranslation('description', $lang))
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
                    @if($reciter->getTranslation('description', $lang))
                    <div x-show="activeTab === '{{ $lang }}'"
                         class="p-4 rounded-xl"
                         :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <p class="leading-relaxed {{ in_array($lang, ['ar', 'ur']) ? 'text-right' : 'text-left' }}"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                           dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                            {{ $reciter->getTranslation('description', $lang) }}
                        </p>
                    </div>
                    @endif
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
                    @if($reciter->getTranslation('name', $lang))
                    <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                            {{ $label }}
                        </label>
                        <p class="text-lg font-semibold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $reciter->getTranslation('name', $lang) }}
                        </p>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">

        <!-- Reciter Info -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                معلومات
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الرابط المختصر</span>
                    <span class="font-mono text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $reciter->slug }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">ترتيب العرض</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $reciter->display_order }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تاريخ الإضافة</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $reciter->created_at?->format('Y/m/d') }}</span>
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
                <a href="{{ route('admin.reciters.edit', $reciter->id) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="edit-2" class="w-5 h-5 text-accent"></i>
                    <span>تعديل القارئ</span>
                </a>

                <form action="{{ route('admin.reciters.toggle-featured', $reciter->id) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                            :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                        <i data-lucide="star" class="w-5 h-5 {{ $reciter->is_featured ? 'text-amber-500' : 'text-gray-400' }}"></i>
                        <span>{{ $reciter->is_featured ? 'إلغاء التمييز' : 'تمييز القارئ' }}</span>
                    </button>
                </form>

                <a href="{{ route('admin.reciters.create') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="plus" class="w-5 h-5 text-accent"></i>
                    <span>إضافة قارئ جديد</span>
                </a>
            </div>
        </div>

        <!-- Delete -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-red-800/50' : 'bg-white border-red-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-red-500">
                <i data-lucide="trash-2" class="w-5 h-5"></i>
                حذف القارئ
            </h3>

            <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                سيتم حذف هذا القارئ نهائياً ولا يمكن استرجاعه.
            </p>

            <form action="{{ route('admin.reciters.destroy', $reciter->id) }}"
                  method="POST"
                  @submit.prevent="$dispatch('show-confirm', { title: '{{ __("admin.confirm_delete") }}', message: '{{ __("admin.delete_warning") }}', type: 'danger', onConfirm: () => $el.submit() })">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 bg-red-500 hover:bg-red-600 text-white flex items-center justify-center gap-2">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    <span>حذف القارئ</span>
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
