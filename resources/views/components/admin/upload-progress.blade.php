{{--
    Upload Progress Component

    Used within media-upload-modal to display individual file upload progress.
    This component is rendered within an x-for loop with file data from parent scope.

    Parent scope variables used:
    - file: The file object with status, progress, name, size, etc.
    - index: The index in the files array
--}}

<div class="rounded-xl border overflow-hidden transition-all hover:shadow-lg"
     :class="{
         'border-gray-700 bg-gray-800': darkMode && file.status === 'pending',
         'border-gray-300 bg-white': !darkMode && file.status === 'pending',
         'border-blue-500 bg-blue-900/10': darkMode && file.status === 'uploading',
         'border-blue-400 bg-blue-50': !darkMode && file.status === 'uploading',
         'border-green-500 bg-green-900/10': darkMode && file.status === 'success',
         'border-green-400 bg-green-50': !darkMode && file.status === 'success',
         'border-red-500 bg-red-900/10': darkMode && file.status === 'error',
         'border-red-400 bg-red-50': !darkMode && file.status === 'error'
     }"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-y-4"
     x-transition:enter-end="opacity-100 transform translate-y-0">

    <div class="p-4">
        <div class="flex items-start gap-4">

            <!-- Thumbnail / Icon -->
            <div class="flex-shrink-0">
                <template x-if="file.thumbnail">
                    <div class="w-16 h-16 rounded-lg overflow-hidden border-2"
                         :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                        <img :src="file.thumbnail"
                             :alt="file.name"
                             class="w-full h-full object-cover">
                    </div>
                </template>

                <template x-if="!file.thumbnail">
                    <div class="w-16 h-16 rounded-lg flex items-center justify-center"
                         :class="{
                             'bg-gray-700': darkMode && file.status === 'pending',
                             'bg-gray-200': !darkMode && file.status === 'pending',
                             'bg-blue-900/30': darkMode && file.status === 'uploading',
                             'bg-blue-100': !darkMode && file.status === 'uploading',
                             'bg-green-900/30': darkMode && file.status === 'success',
                             'bg-green-100': !darkMode && file.status === 'success',
                             'bg-red-900/30': darkMode && file.status === 'error',
                             'bg-red-100': !darkMode && file.status === 'error'
                         }">
                        <i :data-lucide="getFileIcon(file.type)"
                           class="w-8 h-8"
                           :class="{
                               'text-gray-400': darkMode && file.status === 'pending',
                               'text-gray-500': !darkMode && file.status === 'pending',
                               'text-blue-400': darkMode && file.status === 'uploading',
                               'text-blue-600': !darkMode && file.status === 'uploading',
                               'text-green-400': darkMode && file.status === 'success',
                               'text-green-600': !darkMode && file.status === 'success',
                               'text-red-400': darkMode && file.status === 'error',
                               'text-red-600': !darkMode && file.status === 'error'
                           }"></i>
                    </div>
                </template>
            </div>

            <!-- File Info and Progress -->
            <div class="flex-1 min-w-0">

                <!-- File Name and Size -->
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold truncate"
                            :class="darkMode ? 'text-white' : 'text-gray-900'"
                            :title="file.name"
                            x-text="file.name">
                        </h4>
                        <p class="text-sm"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                           x-text="formatFileSize(file.size)">
                        </p>
                    </div>

                    <!-- Status Badge -->
                    <div class="flex items-center gap-2">
                        <!-- Pending -->
                        <template x-if="file.status === 'pending'">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                  :class="darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-200 text-gray-700'">
                                <i data-lucide="clock" class="w-3 h-3 inline me-1"></i>
                                Pending
                            </span>
                        </template>

                        <!-- Uploading -->
                        <template x-if="file.status === 'uploading'">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                  :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-600'">
                                <div class="w-3 h-3 inline-block me-1 border-2 border-current border-t-transparent rounded-full animate-spin"></div>
                                <span x-text="file.progress + '%'"></span>
                            </span>
                        </template>

                        <!-- Success -->
                        <template x-if="file.status === 'success'">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                  :class="darkMode ? 'bg-green-900/30 text-green-400' : 'bg-green-100 text-green-600'">
                                <i data-lucide="check-circle" class="w-3 h-3 inline me-1"></i>
                                Uploaded
                            </span>
                        </template>

                        <!-- Error -->
                        <template x-if="file.status === 'error'">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                  :class="darkMode ? 'bg-red-900/30 text-red-400' : 'bg-red-100 text-red-600'">
                                <i data-lucide="alert-circle" class="w-3 h-3 inline me-1"></i>
                                Failed
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Progress Bar -->
                <template x-if="file.status === 'uploading' || file.status === 'success'">
                    <div class="mb-2">
                        <div class="h-2 rounded-full overflow-hidden"
                             :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'">
                            <div class="h-full transition-all duration-300 ease-out"
                                 :style="`width: ${file.progress}%`"
                                 :class="{
                                     'bg-gradient-to-r from-[#0056b3] to-[#218838]': file.status === 'success',
                                     'bg-blue-500': file.status === 'uploading'
                                 }">
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Error Message -->
                <template x-if="file.status === 'error' && file.error">
                    <div class="mb-2 flex items-start gap-2 p-2 rounded-lg"
                         :class="darkMode ? 'bg-red-900/20' : 'bg-red-50'">
                        <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0 mt-0.5"
                           :class="darkMode ? 'text-red-400' : 'text-red-600'"></i>
                        <p class="text-sm"
                           :class="darkMode ? 'text-red-400' : 'text-red-600'"
                           x-text="file.error">
                        </p>
                    </div>
                </template>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2 mt-3">

                    <!-- Remove Button (for pending files) -->
                    <template x-if="file.status === 'pending'">
                        <button @click="removeFile(file.id)"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all hover:scale-105"
                                :class="darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
                            <i data-lucide="trash-2" class="w-3 h-3 inline me-1"></i>
                            Remove
                        </button>
                    </template>

                    <!-- Cancel Button (for uploading files) -->
                    <template x-if="file.status === 'uploading'">
                        <button @click="removeFile(file.id)"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all hover:scale-105"
                                :class="darkMode ? 'bg-red-900/30 text-red-400 hover:bg-red-900/40' : 'bg-red-100 text-red-600 hover:bg-red-200'">
                            <i data-lucide="x" class="w-3 h-3 inline me-1"></i>
                            Cancel
                        </button>
                    </template>

                    <!-- Retry Button (for failed uploads) -->
                    <template x-if="file.status === 'error'">
                        <button @click="retryUpload(file.id)"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white transition-all hover:scale-105 hover:shadow-lg">
                            <i data-lucide="rotate-cw" class="w-3 h-3 inline me-1"></i>
                            Retry
                        </button>
                        <button @click="removeFile(file.id)"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all hover:scale-105"
                                :class="darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
                            <i data-lucide="trash-2" class="w-3 h-3 inline me-1"></i>
                            Remove
                        </button>
                    </template>

                    <!-- View Button (for successful uploads) -->
                    <template x-if="file.status === 'success'">
                        <span class="px-3 py-1.5 rounded-lg text-xs font-semibold"
                              :class="darkMode ? 'bg-green-900/20 text-green-400' : 'bg-green-100 text-green-600'">
                            <i data-lucide="check-circle-2" class="w-3 h-3 inline me-1"></i>
                            Upload Complete
                        </span>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
