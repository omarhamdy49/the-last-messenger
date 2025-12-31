@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.tafseer.index') }}" class="hover:text-accent transition-colors">{{ __('quran.tafseer.title') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ __('quran.actions.add_tafseer') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="plus" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('quran.actions.add_tafseer') }}
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.tafseer.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="x" class="w-5 h-5"></i>
            <span>{{ __('admin.cancel') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.tafseer.store') }}" method="POST">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Verse Selection -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="text" class="w-5 h-5 text-accent"></i>
                    اختيار الآية
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Surah Selection -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            السورة <span class="text-red-500">*</span>
                        </label>
                        <select name="surah_id"
                                id="surah_id"
                                class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                required>
                            <option value="">{{ __('quran.placeholders.select_surah') }}</option>
                            @foreach($surahs ?? [] as $surah)
                                <option value="{{ $surah->id }}" {{ old('surah_id', request('surah_id')) == $surah->id ? 'selected' : '' }}>
                                    {{ $surah->number }}. {{ $surah->name_ar }}
                                </option>
                            @endforeach
                        </select>
                        @error('surah_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Verse Selection -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الآية <span class="text-red-500">*</span>
                        </label>
                        <select name="verse_id"
                                id="verse_id"
                                class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                required>
                            <option value="">اختر الآية</option>
                            @if(old('verse_id') || request('verse_id'))
                                <option value="{{ old('verse_id', request('verse_id')) }}" selected>
                                    آية {{ old('verse_id', request('verse_id')) }}
                                </option>
                            @endif
                        </select>
                        @error('verse_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Language Selection -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.tafseer.language') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="lang"
                                class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                required>
                            <option value="ar" {{ old('lang') == 'ar' ? 'selected' : '' }}>العربية</option>
                            <option value="en" {{ old('lang') == 'en' ? 'selected' : '' }}>English</option>
                            <option value="fr" {{ old('lang') == 'fr' ? 'selected' : '' }}>Français</option>
                            <option value="tr" {{ old('lang') == 'tr' ? 'selected' : '' }}>Türkçe</option>
                            <option value="ur" {{ old('lang') == 'ur' ? 'selected' : '' }}>اردو</option>
                        </select>
                        @error('lang')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Active Status -->
                    <div class="flex items-end">
                        <label class="flex items-center gap-3 cursor-pointer px-4 py-3 rounded-xl border transition-all"
                               :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700' : 'border-gray-300 bg-white hover:bg-gray-50'">
                            <input type="checkbox"
                                   name="active"
                                   value="1"
                                   {{ old('active', true) ? 'checked' : '' }}
                                   class="w-5 h-5 rounded text-accent border-gray-300 focus:ring-accent">
                            <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">نشط</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Tafseer Text -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="message-square-text" class="w-5 h-5 text-accent"></i>
                    نص التفسير
                </h2>

                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('quran.tafseer.text') }} <span class="text-red-500">*</span>
                    </label>
                    <textarea name="text"
                              rows="12"
                              class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all resize-none"
                              :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                              dir="rtl"
                              placeholder="أدخل نص التفسير..."
                              required>{{ old('text') }}</textarea>
                    @error('text')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Submit Button -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <button type="submit"
                        class="w-full px-6 py-3 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    {{ __('admin.save') }}
                </button>

                <a href="{{ route('admin.tafseer.index') }}"
                   class="mt-4 w-full px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border flex items-center justify-center gap-2"
                   :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                    {{ __('admin.cancel') }}
                </a>
            </div>

            <!-- Famous Tafseer Books -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="book" class="w-5 h-5 text-accent"></i>
                    كتب التفسير المشهورة
                </h3>

                <ul class="space-y-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    <li class="flex items-center gap-2">
                        <i data-lucide="bookmark" class="w-4 h-4 text-accent"></i>
                        {{ __('quran.tafseer.ibn_kathir') }}
                    </li>
                    <li class="flex items-center gap-2">
                        <i data-lucide="bookmark" class="w-4 h-4 text-accent"></i>
                        {{ __('quran.tafseer.tabari') }}
                    </li>
                    <li class="flex items-center gap-2">
                        <i data-lucide="bookmark" class="w-4 h-4 text-accent"></i>
                        {{ __('quran.tafseer.qurtubi') }}
                    </li>
                    <li class="flex items-center gap-2">
                        <i data-lucide="bookmark" class="w-4 h-4 text-accent"></i>
                        {{ __('quran.tafseer.jalalayn') }}
                    </li>
                    <li class="flex items-center gap-2">
                        <i data-lucide="bookmark" class="w-4 h-4 text-accent"></i>
                        {{ __('quran.tafseer.saadi') }}
                    </li>
                </ul>
            </div>

            <!-- Help -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="help-circle" class="w-5 h-5 text-accent"></i>
                    تعليمات
                </h3>

                <ul class="space-y-3 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-0.5 text-green-500"></i>
                        <span>اختر السورة ثم الآية المراد تفسيرها</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-0.5 text-green-500"></i>
                        <span>حدد لغة التفسير</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 mt-0.5 text-green-500"></i>
                        <span>أدخل نص التفسير بالكامل</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="info" class="w-4 h-4 mt-0.5 text-blue-500"></i>
                        <span>يمكن إضافة تفسير واحد فقط لكل آية بكل لغة</span>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();

        // Dynamic verse loading when surah changes
        const surahSelect = document.getElementById('surah_id');
        const verseSelect = document.getElementById('verse_id');

        if (surahSelect && verseSelect) {
            surahSelect.addEventListener('change', function() {
                const surahId = this.value;
                if (!surahId) {
                    verseSelect.innerHTML = '<option value="">اختر الآية</option>';
                    return;
                }

                // Fetch verses for this surah
                fetch(`/su/portal/v1/admin/verses/search?surah_id=${surahId}&per_page=300`)
                    .then(response => response.json())
                    .then(data => {
                        verseSelect.innerHTML = '<option value="">اختر الآية</option>';
                        if (data.data) {
                            data.data.forEach(verse => {
                                const option = document.createElement('option');
                                option.value = verse.id;
                                option.textContent = `آية ${verse.verse_number}`;
                                verseSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        }
    });
</script>
@endpush
