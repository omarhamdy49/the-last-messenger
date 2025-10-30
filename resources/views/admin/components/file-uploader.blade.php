{{-- File Uploader Component with Drag & Drop --}}
{{-- Usage: @include('admin.components.file-uploader', [
    'name' => 'images',
    'accept' => 'image/*',
    'multiple' => true,
    'maxSize' => 5,
    'label' => 'رفع الصور'
]) --}}

@php
    $name = $name ?? 'files';
    $accept = $accept ?? 'image/*,video/*,.pdf';
    $multiple = $multiple ?? true;
    $maxSize = $maxSize ?? 10; // MB
    $label = $label ?? 'رفع الملفات';
@endphp

<div
    x-data="fileUploader({
        name: '{{ $name }}',
        accept: '{{ $accept }}',
        multiple: {{ $multiple ? 'true' : 'false' }},
        maxSize: {{ $maxSize }}
    })"
    x-init="init()"
    class="space-y-4"
>
    <!-- Label -->
    <label class="block font-bold mb-2" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
        {{ $label }}
    </label>

    <!-- Drop Zone -->
    <div
        @drop.prevent="handleDrop($event)"
        @dragover.prevent="dragOver = true"
        @dragleave.prevent="dragOver = false"
        @click="$refs.fileInput.click()"
        class="relative border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all duration-300"
        :class="{
            'border-brand-primary bg-brand-primary/5': dragOver,
            'border-gray-300 dark:border-gray-600 hover:border-brand-primary/50 dark:hover:border-brand-primary/50': !dragOver,
            'bg-gray-50 dark:bg-gray-800/50': !dragOver
        }"
    >
        <input
            type="file"
            x-ref="fileInput"
            @change="uploadFiles($event.target.files)"
            :accept="accept"
            :multiple="multiple"
            class="hidden"
        >

        <!-- Upload Icon -->
        <div class="flex flex-col items-center gap-3">
            <div class="w-16 h-16 rounded-full flex items-center justify-center"
                 :class="dragOver ? 'bg-brand-primary text-white' : 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'">
                <i data-lucide="upload-cloud" class="w-8 h-8"></i>
            </div>

            <div>
                <p class="font-semibold mb-1" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                    <span class="text-brand-primary">انقر للرفع</span> أو اسحب الملفات هنا
                </p>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                    الحد الأقصى: {{ $maxSize }} ميجابايت
                </p>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div
            x-show="uploading"
            x-cloak
            class="absolute inset-0 bg-black/50 backdrop-blur-sm rounded-xl flex items-center justify-center"
        >
            <div class="text-center text-white">
                <div class="animate-spin w-12 h-12 border-4 border-white border-t-transparent rounded-full mx-auto mb-3"></div>
                <p class="font-semibold">جاري الرفع...</p>
                <div class="mt-3 w-48 h-2 bg-gray-700 rounded-full overflow-hidden">
                    <div
                        class="h-full bg-gradient-to-r from-[#0056b3] to-[#218838] transition-all duration-300"
                        :style="`width: ${progress}%`"
                    ></div>
                </div>
                <p class="text-sm mt-2" x-text="`${Math.round(progress)}%`"></p>
            </div>
        </div>
    </div>

    <!-- Uploaded Files List -->
    <div x-show="files.length > 0" x-cloak class="space-y-2">
        <template x-for="(file, index) in files" :key="file.id">
            <div
                class="flex items-center gap-3 p-3 rounded-lg border transition-colors"
                :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'"
            >
                <!-- File Preview/Icon -->
                <div class="flex-shrink-0">
                    <template x-if="file.preview">
                        <img :src="file.preview" :alt="file.name" class="w-12 h-12 object-cover rounded-lg">
                    </template>
                    <template x-if="!file.preview">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center"
                             :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'">
                            <i :data-lucide="getFileIcon(file.type)" class="w-6 h-6" :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                        </div>
                    </template>
                </div>

                <!-- File Info -->
                <div class="flex-1 min-w-0">
                    <p class="font-semibold truncate" :class="darkMode ? 'text-gray-200' : 'text-gray-700'" x-text="file.name"></p>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'" x-text="formatFileSize(file.size)"></p>
                </div>

                <!-- Delete Button -->
                <button
                    @click="deleteFile(file.id)"
                    type="button"
                    class="flex-shrink-0 p-2 rounded-lg transition-colors hover:bg-red-500/10 text-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                    :aria-label="`حذف ${file.name}`"
                >
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </template>
    </div>
