@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.hadith.narrators.index') }}" class="hover:text-accent transition-colors">رواة الأحاديث</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ $narrator->name_ar ?? ($narrator->name['ar'] ?? '') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="user" class="w-7 h-7 text-white"></i>
            </div>
            {{ $narrator->name_ar ?? ($narrator->name['ar'] ?? '') }}
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.hadith.narrators.edit', $narrator->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="edit-2" class="w-5 h-5"></i>
            <span>{{ __('admin.edit') }}</span>
        </a>
        <a href="{{ route('admin.hadith.narrators.index') }}"
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
            <div class="relative h-32 bg-gradient-to-br from-[#0056b3] to-[#218838]">
                @if($narrator->reliability_grade)
                <div class="absolute top-4 start-4">
                    @php
                        $gradeValue = is_object($narrator->reliability_grade) ? $narrator->reliability_grade->value : $narrator->reliability_grade;
                        $gradeLabel = is_object($narrator->reliability_grade) ? $narrator->reliability_grade->labelArabic() : $gradeValue;
                        $badgeColors = [
                            'thiqah' => 'bg-green-500',
                            'saduq' => 'bg-blue-500',
                            'daif' => 'bg-yellow-500',
                            'matruk' => 'bg-red-500',
                        ];
                        $badgeColor = $badgeColors[$gradeValue] ?? 'bg-gray-500';
                    @endphp
                    <span class="inline-flex items-center gap-1 px-4 py-2 rounded-full text-sm font-semibold {{ $badgeColor }} text-white shadow-lg">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                        {{ $gradeLabel }}
                    </span>
                </div>
                @endif
            </div>

            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-2xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $narrator->name_ar ?? ($narrator->name['ar'] ?? '') }}
                        </h2>
                        <p class="text-lg mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ $narrator->name_en ?? ($narrator->name['en'] ?? '') }}
                        </p>
                        @if($narrator->kunya_ar)
                        <p class="text-sm mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                            الكنية: {{ $narrator->kunya_ar }}
                        </p>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        @if(($narrator->status ?? 'active') === 'active')
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

                <!-- Details Row -->
                <div class="flex flex-wrap gap-3 mt-6">
                    @if($narrator->era)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl"
                          :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                        <i data-lucide="clock" class="w-4 h-4 text-accent"></i>
                        {{ $narrator->era }}
                    </span>
                    @endif
                    @if($narrator->country)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl"
                          :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                        <i data-lucide="map-pin" class="w-4 h-4 text-accent"></i>
                        {{ $narrator->country }}
                    </span>
                    @endif
                    @if($narrator->birth_hijri ?? $narrator->birth_year_hijri)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl"
                          :class="darkMode ? 'bg-green-900/30 text-green-400' : 'bg-green-100 text-green-800'">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        ولد: {{ $narrator->birth_hijri ?? $narrator->birth_year_hijri }} هـ
                    </span>
                    @endif
                    @if($narrator->death_hijri ?? $narrator->death_year_hijri)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl"
                          :class="darkMode ? 'bg-red-900/30 text-red-400' : 'bg-red-100 text-red-800'">
                        <i data-lucide="calendar-x" class="w-4 h-4"></i>
                        توفي: {{ $narrator->death_hijri ?? $narrator->death_year_hijri }} هـ
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Biography -->
        @php
            $hasBio = is_array($narrator->biography) && count(array_filter($narrator->biography));
        @endphp
        @if($hasBio)
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="file-text" class="w-5 h-5 text-accent"></i>
                السيرة الذاتية
            </h2>

            @if($hasBio)
            <div x-data="{ activeTab: 'ar' }" class="space-y-4">
                <div class="flex flex-wrap gap-2 border-b pb-4" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                        @if(!empty($narrator->biography[$lang]))
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

                @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                    @if(!empty($narrator->biography[$lang]))
                    <div x-show="activeTab === '{{ $lang }}'"
                         class="p-4 rounded-xl"
                         :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <p class="leading-relaxed {{ in_array($lang, ['ar', 'ur']) ? 'text-right' : 'text-left' }}"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                           dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                            {{ $narrator->biography[$lang] }}
                        </p>
                    </div>
                    @endif
                @endforeach
            </div>
            @endif
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
                        $name = $narrator->{"name_{$lang}"} ?? ($narrator->name[$lang] ?? null);
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

        <!-- Narrator Info -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                معلومات
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">عدد الأحاديث</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $narrator->hadiths_count ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الرابط المختصر</span>
                    <span class="font-mono text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $narrator->slug ?? '-' }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تاريخ الإضافة</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $narrator->created_at?->format('Y/m/d') }}</span>
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
                <a href="{{ route('admin.hadith.narrators.edit', $narrator->id) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="edit-2" class="w-5 h-5 text-accent"></i>
                    <span>تعديل الراوي</span>
                </a>

                <a href="{{ route('admin.hadith.narrators.create') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="plus" class="w-5 h-5 text-accent"></i>
                    <span>إضافة راوي جديد</span>
                </a>
            </div>
        </div>

        <!-- Delete -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-red-800/50' : 'bg-white border-red-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-red-500">
                <i data-lucide="trash-2" class="w-5 h-5"></i>
                حذف الراوي
            </h3>

            <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                سيتم حذف هذا الراوي نهائياً ولا يمكن استرجاعه.
            </p>

            <form action="{{ route('admin.hadith.narrators.destroy', $narrator->id) }}"
                  method="POST"
                  @submit.prevent="$dispatch('show-confirm', { title: '{{ __("admin.confirm_delete") }}', message: '{{ __("admin.delete_warning") }}', type: 'danger', onConfirm: () => $el.submit() })">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 bg-red-500 hover:bg-red-600 text-white flex items-center justify-center gap-2">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    <span>حذف الراوي</span>
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
