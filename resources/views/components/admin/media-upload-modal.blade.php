{{--
    Media Upload Modal Component

    Usage:
    <x-admin.media-upload-modal />

    Trigger via Alpine.js:
    @click="$dispatch('open-media-upload')"

    Listen for success:
    @media-uploaded.window="handleUploadSuccess($event.detail)"
--}}

<div x-data="mediaUploadModal()"
     @open-media-upload.window="openModal()"
     @keydown.escape.window="closeModal()"
     x-show="isOpen"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     role="dialog"
     aria-modal="true"
     aria-labelledby="upload-modal-title">

    <!-- Backdrop -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeModal()"
         class="fixed inset-0 bg-black/70 backdrop-blur-sm">
    </div>

    <!-- Modal Container -->
    <div class="flex min-h-screen items-center justify-center p-4">
        <div x-show="isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-90"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-90"
             @click.stop
             class="relative w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden"
             :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b"
                 :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                        <i data-lucide="upload-cloud" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h2 id="upload-modal-title"
                            class="text-xl font-bold"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ __('media.upload_modal.title') }}
                        </h2>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            <span x-text="files.length"></span> {{ __('media.upload_modal.selection_summary') }} - {{ __('media.upload_modal.limits_summary') }}
                        </p>
                    </div>
                </div>
                <button @click="closeModal()"
                        class="w-10 h-10 rounded-lg flex items-center justify-center transition-all hover:rotate-90"
                        :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Content -->
            <div class="p-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                <div class="mb-6 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-2xl p-4 border"
                         :class="darkMode ? 'bg-gray-950 border-gray-800' : 'bg-gray-50 border-gray-200'">
                        <h3 class="font-bold mb-3"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ __('media.upload_modal.options') }}
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold mb-2"
                                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ __('media.upload_modal.collection') }}
                                </label>
                                <input type="text"
                                       x-model="collectionName"
                                       placeholder="{{ __('media.upload_modal.collection_placeholder') }}"
                                       class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                       :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2"
                                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ __('media.upload_modal.shared_tags') }}
                                </label>
                                <input type="text"
                                       x-model="tagsText"
                                       placeholder="{{ __('media.upload_modal.tags_placeholder') }}"
                                       class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                       :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl p-4 border"
                         :class="darkMode ? 'bg-gray-950 border-gray-800' : 'bg-gray-50 border-gray-200'">
                        <h3 class="font-bold mb-3"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ __('media.upload_modal.image_processing') }}
                        </h3>
                        <div class="space-y-4">
                            <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer"
                                   :class="darkMode ? 'border-gray-800 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-900'">
                                <input type="checkbox"
                                       x-model="generateSizes"
                                       class="mt-1 rounded border-gray-300 text-[#0056b3] focus:ring-[#64CCC5]">
                                <div>
                                    <div class="font-semibold text-sm">{{ __('media.upload_modal.generate_sizes') }}</div>
                                    <div class="text-xs mt-1"
                                         :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                        {{ __('media.upload_modal.generate_sizes_help') }}
                                    </div>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer"
                                   :class="darkMode ? 'border-gray-800 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-900'">
                                <input type="checkbox"
                                       x-model="optimizeOriginals"
                                       class="mt-1 rounded border-gray-300 text-[#0056b3] focus:ring-[#64CCC5]">
                                <div>
                                    <div class="font-semibold text-sm">{{ __('media.upload_modal.optimize_originals') }}</div>
                                    <div class="text-xs mt-1"
                                         :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                        {{ __('media.upload_modal.optimize_originals_help') }}
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Drop Zone -->
                <div x-show="files.length === 0"
                     @drop.prevent="handleDrop($event)"
                     @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @click="$refs.fileInput.click()"
                     class="relative border-3 border-dashed rounded-2xl p-12 text-center cursor-pointer transition-all duration-300"
                     :class="isDragging ?
                        'border-accent bg-accent/10 scale-105' :
                        (darkMode ? 'border-gray-700 hover:border-gray-600 hover:bg-gray-800/50' : 'border-gray-300 hover:border-gray-400 hover:bg-gray-50')">

                    <!-- Upload Icon with Animation -->
                    <div class="flex justify-center mb-4">
                        <div class="relative">
                            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center animate-pulse">
                                <i data-lucide="upload" class="w-10 h-10 text-white"></i>
                            </div>
                            <!-- Animated Ring -->
                            <div class="absolute inset-0 rounded-full border-4 border-accent/30 animate-ping"></div>
                        </div>
                    </div>

                    <h3 class="text-2xl font-bold mb-2"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('media.upload_modal.drop_title') }}
                    </h3>
                    <p class="text-sm mb-4"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('media.upload_modal.drop_help') }}
                    </p>

                    <!-- Accepted File Types -->
                    <div class="flex flex-wrap justify-center gap-3 mt-6">
                        <span class="px-4 py-2 rounded-full text-xs font-semibold"
                              :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                            <i data-lucide="image" class="w-3 h-3 inline me-1"></i>
                            {{ __('media.upload_modal.accepted_images') }}
                        </span>
                        <span class="px-4 py-2 rounded-full text-xs font-semibold"
                              :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                            <i data-lucide="video" class="w-3 h-3 inline me-1"></i>
                            {{ __('media.upload_modal.accepted_videos') }}
                        </span>
                        <span class="px-4 py-2 rounded-full text-xs font-semibold"
                              :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                            <i data-lucide="music-4" class="w-3 h-3 inline me-1"></i>
                            {{ __('media.upload_modal.accepted_audio') }}
                        </span>
                        <span class="px-4 py-2 rounded-full text-xs font-semibold"
                              :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                            <i data-lucide="file-text" class="w-3 h-3 inline me-1"></i>
                            {{ __('media.upload_modal.accepted_documents') }}
                        </span>
                    </div>

                    <!-- Hidden File Input -->
                    <input type="file"
                           x-ref="fileInput"
                           @change="handleFileSelect($event)"
                           accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/avi,video/quicktime,audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/ogg,audio/mp4,application/pdf"
                           multiple
                           class="hidden">
                </div>

                <!-- Upload Queue -->
                <div x-show="files.length > 0" class="space-y-4">

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <button @click="$refs.fileInput.click()"
                                class="px-4 py-2 rounded-lg font-semibold transition-all hover:scale-105"
                                :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                            <i data-lucide="plus" class="w-4 h-4 inline me-2"></i>
                            {{ __('media.upload_modal.add_more_files') }}
                        </button>
                        <button @click="clearAll()"
                                class="px-4 py-2 rounded-lg font-semibold transition-all hover:scale-105"
                                :class="darkMode ? 'bg-red-900/20 text-red-400 hover:bg-red-900/30' : 'bg-red-50 text-red-600 hover:bg-red-100'">
                            <i data-lucide="trash-2" class="w-4 h-4 inline me-2"></i>
                            {{ __('media.actions.clear_all') }}
                        </button>
                    </div>

                    <!-- File List -->
                    <div class="space-y-3">
                        <template x-for="(file, index) in files" :key="file.id">
                            <x-admin.upload-progress />
                        </template>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between px-6 py-4 border-t"
                 :class="darkMode ? 'border-gray-800 bg-gray-900/50' : 'border-gray-200 bg-gray-50'">
                <div class="flex items-center gap-2">
                    <template x-if="uploadingCount > 0">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 border-2 border-accent border-t-transparent rounded-full animate-spin"></div>
                            <span class="text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                <span x-text="uploadingText()"></span>
                            </span>
                        </div>
                    </template>
                    <template x-if="successCount > 0 && uploadingCount === 0">
                        <div class="flex items-center gap-2 text-green-500">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                            <span class="text-sm font-semibold">
                                <span x-text="successCount"></span> {{ __('media.upload_modal.upload_success') }}
                            </span>
                        </div>
                    </template>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="closeModal()"
                            class="px-6 py-2 rounded-lg font-semibold transition-all"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">
                        {{ __('admin.cancel') }}
                    </button>
                    <button @click="startUpload()"
                            :disabled="files.length === 0 || uploadingCount > 0"
                            class="px-6 py-2 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white transition-all hover:shadow-lg hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                        <i data-lucide="upload" class="w-4 h-4 inline me-2"></i>
                        <span x-text="uploadButtonText()"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const mediaUploadModal = () => ({
        translations: {
            closeWhileUploading: @json(__('media.upload_modal.close_while_uploading')),
            clearWhileUploading: @json(__('media.upload_modal.clear_while_uploading')),
            maxFilesError: @json(__('media.upload_modal.max_files_error')),
            invalidType: @json(__('media.upload_modal.invalid_type')),
            imageSizeExceeded: @json(__('media.upload_modal.image_size_exceeded')),
            videoSizeExceeded: @json(__('media.upload_modal.video_size_exceeded')),
            audioSizeExceeded: @json(__('media.upload_modal.audio_size_exceeded')),
            pdfSizeExceeded: @json(__('media.upload_modal.pdf_size_exceeded')),
            uploadFailed: @json(__('media.upload_modal.upload_failed')),
            uploadCancelled: @json(__('media.upload_modal.upload_cancelled')),
            networkError: @json(__('media.upload_modal.network_error')),
            uploadingProgress: @json(__('media.upload_modal.uploading_progress')),
            uploadButton: @json(__('media.upload_modal.upload_button')),
        },
        isOpen: false,
        isDragging: false,
        files: [],
        uploadingCount: 0,
        successCount: 0,
        fileIdCounter: 0,
        collectionName: '',
        tagsText: '',
        generateSizes: true,
        optimizeOriginals: true,

        openModal() {
            this.isOpen = true;
            this.$nextTick(() => lucide.createIcons());
        },

        closeModal() {
            if (this.uploadingCount > 0) {
                if (!confirm(this.translations.closeWhileUploading)) {
                    return;
                }

                this.files.forEach((file) => {
                    if (file.xhr) {
                        file.xhr.abort();
                    }
                });
            }
            this.isOpen = false;
            setTimeout(() => {
                this.reset();
            }, 150);
        },

        reset() {
            this.files = [];
            this.uploadingCount = 0;
            this.successCount = 0;
            this.isDragging = false;
            this.collectionName = '';
            this.tagsText = '';
            this.generateSizes = true;
            this.optimizeOriginals = true;
        },

        handleDrop(event) {
            this.isDragging = false;
            const droppedFiles = Array.from(event.dataTransfer.files);
            this.addFiles(droppedFiles);
        },

        handleFileSelect(event) {
            const selectedFiles = Array.from(event.target.files);
            this.addFiles(selectedFiles);
            event.target.value = ''; // Reset input
        },

        addFiles(newFiles) {
            // Validate max files
            if (this.files.length + newFiles.length > 10) {
                alert(this.translations.maxFilesError);
                return;
            }

            newFiles.forEach(file => {
                // Validate file
                const validation = this.validateFile(file);

                const fileData = {
                    id: ++this.fileIdCounter,
                    file: file,
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    progress: 0,
                    status: validation.valid ? 'pending' : 'error',
                    error: validation.error || null,
                    thumbnail: null
                };

                this.files.push(fileData);

                // Generate thumbnail for images
                if (validation.valid && file.type.startsWith('image/')) {
                    this.generateThumbnail(file, fileData);
                }
            });

            this.$nextTick(() => lucide.createIcons());
        },

        validateFile(file) {
            const maxImageSize = 10 * 1024 * 1024; // 10MB
            const maxVideoSize = 100 * 1024 * 1024; // 100MB
            const maxAudioSize = 30 * 1024 * 1024; // 30MB
            const allowedTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'video/mp4', 'video/webm', 'video/avi', 'video/quicktime',
                'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/ogg', 'audio/mp4',
                'application/pdf'
            ];

            if (!allowedTypes.includes(file.type)) {
                return { valid: false, error: this.translations.invalidType };
            }

            if (file.type.startsWith('image/') && file.size > maxImageSize) {
                return { valid: false, error: this.translations.imageSizeExceeded };
            }

            if (file.type.startsWith('video/') && file.size > maxVideoSize) {
                return { valid: false, error: this.translations.videoSizeExceeded };
            }

            if (file.type.startsWith('audio/') && file.size > maxAudioSize) {
                return { valid: false, error: this.translations.audioSizeExceeded };
            }

            if (file.type === 'application/pdf' && file.size > maxImageSize) {
                return { valid: false, error: this.translations.pdfSizeExceeded };
            }

            return { valid: true };
        },

        generateThumbnail(file, fileData) {
            const reader = new FileReader();
            reader.onload = (e) => {
                fileData.thumbnail = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        removeFile(fileId) {
            const fileIndex = this.files.findIndex(f => f.id === fileId);
            if (fileIndex !== -1) {
                const file = this.files[fileIndex];
                // Cancel upload if in progress
                if (file.xhr) {
                    file.xhr.abort();
                }
                this.files.splice(fileIndex, 1);
            }
        },

        retryUpload(fileId) {
            const file = this.files.find(f => f.id === fileId);
            if (file) {
                file.status = 'pending';
                file.progress = 0;
                file.error = null;
                this.uploadFile(file);
            }
        },

        clearAll() {
            if (this.uploadingCount > 0) {
                if (!confirm(this.translations.clearWhileUploading)) {
                    return;
                }
            }
            this.files.forEach(file => {
                if (file.xhr) {
                    file.xhr.abort();
                }
            });
            this.files = [];
            this.uploadingCount = 0;
            this.successCount = 0;
        },

        async startUpload() {
            const pendingFiles = this.files.filter(f => f.status === 'pending' || f.status === 'error');

            if (pendingFiles.length === 0) {
                return;
            }

            // Upload files in parallel (max 3 at a time)
            const chunks = [];
            for (let i = 0; i < pendingFiles.length; i += 3) {
                chunks.push(pendingFiles.slice(i, i + 3));
            }

            for (const chunk of chunks) {
                await Promise.all(chunk.map(file => this.uploadFile(file)));
            }

            // Auto-close on success
            if (this.successCount === this.files.length) {
                setTimeout(() => {
                    this.closeModal();
                }, 2000);
            }
        },

        uploadFile(fileData) {
            return new Promise((resolve) => {
                fileData.status = 'uploading';
                fileData.progress = 0;
                fileData.error = null;
                this.uploadingCount++;

                const formData = new FormData();
                formData.append('file', fileData.file);
                formData.append('collection_name', this.collectionName || 'general');
                formData.append('tags', this.tagsText);
                formData.append('generate_sizes', this.generateSizes ? '1' : '0');
                formData.append('optimize', this.optimizeOriginals ? '1' : '0');
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                const xhr = new XMLHttpRequest();
                fileData.xhr = xhr;

                // Progress handler
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        fileData.progress = Math.round((e.loaded / e.total) * 100);
                    }
                });

                // Success handler
                xhr.addEventListener('load', () => {
                    this.uploadingCount = Math.max(0, this.uploadingCount - 1);

                    if (xhr.status === 200 || xhr.status === 201) {
                        fileData.status = 'success';
                        fileData.progress = 100;
                        this.successCount++;

                        // Dispatch success event
                        try {
                            const response = JSON.parse(xhr.responseText);
                            window.dispatchEvent(new CustomEvent('media-uploaded', {
                                detail: { file: fileData, response: response }
                            }));
                        } catch (e) {
                            console.error('Failed to parse response:', e);
                        }
                    } else {
                        fileData.status = 'error';
                        fileData.error = this.translations.uploadFailed;
                        try {
                            const response = JSON.parse(xhr.responseText);
                            fileData.error = response.message || this.translations.uploadFailed;
                        } catch (e) {
                            fileData.error = `${this.translations.uploadFailed} (${xhr.status})`;
                        }
                    }

                    resolve();
                });

                // Error handler
                xhr.addEventListener('error', () => {
                    this.uploadingCount = Math.max(0, this.uploadingCount - 1);
                    fileData.status = 'error';
                    fileData.error = this.translations.networkError;
                    resolve();
                });

                // Abort handler
                xhr.addEventListener('abort', () => {
                    this.uploadingCount = Math.max(0, this.uploadingCount - 1);
                    fileData.status = 'error';
                    fileData.error = this.translations.uploadCancelled;
                    resolve();
                });

                // Send request
                xhr.open('POST', @json(route('admin.media-library.store')), true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send(formData);
            });
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        getFileIcon(type) {
            if (type.startsWith('image/')) return 'image';
            if (type.startsWith('video/')) return 'video';
            if (type === 'application/pdf') return 'file-text';
            return 'file';
        },

        uploadingText() {
            return this.translations.uploadingProgress
                .replace(':current', this.uploadingCount)
                .replace(':total', this.files.length);
        },

        uploadButtonText() {
            return this.translations.uploadButton.replace(':count', this.files.length);
        }
    });

if (window.Alpine) {
    window.Alpine.data('mediaUploadModal', mediaUploadModal);
} else {
    document.addEventListener('alpine:init', () => {
        Alpine.data('mediaUploadModal', mediaUploadModal);
    }, { once: true });
}
</script>
@endpush
