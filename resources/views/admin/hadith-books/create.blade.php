@extends('layouts.admin')

@section('title', 'Create Hadith Book')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ activeLanguage: 'en', darkMode: localStorage.getItem('darkMode') === 'true' }">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="book-open" class="w-8 h-8 inline ml-2"></i>
            Create New Hadith Book
        </h1>
        <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Add a new hadith book collection to the database
        </p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.hadith-books.store') }}" method="POST"
          class="glass-light rounded-lg p-8 max-w-4xl" :class="darkMode ? 'glass-dark' : ''">
        @csrf

        <!-- Language Tabs -->
        <div class="flex gap-2 mb-6 border-b" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                <button type="button"
                        @click="activeLanguage = '{{ $lang }}'; localStorage.setItem('activeLanguage', '{{ $lang }}')"
                        :class="activeLanguage === '{{ $lang }}' ? 'border-b-2 border-[#0056b3] font-bold' : ''"
                        class="px-4 py-2 transition"
                        :class="darkMode ? 'text-white hover:text-[#64CCC5]' : 'text-gray-700 hover:text-[#0056b3]'">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <!-- Multi-language Fields -->
        @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
            <div x-show="activeLanguage === '{{ $lang }}'" class="space-y-6">
                <!-- Name Field -->
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Book Name ({{ strtoupper($lang) }}) <span class="text-red-600">*</span>
                    </label>
                    <input type="text"
                           name="name[{{ $lang }}]"
                           value="{{ old("name.$lang") }}"
                           {{ in_array($lang, ['ar', 'en']) ? 'required' : '' }}
                           placeholder="e.g., Sahih al-Bukhari, Sahih Muslim..."
                           class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] @error("name.$lang") border-red-500 @enderror"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                           dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                    @error("name.$lang")
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description Field -->
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Description ({{ strtoupper($lang) }})
                    </label>
                    <textarea name="description[{{ $lang }}]"
                              rows="4"
                              placeholder="Brief description of the book's history and significance..."
                              class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                              :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                              dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">{{ old("description.$lang") }}</textarea>
                </div>

                <!-- Author Field -->
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Author ({{ strtoupper($lang) }})
                    </label>
                    <input type="text"
                           name="author[{{ $lang }}]"
                           value="{{ old("author.$lang") }}"
                           placeholder="e.g., Imam al-Bukhari, Imam Muslim..."
                           class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                           dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                </div>
            </div>
        @endforeach

        <!-- Other Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <!-- Book Type -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Book Type <span class="text-red-600">*</span>
                </label>
                <select name="book_type"
                        required
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] @error('book_type') border-red-500 @enderror"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">Select Book Type</option>
                    <option value="sahih" {{ old('book_type') === 'sahih' ? 'selected' : '' }}>Sahih (الصحيح)</option>
                    <option value="sunan" {{ old('book_type') === 'sunan' ? 'selected' : '' }}>Sunan (السنن)</option>
                    <option value="musnad" {{ old('book_type') === 'musnad' ? 'selected' : '' }}>Musnad (المسند)</option>
                    <option value="jami" {{ old('book_type') === 'jami' ? 'selected' : '' }}>Jami (الجامع)</option>
                    <option value="tafsir" {{ old('book_type') === 'tafsir' ? 'selected' : '' }}>Tafsir (التفسير)</option>
                    <option value="other" {{ old('book_type') === 'other' ? 'selected' : '' }}>Other (أخرى)</option>
                </select>
                @error('book_type')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                    <i data-lucide="info" class="w-3 h-3 inline"></i>
                    Choose the classification of this hadith book
                </p>
            </div>

            <!-- Year of Compilation -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Year of Compilation (Hijri)
                </label>
                <input type="number"
                       name="year_of_compilation"
                       value="{{ old('year_of_compilation') }}"
                       min="0"
                       max="1500"
                       placeholder="e.g., 256"
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <p class="text-xs mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                    Year in Hijri calendar (optional)
                </p>
            </div>

            <!-- Total Chapters -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Total Chapters
                </label>
                <input type="number"
                       name="total_chapters"
                       value="{{ old('total_chapters') }}"
                       min="0"
                       placeholder="e.g., 97"
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <p class="text-xs mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                    For reference; actual count will be calculated from database
                </p>
            </div>

            <!-- Total Hadith -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Total Hadith
                </label>
                <input type="number"
                       name="total_hadith"
                       value="{{ old('total_hadith') }}"
                       min="0"
                       placeholder="e.g., 7563"
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <p class="text-xs mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                    For reference; actual count will be calculated from database
                </p>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Status <span class="text-red-600">*</span>
                </label>
                <select name="status"
                        required
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>

            <!-- Display Order -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Display Order
                </label>
                <input type="number"
                       name="display_order"
                       value="{{ old('display_order', 0) }}"
                       min="0"
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <p class="text-xs mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                    Lower numbers appear first in lists
                </p>
            </div>
        </div>

        <!-- Help Section -->
        <div class="mt-6 p-4 rounded-lg border-r-4 border-[#0056b3]" :class="darkMode ? 'bg-blue-900/20 text-blue-300' : 'bg-blue-50 text-blue-800'">
            <div class="flex items-start">
                <i data-lucide="info" class="w-5 h-5 ml-3 mt-0.5"></i>
                <div>
                    <p class="font-semibold mb-1">Important Notes:</p>
                    <ul class="text-sm space-y-1 list-disc list-inside">
                        <li>Arabic and English names are required</li>
                        <li>Total chapters and hadith are for reference only</li>
                        <li>Actual counts will be calculated from database entries</li>
                        <li>Use "Draft" status while entering chapters and hadith</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex gap-4 mt-8">
            <button type="submit"
                    class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition">
                <i data-lucide="check" class="w-4 h-4 inline ml-2"></i>
                Create Hadith Book
            </button>
            <a href="{{ route('admin.hadith-books.index') }}"
               class="px-8 py-3 rounded-lg font-semibold border transition"
               :class="darkMode ? 'border-gray-700 text-white hover:bg-gray-800' : 'border-gray-300 text-gray-900 hover:bg-gray-100'">
                <i data-lucide="x" class="w-4 h-4 inline ml-2"></i>
                Cancel
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Restore last selected language tab
    document.addEventListener('alpine:init', () => {
        const savedLang = localStorage.getItem('activeLanguage');
        if (savedLang) {
            Alpine.store('activeLanguage', savedLang);
        }
    });

    lucide.createIcons();
</script>
@endpush
@endsection
