@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.surahs.index') }}" class="hover:text-accent transition-colors">{{ __('quran.surahs.title') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <a href="{{ route('admin.surahs.show', $surah->id) }}" class="hover:text-accent transition-colors">{{ $surah->name_ar }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ __('admin.edit') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="edit-2" class="w-7 h-7 text-white"></i>
            </div>
            تعديل سورة {{ $surah->name_ar }}
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.surahs.show', $surah->id) }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="x" class="w-5 h-5"></i>
            <span>{{ __('admin.cancel') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.surahs.update', $surah->id) }}" method="POST" x-data="{ activeTab: 'ar' }">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Basic Info -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                    المعلومات الأساسية
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Arabic Name -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.surahs.name_arabic') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="name_ar"
                               value="{{ old('name_ar', $surah->name_ar) }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all text-xl"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               required>
                        @error('name_ar')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- English Name -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.surahs.name_translation') }}
                        </label>
                        <input type="text"
                               name="name_en"
                               value="{{ old('name_en', $surah->name_en) }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('name_en')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Transliteration -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.surahs.name_transliteration') }}
                        </label>
                        <input type="text"
                               name="transliteration"
                               value="{{ old('transliteration', $surah->transliteration) }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('transliteration')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Revelation Place -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.surahs.revelation_place') }}
                        </label>
                        <select name="revelation_place"
                                class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="makkah" {{ old('revelation_place', $surah->revelation_place) == 'makkah' ? 'selected' : '' }}>
                                {{ __('quran.surahs.makkah') }}
                            </option>
                            <option value="madinah" {{ old('revelation_place', $surah->revelation_place) == 'madinah' ? 'selected' : '' }}>
                                {{ __('quran.surahs.madinah') }}
                            </option>
                        </select>
                        @error('revelation_place')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Revelation Order -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.surahs.revelation_order') }}
                        </label>
                        <input type="number"
                               name="revelation_order"
                               value="{{ old('revelation_order', $surah->revelation_order) }}"
                               min="1"
                               max="114"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                            {{ __('quran.help.revelation_order') }}
                        </p>
                        @error('revelation_order')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Bismillah -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            البسملة
                        </label>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="bismillah_pre" value="1"
                                       {{ old('bismillah_pre', $surah->bismillah_pre) ? 'checked' : '' }}
                                       class="w-5 h-5 text-accent border-gray-300 focus:ring-accent">
                                <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">نعم</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="bismillah_pre" value="0"
                                       {{ !old('bismillah_pre', $surah->bismillah_pre) ? 'checked' : '' }}
                                       class="w-5 h-5 text-accent border-gray-300 focus:ring-accent">
                                <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">لا</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Surah Description (Multi-language) -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="file-text" class="w-5 h-5 text-accent"></i>
                    وصف السورة
                </h2>

                <!-- Language Tabs -->
                <div class="flex flex-wrap gap-2 border-b pb-4 mb-6" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                    <button type="button"
                            @click="activeTab = '{{ $lang }}'"
                            class="px-4 py-2 rounded-lg font-medium transition-all"
                            :class="activeTab === '{{ $lang }}' ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' :
                                (darkMode ? 'bg-gray-800 text-gray-400 hover:text-white' : 'bg-gray-100 text-gray-600 hover:text-gray-900')">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>

                <!-- Tab Contents -->
                @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                @php
                    $info = $surah->info?->where('lang', $lang)->first();
                @endphp
                <div x-show="activeTab === '{{ $lang }}'" class="space-y-4">
                    <!-- Short Description -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الوصف المختصر ({{ strtoupper($lang) }})
                        </label>
                        <textarea name="info[{{ $lang }}][desc]"
                                  rows="3"
                                  class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all resize-none"
                                  :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                  placeholder="أدخل وصفاً مختصراً للسورة...">{{ old("info.{$lang}.desc", $info?->desc) }}</textarea>
                    </div>

                    <!-- Full Content -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            المحتوى الكامل ({{ strtoupper($lang) }})
                        </label>
                        <textarea name="info[{{ $lang }}][content]"
                                  rows="8"
                                  class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all resize-none"
                                  :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                  placeholder="أدخل المحتوى الكامل والتفصيلي...">{{ old("info.{$lang}.content", $info?->content) }}</textarea>
                    </div>

                    <!-- Source -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            المصدر ({{ strtoupper($lang) }})
                        </label>
                        <input type="text"
                               name="info[{{ $lang }}][source]"
                               value="{{ old("info.{$lang}.source", $info?->source) }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="مصدر المعلومات...">
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Surah Info (Read-only) -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="lock" class="w-5 h-5 text-accent"></i>
                    بيانات ثابتة
                </h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">رقم السورة</span>
                        <span class="font-bold text-xl" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $surah->number }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">عدد الآيات</span>
                        <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $surah->verses_count }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الصفحات</span>
                        <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            @if($surah->pages && is_array($surah->pages))
                                {{ implode(' - ', $surah->pages) }}
                            @else
                                -
                            @endif
                        </span>
                    </div>
                </div>

                <p class="mt-4 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                    <i data-lucide="info" class="w-3 h-3 inline"></i>
                    هذه البيانات ثابتة ولا يمكن تعديلها
                </p>
            </div>

            <!-- Submit Button -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <button type="submit"
                        class="w-full px-6 py-3 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    {{ __('admin.save') }}
                </button>

                <div class="flex gap-3 mt-4">
                    <a href="{{ route('admin.surahs.show', $surah->id) }}"
                       class="flex-1 px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border"
                       :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                        {{ __('admin.cancel') }}
                    </a>
                    <a href="{{ route('admin.surahs.index') }}"
                       class="flex-1 px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border"
                       :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                        القائمة
                    </a>
                </div>
            </div>

        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