</div>

<script>
    function fileUploader(config) {
        return {
            name: config.name,
            accept: config.accept,
            multiple: config.multiple,
            maxSize: config.maxSize * 1024 * 1024, // Convert MB to bytes
            files: [],
            uploading: false,
            progress: 0,
            dragOver: false,
            nextId: 1,

            init() {
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            },

            handleDrop(e) {
                this.dragOver = false;
                const files = Array.from(e.dataTransfer.files);
                this.uploadFiles(files);
            },

            uploadFiles(fileList) {
                if (!fileList || fileList.length === 0) return;

                const files = Array.from(fileList);

                // Validate file count
                if (!this.multiple && files.length > 1) {
                    window.showToast('يمكنك رفع ملف واحد فقط', 'error');
                    return;
                }

                // Validate and upload each file
                files.forEach(file => {
                    // Validate file size
                    if (file.size > this.maxSize) {
                        window.showToast(`حجم الملف ${file.name} يتجاوز الحد المسموح (${this.maxSize / 1024 / 1024} ميجابايت)`, 'error');
                        return;
                    }

                    this.uploadSingleFile(file);
                });
            },

            async uploadSingleFile(file) {
                const id = this.nextId++;
                const fileObj = {
                    id,
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    preview: null,
                    url: null
                };

                // Generate preview for images
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        fileObj.preview = e.target.result;
                        this.$nextTick(() => {
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons();
                            }
                        });
                    };
                    reader.readAsDataURL(file);
                }

                this.files.push(fileObj);

                // Simulate upload progress (replace with actual upload logic)
                this.uploading = true;
                this.progress = 0;

                // In real implementation, use FormData and fetch/axios to upload
                // Example:
                // const formData = new FormData();
                // formData.append(this.name, file);
                //
                // try {
                //     const response = await fetch('/api/upload', {
                //         method: 'POST',
                //         body: formData,
                //         headers: {
                //             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                //         }
                //     });
                //     const data = await response.json();
                //     fileObj.url = data.url;
                // } catch (error) {
                //     window.showToast(`فشل رفع ${file.name}`, 'error');
                //     this.deleteFile(id);
                // }

                // Simulate progress
                const interval = setInterval(() => {
                    this.progress += 10;
                    if (this.progress >= 100) {
                        clearInterval(interval);
                        this.uploading = false;
                        this.progress = 0;
                        window.showToast(`تم رفع ${file.name} بنجاح`, 'success');
                    }
                }, 100);

                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            },

            async deleteFile(id) {
                const file = this.files.find(f => f.id === id);
                if (!file) return;

                // In real implementation, delete from server
                // if (file.url) {
                //     await fetch(file.url, { method: 'DELETE' });
                // }

                this.files = this.files.filter(f => f.id !== id);
                window.showToast(`تم حذف ${file.name}`, 'info');

                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            },

            getFileIcon(type) {
                if (type.startsWith('image/')) return 'image';
                if (type.startsWith('video/')) return 'video';
                if (type === 'application/pdf') return 'file-text';
                return 'file';
            },

            formatFileSize(bytes) {
                if (bytes === 0) return '0 بايت';
                const k = 1024;
                const sizes = ['بايت', 'كيلوبايت', 'ميجابايت', 'جيجابايت'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
            }
        };
    }
</script>

<style>
    [x-cloak] { display: none !important; }
</style>
