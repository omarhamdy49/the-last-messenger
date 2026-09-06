{{--
    Media Picker Modal Upload Component

    Displays the upload tab with drag-and-drop zone, upload options, and upload queue.
    Supports dark mode, RTL layouts, and multiple file types.

    Usage:
    <x-admin.media.picker-upload />

    This component is part of the media-picker-modal and relies on Alpine.js data context
    from the parent mediaPickerModal() component.
--}}

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

                                <!-- Error Reason -->
                                <p x-show="file.status === 'error' && file.error"
                                   class="mt-1 text-xs text-red-500"
                                   x-text="file.error"></p>

                                <!-- Progress Bar -->
                                <div x-show="file.status !== 'error'"
                                     class="mt-2 h-1.5 rounded-full overflow-hidden"
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
