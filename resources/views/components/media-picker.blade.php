@props([
    'mode' => 'single', // 'single' or 'multiple'
    'selected' => null, // For single mode: object or null
    'selectedItems' => [], // For multiple mode: array
    'label' => 'اختر من مكتبة الوسائط',
    'previewSize' => 'default', // 'default', 'large', 'small'
])

<div x-data="mediaPicker(@js($mode), @js($selected), @js($selectedItems))" x-init="init()">

    @if($mode === 'single')
        <!-- Single Image Picker -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-right">
                {{ $label }}
            </label>

            <!-- Empty State -->
            <div x-show="!selectedMedia" class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center">
                <button type="button" @click="openModal()" class="w-full">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">{{ $label }}</p>
                    <p class="text-xs text-gray-500 mt-1">JPEG, PNG, WebP</p>
                </button>
            </div>

            <!-- Preview -->
            <div x-show="selectedMedia" class="relative group">
                <div class="image-preview rounded-lg overflow-hidden"
                     :class="{
                         'aspect-video': '{{ $previewSize }}' === 'default',
                         'aspect-square': '{{ $previewSize }}' === 'small',
                         'h-96': '{{ $previewSize }}' === 'large'
                     }">
                    <img :src="selectedMedia?.url" alt="Selected" class="w-full h-full object-cover">
                </div>
                <div class="mt-3 flex gap-2">
                    <button type="button" @click="openModal()" class="flex-1 px-3 py-2 text-center text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        تغيير
                    </button>
                    <button type="button" @click="removeMedia()" class="flex-1 px-3 py-2 text-sm border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                        حذف
                    </button>
                </div>
            </div>

            <!-- Hidden Input for Form Submission (saves URL) -->
            <input type="hidden" name="{{ $attributes->get('name', 'media_url') }}" :value="selectedMedia?.url || ''">
        </div>

    @else
        <!-- Multiple Images Picker (Gallery) -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 text-right">
                {{ $label }}
            </label>

            <!-- Selected Items Grid -->
            <div class="grid grid-cols-3 gap-4 mb-4" x-show="selectedMediaItems.length > 0">
                <template x-for="(media, index) in selectedMediaItems" :key="media.id">
                    <div class="relative group">
                        <img :src="media.url" alt="Gallery" class="w-full h-40 object-cover rounded-lg">
                        <button type="button" @click="removeMediaItem(index)" class="absolute top-2 left-2 bg-red-500 text-white p-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            <!-- Add Button -->
            <button type="button" @click="openModal()" class="flex items-center justify-center gap-2 w-full px-6 py-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="text-gray-600 dark:text-gray-400 font-medium">إضافة صور من المكتبة</span>
            </button>

            <!-- Hidden Inputs for Form Submission (saves URLs) -->
            <template x-for="(media, index) in selectedMediaItems" :key="media.id">
                <input type="hidden" :name="`{{ $attributes->get('name', 'media_urls') }}[` + index + `]`" :value="media.url">
            </template>
        </div>
    @endif

    <!-- Media Library Modal (Teleported to body) -->
    <template x-teleport="body">
        <x-media-library-modal />
    </template>

</div>

<style>
    .image-preview {
        background: #f3f4f6;
    }
    .dark .image-preview {
        background: #374151;
    }

    /* Ensure modal can escape parent container */
    [x-cloak] {
        display: none !important;
    }
</style>
