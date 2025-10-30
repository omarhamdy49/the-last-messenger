@extends('layouts.admin')

@section('title', 'Create Hadith Chapter')

@section('content')
<div x-data="chapterForm()" x-init="init()">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center space-x-3 space-x-reverse mb-4">
            <a href="{{ route('admin.hadith-chapters.index') }}"
               class="text-gray-400 hover:text-white transition">
                <i data-lucide="arrow-left" class="w-6 h-6"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white">Create New Hadith Chapter</h1>
                <p class="mt-2 text-gray-400">Add a new chapter to organize hadiths by book</p>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.hadith-chapters.store') }}"
          method="POST"
          class="max-w-5xl transition-colors rounded-2xl p-8"
          :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        @csrf

        {{-- Language Tabs --}}
        <div class="flex gap-2 mb-6 border-b transition-colors"
             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                <button type="button"
                        @click="activeLanguage = '{{ $lang }}'"
                        :class="activeLanguage === '{{ $lang }}' ? 'border-b-2 border-[#0056b3] font-bold text-white' : 'text-gray-400'"
                        class="px-6 py-3 transition hover:text-white">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Multi-language Fields (Name & Description) --}}
        <div class="space-y-6 mb-8">
            @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                <div x-show="activeLanguage === '{{ $lang }}'">
                    {{-- Chapter Name --}}
                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-2 text-gray-300">
                            Chapter Name ({{ strtoupper($lang) }})
                            @if(in_array($lang, ['ar', 'en']))
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <input type="text"
                               name="name[{{ $lang }}]"
                               value="{{ old("name.$lang") }}"
                               @if(in_array($lang, ['ar', 'en'])) required @endif
                               placeholder="Enter chapter name in {{ strtoupper($lang) }}"
                               class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                        @error("name.$lang")
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Chapter Description --}}
                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-2 text-gray-300">
                            Chapter Description ({{ strtoupper($lang) }})
                        </label>
                        <textarea name="description[{{ $lang }}]"
                                  rows="4"
                                  placeholder="Enter chapter description in {{ strtoupper($lang) }}"
                                  class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                                  :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">{{ old("description.$lang") }}</textarea>
                        @error("description.$lang")
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Non-translatable Fields --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            {{-- Book Selection --}}
            <div>
                <label class="block text-sm font-semibold mb-2 text-gray-300">
                    Hadith Book
                    <span class="text-red-500">*</span>
                </label>
                <select name="hadith_book_id"
                        required
                        x-model="selectedBook"
                        class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">Select a book...</option>
                    @foreach($books as $book)
                        <option value="{{ $book->id }}"
                                {{ old('hadith_book_id') == $book->id ? 'selected' : '' }}>
                            {{ $book->name['en'] ?? $book->name['ar'] }}
                        </option>
                    @endforeach
                </select>
                @error('hadith_book_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Chapter Number --}}
            <div>
                <label class="block text-sm font-semibold mb-2 text-gray-300">
                    Chapter Number
                    <span class="text-red-500">*</span>
                </label>
                <input type="number"
                       name="chapter_number"
                       value="{{ old('chapter_number') }}"
                       required
                       min="1"
                       placeholder="e.g., 1, 2, 3..."
                       class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                @error('chapter_number')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-sm mt-1 text-gray-400">Must be unique within the selected book</p>
            </div>

            {{-- Total Hadiths (Optional) --}}
            <div>
                <label class="block text-sm font-semibold mb-2 text-gray-300">
                    Total Hadiths
                    <span class="text-gray-500 text-xs">(Optional)</span>
                </label>
                <input type="number"
                       name="total_hadiths"
                       value="{{ old('total_hadiths', 0) }}"
                       min="0"
                       placeholder="Number of hadiths in this chapter"
                       class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                @error('total_hadiths')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-semibold mb-2 text-gray-300">
                    Status
                </label>
                <select name="status"
                        class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ old('status', 'published') == 'published' ? 'selected' : '' }}>Published</option>
                    <option value="archived" {{ old('status') == 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
                @error('status')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Display Order --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-2 text-gray-300">
                    Display Order
                    <span class="text-gray-500 text-xs">(Optional)</span>
                </label>
                <input type="number"
                       name="display_order"
                       value="{{ old('display_order', 0) }}"
                       min="0"
                       placeholder="0 for default ordering"
                       class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                @error('display_order')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex gap-4 mt-8 pt-6 border-t"
             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <button type="submit"
                    class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition-all duration-300 flex items-center space-x-2 space-x-reverse">
                <i data-lucide="save" class="w-5 h-5"></i>
                <span>Create Chapter</span>
            </button>
            <a href="{{ route('admin.hadith-chapters.index') }}"
               class="px-8 py-3 rounded-lg font-semibold border transition-colors flex items-center space-x-2 space-x-reverse"
               :class="darkMode ? 'border-gray-700 text-white hover:bg-gray-800' : 'border-gray-300 text-gray-900 hover:bg-gray-100'">
                <i data-lucide="x" class="w-5 h-5"></i>
                <span>Cancel</span>
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function chapterForm() {
    return {
        activeLanguage: 'en',
        selectedBook: '',

        init() {
            this.$nextTick(() => lucide.createIcons());
        }
    };
}
</script>
@endpush
@endsection
