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
                            Upload Media Files
                        </h2>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            <span x-text="files.length"></span> file(s) selected - Max 10 files, 10MB for images, 100MB for videos
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
                        Drag & Drop Files Here
                    </h3>
                    <p class="text-sm mb-4"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        or click to browse your files
                    </p>

                    <!-- Accepted File Types -->
                    <div class="flex flex-wrap justify-center gap-3 mt-6">
                        <span class="px-4 py-2 rounded-full text-xs font-semibold"
                              :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                            <i data-lucide="image" class="w-3 h-3 inline me-1"></i>
                            JPG, PNG, GIF, WEBP
                        </span>
                        <span class="px-4 py-2 rounded-full text-xs font-semibold"
                              :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                            <i data-lucide="video" class="w-3 h-3 inline me-1"></i>
                            MP4, WEBM
                        </span>
                        <span class="px-4 py-2 rounded-full text-xs font-semibold"
                              :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                            <i data-lucide="file-text" class="w-3 h-3 inline me-1"></i>
                            PDF
                        </span>
                    </div>

                    <!-- Hidden File Input -->
                    <input type="file"
                           x-ref="fileInput"
                           @change="handleFileSelect($event)"
                           accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,application/pdf"
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
                            Add More Files
                        </button>
                        <button @click="clearAll()"
                                class="px-4 py-2 rounded-lg font-semibold transition-all hover:scale-105"
                                :class="darkMode ? 'bg-red-900/20 text-red-400 hover:bg-red-900/30' : 'bg-red-50 text-red-600 hover:bg-red-100'">
                            <i data-lucide="trash-2" class="w-4 h-4 inline me-2"></i>
                            Clear All
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
                                Uploading <span x-text="uploadingCount"></span> of <span x-text="files.length"></span> files...
                            </span>
                        </div>
                    </template>
                    <template x-if="successCount > 0 && uploadingCount === 0">
                        <div class="flex items-center gap-2 text-green-500">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                            <span class="text-sm font-semibold">
                                <span x-text="successCount"></span> file(s) uploaded successfully!
                            </span>
                        </div>
                    </template>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="closeModal()"
                            class="px-6 py-2 rounded-lg font-semibold transition-all"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">
                        Cancel
                    </button>
                    <button @click="startUpload()"
                            :disabled="files.length === 0 || uploadingCount > 0"
                            class="px-6 py-2 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white transition-all hover:shadow-lg hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                        <i data-lucide="upload" class="w-4 h-4 inline me-2"></i>
                        Upload <span x-text="files.length"></span> File(s)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function mediaUploadModal() {
    return {
        isOpen: false,
        isDragging: false,
        files: [],
        uploadingCount: 0,
        successCount: 0,
        fileIdCounter: 0,

        openModal() {
            this.isOpen = true;
            this.$nextTick(() => lucide.createIcons());
        },

        closeModal() {
            if (this.uploadingCount > 0) {
                if (!confirm('Files are still uploading. Are you sure you want to close?')) {
                    return;
                }
            }
            this.isOpen = false;
            // Auto-close after all successful uploads
            if (this.successCount === this.files.length && this.files.length > 0) {
                setTimeout(() => {
                    this.reset();
                }, 300);
            }
        },

        reset() {
            this.files = [];
            this.uploadingCount = 0;
            this.successCount = 0;
            this.isDragging = false;
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
                alert('Maximum 10 files allowed at once');
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
            const allowedTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'video/mp4', 'video/webm',
                'application/pdf'
            ];

            if (!allowedTypes.includes(file.type)) {
                return { valid: false, error: 'Invalid file type' };
            }

            if (file.type.startsWith('image/') && file.size > maxImageSize) {
                return { valid: false, error: 'Image size exceeds 10MB' };
            }

            if (file.type.startsWith('video/') && file.size > maxVideoSize) {
                return { valid: false, error: 'Video size exceeds 100MB' };
            }

            if (file.type === 'application/pdf' && file.size > maxImageSize) {
                return { valid: false, error: 'PDF size exceeds 10MB' };
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
                if (!confirm('Files are still uploading. Are you sure you want to clear all?')) {
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
                    this.uploadingCount--;

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
                        fileData.error = 'Upload failed';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            fileData.error = response.message || 'Upload failed';
                        } catch (e) {
                            fileData.error = `Upload failed (${xhr.status})`;
                        }
                    }

                    resolve();
                });

                // Error handler
                xhr.addEventListener('error', () => {
                    this.uploadingCount--;
                    fileData.status = 'error';
                    fileData.error = 'Network error';
                    resolve();
                });

                // Abort handler
                xhr.addEventListener('abort', () => {
                    this.uploadingCount--;
                    fileData.status = 'error';
                    fileData.error = 'Upload cancelled';
                    resolve();
                });

                // Send request
                xhr.open('POST', '/admin/media/upload', true);
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
        }
    };
}
</script>
@endpush
