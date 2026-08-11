{{--
    Unified Image Upload Component

    Provides two options:
    1. Browse from media library (opens media picker modal)
    2. Direct file upload

    @props
    - name: string (required) - Input file name, e.g. 'thumbnail', 'profile_image'
    - idName: string|null - Hidden input name for media library ID, e.g. 'thumb_id'
    - currentImage: string|null - Current image URL for edit pages
    - aspectClass: string - Tailwind aspect class for preview (default: 'aspect-video')
    - placeholderIcon: string - Lucide icon name for empty state (default: 'image-plus')
    - accept: string - Accepted file types (default: 'image/jpeg,image/png,image/webp')
    - maxSize: string - Max file size label (default: '5MB')
    - formats: string - Supported formats label (default: 'JPG, PNG, WebP')
    - mediaLibrary: bool - Enable media library button (default: true)
    - label: string|null - Section label
--}}

@props([
    'name' => 'thumbnail',
    'idName' => null,
    'currentImage' => null,
    'aspectClass' => 'aspect-video',
    'placeholderIcon' => 'image-plus',
    'accept' => 'image/jpeg,image/png,image/webp',
    'maxSize' => '5MB',
    'formats' => 'JPG, PNG, WebP',
    'mediaLibrary' => true,
    'label' => null,
])

@php
    $uniqueId = 'img_upload_' . Str::random(6);
@endphp

<div x-data="{
        preview: {{ $currentImage ? '\'' . e($currentImage) . '\'' : 'null' }},
        mediaId: null,

        handleFileChange(event) {
            const file = event.target.files[0];
            if (file) {
                this.preview = URL.createObjectURL(file);
                this.mediaId = null;
            }
        },

        handleMediaSelect(detail) {
            if (detail.target === '{{ $uniqueId }}' && detail.media) {
                const media = Array.isArray(detail.media) ? detail.media[0] : detail.media;
                this.preview = media.url || media.path || ('/storage/' + media.path);
                this.mediaId = media.id;
                // Clear the file input since we're using media library
                const fileInput = this.$refs.fileInput;
                if (fileInput) fileInput.value = '';
            }
        },

        removeImage() {
            this.preview = null;
            this.mediaId = null;
            const fileInput = this.$refs.fileInput;
            if (fileInput) fileInput.value = '';
        }
     }"
     @media-selected.window="handleMediaSelect($event.detail)"
     {{-- Preview-only fill (e.g. a fetched YouTube thumbnail): shows the image
          without sending a file or library id — the server-side fallback is
          expected to resolve the same URL. --}}
     @image-preview-set.window="if ($event.detail.name === '{{ $name }}') { preview = $event.detail.url; mediaId = null; if ($refs.fileInput) $refs.fileInput.value = ''; }"
     class="space-y-3">

    @if($label)
        <h3 class="text-sm font-semibold flex items-center gap-2"
            :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="image" class="w-4 h-4 text-gray-400"></i>
            {{ $label }}
        </h3>
    @endif

    {{-- Preview Area --}}
    <div class="relative {{ $aspectClass }} rounded-xl overflow-hidden"
         :class="darkMode ? 'bg-gray-700' : 'bg-gray-100'">
        <template x-if="preview">
            <div class="relative w-full h-full group">
                <img :src="preview" class="w-full h-full object-cover" alt="">
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                    <button type="button" @click="removeImage()"
                            class="p-2 rounded-full bg-red-500 text-white hover:bg-red-600 transition-colors shadow-lg">
                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </template>
        <template x-if="!preview">
            <div class="flex flex-col items-center justify-center h-full gap-2 text-gray-400">
                <i data-lucide="{{ $placeholderIcon }}" class="w-10 h-10"></i>
                <span class="text-xs">{{ $maxSize }} | {{ $formats }}</span>
            </div>
        </template>
    </div>

    {{-- Action Buttons --}}
    <div class="grid {{ $mediaLibrary ? 'grid-cols-2' : 'grid-cols-1' }} gap-2">
        @if($mediaLibrary)
            <button type="button"
                    @click="$dispatch('open-media-library', { target: '{{ $uniqueId }}', multiple: false })"
                    class="px-3 py-2 text-xs rounded-lg border transition-colors text-center font-medium"
                    :class="darkMode ? 'border-gray-600 hover:bg-gray-700 text-gray-300' : 'border-gray-300 hover:bg-gray-50 text-gray-700'">
                <i data-lucide="folder-open" class="w-3.5 h-3.5 inline me-1"></i>
                {{ __('media.picker.browse') }}
            </button>
        @endif
        <label class="px-3 py-2 text-xs rounded-lg border transition-colors cursor-pointer text-center font-medium"
               :class="darkMode ? 'border-gray-600 hover:bg-gray-700 text-gray-300' : 'border-gray-300 hover:bg-gray-50 text-gray-700'">
            <i data-lucide="upload" class="w-3.5 h-3.5 inline me-1"></i>
            {{ __('common.upload') }}
            <input type="file"
                   name="{{ $name }}"
                   accept="{{ $accept }}"
                   @change="handleFileChange($event)"
                   x-ref="fileInput"
                   class="hidden">
        </label>
    </div>

    {{-- Hidden media ID field --}}
    @if($idName)
        <input type="hidden" name="{{ $idName }}" x-model="mediaId">
    @endif

    {{-- Validation Error --}}
    @error($name)
        <p class="text-sm text-red-500">{{ $message }}</p>
    @enderror
</div>
