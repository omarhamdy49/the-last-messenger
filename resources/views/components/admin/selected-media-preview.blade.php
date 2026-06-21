{{--
    Selected Media Preview Component

    Displays selected media items with remove and reorder functionality.
    Used within the media picker modal sidebar.

    This component is automatically scoped to the parent's selectedItems array.
--}}

<div class="p-4 space-y-3">
    <template x-for="(media, index) in (selectedItems || [])" :key="media.id">
        <div class="group relative rounded-lg overflow-hidden border transition-all duration-300 hover:shadow-lg"
             :class="$root.darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'"
             x-data="{ showControls: false }">

            <div class="flex gap-3 p-3">
                <!-- Reorder Handle (Multiple Mode Only) -->
                <div x-show="selectionMode === 'multiple'"
                     class="flex-shrink-0 cursor-move flex items-center"
                     :class="$root.darkMode ? 'text-gray-500' : 'text-gray-400'">
                    <i data-lucide="grip-vertical" class="w-5 h-5"></i>
                </div>

                <!-- Media Thumbnail -->
                <div class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden"
                     :class="$root.darkMode ? 'bg-gray-900' : 'bg-gray-100'">
                    <template x-if="media.type === 'image' || media.mime_type.startsWith('image/')">
                        <img :src="media.thumbnail_url || media.url"
                             :alt="media.alt_text || media.filename"
                             class="w-full h-full object-cover">
                    </template>

                    <template x-if="media.type === 'video' || media.mime_type.startsWith('video/')">
                        <div class="w-full h-full flex items-center justify-center">
                            <i data-lucide="video" class="w-8 h-8"
                               :class="$root.darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                        </div>
                    </template>
                </div>

                <!-- Media Info -->
                <div class="flex-1 min-w-0">
                    <p class="font-medium truncate text-sm"
                       :class="$root.darkMode ? 'text-white' : 'text-gray-900'"
                       x-text="media.original_filename"></p>

                    <div class="flex items-center gap-2 mt-1">
                        <!-- Type Badge -->
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium"
                              :class="(media.type === 'image' || media.mime_type.startsWith('image/')) ?
                                  ($root.darkMode ? 'bg-blue-500/20 text-blue-400' : 'bg-blue-100 text-blue-700') :
                                  ($root.darkMode ? 'bg-purple-500/20 text-purple-400' : 'bg-purple-100 text-purple-700')">
                            <i :data-lucide="(media.type === 'image' || media.mime_type.startsWith('image/')) ? 'image' : 'video'"
                               class="w-3 h-3"></i>
                            <span x-text="(media.type === 'image' || media.mime_type.startsWith('image/'))
                                ? @js(__('media.preview.image'))
                                : (media.type === 'audio' || media.mime_type.startsWith('audio/'))
                                    ? @js(__('media.preview.audio'))
                                : (media.type === 'document' || media.mime_type.startsWith('application/'))
                                    ? @js(__('media.preview.document'))
                                : @js(__('media.preview.video'))"></span>
                        </span>

                        <!-- File Size -->
                        <span class="text-xs"
                              :class="$root.darkMode ? 'text-gray-500' : 'text-gray-600'"
                              x-text="formatFileSize(media.size)">
                        </span>
                    </div>

                    <!-- Dimensions (if available) -->
                    <p class="text-xs mt-1"
                       x-show="media.width && media.height"
                       :class="$root.darkMode ? 'text-gray-500' : 'text-gray-600'">
                        <span x-text="media.width"></span> x <span x-text="media.height"></span> px
                    </p>
                </div>

                <!-- Remove Button -->
                <div class="flex-shrink-0 flex items-start">
                    <button @click="removeFromSelection(media.id)"
                            class="p-1.5 rounded-lg transition-all hover:scale-110"
                            :class="$root.darkMode ?
                                'hover:bg-red-500/20 text-gray-400 hover:text-red-400' :
                                'hover:bg-red-50 text-gray-600 hover:text-red-600'">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Quality / Size selector (images with generated variants only) -->
            <div x-show="sizeOptionsFor(media).length > 1"
                 class="px-3 pb-3">
                <p class="text-xs font-medium mb-1.5"
                   :class="$root.darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('media.picker.quality') }}
                </p>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="opt in sizeOptionsFor(media)" :key="opt.key">
                        <button type="button"
                                @click="selectVariant(media, opt.key)"
                                class="px-2 py-1 rounded-md text-xs font-medium transition-all hover:scale-105"
                                :class="variantKeyFor(media) === opt.key ?
                                    'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow' :
                                    ($root.darkMode ? 'bg-gray-700 hover:bg-gray-600 text-gray-300' : 'bg-gray-100 hover:bg-gray-200 text-gray-700')">
                            <span x-text="opt.label"></span>
                            <span x-show="opt.width && opt.height"
                                  class="opacity-70"
                                  x-text="' · ' + opt.width + '×' + opt.height"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Expanded Details (on hover/click) -->
            <div x-show="showControls"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="px-3 pb-3 border-t"
                 :class="$root.darkMode ? 'border-gray-700' : 'border-gray-200'">

                <!-- Alt Text -->
                <div class="mt-2" x-show="media.alt_text">
                    <p class="text-xs font-medium mb-1"
                       :class="$root.darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('media.preview.alt_text') }}:
                    </p>
                    <p class="text-xs"
                       :class="$root.darkMode ? 'text-gray-300' : 'text-gray-700'"
                       x-text="media.alt_text">
                    </p>
                </div>

                <!-- Caption -->
                <div class="mt-2" x-show="media.caption">
                    <p class="text-xs font-medium mb-1"
                       :class="$root.darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('media.preview.caption') }}:
                    </p>
                    <p class="text-xs"
                       :class="$root.darkMode ? 'text-gray-300' : 'text-gray-700'"
                       x-text="media.caption">
                    </p>
                </div>

                <!-- Quick Actions -->
                <div class="mt-3 flex gap-2">
                    <button @click="$dispatch('media-preview', { media })"
                            class="flex-1 px-3 py-1.5 rounded-lg text-xs font-medium transition-all flex items-center justify-center gap-1.5 hover:scale-105"
                            :class="$root.darkMode ?
                                'bg-gray-700 hover:bg-gray-600 text-white' :
                                'bg-gray-100 hover:bg-gray-200 text-gray-900'">
                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                        <span>{{ __('media.preview.view') }}</span>
                    </button>

                    <button @click="copyToClipboard(media.url)"
                            class="flex-1 px-3 py-1.5 rounded-lg text-xs font-medium transition-all flex items-center justify-center gap-1.5 hover:scale-105"
                            :class="$root.darkMode ?
                                'bg-gray-700 hover:bg-gray-600 text-white' :
                                'bg-gray-100 hover:bg-gray-200 text-gray-900'">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        <span>{{ __('media.preview.copy_url') }}</span>
                    </button>
                </div>
            </div>

            <!-- Toggle Details Button -->
            <button @click="showControls = !showControls"
                    class="absolute bottom-2 right-2 p-1 rounded-full transition-all opacity-0 group-hover:opacity-100"
                    :class="$root.darkMode ?
                        'bg-gray-700 hover:bg-gray-600 text-gray-300' :
                        'bg-white hover:bg-gray-100 text-gray-600'">
                <i :data-lucide="showControls ? 'chevron-up' : 'chevron-down'" class="w-4 h-4"></i>
            </button>
        </div>
    </template>
</div>

@once
@push('scripts')
<script>
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            // Could dispatch a toast notification here
            console.log('URL copied to clipboard');
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            console.log('URL copied to clipboard');
        } catch (err) {
            console.error('Failed to copy:', err);
        }
        document.body.removeChild(textArea);
    }
}
</script>
@endpush
@endonce
