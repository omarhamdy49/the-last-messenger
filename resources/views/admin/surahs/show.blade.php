@extends('layouts.admin')

@section('title', 'عرض السورة - ' . ($surah->name['ar'] ?? 'السورة'))

@section('content')
<div class="max-w-6xl mx-auto" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold gradient-text mb-2" dir="rtl">{{ $surah->name['ar'] ?? $surah->name['en'] }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $surah->translation['ar'] ?? $surah->name_transliteration }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.surahs.edit', $surah) }}"
                   class="flex items-center gap-2 px-4 py-2 gradient-primary text-white rounded-lg hover:shadow-lg transition-all">
                    <i data-lucide="edit" class="w-5 h-5"></i>
                    <span>تعديل</span>
                </a>
                <a href="{{ route('admin.surahs.index') }}"
                   class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                    <span>العودة</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="rounded-xl p-6 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">رقم السورة</p>
                    <span class="text-3xl font-black text-white">{{ $surah->number }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <i data-lucide="hash" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">عدد الآيات</p>
                    <span class="text-3xl font-black text-white">{{ $surah->verses_count }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                    <i data-lucide="list" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">مكان النزول</p>
                    <span class="text-xl font-black text-white">
                        @if($surah->revelation_place === 'makkah')
                            مكية
                        @else
                            مدنية
                        @endif
                    </span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <i data-lucide="map-pin" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">رقم الصفحة</p>
                    <span class="text-3xl font-black text-white">{{ $surah->page_number }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
                    <i data-lucide="bookmark" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Surah Details --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Names Card --}}
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="type" class="w-6 h-6"></i>
                أسماء السورة
            </h2>

            <div class="space-y-4">
                @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                    @if(!empty($surah->name[$lang]))
                        <div class="flex items-start justify-between p-4 rounded-lg" :class="darkMode ? 'bg-white/5' : 'bg-black/5'">
                            <div class="flex-1">
                                <p class="text-sm text-gray-400 mb-1">{{ $label }}</p>
                                <p class="text-white font-bold text-lg" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                                    {{ $surah->name[$lang] }}
                                </p>
                            </div>
                        </div>
                    @endif
                @endforeach

                @if($surah->name_transliteration)
                    <div class="flex items-start justify-between p-4 rounded-lg" :class="darkMode ? 'bg-white/5' : 'bg-black/5'">
                        <div class="flex-1">
                            <p class="text-sm text-gray-400 mb-1">الرسم العثماني</p>
                            <p class="text-white font-bold text-lg">{{ $surah->name_transliteration }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Translations Card --}}
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="globe" class="w-6 h-6"></i>
                الترجمات
            </h2>

            <div class="space-y-4">
                @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                    @if(!empty($surah->translation[$lang]))
                        <div class="flex items-start justify-between p-4 rounded-lg" :class="darkMode ? 'bg-white/5' : 'bg-black/5'">
                            <div class="flex-1">
                                <p class="text-sm text-gray-400 mb-1">{{ $label }}</p>
                                <p class="text-white font-semibold" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                                    {{ $surah->translation[$lang] }}
                                </p>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- Description Card --}}
    @if(!empty(array_filter($surah->description ?? [])))
        <div class="rounded-2xl p-6 mb-8 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="file-text" class="w-6 h-6"></i>
                الوصف
            </h2>

            <div class="space-y-6">
                @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                    @if(!empty($surah->description[$lang]))
                        <div class="p-4 rounded-lg" :class="darkMode ? 'bg-white/5' : 'bg-black/5'">
                            <p class="text-sm font-semibold text-brand-accent mb-2">{{ $label }}</p>
                            <p class="text-gray-300 leading-relaxed" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                                {{ $surah->description[$lang] }}
                            </p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Additional Info Card --}}
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="info" class="w-6 h-6"></i>
            معلومات إضافية
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($surah->revelation_order)
                <div class="flex items-center justify-between p-4 rounded-lg" :class="darkMode ? 'bg-white/5' : 'bg-black/5'">
                    <span class="text-gray-400 font-semibold">ترتيب النزول</span>
                    <span class="text-white font-bold text-lg">{{ $surah->revelation_order }}</span>
                </div>
            @endif

            <div class="flex items-center justify-between p-4 rounded-lg" :class="darkMode ? 'bg-white/5' : 'bg-black/5'">
                <span class="text-gray-400 font-semibold">تاريخ الإنشاء</span>
                <span class="text-white font-semibold">{{ $surah->created_at->format('Y-m-d') }}</span>
            </div>

            <div class="flex items-center justify-between p-4 rounded-lg" :class="darkMode ? 'bg-white/5' : 'bg-black/5'">
                <span class="text-gray-400 font-semibold">آخر تحديث</span>
                <span class="text-white font-semibold">{{ $surah->updated_at->format('Y-m-d') }}</span>
            </div>

            <div class="flex items-center justify-between p-4 rounded-lg" :class="darkMode ? 'bg-white/5' : 'bg-black/5'">
                <span class="text-gray-400 font-semibold">عدد الآيات</span>
                <span class="text-white font-bold text-lg">{{ $surah->verses_count }}</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    lucide.createIcons();
</script>
@endpush
