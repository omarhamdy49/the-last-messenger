@extends('layouts.admin')

@section('title', 'Create Series')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ activeLanguage: 'en', darkMode: localStorage.getItem('darkMode') === 'true', thumbnailPreview: null }">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
            Create New Series
        </h1>
        <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Create a new article series
        </p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.series.store') }}" method="POST" enctype="multipart/form-data"
          class="glass rounded-lg p-8 max-w-4xl transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
        @csrf

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
                <div x-show="activeLanguage === '{{ $lang }}'">
                    <!-- Title Field -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Title ({{ strtoupper($lang) }})
                            @if(in_array($lang, ['ar', 'en']))
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <input type="text"
                               name="title[{{ $lang }}]"
                               value="{{ old("title.$lang") }}"
                               @if(in_array($lang, ['ar', 'en'])) required @endif
                               class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error("title.$lang")
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div x-show="activeLanguage === '{{ $lang }}'">
                    <!-- Description Field -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Description ({{ strtoupper($lang) }})
                        </label>
                        <textarea name="description[{{ $lang }}]"
                                  rows="4"
                                  class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                                  :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">{{ old("description.$lang") }}</textarea>
                        @error("description.$lang")
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Thumbnail Upload -->
        <div class="mb-6">
            <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                Thumbnail Image
            </label>
            <div class="flex items-start gap-4">
                <div class="flex-1">
                    <input type="file"
                           name="thumbnail"
                           accept="image/*"
                           @change="thumbnailPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                           class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <p class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        Recommended: 800x600px, Max 2MB
                    </p>
                    @error('thumbnail')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div x-show="thumbnailPreview" class="w-32 h-32 rounded-lg overflow-hidden border-2 transition-colors"
                     :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
                    <img :src="thumbnailPreview" alt="Preview" class="w-full h-full object-cover">
                </div>
            </div>
        </div>

        <!-- Display Order -->
        <div class="mb-6">
            <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                Display Order (Optional)
            </label>
            <input type="number"
                   name="display_order"
                   value="{{ old('display_order', 0) }}"
                   min="0"
                   class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            @error('display_order')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Buttons -->
        <div class="flex gap-4 mt-8">
            <button type="submit"
                    class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition">
                <i data-lucide="save" class="w-5 h-5 inline-block mr-2"></i>
                Create Series
            </button>
            <a href="{{ route('admin.series.index') }}"
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
