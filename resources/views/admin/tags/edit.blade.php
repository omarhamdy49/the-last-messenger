@extends('layouts.admin')

@section('title', 'Edit Tag')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ activeLanguage: 'ar', darkMode: localStorage.getItem('darkMode') === 'true' }">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
            Edit Tag
        </h1>
        <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Update tag information
        </p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.tags.update', $tag->id) }}" method="POST" novalidate
          class="glass-light rounded-lg p-8 max-w-4xl" :class="darkMode ? 'glass-dark' : ''">
        @csrf
        @method('PUT')

        <!-- Language Tabs -->
        <div class="flex gap-2 mb-6 border-b" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                <button type="button"
                        @click="activeLanguage = '{{ $lang }}'"
                        :class="activeLanguage === '{{ $lang }}' ? 'border-b-2 border-[#0056b3] font-bold' : ''"
                        class="px-4 py-2 transition"
                        :class="darkMode ? 'text-white hover:text-[#64CCC5]' : 'text-gray-700 hover:text-[#0056b3]'">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <!-- Info Message -->
        @if($tag->usage_count > 0)
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border-r-4 border-blue-500 text-blue-800 dark:text-blue-300 rounded-lg mb-6">
                <div class="flex items-center">
                    <i data-lucide="info" class="w-5 h-5 ml-3"></i>
                    <span class="font-semibold">This tag is used in {{ $tag->usage_count }} items</span>
                </div>
            </div>
        @endif

        <!-- Multi-language Fields -->
        @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
            <div x-show="activeLanguage === '{{ $lang }}'" x-cloak class="space-y-6">
                <!-- Name Field -->
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Name ({{ strtoupper($lang) }}) <span class="text-red-600">*</span>
                    </label>
                    @php
                        // Get all translations for the name attribute
                        $translations = $tag->getNameTranslations();
                        $nameValue = $translations[$lang] ?? '';
                    @endphp
                    <input type="text"
                           name="name[{{ $lang }}]"
                           value="{{ old("name.$lang", $nameValue) }}"
                           required
                           class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] @error("name.$lang") border-red-500 @enderror"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @error("name.$lang")
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endforeach

        <!-- Other Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <!-- Type -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Type <span class="text-red-600">*</span>
                </label>
                <select name="type"
                        required
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] @error('type') border-red-500 @enderror"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">Select Type</option>
                    <option value="article" {{ old('type', $tag->type) === 'article' ? 'selected' : '' }}>Article</option>
                    <option value="hadith" {{ old('type', $tag->type) === 'hadith' ? 'selected' : '' }}>Hadith</option>
                    <option value="program" {{ old('type', $tag->type) === 'program' ? 'selected' : '' }}>Program</option>
                    <option value="event" {{ old('type', $tag->type) === 'event' ? 'selected' : '' }}>Event</option>
                </select>
                @error('type')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Slug (Read-only) -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Slug
                </label>
                <input type="text"
                       value="{{ $tag->slug }}"
                       readonly
                       class="w-full px-4 py-2 rounded-lg border opacity-75 cursor-not-allowed focus:outline-none"
                       :class="darkMode ? 'bg-gray-700 border-gray-600 text-gray-400' : 'bg-gray-100 border-gray-300 text-gray-600'">
                <p class="text-xs mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                    Auto-generated from English name
                </p>
            </div>
        </div>

        <!-- Merge Option -->
        <div class="border-t pt-6 mt-6" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <div class="p-4 bg-purple-50 dark:bg-purple-900/20 border-r-4 border-purple-500 text-purple-800 dark:text-purple-300 rounded-lg">
                <div class="flex items-start justify-between">
                    <div class="flex items-start">
                        <i data-lucide="git-merge" class="w-5 h-5 ml-3 mt-1"></i>
                        <div class="mr-3">
                            <p class="font-semibold">Need to merge this tag?</p>
                            <p class="text-sm mt-1">You can merge this tag with another tag from the tags index page.</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.tags.index') }}" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-purple-700 transition whitespace-nowrap">
                        Go to Tags
                    </a>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex gap-4 mt-8">
            <button type="submit"
                    class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition">
                <i data-lucide="check" class="w-4 h-4 inline ml-2"></i>
                Update Tag
            </button>
            <a href="{{ route('admin.tags.index') }}"
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
    lucide.createIcons();

    // Form validation for multi-language fields
    document.querySelector('form')?.addEventListener('submit', function(e) {
        const form = this;
        let isValid = true;
        const errors = [];

        // Check all required language fields
        ['ar', 'en', 'fr', 'tr', 'ur'].forEach(lang => {
            const nameInput = form.querySelector(`input[name="name[${lang}]"]`);
            if (nameInput && nameInput.value.trim() === '') {
                isValid = false;
                errors.push(`Name (${lang.toUpperCase()}) is required`);
            }
        });

        // Check type field
        const typeSelect = form.querySelector('select[name="type"]');
        if (typeSelect && typeSelect.value === '') {
            isValid = false;
            errors.push('Type is required');
        }

        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields:\n\n' + errors.join('\n'));
            return false;
        }
    });
</script>
@endpush
@endsection
