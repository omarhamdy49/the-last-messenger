@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.tafseer.index') }}" class="hover:text-accent transition-colors">{{ __('quran.tafseer.title') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <a href="{{ route('admin.tafseer.show', $tafsir->id) }}" class="hover:text-accent transition-colors">{{ $tafsir->verse_key }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ __('admin.edit') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="edit-2" class="w-7 h-7 text-white"></i>
            </div>
            تعديل تفسير الآية {{ $tafsir->verse_key }}
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.tafseer.show', $tafsir->id) }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="x" class="w-5 h-5"></i>
            <span>{{ __('admin.cancel') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.tafseer.update', $tafsir->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Verse Info (Read-only) -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="text" class="w-5 h-5 text-accent"></i>
                    معلومات الآية
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Surah (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            السورة
                        </label>
                        <div class="px-4 py-3 rounded-xl" :class="darkMode ? 'bg-gray-800/70' : 'bg-gray-100'">
                            <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $tafsir->verse?->surah?->name_ar ?? '-' }}</span>
                        </div>
                    </div>

                    <!-- Verse (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الآية
                        </label>
                        <div class="px-4 py-3 rounded-xl" :class="darkMode ? 'bg-gray-800/70' : 'bg-gray-100'">
                            <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $tafsir->verse_key }}</span>
                        </div>
                        <input type="hidden" name="verse_id" value="{{ $tafsir->verse_id }}">
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
                            <option value="ar" {{ old('lang', $tafsir->lang) == 'ar' ? 'selected' : '' }}>العربية</option>
                            <option value="en" {{ old('lang', $tafsir->lang) == 'en' ? 'selected' : '' }}>English</option>
                            <option value="fr" {{ old('lang', $tafsir->lang) == 'fr' ? 'selected' : '' }}>Français</option>
                            <option value="tr" {{ old('lang', $tafsir->lang) == 'tr' ? 'selected' : '' }}>Türkçe</option>
                            <option value="ur" {{ old('lang', $tafsir->lang) == 'ur' ? 'selected' : '' }}>اردو</option>
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
                                   {{ old('active', $tafsir->active) ? 'checked' : '' }}
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
                              rows="15"
                              class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all resize-none"
                              :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                              dir="rtl"
                              placeholder="أدخل نص التفسير..."
                              required>{{ old('text', $tafsir->text) }}</textarea>
                    @error('text')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Tafseer Info -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                    معلومات
                </h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تاريخ الإنشاء</span>
                        <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $tafsir->created_at?->format('Y/m/d') ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">آخر تحديث</span>
                        <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $tafsir->updated_at?->format('Y/m/d') ?? '-' }}</span>
                    </div>
                </div>
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
                    <a href="{{ route('admin.tafseer.show', $tafsir->id) }}"
                       class="flex-1 px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border"
                       :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                        {{ __('admin.cancel') }}
                    </a>
                    <a href="{{ route('admin.tafseer.index') }}"
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
