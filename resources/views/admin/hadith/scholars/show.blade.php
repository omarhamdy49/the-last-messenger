@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.hadith.scholars.index') }}" class="hover:text-accent transition-colors">علماء الحديث</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ $scholar->name_ar ?? ($scholar->name['ar'] ?? '') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="graduation-cap" class="w-7 h-7 text-white"></i>
            </div>
            {{ $scholar->name_ar ?? ($scholar->name['ar'] ?? '') }}
        </h1>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.hadith.scholars.edit', $scholar->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="edit-2" class="w-5 h-5"></i> <span>{{ __('admin.edit') }}</span>
        </a>
        <a href="{{ route('admin.hadith.scholars.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="arrow-right" class="w-5 h-5"></i> <span>رجوع</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">

        <!-- Profile Card -->
        <div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="relative h-32 bg-gradient-to-br from-[#0056b3] to-[#218838]"></div>
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-2xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $scholar->name_ar ?? ($scholar->name['ar'] ?? '') }}</h2>
                        <p class="text-lg mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $scholar->name_en ?? ($scholar->name['en'] ?? '') }}</p>
                    </div>
                    <div>
                        @if(($scholar->status ?? 'active') === 'active')
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                <i data-lucide="check" class="w-4 h-4"></i> نشط
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                <i data-lucide="x" class="w-4 h-4"></i> غير نشط
                            </span>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 mt-6">
                    @if($scholar->era)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                        <i data-lucide="clock" class="w-4 h-4 text-accent"></i> {{ $scholar->era }}
                    </span>
                    @endif
                    @if($scholar->country)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                        <i data-lucide="map-pin" class="w-4 h-4 text-accent"></i> {{ $scholar->country }}
                    </span>
                    @endif
                    @if($scholar->birth_hijri ?? $scholar->birth_year_hijri)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl" :class="darkMode ? 'bg-green-900/30 text-green-400' : 'bg-green-100 text-green-800'">
                        <i data-lucide="calendar" class="w-4 h-4"></i> ولد: {{ $scholar->birth_hijri ?? $scholar->birth_year_hijri }} هـ
                    </span>
                    @endif
                    @if($scholar->death_hijri ?? $scholar->death_year_hijri)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl" :class="darkMode ? 'bg-red-900/30 text-red-400' : 'bg-red-100 text-red-800'">
                        <i data-lucide="calendar-x" class="w-4 h-4"></i> توفي: {{ $scholar->death_hijri ?? $scholar->death_year_hijri }} هـ
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Biography -->
        @php
            $hasBio = ($scholar->biography_json && is_array($scholar->biography_json) && count(array_filter($scholar->biography_json))) || $scholar->biography;
        @endphp
        @if($hasBio)
        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="file-text" class="w-5 h-5 text-accent"></i> السيرة الذاتية
            </h2>
            @if($scholar->biography_json && is_array($scholar->biography_json))
            <div x-data="{ activeTab: 'ar' }" class="space-y-4">
                <div class="flex flex-wrap gap-2 border-b pb-4" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                        @if(!empty($scholar->biography_json[$lang]))
                        <button @click="activeTab = '{{ $lang }}'" class="px-4 py-2 rounded-lg font-medium transition-all"
                                :class="activeTab === '{{ $lang }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'bg-gray-800 text-gray-400 hover:text-white' : 'bg-gray-100 text-gray-600 hover:text-gray-900')">
                            {{ $label }}
                        </button>
                        @endif
                    @endforeach
                </div>
                @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                    @if(!empty($scholar->biography_json[$lang]))
                    <div x-show="activeTab === '{{ $lang }}'" class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <p class="leading-relaxed {{ in_array($lang, ['ar', 'ur']) ? 'text-right' : 'text-left' }}" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                            {{ $scholar->biography_json[$lang] }}
                        </p>
                    </div>
                    @endif
                @endforeach
            </div>
            @elseif($scholar->biography)
            <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                <p class="leading-relaxed text-right" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" dir="rtl">{{ $scholar->biography }}</p>
            </div>
            @endif
        </div>
        @endif

        <!-- Names -->
        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="languages" class="w-5 h-5 text-accent"></i> الاسم بلغات مختلفة
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                    @php $name = $scholar->{"name_{$lang}"} ?? ($scholar->name[$lang] ?? null); @endphp
                    @if($name)
                    <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $label }}</label>
                        <p class="text-lg font-semibold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $name }}</p>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-accent"></i> معلومات
            </h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الرابط المختصر</span>
                    <span class="font-mono text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $scholar->slug ?? '-' }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تاريخ الإضافة</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $scholar->created_at?->format('Y/m/d') }}</span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="zap" class="w-5 h-5 text-accent"></i> إجراءات سريعة
            </h3>
            <div class="space-y-3">
                <a href="{{ route('admin.hadith.scholars.edit', $scholar->id) }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102" :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="edit-2" class="w-5 h-5 text-accent"></i> <span>تعديل العالم</span>
                </a>
                <a href="{{ route('admin.hadith.scholars.create') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102" :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="plus" class="w-5 h-5 text-accent"></i> <span>إضافة عالم جديد</span>
                </a>
            </div>
        </div>

        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-red-800/50' : 'bg-white border-red-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-red-500">
                <i data-lucide="trash-2" class="w-5 h-5"></i> حذف العالم
            </h3>
            <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">سيتم حذف هذا العالم نهائياً ولا يمكن استرجاعه.</p>
            <form action="{{ route('admin.hadith.scholars.destroy', $scholar->id) }}" method="POST" onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                @csrf @method('DELETE')
                <button type="submit" class="w-full px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 bg-red-500 hover:bg-red-600 text-white flex items-center justify-center gap-2">
                    <i data-lucide="trash-2" class="w-5 h-5"></i> <span>حذف العالم</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => { lucide.createIcons(); });
</script>
@endpush
