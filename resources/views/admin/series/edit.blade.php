@extends('layouts.admin')

@section('title', 'Edit Series')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{
    activeLanguage: 'ar',
    darkMode: localStorage.getItem('darkMode') === 'true',
    thumbnailPreview: '{{ $series->thumbnail ?? '' }}',
    deleteThumbnail: false
}">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
            Edit Series
        </h1>
        <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Edit series: {{ $series->title['en'] ?? $series->title['ar'] ?? 'Untitled' }}
        </p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.series.update', $series->id) }}" method="POST" enctype="multipart/form-data"
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
                    <!-- Title Field -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Title ({{ strtoupper($lang) }}) <span class="text-red-500">*</span>
                        </label>
                        @php
                            // Get all translations for the title attribute
                            $translations = $series->getTitleTranslations();
                            $titleValue = $translations[$lang] ?? '';
                        @endphp
                        <input type="text"
                               name="title[{{ $lang }}]"
                               value="{{ old("title.$lang", $titleValue) }}"
                               required
                               class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error("title.$lang")
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
                            $translations = $series->getDescriptionTranslations();
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
                           @change="thumbnailPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : thumbnailPreview"
                           class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <p class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        Leave empty to keep current thumbnail. Recommended: 800x600px, Max 2MB
                    </p>
                    @if($series->thumbnail)
                        <label class="flex items-center gap-2 mt-2 cursor-pointer">
                            <input type="checkbox" name="delete_thumbnail" x-model="deleteThumbnail" class="rounded">
                            <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Delete current thumbnail</span>
                        </label>
                    @endif
                    @error('thumbnail')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div x-show="thumbnailPreview && !deleteThumbnail" class="w-32 h-32 rounded-lg overflow-hidden border-2 transition-colors"
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
                   value="{{ old('display_order', $series->display_order ?? 0) }}"
                   min="0"
                   class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            @error('display_order')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Article Count Info -->
        <div class="mb-6 p-4 rounded-lg transition-colors" :class="darkMode ? 'bg-blue-900/20 border border-blue-500/30' : 'bg-blue-50 border border-blue-200'">
            <div class="flex items-center gap-2">
                <i data-lucide="info" class="w-5 h-5 text-blue-500"></i>
                <span class="font-semibold" :class="darkMode ? 'text-blue-400' : 'text-blue-700'">
                    This series contains {{ $series->articles_count ?? 0 }} article(s)
                </span>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex gap-4 mt-8">
            <button type="submit"
                    class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition">
                <i data-lucide="save" class="w-5 h-5 inline-block mr-2"></i>
                Update Series
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
