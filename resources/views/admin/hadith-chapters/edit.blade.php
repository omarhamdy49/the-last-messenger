@extends('layouts.admin')

@section('title', 'Edit Hadith Chapter')

@section('content')
<div x-data="chapterEditForm()" x-init="init()">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3 space-x-reverse">
                <a href="{{ route('admin.hadith-chapters.index') }}"
                   class="text-gray-400 hover:text-white transition">
                    <i data-lucide="arrow-left" class="w-6 h-6"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-white">Edit Hadith Chapter</h1>
                    <p class="mt-2 text-gray-400">
                        <span class="text-[#64CCC5]">{{ $chapter->book->name['en'] ?? $chapter->book->name['ar'] }}</span>
                        - Chapter {{ $chapter->chapter_number }}
                    </p>
                </div>
            </div>

            {{-- Delete Button --}}
            <button @click="confirmDelete()"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition flex items-center space-x-2 space-x-reverse">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>Delete Chapter</span>
            </button>
        </div>

        {{-- Warning if chapter has hadiths --}}
        @if($chapter->hadiths_count > 0)
            <div class="mt-4 p-4 rounded-lg bg-yellow-500/10 border border-yellow-500/20">
                <div class="flex items-center space-x-3 space-x-reverse">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-yellow-500"></i>
                    <p class="text-yellow-500 text-sm">
                        <strong>Warning:</strong> This chapter contains {{ $chapter->hadiths_count }} hadith(s).
                        Deleting this chapter will affect these hadiths.
                    </p>
                </div>
            </div>
        @endif
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.hadith-chapters.update', $chapter) }}"
          method="POST"
          class="max-w-5xl transition-colors rounded-2xl p-8"
          :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        @csrf
        @method('PUT')

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
                               value="{{ old("name.$lang", $chapter->name[$lang] ?? '') }}"
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
                                  :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">{{ old("description.$lang", $chapter->description[$lang] ?? '') }}</textarea>
                        @error("description.$lang")
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Non-translatable Fields --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            {{-- Book Selection (Read-only) --}}
            <div>
                <label class="block text-sm font-semibold mb-2 text-gray-300">
                    Hadith Book
                </label>
                <div class="w-full px-4 py-3 rounded-lg border bg-gray-900/50 border-gray-700 text-gray-400 flex items-center justify-between">
                    <span>{{ $chapter->book->name['en'] ?? $chapter->book->name['ar'] }}</span>
                    <i data-lucide="lock" class="w-4 h-4"></i>
                </div>
                <p class="text-sm mt-1 text-gray-400">Book cannot be changed after creation</p>
                <input type="hidden" name="hadith_book_id" value="{{ $chapter->hadith_book_id }}">
            </div>

            {{-- Chapter Number --}}
            <div>
                <label class="block text-sm font-semibold mb-2 text-gray-300">
                    Chapter Number
                    <span class="text-red-500">*</span>
                </label>
                <input type="number"
                       name="chapter_number"
                       value="{{ old('chapter_number', $chapter->chapter_number) }}"
                       required
                       min="1"
                       placeholder="e.g., 1, 2, 3..."
                       class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                @error('chapter_number')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-sm mt-1 text-gray-400">Must be unique within the book</p>
            </div>

            {{-- Total Hadiths (Info) --}}
            <div>
                <label class="block text-sm font-semibold mb-2 text-gray-300">
                    Total Hadiths
                </label>
                <div class="w-full px-4 py-3 rounded-lg border bg-gray-900/50 border-gray-700 text-[#64CCC5] font-semibold">
                    {{ $chapter->hadiths_count ?? 0 }} hadith(s)
                </div>
                <p class="text-sm mt-1 text-gray-400">Automatically counted from database</p>
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-semibold mb-2 text-gray-300">
                    Status
                </label>
                <select name="status"
                        class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="draft" {{ old('status', $chapter->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ old('status', $chapter->status) == 'published' ? 'selected' : '' }}>Published</option>
                    <option value="archived" {{ old('status', $chapter->status) == 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
                @error('status')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Display Order --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-2 text-gray-300">
                    Display Order
                </label>
                <input type="number"
                       name="display_order"
                       value="{{ old('display_order', $chapter->display_order ?? 0) }}"
                       min="0"
                       placeholder="0 for default ordering"
                       class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                @error('display_order')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Metadata --}}
        <div class="mb-8 p-4 rounded-lg bg-blue-500/10 border border-blue-500/20">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-400">Created:</span>
                    <span class="text-white ml-2">{{ $chapter->created_at->format('M d, Y') }}</span>
                </div>
                <div>
                    <span class="text-gray-400">Last Modified:</span>
                    <span class="text-white ml-2">{{ $chapter->updated_at->format('M d, Y') }}</span>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex gap-4 mt-8 pt-6 border-t"
             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <button type="submit"
                    class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition-all duration-300 flex items-center space-x-2 space-x-reverse">
                <i data-lucide="save" class="w-5 h-5"></i>
                <span>Update Chapter</span>
            </button>
            <a href="{{ route('admin.hadith-chapters.index') }}"
               class="px-8 py-3 rounded-lg font-semibold border transition-colors flex items-center space-x-2 space-x-reverse"
               :class="darkMode ? 'border-gray-700 text-white hover:bg-gray-800' : 'border-gray-300 text-gray-900 hover:bg-gray-100'">
                <i data-lucide="x" class="w-5 h-5"></i>
                <span>Cancel</span>
            </a>
        </div>
    </form>

    {{-- Delete Confirmation Modal --}}
    <div x-show="showDeleteModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-75" @click="showDeleteModal = false"></div>

            <div class="relative bg-gray-900 rounded-lg max-w-md w-full p-6 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-4">Delete Chapter?</h3>
                <p class="text-gray-400 mb-6">
                    Are you sure you want to delete this chapter?
                    @if($chapter->hadiths_count > 0)
                        <span class="text-red-400 font-semibold">
                            This will affect {{ $chapter->hadiths_count }} hadith(s).
                        </span>
                    @else
                        This action cannot be undone.
                    @endif
                </p>

                <div class="flex gap-3">
                    <form action="{{ route('admin.hadith-chapters.destroy', $chapter) }}"
                          method="POST"
                          class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                            Yes, Delete
                        </button>
                    </form>
                    <button @click="showDeleteModal = false"
                            class="flex-1 bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function chapterEditForm() {
    return {
        activeLanguage: 'en',
        showDeleteModal: false,

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        confirmDelete() {
            this.showDeleteModal = true;
        }
    };
}
</script>
@endpush
@endsection
