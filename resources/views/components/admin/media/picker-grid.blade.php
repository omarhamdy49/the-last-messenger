{{--
    Media Picker Modal Grid Component

    Displays the media grid with items, loading state, empty state, and load more button.
    Supports dark mode, RTL layouts, and multiple media types (image, video, audio, document).

    Usage:
    <x-admin.media.picker-grid />

    This component is part of the media-picker-modal and relies on Alpine.js data context
    from the parent mediaPickerModal() component.
--}}

<div class="flex-1 overflow-y-auto custom-scrollbar px-6 py-6"
     :class="$root.darkMode ? 'bg-gray-950' : 'bg-white'">

    <!-- Loading State -->
    <div x-show="isLoading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        <template x-for="i in 10" :key="i">
            <div class="aspect-square rounded-lg animate-pulse"
                 :class="$root.darkMode ? 'bg-gray-800' : 'bg-gray-200'">
            </div>
        </template>
    </div>

    <!-- Media Grid -->
    <div x-show="!isLoading && (mediaItems?.length || 0) > 0"
         class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        <template x-for="media in (mediaItems || [])" :key="media.id">
            <div @click="toggleSelection(media)"
                 class="group relative aspect-square rounded-lg overflow-hidden cursor-pointer transition-all duration-300 border-2"
                 :class="isSelected(media.id) ?
                     'border-accent shadow-lg shadow-accent/30 scale-95' :
                     ($root.darkMode ? 'border-gray-800 hover:border-gray-700' : 'border-gray-200 hover:border-gray-300')">

                <!-- Media Preview -->
                <div class="w-full h-full relative"
                     :class="$root.darkMode ? 'bg-gray-900' : 'bg-gray-100'">
                    <template x-if="media.type === 'image' || media.mime_type.startsWith('image/')">
                        <img :src="media.thumbnail_url || media.url"
                             :alt="media.alt_text || media.filename"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                    </template>

                    <template x-if="media.type === 'video' || media.mime_type.startsWith('video/')">
                        <div class="w-full h-full flex items-center justify-center"
                             :class="$root.darkMode ? 'bg-gray-800' : 'bg-gray-200'">
                            <i data-lucide="video" class="w-12 h-12"
                               :class="$root.darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                        </div>
                    </template>

                    <template x-if="media.type === 'audio'">
                        <div class="w-full h-full flex items-center justify-center"
                             :class="$root.darkMode ? 'bg-gray-800' : 'bg-gray-200'">
                            <i data-lucide="music-4" class="w-12 h-12"
                               :class="$root.darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                        </div>
                    </template>

                    <template x-if="media.type === 'document'">
                        <div class="w-full h-full flex items-center justify-center"
                             :class="$root.darkMode ? 'bg-gray-800' : 'bg-gray-200'">
                            <i data-lucide="file-text" class="w-12 h-12"
                               :class="$root.darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                        </div>
                    </template>
                </div>

                <!-- Selection Indicator -->
                <div class="absolute top-2 right-2 w-6 h-6 rounded-full transition-all duration-300 flex items-center justify-center"
                     :class="isSelected(media.id) ?
                         'bg-accent scale-100' :
                         'bg-black/30 backdrop-blur-sm scale-0 group-hover:scale-100'">
                    <i data-lucide="check" class="w-4 h-4 text-white"></i>
                </div>

                <!-- Media Info Overlay -->
                <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                    <p class="text-white text-xs truncate font-medium" x-text="media.original_filename"></p>
                    <p class="text-white/70 text-xs" x-text="formatFileSize(media.size)"></p>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="!isLoading && (mediaItems?.length || 0) === 0"
         class="flex flex-col items-center justify-center h-full py-12">
        <div class="w-20 h-20 rounded-full flex items-center justify-center mb-4"
             :class="$root.darkMode ? 'bg-gray-800' : 'bg-gray-100'">
            <i data-lucide="image-off" class="w-10 h-10"
               :class="$root.darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
        </div>
        <h3 class="text-xl font-bold mb-2"
            :class="$root.darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ __('media.picker.empty_title') }}
        </h3>
        <p class="text-sm"
           :class="$root.darkMode ? 'text-gray-500' : 'text-gray-500'">
            {{ __('media.picker.empty_description') }}
        </p>
    </div>

    <!-- Load More -->
    <div x-show="!isLoading && hasMorePages" class="mt-6 text-center">
        <button @click="loadMore()"
                class="px-6 py-2 rounded-lg font-semibold transition-all hover:scale-105"
                :class="$root.darkMode ?
                    'bg-gray-800 hover:bg-gray-700 text-white' :
                    'bg-gray-100 hover:bg-gray-200 text-gray-900'">
            {{ __('media.actions.load_more') }}
        </button>
    </div>
</div>
