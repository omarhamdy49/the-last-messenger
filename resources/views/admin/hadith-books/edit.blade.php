@extends('layouts.admin')

@section('title', 'Edit Hadith Book')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ activeLanguage: 'en', darkMode: localStorage.getItem('darkMode') === 'true' }">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="book-open" class="w-8 h-8 inline ml-2"></i>
            Edit Hadith Book
        </h1>
        <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Update hadith book information
        </p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.hadith-books.update', $book->id) }}" method="POST"
          class="glass-light rounded-lg p-8 max-w-4xl" :class="darkMode ? 'glass-dark' : ''">
        @csrf
        @method('PUT')

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

        <!-- Warning Messages -->
        @if($book->chapters_count > 0)
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border-r-4 border-yellow-500 text-yellow-800 dark:text-yellow-300 rounded-lg mb-6">
                <div class="flex items-center">
                    <i data-lucide="alert-triangle" class="w-5 h-5 ml-3"></i>
                    <span class="font-semibold">Warning: This book has {{ $book->chapters_count }} chapters. Deleting will remove all chapters and their hadith.</span>
                </div>
            </div>
        @endif

        @if($book->hadith_count > 0)
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border-r-4 border-blue-500 text-blue-800 dark:text-blue-300 rounded-lg mb-6">
                <div class="flex items-center">
                    <i data-lucide="info" class="w-5 h-5 ml-3"></i>
                    <span class="font-semibold">This book contains {{ $book->hadith_count }} hadith across all chapters</span>
                </div>
            </div>
        @endif

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
                           value="{{ old("name.$lang", $book->name[$lang] ?? '') }}"
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
                              dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">{{ old("description.$lang", $book->description[$lang] ?? '') }}</textarea>
                </div>

                <!-- Author Field -->
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Author ({{ strtoupper($lang) }})
                    </label>
                    <input type="text"
                           name="author[{{ $lang }}]"
                           value="{{ old("author.$lang", $book->author[$lang] ?? '') }}"
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
                    <option value="sahih" {{ old('book_type', $book->book_type) === 'sahih' ? 'selected' : '' }}>Sahih (الصحيح)</option>
                    <option value="sunan" {{ old('book_type', $book->book_type) === 'sunan' ? 'selected' : '' }}>Sunan (السنن)</option>
                    <option value="musnad" {{ old('book_type', $book->book_type) === 'musnad' ? 'selected' : '' }}>Musnad (المسند)</option>
                    <option value="jami" {{ old('book_type', $book->book_type) === 'jami' ? 'selected' : '' }}>Jami (الجامع)</option>
                    <option value="tafsir" {{ old('book_type', $book->book_type) === 'tafsir' ? 'selected' : '' }}>Tafsir (التفسير)</option>
                    <option value="other" {{ old('book_type', $book->book_type) === 'other' ? 'selected' : '' }}>Other (أخرى)</option>
                </select>
                @error('book_type')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Year of Compilation -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Year of Compilation (Hijri)
                </label>
                <input type="number"
                       name="year_of_compilation"
                       value="{{ old('year_of_compilation', $book->year_of_compilation) }}"
                       min="0"
                       max="1500"
                       placeholder="e.g., 256"
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <!-- Total Chapters (readonly - calculated) -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Total Chapters
                </label>
                <input type="number"
                       name="total_chapters"
                       value="{{ old('total_chapters', $book->total_chapters) }}"
                       min="0"
                       placeholder="e.g., 97"
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <p class="text-xs mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                    Current database count: <span class="font-bold text-[#0056b3]">{{ $book->chapters_count ?? 0 }}</span>
                </p>
            </div>

            <!-- Total Hadith (readonly - calculated) -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Total Hadith
                </label>
                <input type="number"
                       name="total_hadith"
                       value="{{ old('total_hadith', $book->total_hadith) }}"
                       min="0"
                       placeholder="e.g., 7563"
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <p class="text-xs mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                    Current database count: <span class="font-bold text-[#64CCC5]">{{ $book->hadith_count ?? 0 }}</span>
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
                    <option value="draft" {{ old('status', $book->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ old('status', $book->status) === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="archived" {{ old('status', $book->status) === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>

            <!-- Display Order -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Display Order
                </label>
                <input type="number"
                       name="display_order"
                       value="{{ old('display_order', $book->display_order ?? 0) }}"
                       min="0"
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex gap-4 mt-8">
            <button type="submit"
                    class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition">
                <i data-lucide="check" class="w-4 h-4 inline ml-2"></i>
                Update Hadith Book
            </button>
            <a href="{{ route('admin.hadith-books.index') }}"
               class="px-8 py-3 rounded-lg font-semibold border transition"
               :class="darkMode ? 'border-gray-700 text-white hover:bg-gray-800' : 'border-gray-300 text-gray-900 hover:bg-gray-100'">
                <i data-lucide="x" class="w-4 h-4 inline ml-2"></i>
                Cancel
            </a>
        </div>
    </form>

    <!-- Delete Button (Separate Form) -->
    <div class="glass-light rounded-lg p-6 max-w-4xl mt-6" :class="darkMode ? 'glass-dark' : ''">
        <h3 class="text-lg font-bold mb-2 text-red-600">Danger Zone</h3>
        <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Deleting this book will permanently remove all associated chapters and hadith. This action cannot be undone.
        </p>
        <form action="{{ route('admin.hadith-books.destroy', $book->id) }}" method="POST" class="inline">
            @csrf
            @method('DELETE')
            <button type="submit"
                    onclick="return confirm('Are you sure? This will delete {{ $book->chapters_count ?? 0 }} chapters and {{ $book->hadith_count ?? 0 }} hadith!')"
                    class="bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700 transition-all">
                <i data-lucide="trash-2" class="w-4 h-4 inline ml-2"></i>
                Delete This Book
            </button>
        </form>
    </div>
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
