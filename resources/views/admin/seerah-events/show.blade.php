@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.seerah-events.index') }}" class="hover:text-accent transition-colors">أحداث السيرة النبوية</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ $event->getTranslation('title', 'ar') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="{{ $event->event_type?->icon() ?? 'calendar' }}" class="w-7 h-7 text-white"></i>
            </div>
            {{ $event->getTranslation('title', 'ar') }}
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.seerah-events.edit', $event->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="edit-2" class="w-5 h-5"></i>
            <span>{{ __('admin.edit') }}</span>
        </a>
        <a href="{{ route('admin.seerah-events.index') }}"
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

        <!-- Event Header Card -->
        <div class="rounded-2xl border overflow-hidden"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="relative h-48 bg-gradient-to-br from-[#0056b3] to-[#218838]">
                @if($event->getFirstMediaUrl('featured_image'))
                    <img src="{{ $event->getFirstMediaUrl('featured_image') }}"
                         alt="{{ $event->getTranslation('title', 'ar') }}"
                         class="w-full h-full object-cover opacity-60">
                @endif

                @if($event->is_featured)
                <div class="absolute top-4 start-4">
                    <span class="inline-flex items-center gap-1 px-4 py-2 rounded-full text-sm font-semibold bg-amber-500 text-white shadow-lg">
                        <i data-lucide="star" class="w-4 h-4"></i>
                        حدث مميز
                    </span>
                </div>
                @endif

                @if($event->event_type)
                <div class="absolute bottom-4 start-4">
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-white/20 backdrop-blur-sm text-white">
                        <i data-lucide="{{ $event->event_type->icon() }}" class="w-4 h-4"></i>
                        {{ $event->event_type->labelArabic() }}
                    </span>
                </div>
                @endif

                @if($event->importance)
                <div class="absolute bottom-4 end-4">
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-white/20 backdrop-blur-sm text-white">
                        {{ $event->importance->labelArabic() }}
                    </span>
                </div>
                @endif
            </div>

            <div class="p-6">
                <h2 class="text-2xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ $event->getTranslation('title', 'ar') }}
                </h2>
                @if($event->getTranslation('title', 'en'))
                <p class="text-lg" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ $event->getTranslation('title', 'en') }}
                </p>
                @endif

                <div class="flex flex-wrap gap-3 mt-4">
                    @if($event->hijri_year)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl"
                          :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                        <i data-lucide="calendar" class="w-4 h-4 text-accent"></i>
                        {{ $event->hijri_year }} هـ
                    </span>
                    @endif
                    @if($event->gregorian_year)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl"
                          :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                        <i data-lucide="calendar" class="w-4 h-4 text-accent"></i>
                        {{ $event->gregorian_year }} م
                    </span>
                    @endif
                    @if($event->location)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl"
                          :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                        <i data-lucide="map-pin" class="w-4 h-4 text-accent"></i>
                        {{ $event->location }}
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Description -->
        @if($event->getTranslation('description', 'ar') || $event->getTranslation('description', 'en'))
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
                        @if($event->getTranslation('description', $lang))
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
                    @if($event->getTranslation('description', $lang))
                    <div x-show="activeTab === '{{ $lang }}'"
                         class="p-4 rounded-xl"
                         :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <p class="leading-relaxed {{ in_array($lang, ['ar', 'ur']) ? 'text-right' : 'text-left' }}"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                           dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                            {{ $event->getTranslation('description', $lang) }}
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
                العنوان بلغات مختلفة
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                    @if($event->getTranslation('title', $lang))
                    <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                            {{ $label }}
                        </label>
                        <p class="text-lg font-semibold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $event->getTranslation('title', $lang) }}
                        </p>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">

        <!-- Event Info -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                معلومات
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الرابط المختصر</span>
                    <span class="font-mono text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $event->slug }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">نوع الحدث</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $event->event_type?->labelArabic() ?? '-' }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الأهمية</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $event->importance?->labelArabic() ?? '-' }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">ترتيب العرض</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $event->display_order }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">المشاهدات</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ number_format($event->views_count) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تاريخ الإضافة</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $event->created_at?->format('Y/m/d') }}</span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="link-2" class="w-5 h-5 text-accent"></i>
                المقال المرتبط
            </h3>

            @if($event->article)
            <a href="{{ route('admin.articles.show', $event->article->id) }}"
               class="block p-4 rounded-xl transition-colors"
               :class="darkMode ? 'bg-gray-800/50 hover:bg-gray-800 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                <p class="font-semibold">
                    {{ $event->article->locales->firstWhere('locale', 'ar')?->title
                        ?? $event->article->locales->firstWhere('locale', 'en')?->title
                        ?? ('Article #' . $event->article->id) }}
                </p>
                <p class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                    #{{ $event->article->id }} · {{ number_format($event->article->views ?? 0) }} مشاهدة
                </p>
            </a>
            @else
            <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                لا يوجد مقال مرتبط بهذا الحدث.
            </p>
            @endif
        </div>

        <!-- Quick Actions -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="zap" class="w-5 h-5 text-accent"></i>
                إجراءات سريعة
            </h3>

            <div class="space-y-3">
                <a href="{{ route('admin.seerah-events.edit', $event->id) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="edit-2" class="w-5 h-5 text-accent"></i>
                    <span>تعديل الحدث</span>
                </a>

                <a href="{{ route('admin.seerah-events.create') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="plus" class="w-5 h-5 text-accent"></i>
                    <span>إضافة حدث جديد</span>
                </a>
            </div>
        </div>

        <!-- Delete -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-red-800/50' : 'bg-white border-red-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-red-500">
                <i data-lucide="trash-2" class="w-5 h-5"></i>
                حذف الحدث
            </h3>

            <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                سيتم حذف هذا الحدث نهائياً ولا يمكن استرجاعه.
            </p>

            <form action="{{ route('admin.seerah-events.destroy', $event->id) }}"
                  method="POST"
                  onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 bg-red-500 hover:bg-red-600 text-white flex items-center justify-center gap-2">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    <span>حذف الحدث</span>
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
