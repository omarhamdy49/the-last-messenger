{{--
    Media Card Component - Netflix-style media card with hover effects

    Usage:
    <x-admin.media-card :item="$mediaItem" :index="0" />
--}}

<div class="group relative rounded-2xl overflow-hidden shadow-lg transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:z-10 animate-fadeInUp"
     :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'"
     :style="`animation-delay: ${(index % 12) * 0.05}s`">

    <!-- Checkbox - Always visible in selection mode -->
    <div class="absolute top-3 left-3 z-20">
        <label class="cursor-pointer">
            <input type="checkbox"
                   :value="item.id"
                   x-model="selectedItems"
                   class="rounded border-gray-300 text-[#0056b3] focus:ring-[#64CCC5] w-5 h-5 shadow-lg"
                   :class="selectedItems.includes(item.id) ? 'opacity-100' : 'opacity-0 group-hover:opacity-100 transition-opacity'">
        </label>
    </div>

    <!-- Thumbnail Container -->
    <div class="relative aspect-video overflow-hidden"
         :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">

        <!-- Thumbnail Image -->
        <img :src="item.thumbnail"
             :alt="item.name"
             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
             onerror="this.src='/images/placeholder.png'">

        <!-- Overlay on Hover -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">

            <!-- Play Icon for Videos -->
            <template x-if="item.type === 'video'">
                <div class="w-16 h-16 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center transform scale-0 group-hover:scale-100 transition-transform duration-300">
                    <i data-lucide="play" class="w-8 h-8 text-[#0056b3] ms-1"></i>
                </div>
            </template>

            <!-- Audio Icon -->
            <template x-if="item.type === 'audio'">
                <div class="w-16 h-16 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center transform scale-0 group-hover:scale-100 transition-transform duration-300">
                    <i data-lucide="volume-2" class="w-8 h-8 text-[#0056b3]"></i>
                </div>
            </template>

            <!-- Document Icon -->
            <template x-if="item.type === 'document'">
                <div class="w-16 h-16 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center transform scale-0 group-hover:scale-100 transition-transform duration-300">
                    <i data-lucide="file-text" class="w-8 h-8 text-[#0056b3]"></i>
                </div>
            </template>

            <!-- Image Icon -->
            <template x-if="item.type === 'image'">
                <div class="w-16 h-16 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center transform scale-0 group-hover:scale-100 transition-transform duration-300">
                    <i data-lucide="image" class="w-8 h-8 text-[#0056b3]"></i>
                </div>
            </template>

            <!-- Quick Actions (Bottom of overlay) -->
            <div class="absolute bottom-3 right-3 flex items-center gap-2">
                <button @click.stop="viewMedia(item)"
                        class="p-2 rounded-lg bg-white/90 backdrop-blur-sm hover:bg-white transition-colors shadow-lg"
                        title="{{ __('media.actions.view') }}">
                    <i data-lucide="eye" class="w-4 h-4 text-gray-700"></i>
                </button>
                <button @click.stop="editMedia(item)"
                        class="p-2 rounded-lg bg-white/90 backdrop-blur-sm hover:bg-white transition-colors shadow-lg"
                        title="{{ __('media.actions.edit') }}">
                    <i data-lucide="edit" class="w-4 h-4 text-gray-700"></i>
                </button>
                <button @click.stop="deleteMedia(item)"
                        class="p-2 rounded-lg bg-red-500/90 backdrop-blur-sm hover:bg-red-600 transition-colors shadow-lg"
                        title="{{ __('media.actions.delete') }}">
                    <i data-lucide="trash-2" class="w-4 h-4 text-white"></i>
                </button>
            </div>
        </div>

        <!-- File Type Badge -->
        <div class="absolute top-3 right-3">
            <span class="px-2 py-1 rounded-full text-xs font-bold shadow-lg backdrop-blur-sm"
                  :class="{
                      'bg-blue-500/90 text-white': item.type === 'image',
                      'bg-purple-500/90 text-white': item.type === 'video',
                      'bg-green-500/90 text-white': item.type === 'audio',
                      'bg-orange-500/90 text-white': item.type === 'document'
                  }"
                  x-text="item.type_label || item.type"></span>
        </div>

        <!-- Shimmer Effect on Hover -->
        <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent transform -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
        </div>
    </div>

    <!-- Card Body -->
    <div class="p-4">
        <!-- Title -->
        <h3 class="font-bold text-sm mb-1 line-clamp-1 group-hover:text-[#64CCC5] transition-colors"
            :class="darkMode ? 'text-white' : 'text-gray-900'"
            x-text="item.name"></h3>

        <!-- Filename -->
        <p class="text-xs mb-2 line-clamp-1"
           :class="darkMode ? 'text-gray-500' : 'text-gray-500'"
           x-text="item.filename"></p>

        <!-- Meta Information -->
        <div class="flex items-center justify-between text-xs"
             :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <div class="flex items-center gap-2">
                <!-- File Size -->
                <span class="flex items-center gap-1">
                    <i data-lucide="hard-drive" class="w-3 h-3"></i>
                    <span x-text="item.size"></span>
                </span>

                <!-- Dimensions (for images/videos) -->
                <template x-if="item.dimensions">
                    <span class="flex items-center gap-1">
                        <i data-lucide="maximize-2" class="w-3 h-3"></i>
                        <span x-text="item.dimensions"></span>
                    </span>
                </template>
            </div>
        </div>

        <!-- Upload Date -->
        <div class="mt-2 pt-2 border-t"
             :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <div class="flex items-center gap-1 text-xs"
                 :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                <i data-lucide="clock" class="w-3 h-3"></i>
                <span x-text="item.uploaded"></span>
            </div>
        </div>

        <!-- Tags (if available) -->
        <template x-if="item.tags && item.tags.length > 0">
            <div class="mt-2 flex flex-wrap gap-1">
                <template x-for="tag in item.tags.slice(0, 3)" :key="tag">
                    <span class="px-2 py-0.5 rounded-full text-xs"
                          :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'"
                          x-text="tag"></span>
                </template>
                <template x-if="item.tags.length > 3">
                    <span class="px-2 py-0.5 rounded-full text-xs"
                          :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'"
                          x-text="`+${item.tags.length - 3}`"></span>
                </template>
            </div>
        </template>
    </div>

    <!-- Click Handler for Card -->
    <button @click="viewMedia(item)"
            class="absolute inset-0 z-0"
            aria-label="{{ __('media.actions.view') }}">
    </button>
</div>

@once
@push('styles')
<style>
    /* Premium card hover effects */
    .premium-card {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .premium-card:hover {
        transform: scale(1.05) translateY(-8px);
    }

    /* Line clamp utility */
    .line-clamp-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush
@endonce
