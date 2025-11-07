@props([
    'name' => '',
    'label' => '',
    'required' => false,
    'disabled' => false,
    'multiple' => false,
    'accept' => '',
    'helpText' => '',
    'maxSize' => null, // in MB
    'preview' => true,
    'existingFile' => null,
])

@php
    $acceptTypes = $accept ? explode(',', $accept) : [];
    $isImage = !empty($acceptTypes) && collect($acceptTypes)->contains(fn($type) => str_starts_with(trim($type), 'image/'));
@endphp

<div class="form-field"
     x-data="{
         hasError: false,
         isDragging: false,
         files: [],
         existingFile: '{{ $existingFile }}',
         preview: {{ $preview ? 'true' : 'false' }},
         maxSize: {{ $maxSize ?? 'null' }},
         multiple: {{ $multiple ? 'true' : 'false' }},
         isImage: {{ $isImage ? 'true' : 'false' }},

         init() {
             @if($errors->has($name))
                 this.hasError = true;
             @endif
         },

         handleFiles(event) {
             const selectedFiles = Array.from(event.target.files || []);

             // Validate file size
             if (this.maxSize) {
                 const maxBytes = this.maxSize * 1024 * 1024;
                 const invalidFiles = selectedFiles.filter(file => file.size > maxBytes);

                 if (invalidFiles.length > 0) {
                     alert(`Some files exceed the maximum size of ${this.maxSize}MB`);
                     return;
                 }
             }

             // Store files for preview
             this.files = selectedFiles.map(file => ({
                 name: file.name,
                 size: this.formatFileSize(file.size),
                 type: file.type,
                 url: URL.createObjectURL(file)
             }));

             // Clear existing file reference when new files are selected
             this.existingFile = null;
         },

         handleDrop(event) {
             event.preventDefault();
             this.isDragging = false;

             const dt = event.dataTransfer;
             const files = dt.files;

             // Trigger the file input change
             this.$refs.fileInput.files = files;
             this.handleFiles({ target: { files } });
         },

         removeFile(index) {
             this.files.splice(index, 1);

             // Clear the file input if no files left
             if (this.files.length === 0) {
                 this.$refs.fileInput.value = '';
             }
         },

         removeExistingFile() {
             this.existingFile = null;
             // Add a hidden input to signal file removal
             const hiddenInput = document.createElement('input');
             hiddenInput.type = 'hidden';
             hiddenInput.name = '{{ $name }}_remove';
             hiddenInput.value = '1';
             this.$el.appendChild(hiddenInput);
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
             if (type.startsWith('audio/')) return 'music';
             if (type.includes('pdf')) return 'file-text';
             if (type.includes('word')) return 'file-text';
             if (type.includes('excel') || type.includes('spreadsheet')) return 'table';
             return 'file';
         }
     }">

    <!-- Label -->
    @if($label)
        <label class="block mb-2 font-semibold"
               :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <!-- Existing File Preview (if any) -->
    <div x-show="existingFile && !files.length"
         class="mb-4 p-4 rounded-lg border-2 border-dashed flex items-center justify-between"
         :class="darkMode ? 'bg-gray-900 border-gray-700' : 'bg-gray-50 border-gray-300'">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-gradient-to-br from-[#0056b3] to-[#218838]">
                <i data-lucide="file-check" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <p class="font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                    Current File
                </p>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Click to view or remove
                </p>
            </div>
        </div>
        <button type="button"
                @click="removeExistingFile()"
                class="p-2 rounded-lg transition-colors text-red-500 hover:bg-red-500/10">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- File Input (Hidden) -->
    <input type="file"
           x-ref="fileInput"
           name="{{ $name }}{{ $multiple ? '[]' : '' }}"
           @if($required) required @endif
           @if($disabled) disabled @endif
           @if($multiple) multiple @endif
           @if($accept) accept="{{ $accept }}" @endif
           @change="handleFiles($event)"
           class="sr-only">

    <!-- Drop Zone -->
    <div class="relative">
        <div @click="$refs.fileInput.click()"
             @dragover.prevent="isDragging = true"
             @dragleave.prevent="isDragging = false"
             @drop.prevent="handleDrop($event)"
             class="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-all duration-300"
             :class="[
                 isDragging
                     ? 'border-[#64CCC5] bg-gradient-to-r from-[#0056b3]/5 to-[#218838]/5 scale-105'
                     : (hasError ? 'border-red-500' : (darkMode ? 'border-gray-700 hover:border-gray-600 bg-gray-900/50' : 'border-gray-300 hover:border-gray-400 bg-gray-50/50')),
                 files.length > 0 ? 'border-[#64CCC5]' : '',
                 '{{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}'
             ]">

            <!-- Upload Icon and Text -->
            <div class="space-y-3">
                <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center bg-gradient-to-br from-[#0056b3] to-[#218838]">
                    <i data-lucide="upload-cloud" class="w-8 h-8 text-white"></i>
                </div>

                <div>
                    <p class="font-semibold text-lg mb-1"
                       :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                        <span x-show="!isDragging">Click to upload or drag and drop</span>
                        <span x-show="isDragging" class="text-[#64CCC5]">Drop files here</span>
                    </p>

                    @if($accept || $maxSize)
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            @if($accept)
                                Accepted: {{ $accept }}
                            @endif
                            @if($maxSize)
                                (Max: {{ $maxSize }}MB)
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- File Preview List -->
    <div x-show="files.length > 0" class="mt-4 space-y-3">
        <template x-for="(file, index) in files" :key="index">
            <div class="flex items-center gap-4 p-4 rounded-lg border transition-colors"
                 :class="darkMode ? 'bg-gray-900 border-gray-700' : 'bg-gray-50 border-gray-200'">

                <!-- Preview Thumbnail (for images) -->
                <template x-if="preview && isImage && file.type.startsWith('image/')">
                    <img :src="file.url"
                         :alt="file.name"
                         class="w-16 h-16 object-cover rounded-lg">
                </template>

                <!-- File Icon (for non-images) -->
                <template x-if="!preview || !isImage || !file.type.startsWith('image/')">
                    <div class="w-16 h-16 rounded-lg flex items-center justify-center bg-gradient-to-br from-[#0056b3] to-[#218838]">
                        <i :data-lucide="getFileIcon(file.type)" class="w-8 h-8 text-white"></i>
                    </div>
                </template>

                <!-- File Info -->
                <div class="flex-1 min-w-0">
                    <p class="font-semibold truncate" :class="darkMode ? 'text-gray-200' : 'text-gray-800'"
                       x-text="file.name"></p>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                       x-text="file.size"></p>
                </div>

                <!-- Success Icon -->
                <div class="flex-shrink-0">
                    <i data-lucide="check-circle" class="w-6 h-6 text-green-500"></i>
                </div>

                <!-- Remove Button -->
                <button type="button"
                        @click="removeFile(index)"
                        class="flex-shrink-0 p-2 rounded-lg transition-colors text-red-500 hover:bg-red-500/10">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </template>
    </div>

    <!-- Error Message -->
    @if($errors->has($name))
        <div class="mt-2 flex items-start gap-2 text-red-500 text-sm error-message animate-fadeInUp">
            <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
            <span>{{ $errors->first($name) }}</span>
        </div>
    @endif

    <!-- Help Text -->
    @if($helpText)
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <i data-lucide="info" class="w-4 h-4 inline mr-1"></i>
            {{ $helpText }}
        </p>
    @endif
</div>
