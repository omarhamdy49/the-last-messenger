{{--
    Media Picker Modal Component

    A reusable media selection modal with tabbed interface for browsing and uploading media.

    Usage:
    <x-admin.media-picker-modal />

    JavaScript API:
    window.MediaPicker.open({ mode: 'single|multiple', type: 'image|video|all', onSelect: callback })
    window.MediaPicker.close()
    window.MediaPicker.getSelected()
--}}

<div x-data="mediaPickerModal()"
     x-show="isOpen"
     x-cloak
     @open-media-library.window="handleOpen($event.detail)"
     @open-media-episodes-modal.window="handleOpen($event.detail)"
     @media-preview.window="openPreview($event.detail.media)"
     @keydown.escape.window="close()"
     class="fixed inset-0 z-50 overflow-hidden"
     style="display: none;">

    <!-- Backdrop -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="close()"
         class="fixed inset-0 glass"
         :class="$root.darkMode ? 'bg-black/80' : 'bg-black/50'">
    </div>

    <!-- Modal Container -->
    <div class="fixed inset-0 overflow-hidden flex items-center justify-center p-4">
        <div x-show="isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.stop
             class="relative w-full max-w-7xl h-[90vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden glass"
             :class="$root.darkMode ? 'bg-gray-900/95 border border-gray-800' : 'bg-white border border-gray-200'">

            <!-- Header -->
            <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b"
                 :class="$root.darkMode ? 'border-gray-800' : 'border-gray-200'">
                <div>
                    <h2 class="text-2xl font-bold gradient-text">
                        {{ __('media.picker.title') }}
                    </h2>
                    <p class="text-sm mt-1"
                       :class="$root.darkMode ? 'text-gray-400' : 'text-gray-600'">
                        <span x-text="selectionHelp()"></span>
                        <span x-show="filterType !== 'all'" class="ms-1">
                            (<span x-text="typeLabel(filterType)"></span>)
                        </span>
                    </p>
                </div>

                <button @click="close()"
                        class="p-2 rounded-lg transition-all hover:scale-110"
                        :class="$root.darkMode ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <!-- Tabs -->
            <div class="flex border-b"
                 :class="$root.darkMode ? 'border-gray-800 bg-gray-900/50' : 'border-gray-200 bg-gray-50'">
                <button @click="activeTab = 'browse'"
                        class="flex items-center gap-2 px-6 py-3 font-semibold transition-colors relative"
                        :class="activeTab === 'browse' ?
                            'text-accent border-b-2 border-accent' :
                            ($root.darkMode ? 'text-gray-400 hover:text-gray-200' : 'text-gray-600 hover:text-gray-900')">
                    <i data-lucide="image" class="w-5 h-5"></i>
                    <span>{{ __('media.picker.browse') }}</span>
                </button>

                <button @click="activeTab = 'upload'"
                        class="flex items-center gap-2 px-6 py-3 font-semibold transition-colors relative"
                        :class="activeTab === 'upload' ?
                            'text-accent border-b-2 border-accent' :
                            ($root.darkMode ? 'text-gray-400 hover:text-gray-200' : 'text-gray-600 hover:text-gray-900')">
                    <i data-lucide="upload" class="w-5 h-5"></i>
                    <span>{{ __('media.picker.upload_new') }}</span>
                </button>
            </div>

            <!-- Browse Tab -->
            <div x-show="activeTab === 'browse'" class="flex-1 flex overflow-hidden">

                <!-- Main Content Area -->
                <div class="flex-1 flex flex-col overflow-hidden">

                    <!-- Search and Filters -->
                    <div class="px-6 py-4 border-b"
                         :class="$root.darkMode ? 'border-gray-800 bg-gray-900/50' : 'border-gray-200 bg-gray-50'">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <!-- Search -->
                            <div class="flex-1 relative">
                                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5"
                                   :class="$root.darkMode ? 'text-gray-500' : 'text-gray-400'"></i>
                                <input type="text"
                                       x-model="searchQuery"
                                       @input.debounce.300ms="searchMedia()"
                                       placeholder="{{ __('media.picker.search_placeholder') }}"
                                       class="w-full ps-10 pe-4 py-2 rounded-lg border focus:ring-2 focus:ring-accent/50 focus:border-accent transition-all"
                                       :class="$root.darkMode ?
                                           'bg-gray-800 border-gray-700 text-white placeholder-gray-500' :
                                           'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                            </div>

                            <!-- Type Filter -->
                            <div class="sm:w-48">
                                <select x-model="filterType"
                                        @change="loadMedia()"
                                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-accent/50 focus:border-accent transition-all"
                                        :class="$root.darkMode ?
                                            'bg-gray-800 border-gray-700 text-white' :
                                            'bg-white border-gray-300 text-gray-900'">
                                    <option value="all">{{ __('media.picker.all_types') }}</option>
                                    <option value="image">{{ __('media.filter_panel.images') }}</option>
                                    <option value="video">{{ __('media.filter_panel.videos') }}</option>
                                    <option value="audio">{{ __('media.filter_panel.audio') }}</option>
                                    <option value="document">{{ __('media.filter_panel.documents') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Media Grid -->
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
                </div>

                <!-- Selected Items Sidebar -->
                <div x-show="(selectedItems?.length || 0) > 0"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="w-80 border-s overflow-y-auto custom-scrollbar"
                     :class="$root.darkMode ? 'border-gray-800 bg-gray-900' : 'border-gray-200 bg-gray-50'">

                    <div class="p-4 border-b sticky top-0 z-10"
                         :class="$root.darkMode ? 'border-gray-800 bg-gray-900' : 'border-gray-200 bg-gray-50'">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-bold"
                                :class="$root.darkMode ? 'text-white' : 'text-gray-900'">
                                {{ __('media.picker.selected') }} (<span x-text="selectedItems?.length || 0"></span>)
                            </h3>
                            <button @click="clearSelection()"
                                    class="text-sm font-medium text-red-500 hover:text-red-600 transition-colors">
                                {{ __('media.actions.clear_all') }}
                            </button>
                        </div>
                    </div>

                    <x-admin.selected-media-preview />
                </div>
            </div>

            <!-- Upload Tab -->
            <div x-show="activeTab === 'upload'" class="flex-1 overflow-y-auto custom-scrollbar px-6 py-6">
                <div class="max-w-2xl mx-auto">
                    <!-- Upload Area -->
                    <div class="space-y-6">
                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="p-4 rounded-2xl border"
                                 :class="$root.darkMode ? 'bg-gray-950 border-gray-800' : 'bg-gray-50 border-gray-200'">
                                <label class="block text-sm font-semibold mb-2"
                                       :class="$root.darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ __('media.picker.collection') }}
                                </label>
                                <input type="text"
                                       x-model="collectionName"
                                       placeholder="{{ __('media.picker.collection_placeholder') }}"
                                       class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                       :class="$root.darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">

                                <label class="block text-sm font-semibold mt-4 mb-2"
                                       :class="$root.darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ __('media.picker.shared_tags') }}
                                </label>
                                <input type="text"
                                       x-model="tagsText"
                                       placeholder="{{ __('media.picker.tags_placeholder') }}"
                                       class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                       :class="$root.darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            </div>

                            <div class="p-4 rounded-2xl border space-y-4"
                                 :class="$root.darkMode ? 'bg-gray-950 border-gray-800' : 'bg-gray-50 border-gray-200'">
                                <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer"
                                       :class="$root.darkMode ? 'border-gray-800 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-900'">
                                    <input type="checkbox"
                                           x-model="generateSizes"
                                           class="mt-1 rounded border-gray-300 text-[#0056b3] focus:ring-[#64CCC5]">
                                    <div>
                                        <div class="font-semibold text-sm">{{ __('media.picker.generate_sizes') }}</div>
                                        <div class="text-xs mt-1"
                                             :class="$root.darkMode ? 'text-gray-400' : 'text-gray-600'">
                                            {{ __('media.picker.generate_sizes_help') }}
                                        </div>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer"
                                       :class="$root.darkMode ? 'border-gray-800 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-900'">
                                    <input type="checkbox"
                                           x-model="optimizeOriginals"
                                           class="mt-1 rounded border-gray-300 text-[#0056b3] focus:ring-[#64CCC5]">
                                    <div>
                                        <div class="font-semibold text-sm">{{ __('media.picker.optimize_originals') }}</div>
                                        <div class="text-xs mt-1"
                                             :class="$root.darkMode ? 'text-gray-400' : 'text-gray-600'">
                                            {{ __('media.picker.optimize_originals_help') }}
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Drag and Drop Zone -->
                        <div @drop.prevent="handleDrop($event)"
                             @dragover.prevent="dragOver = true"
                             @dragleave.prevent="dragOver = false"
                             class="relative border-2 border-dashed rounded-2xl p-12 text-center transition-all duration-300"
                             :class="dragOver ?
                                 'border-accent bg-accent/5 scale-[1.02]' :
                                 ($root.darkMode ? 'border-gray-700 hover:border-gray-600' : 'border-gray-300 hover:border-gray-400')">

                            <input type="file"
                                   x-ref="fileInput"
                                   @change="handleFileSelect($event)"
                                   :accept="getAcceptedTypes()"
                                   multiple
                                   class="hidden">

                            <div class="flex flex-col items-center gap-4">
                                <div class="w-16 h-16 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center">
                                    <i data-lucide="upload-cloud" class="w-8 h-8 text-white"></i>
                                </div>

                                <div>
                                    <h3 class="text-lg font-bold mb-2"
                                        :class="$root.darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ __('media.picker.drop_or_browse') }}
                                    </h3>
                                    <p class="text-sm"
                                       :class="$root.darkMode ? 'text-gray-400' : 'text-gray-600'">
                                        <span x-show="filterType === 'image'">{{ __('media.picker.supports_images') }}</span>
                                        <span x-show="filterType === 'video'">{{ __('media.picker.supports_videos') }}</span>
                                        <span x-show="filterType === 'audio'">{{ __('media.picker.supports_audio') }}</span>
                                        <span x-show="filterType === 'document'">{{ __('media.picker.supports_documents') }}</span>
                                        <span x-show="filterType === 'all'">{{ __('media.picker.supports_all') }}</span>
                                        <span class="block mt-1">{{ __('media.picker.max_file_size') }}</span>
                                    </p>
                                </div>

                                <button @click="$refs.fileInput.click()"
                                        type="button"
                                        class="px-6 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg shadow-accent/30">
                                    {{ __('media.picker.choose_files') }}
                                </button>
                            </div>
                        </div>

                        <!-- Upload Queue -->
                        <div x-show="(uploadQueue?.length || 0) > 0" class="space-y-3">
                            <h4 class="font-bold"
                                :class="$root.darkMode ? 'text-white' : 'text-gray-900'">
                                {{ __('media.picker.uploading') }} (<span x-text="uploadQueue?.length || 0"></span>)
                            </h4>

                            <template x-for="(file, index) in (uploadQueue || [])" :key="index">
                                <div class="p-4 rounded-lg border"
                                     :class="$root.darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'">
                                    <div class="flex items-center gap-4">
                                        <div class="flex-shrink-0 w-12 h-12 rounded-lg overflow-hidden"
                                             :class="$root.darkMode ? 'bg-gray-700' : 'bg-gray-100'">
                                            <template x-if="file.preview">
                                                <img :src="file.preview" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!file.preview">
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <i data-lucide="file" class="w-6 h-6"
                                                       :class="$root.darkMode ? 'text-gray-500' : 'text-gray-400'"></i>
                                                </div>
                                            </template>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium truncate"
                                               :class="$root.darkMode ? 'text-white' : 'text-gray-900'"
                                               x-text="file.name"></p>
                                            <p class="text-sm"
                                               :class="$root.darkMode ? 'text-gray-400' : 'text-gray-600'"
                                               x-text="formatFileSize(file.size)"></p>

                                            <!-- Progress Bar -->
                                            <div class="mt-2 h-1.5 rounded-full overflow-hidden"
                                                 :class="$root.darkMode ? 'bg-gray-700' : 'bg-gray-200'">
                                                <div class="h-full bg-gradient-to-r from-[#0056b3] to-[#218838] transition-all duration-300"
                                                     :style="`width: ${file.progress}%`">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex-shrink-0 text-sm font-medium"
                                             :class="file.status === 'error' ? 'text-red-500' : 'text-accent'"
                                             x-text="file.status === 'error' ? @js(__('media.upload_progress.failed')) : file.progress + '%'">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center justify-between px-6 py-4 border-t"
                 :class="$root.darkMode ? 'border-gray-800 bg-gray-900' : 'border-gray-200 bg-gray-50'">
                <div class="text-sm"
                    :class="$root.darkMode ? 'text-gray-400' : 'text-gray-600'">
                    <span x-show="(selectedItems?.length || 0) > 0">
                        <span x-text="selectedItems?.length || 0"></span> {{ __('media.picker.selected_items') }}
                    </span>
                    <span x-show="(selectedItems?.length || 0) === 0">
                        {{ __('media.picker.no_items_selected') }}
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="close()"
                            class="px-6 py-2 rounded-lg font-semibold transition-all hover:scale-105"
                            :class="$root.darkMode ?
                                'bg-gray-800 hover:bg-gray-700 text-white' :
                                'bg-gray-100 hover:bg-gray-200 text-gray-900'">
                        {{ __('admin.cancel') }}
                    </button>

                    <button @click="confirmSelection()"
                            :disabled="(selectedItems?.length || 0) === 0"
                            class="px-6 py-2 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed hover:scale-105 disabled:hover:scale-100"
                            :class="(selectedItems?.length || 0) > 0 ? 'shadow-accent/30' : ''">
                        {{ __('media.actions.confirm_selection') }}
                    </button>
                </div>
            </div>

            <div x-show="previewModal.open"
                 x-cloak
                 class="absolute inset-0 z-10 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/80" @click="closePreview()"></div>
                <div class="relative w-full max-w-5xl rounded-2xl overflow-hidden shadow-2xl"
                     :class="$root.darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                    <div class="flex items-center justify-between px-6 py-4 border-b"
                         :class="$root.darkMode ? 'border-gray-800' : 'border-gray-200'">
                        <div>
                            <h3 class="text-xl font-bold" :class="$root.darkMode ? 'text-white' : 'text-gray-900'">{{ __('media.preview.title') }}</h3>
                            <p class="text-sm" :class="$root.darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="previewModal.media?.title || previewModal.media?.original_filename || ''"></p>
                        </div>
                        <button @click="closePreview()"
                                class="p-2 rounded-lg transition-all"
                                :class="$root.darkMode ? 'text-gray-400 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-100'">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="grid lg:grid-cols-[minmax(0,1fr)_280px]">
                        <div class="p-6"
                             :class="$root.darkMode ? 'bg-gray-950' : 'bg-gray-50'">
                            <div class="rounded-2xl overflow-hidden flex items-center justify-center min-h-[420px]"
                                 :class="$root.darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                                <template x-if="previewModal.media && previewModal.media.is_image">
                                    <img :src="activePreviewSource()?.url || previewModal.media.preview_url || previewModal.media.src"
                                         :alt="previewModal.media.title"
                                         class="max-h-[70vh] max-w-full h-auto w-auto object-contain">
                                </template>

                                <template x-if="previewModal.media && previewModal.media.is_video">
                                    <video :src="activePreviewSource()?.url || previewModal.media.src"
                                           controls
                                           class="max-h-[70vh] w-full bg-black"></video>
                                </template>

                                <template x-if="previewModal.media && previewModal.media.is_audio">
                                    <div class="w-full max-w-xl px-8 py-10 text-center space-y-6">
                                        <div class="w-20 h-20 mx-auto rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center">
                                            <i data-lucide="music-4" class="w-10 h-10 text-white"></i>
                                        </div>
                                        <audio :src="activePreviewSource()?.url || previewModal.media.src"
                                               controls
                                               class="w-full"></audio>
                                    </div>
                                </template>

                                <template x-if="previewModal.media && previewModal.media.is_document">
                                    <iframe :src="activePreviewSource()?.url || previewModal.media.src"
                                            class="h-[70vh] w-full"
                                            :title="previewModal.media.title"></iframe>
                                </template>
                            </div>
                        </div>

                        <aside class="p-6 border-t lg:border-t-0 lg:border-s space-y-5"
                               :class="$root.darkMode ? 'border-gray-800 bg-gray-900' : 'border-gray-200 bg-white'">
                            <div>
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em]"
                                    :class="$root.darkMode ? 'text-gray-500' : 'text-gray-500'">
                                    {{ __('media.preview.available_sizes') }}
                                </h4>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <template x-for="source in previewSources(previewModal.media)" :key="source.key">
                                        <button @click="previewModal.activeSource = source.key"
                                                type="button"
                                                class="px-3 py-2 rounded-xl text-sm font-semibold transition-all"
                                                :class="previewModal.activeSource === source.key
                                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg'
                                                    : ($root.darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')">
                                            <span x-text="source.label"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div class="space-y-3 text-sm">
                                <div>
                                    <p class="font-semibold" :class="$root.darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.preview.file_name') }}</p>
                                    <p class="mt-1 break-all" :class="$root.darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="previewModal.media?.original_filename || ''"></p>
                                </div>
                                <div>
                                    <p class="font-semibold" :class="$root.darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.preview.dimensions') }}</p>
                                    <p class="mt-1" :class="$root.darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="activePreviewDimensions()"></p>
                                </div>
                                <div>
                                    <p class="font-semibold" :class="$root.darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.fields.size') }}</p>
                                    <p class="mt-1" :class="$root.darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="activePreviewSize()"></p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-3 pt-2">
                                <a :href="activePreviewSource()?.url || previewModal.media?.src || '#'"
                                   target="_blank"
                                   rel="noopener"
                                   class="px-4 py-3 rounded-xl font-semibold transition-all"
                                   :class="$root.darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                                    {{ __('media.preview.open_original') }}
                                </a>
                                <button @click="closePreview()"
                                        type="button"
                                        class="px-4 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold shadow-lg">
                                    {{ __('admin.cancel') }}
                                </button>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function mediaPickerModal() {
    return {
        translations: {
            singleHelp: @json(__('media.picker.single_help')),
            multipleHelp: @json(__('media.picker.multiple_help')),
            typeLabels: {
                image: @json(__('media.types.image')),
                video: @json(__('media.types.video')),
                audio: @json(__('media.types.audio')),
                document: @json(__('media.types.document')),
            },
        },
        isOpen: false,
        activeTab: 'browse',
        selectionMode: 'single', // 'single' or 'multiple'
        filterType: 'all', // 'all', 'image', 'video'
        searchQuery: '',
        mediaItems: [],
        selectedItems: [],
        isLoading: false,
        currentPage: 1,
        hasMorePages: false,
        onSelectCallback: null,
        currentTarget: null, // 'thumbnail', 'gallery', etc.
        collectionName: '',
        tagsText: '',
        generateSizes: true,
        optimizeOriginals: true,
        previewModal: {
            open: false,
            media: null,
            activeSource: 'original',
        },

        // File Upload Properties
        dragOver: false,
        uploadQueue: [],

        init() {
            // Set up global MediaPicker API
            window.MediaPicker = {
                open: (options = {}) => {
                    this.open(options);
                },
                close: () => {
                    this.close();
                },
                getSelected: () => {
                    return this.selectedItems;
                }
            };
        },

        handleOpen(options = {}) {
            // Convert 'multiple' boolean to 'mode' string
            if (options.multiple !== undefined) {
                this.selectionMode = options.multiple ? 'multiple' : 'single';
            } else {
                this.selectionMode = options.mode || 'single';
            }

            // Store target for callback
            this.currentTarget = options.target || null;

            this.filterType = options.type || 'all';
            this.onSelectCallback = options.onSelect || null;
            this.selectedItems = [];
            this.activeTab = 'browse';
            this.searchQuery = '';
            this.currentPage = 1;
            this.isOpen = true;

            this.loadMedia();

            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        open(options = {}) {
            this.handleOpen(options);
        },

        close() {
            this.isOpen = false;
            this.selectedItems = [];
            this.mediaItems = [];
            this.uploadQueue = [];
            this.collectionName = '';
            this.tagsText = '';
            this.generateSizes = true;
            this.optimizeOriginals = true;
            this.closePreview();
        },

        openPreview(media) {
            if (!media) {
                return;
            }

            this.previewModal.media = media;
            this.previewModal.activeSource = this.defaultPreviewSource(media);
            this.previewModal.open = true;
            this.$nextTick(() => lucide.createIcons());
        },

        closePreview() {
            this.previewModal.open = false;
            this.previewModal.media = null;
            this.previewModal.activeSource = 'original';
        },

        previewSources(media) {
            if (!media) {
                return [];
            }

            const sources = [{
                key: 'original',
                label: @json(__('media.preview.original')),
                url: media.src,
                width: media.width,
                height: media.height,
                size: media.human_readable_size,
            }];

            Object.entries(media.variants || {}).forEach(([key, variant]) => {
                sources.push({
                    key,
                    label: key,
                    url: variant.url,
                    width: variant.width || null,
                    height: variant.height || null,
                    size: variant.human_readable_size || variant.size || null,
                });
            });

            return sources;
        },

        defaultPreviewSource(media) {
            if (!media) {
                return 'original';
            }

            if (media.is_image && media.variants && media.variants.medium) {
                return 'medium';
            }

            return 'original';
        },

        activePreviewSource() {
            return this.previewSources(this.previewModal.media)
                .find((source) => source.key === this.previewModal.activeSource) || null;
        },

        activePreviewDimensions() {
            const source = this.activePreviewSource();
            if (!source || !source.width || !source.height) {
                return '-';
            }

            return `${source.width} × ${source.height}`;
        },

        activePreviewSize() {
            const source = this.activePreviewSource();

            return source?.size || this.previewModal.media?.human_readable_size || '-';
        },

        selectionHelp() {
            return this.selectionMode === 'multiple'
                ? this.translations.multipleHelp
                : this.translations.singleHelp;
        },

        typeLabel(type) {
            return this.translations.typeLabels[type] || type;
        },

        async loadMedia(page = 1) {
            this.isLoading = true;
            this.currentPage = page;

            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: 20
                });

                if (this.filterType !== 'all') {
                    params.append('type', this.filterType);
                }

                if (this.searchQuery) {
                    params.append('search', this.searchQuery);
                }

                const response = await fetch(`${@json(route('admin.media-library.index'))}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const payload = await response.json();
                if (!response.ok || payload.success === false) {
                    throw new Error(payload.message || @json(__('media.library_page.messages.request_failed')));
                }

                const items = payload.data || [];
                const meta = payload.meta || {};

                if (page === 1) {
                    this.mediaItems = items;
                } else {
                    this.mediaItems = [...this.mediaItems, ...items];
                }

                this.currentPage = meta.current_page || page;
                this.hasMorePages = (meta.current_page || page) < (meta.last_page || page);
            } catch (error) {
                console.error('Failed to load media:', error);
            } finally {
                this.isLoading = false;
                this.$nextTick(() => {
                    lucide.createIcons();
                });
            }
        },

        async loadMore() {
            await this.loadMedia(this.currentPage + 1);
        },

        searchMedia() {
            this.currentPage = 1;
            this.loadMedia();
        },

        toggleSelection(media) {
            if (this.selectionMode === 'single') {
                this.selectedItems = [media];
            } else {
                const index = this.selectedItems.findIndex(item => item.id === media.id);
                if (index > -1) {
                    this.selectedItems.splice(index, 1);
                } else {
                    this.selectedItems.push(media);
                }
            }
        },

        isSelected(mediaId) {
            return this.selectedItems.some(item => item.id === mediaId);
        },

        removeFromSelection(mediaId) {
            const index = this.selectedItems.findIndex(item => item.id === mediaId);
            if (index > -1) {
                this.selectedItems.splice(index, 1);
            }
        },

        clearSelection() {
            this.selectedItems = [];
        },

        confirmSelection() {
            if (this.selectedItems.length === 0) return;

            const result = this.selectionMode === 'single'
                ? this.selectedItems[0]
                : this.selectedItems;

            if (this.onSelectCallback) {
                this.onSelectCallback(result);
            }

            // Dispatch event for other components with target information
            window.dispatchEvent(new CustomEvent('media-selected', {
                detail: {
                    target: this.currentTarget,
                    media: result,
                    multiple: this.selectionMode === 'multiple'
                }
            }));

            this.close();
        },

        formatFileSize(bytes) {
            if (!bytes) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        // File Upload Methods
        handleDrop(event) {
            this.dragOver = false;
            const files = Array.from(event.dataTransfer.files);
            this.uploadFiles(files);
        },

        handleFileSelect(event) {
            const files = Array.from(event.target.files);
            this.uploadFiles(files);
            event.target.value = ''; // Reset input
        },

        getAcceptedTypes() {
            if (this.filterType === 'image') {
                return 'image/jpeg,image/png,image/gif,image/webp';
            } else if (this.filterType === 'video') {
                return 'video/mp4,video/webm,video/avi,video/quicktime';
            } else if (this.filterType === 'audio') {
                return 'audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/ogg,audio/mp4';
            } else if (this.filterType === 'document') {
                return 'application/pdf';
            }
            return 'image/*,video/*,audio/*,application/pdf';
        },

        async uploadFiles(files) {
            for (const file of files) {
                const queueItem = {
                    name: file.name,
                    size: file.size,
                    progress: 0,
                    status: 'uploading',
                    preview: null
                };

                // Generate preview for images
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        queueItem.preview = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }

                this.uploadQueue.push(queueItem);
                await this.uploadFile(file, queueItem);
            }
        },

        async uploadFile(file, queueItem) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('collection_name', this.collectionName || 'general');
            formData.append('tags', this.tagsText);
            formData.append('generate_sizes', this.generateSizes ? '1' : '0');
            formData.append('optimize', this.optimizeOriginals ? '1' : '0');

            try {
                const xhr = new XMLHttpRequest();

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        queueItem.progress = Math.round((e.loaded / e.total) * 100);
                    }
                });

                xhr.addEventListener('load', () => {
                    if (xhr.status === 201) {
                        queueItem.status = 'complete';
                        queueItem.progress = 100;

                        const response = JSON.parse(xhr.responseText);

                        // Add to media grid
                        this.mediaItems.unshift(response.data);

                        // Auto-select if single mode
                        if (this.selectionMode === 'single') {
                            this.selectedItems = [response.data];
                        }

                        // Remove from queue after 2 seconds
                        setTimeout(() => {
                            const index = this.uploadQueue.indexOf(queueItem);
                            if (index > -1) {
                                this.uploadQueue.splice(index, 1);
                            }
                        }, 2000);

                        this.$nextTick(() => {
                            lucide.createIcons();
                        });
                    } else {
                        queueItem.status = 'error';
                    }
                });

                xhr.addEventListener('error', () => {
                    queueItem.status = 'error';
                });

                xhr.open('POST', @json(route('admin.media-library.store')));
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
                xhr.send(formData);
            } catch (error) {
                console.error('Upload failed:', error);
                queueItem.status = 'error';
            }
        }
    };
}
</script>
@endpush
