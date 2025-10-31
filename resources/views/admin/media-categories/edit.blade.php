@extends('layouts.admin')

@section('title', 'Edit Media Category')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ activeLanguage: 'ar', darkMode: localStorage.getItem('darkMode') === 'true' }">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
            Edit Media Category
        </h1>
        <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Edit category: {{ $mediaCategory->name['en'] ?? $mediaCategory->name['ar'] ?? 'Untitled' }}
        </p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.media-categories.update', $mediaCategory->id) }}" method="POST"
          class="glass rounded-lg p-8 max-w-4xl transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
        @csrf
        @method('PUT')

        <!-- Language Tabs -->
        <div class="flex gap-2 mb-6 border-b transition-colors" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                <div x-show="activeLanguage === '{{ $lang }}'" x-cloak>
                    <!-- Name Field -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Name ({{ strtoupper($lang) }}) <span class="text-red-500">*</span>
                        </label>
                        @php
                            // Get all translations for the name attribute
                            $translations = $mediaCategory->getNameTranslations();
                            $nameValue = $translations[$lang] ?? '';
                        @endphp
                        <input type="text"
                               name="name[{{ $lang }}]"
                               value="{{ old("name.$lang", $nameValue) }}"
                               required
                               class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error("name.$lang")
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div x-show="activeLanguage === '{{ $lang }}'" x-cloak>
                    <!-- Description Field -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Description ({{ strtoupper($lang) }})
                        </label>
                        @php
                            // Get all translations for the description attribute
                            $translations = $mediaCategory->getDescriptionTranslations();
                            $descValue = $translations[$lang] ?? '';
                        @endphp
                        <textarea name="description[{{ $lang }}]"
                                  rows="4"
                                  class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                                  :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">{{ old("description.$lang", $descValue) }}</textarea>
                        @error("description.$lang")
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Icon Field -->
        <div class="mb-6">
            <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                Icon (Lucide icon name)
            </label>
            <input type="text"
                   name="icon"
                   value="{{ old('icon', $mediaCategory->icon ?? 'video') }}"
                   placeholder="video, film, tv, play-circle, etc."
                   class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            <p class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Enter a valid Lucide icon name. See: <a href="https://lucide.dev/icons/" target="_blank" class="text-[#0056b3] hover:underline">lucide.dev/icons</a>
            </p>
            @error('icon')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Display Order -->
        <div class="mb-6">
            <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                Display Order (Optional)
            </label>
            <input type="number"
                   name="display_order"
                   value="{{ old('display_order', $mediaCategory->display_order ?? 0) }}"
                   min="0"
                   class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            @error('display_order')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Program Count Info -->
        <div class="mb-6 p-4 rounded-lg transition-colors" :class="darkMode ? 'bg-blue-900/20 border border-blue-500/30' : 'bg-blue-50 border border-blue-200'">
            <div class="flex items-center gap-2">
                <i data-lucide="info" class="w-5 h-5 text-blue-500"></i>
                <span class="font-semibold" :class="darkMode ? 'text-blue-400' : 'text-blue-700'">
                    This category has {{ $mediaCategory->programs_count ?? 0 }} program(s)
                </span>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex gap-4 mt-8">
            <button type="submit"
                    class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition">
                <i data-lucide="save" class="w-5 h-5 inline-block mr-2"></i>
                Update Category
            </button>
            <a href="{{ route('admin.media-categories.index') }}"
               class="px-8 py-3 rounded-lg font-semibold border transition"
               :class="darkMode ? 'border-gray-700 text-white hover:bg-gray-800' : 'border-gray-300 text-gray-900 hover:bg-gray-100'">
                Cancel
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
    lucide.createIcons();
</script>
@endpush
@endsection
