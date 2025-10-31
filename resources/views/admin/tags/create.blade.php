@extends('layouts.admin')

@section('title', 'Create Tag')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ activeLanguage: 'ar', darkMode: localStorage.getItem('darkMode') === 'true', slug: '' }">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
            Create New Tag
        </h1>
        <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Fill in the tag information below
        </p>
    </div>

    <!-- Error Messages -->
    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border-r-4 border-red-500 text-red-800 rounded-lg" :class="darkMode ? 'bg-red-900/20 text-red-300 border-red-400' : ''">
            <div class="flex items-start">
                <i data-lucide="alert-circle" class="w-5 h-5 mt-0.5 mr-3 flex-shrink-0"></i>
                <div>
                    <h3 class="font-semibold mb-2">Validation Errors</h3>
                    <ul class="list-disc list-inside space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Form -->
    <form action="{{ route('admin.tags.store') }}" method="POST" novalidate
          class="glass-light rounded-lg p-8 max-w-4xl" :class="darkMode ? 'glass-dark' : ''">
        @csrf

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

        <!-- Multi-language Fields -->
        @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
            <div x-show="activeLanguage === '{{ $lang }}'" x-cloak class="space-y-6">
                <!-- Name Field -->
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Name ({{ strtoupper($lang) }}) <span class="text-red-600">*</span>
                    </label>
                    <input type="text"
                           name="name[{{ $lang }}]"
                           value="{{ old("name.$lang") }}"
                           required
                           @if($lang === 'en')
                           @input="slug = $event.target.value.toLowerCase().replace(/\s+/g, '-').replace(/[^\w\-]+/g, '')"
                           @endif
                           class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] @error("name.$lang") border-red-500 @enderror"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @error("name.$lang")
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endforeach

        <!-- Duplicate Warning -->
        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border-r-4 border-yellow-500 text-yellow-800 dark:text-yellow-300 rounded-lg mb-6" style="display: none;" id="duplicate-warning">
            <div class="flex items-center">
                <i data-lucide="alert-triangle" class="w-5 h-5 ml-3"></i>
                <span class="font-semibold">A similar tag already exists. Please check before creating.</span>
            </div>
        </div>

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
                    <option value="article" {{ old('type') === 'article' ? 'selected' : '' }}>Article</option>
                    <option value="hadith" {{ old('type') === 'hadith' ? 'selected' : '' }}>Hadith</option>
                    <option value="program" {{ old('type') === 'program' ? 'selected' : '' }}>Program</option>
                    <option value="event" {{ old('type') === 'event' ? 'selected' : '' }}>Event</option>
                </select>
                @error('type')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Slug -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Slug
                </label>
                <input type="text"
                       name="slug"
                       :value="slug"
                       x-model="slug"
                       placeholder="auto-generated-slug"
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] @error('slug') border-red-500 @enderror"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <p class="text-xs mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                    Auto-generated from English name, can be edited
                </p>
                @error('slug')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex gap-4 mt-8">
            <button type="submit"
                    class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition">
                <i data-lucide="check" class="w-4 h-4 inline ml-2"></i>
                Create Tag
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

        // Check Arabic name (required)
        const arName = form.querySelector('input[name="name[ar]"]');
        if (!arName || arName.value.trim() === '') {
            isValid = false;
            errors.push('Arabic name is required');
        }

        // Check English name (required)
        const enName = form.querySelector('input[name="name[en]"]');
        if (!enName || enName.value.trim() === '') {
            isValid = false;
            errors.push('English name is required');
        }

        // Check type field (required)
        const typeSelect = form.querySelector('select[name="type"]');
        if (!typeSelect || typeSelect.value === '') {
            isValid = false;
            errors.push('Type is required');
        }

        if (!isValid) {
            e.preventDefault();
            e.stopPropagation();
            alert('Validation errors:\n\n' + errors.join('\n'));
            return false;
        }
    });
</script>
@endpush
@endsection
