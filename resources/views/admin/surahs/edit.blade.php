@extends('layouts.admin')

@section('title', 'تعديل السورة - ' . ($surah->name['ar'] ?? 'السورة'))

@section('content')
<div class="max-w-4xl mx-auto" x-data="surahForm()" x-init="init()">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">تعديل السورة</h1>
                <p class="text-gray-600 dark:text-gray-400">تحديث بيانات السورة</p>
            </div>
            <a href="{{ route('admin.surahs.index') }}"
               class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
                <span>العودة للقائمة</span>
            </a>
        </div>
    </div>

    <form action="{{ route('admin.surahs.update', $surah) }}" method="POST" @submit="handleSubmit" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Basic Information Card --}}
        <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="book" class="w-6 h-6"></i>
                المعلومات الأساسية
            </h2>

            <div class="space-y-6">
                {{-- Surah Number & Order Row --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            <span class="text-red-400">*</span> رقم السورة
                        </label>
                        <input
                            type="number"
                            name="number"
                            value="{{ old('number', $surah->number) }}"
                            min="1"
                            max="114"
                            class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'"
                            required
                            readonly
                        >
                        @error('number')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            ترتيب النزول
                        </label>
                        <input
                            type="number"
                            name="revelation_order"
                            value="{{ old('revelation_order', $surah->revelation_order) }}"
                            min="1"
                            max="114"
                            class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'"
                        >
                        @error('revelation_order')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            <span class="text-red-400">*</span> مكان النزول
                        </label>
                        <select
                            name="revelation_place"
                            class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="darkMode ? 'bg-white/10 border border-white/20 text-white' : 'glass-light-strong text-gray-900'"
                            required
                        >
                            <option value="makkah" {{ old('revelation_place', $surah->revelation_place) === 'makkah' ? 'selected' : '' }}>مكية</option>
                            <option value="madinah" {{ old('revelation_place', $surah->revelation_place) === 'madinah' ? 'selected' : '' }}>مدنية</option>
                        </select>
                        @error('revelation_place')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Verses & Page Row --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            <span class="text-red-400">*</span> عدد الآيات
                        </label>
                        <input
                            type="number"
                            name="verses_count"
                            value="{{ old('verses_count', $surah->verses_count) }}"
                            min="3"
                            max="286"
                            class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'"
                            required
                        >
                        @error('verses_count')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            رقم الصفحة
                        </label>
                        <input
                            type="number"
                            name="page_number"
                            value="{{ old('page_number', $surah->page_number) }}"
                            min="1"
                            max="604"
                            class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'"
                        >
                        @error('page_number')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Multi-Language Names Card --}}
        <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="languages" class="w-6 h-6"></i>
                أسماء السورة متعددة اللغات
            </h2>

            {{-- Language Tabs --}}
            <div class="flex flex-wrap gap-2 mb-6" x-data="{ activeTab: 'ar' }">
                @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                    <button
                        type="button"
                        @click="activeTab = '{{ $lang }}'"
                        :class="activeTab === '{{ $lang }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'glass-dark text-gray-400' : 'glass-light-soft text-gray-600')"
                        class="px-6 py-2 rounded-full font-semibold transition-all hover:scale-105"
                    >
                        {{ $label }}
                    </button>
                @endforeach

                {{-- Language Content --}}
                @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                    <div x-show="activeTab === '{{ $lang }}'" x-cloak class="w-full mt-4">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    <span class="text-red-400">*</span> اسم السورة ({{ $lang }})
                                </label>
                                <input
                                    type="text"
                                    name="name[{{ $lang }}]"
                                    value="{{ old('name.' . $lang, $surah->name[$lang] ?? '') }}"
                                    class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'"
                                    dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                    required
                                >
                                @error('name.' . $lang)
                                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    الترجمة ({{ $lang }})
                                </label>
                                <input
                                    type="text"
                                    name="translation[{{ $lang }}]"
                                    value="{{ old('translation.' . $lang, $surah->translation[$lang] ?? '') }}"
                                    class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'"
                                    dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                >
                                @error('translation.' . $lang)
                                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    الوصف ({{ $lang }})
                                </label>
                                <textarea
                                    name="description[{{ $lang }}]"
                                    rows="4"
                                    class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'"
                                    dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                >{{ old('description.' . $lang, $surah->description[$lang] ?? '') }}</textarea>
                                @error('description.' . $lang)
                                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Additional Info Card --}}
        <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="info" class="w-6 h-6"></i>
                معلومات إضافية
            </h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        الرسم العثماني
                    </label>
                    <input
                        type="text"
                        name="name_transliteration"
                        value="{{ old('name_transliteration', $surah->name_transliteration) }}"
                        class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'"
                        placeholder="Al-Fatihah"
                    >
                    @error('name_transliteration')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <a href="{{ route('admin.surahs.index') }}" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors flex items-center gap-2">
                    <i data-lucide="x" class="w-5 h-5"></i>
                    إلغاء
                </a>

                <button type="submit" class="px-8 py-3 gradient-primary text-white rounded-lg font-semibold flex items-center gap-2 hover:shadow-lg transition-all" x-bind:disabled="isSubmitting">
                    <i data-lucide="check-circle" class="w-5 h-5" x-show="!isSubmitting"></i>
                    <span x-text="isSubmitting ? 'جاري الحفظ...' : 'حفظ التغييرات'"></span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function surahForm() {
    return {
        isSubmitting: false,

        handleSubmit() {
            this.isSubmitting = true;
            return true;
        },

        init() {
            this.$nextTick(() => lucide.createIcons());
        }
    }
}
</script>
@endpush
