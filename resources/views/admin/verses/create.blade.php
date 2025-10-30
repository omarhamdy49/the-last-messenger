@extends('layouts.admin')

@section('title', 'إضافة آية جديدة')

@section('content')
<div class="max-w-6xl mx-auto" x-data="verseForm()" x-init="init()">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">إضافة آية جديدة</h1>
                <p class="text-gray-600 dark:text-gray-400">إضافة آية من القرآن الكريم</p>
            </div>
            <a href="{{ route('admin.verses.index') }}"
               class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
                <span>العودة للقائمة</span>
            </a>
        </div>
    </div>

    <form action="{{ route('admin.verses.store') }}" method="POST" @submit="handleSubmit" class="space-y-6">
        @csrf

        {{-- Basic Information Card --}}
        <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="info" class="w-6 h-6"></i>
                المعلومات الأساسية
            </h2>

            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            <span class="text-red-400">*</span> السورة
                        </label>
                        <select
                            name="surah_id"
                            class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="darkMode ? 'bg-white/10 border border-white/20 text-white' : 'glass-light-strong text-gray-900'"
                            required
                        >
                            <option value="">اختر السورة</option>
                            @foreach($surahs ?? [] as $surah)
                                <option value="{{ $surah->id }}" {{ old('surah_id') == $surah->id ? 'selected' : '' }}>
                                    {{ $surah->number }}. {{ $surah->name['ar'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('surah_id')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            <span class="text-red-400">*</span> رقم الآية
                        </label>
                        <input
                            type="number"
                            name="verse_number"
                            value="{{ old('verse_number') }}"
                            min="1"
                            class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'"
                            placeholder="1"
                            required
                        >
                        @error('verse_number')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الجزء
                        </label>
                        <input
                            type="number"
                            name="juz_number"
                            value="{{ old('juz_number') }}"
                            min="1"
                            max="30"
                            class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'"
                        >
                        @error('juz_number')
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
                            value="{{ old('page_number') }}"
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

        {{-- Verse Text Card --}}
        <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="languages" class="w-6 h-6"></i>
                نص الآية متعدد اللغات
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
                                    <span class="text-red-400">*</span> نص الآية ({{ $lang }})
                                </label>
                                <textarea
                                    name="text[{{ $lang }}]"
                                    rows="6"
                                    class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'"
                                    dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                    required
                                >{{ old('text.' . $lang) }}</textarea>
                                @error('text.' . $lang)
                                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            @if($lang === 'ar')
                                <div>
                                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        الترجمة الصوتية (Transliteration)
                                    </label>
                                    <textarea
                                        name="transliteration"
                                        rows="3"
                                        class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'"
                                        placeholder="Bismillahir Rahmanir Rahim"
                                    >{{ old('transliteration') }}</textarea>
                                    @error('transliteration')
                                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <a href="{{ route('admin.verses.index') }}" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors flex items-center gap-2">
                    <i data-lucide="x" class="w-5 h-5"></i>
                    إلغاء
                </a>

                <button type="submit" class="px-8 py-3 gradient-primary text-white rounded-lg font-semibold flex items-center gap-2 hover:shadow-lg transition-all" x-bind:disabled="isSubmitting">
                    <i data-lucide="check-circle" class="w-5 h-5" x-show="!isSubmitting"></i>
                    <span x-text="isSubmitting ? 'جاري الحفظ...' : 'حفظ الآية'"></span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function verseForm() {
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
