@extends('layouts.admin')

@section('title', 'مكتبة الوسائط')

@section('content')
<div class="space-y-6" x-data="mediaLibrary()">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                مكتبة الوسائط
            </h1>
            <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                إدارة ورفع الصور والملفات
            </p>
        </div>
        <button @click="showUploadModal = true"
                class="px-6 py-3 rounded-xl font-bold text-white transition-all flex items-center gap-2 bg-gradient-to-r from-[#0056b3] to-[#218838] hover:shadow-lg">
            <i data-lucide="upload" class="w-5 h-5"></i>
            <span>رفع ملف جديد</span>
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
        <div class="rounded-xl p-6 backdrop-blur-lg border transition-all hover:shadow-lg"
             :class="darkMode ? 'bg-white/5 border-white/10' : 'bg-white border-gray-200'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">إجمالي الملفات</p>
                    <p class="text-2xl font-bold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'"
                       x-text="stats.total"></p>
                </div>
                <div class="p-3 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838]">
                    <i data-lucide="image" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 backdrop-blur-lg border transition-all hover:shadow-lg"
             :class="darkMode ? 'bg-white/5 border-white/10' : 'bg-white border-gray-200'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الصور</p>
                    <p class="text-2xl font-bold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'"
                       x-text="stats.images"></p>
                </div>
                <div class="p-3 rounded-lg bg-blue-500">
                    <i data-lucide="image" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 backdrop-blur-lg border transition-all hover:shadow-lg"
             :class="darkMode ? 'bg-white/5 border-white/10' : 'bg-white border-gray-200'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الفيديو</p>
                    <p class="text-2xl font-bold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'"
                       x-text="stats.videos"></p>
                </div>
                <div class="p-3 rounded-lg bg-purple-500">
                    <i data-lucide="video" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 backdrop-blur-lg border transition-all hover:shadow-lg"
             :class="darkMode ? 'bg-white/5 border-white/10' : 'bg-white border-gray-200'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الحجم الكلي</p>
                    <p class="text-2xl font-bold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'"
                       x-text="formatBytes(stats.totalSize)"></p>
                </div>
                <div class="p-3 rounded-lg bg-green-500">
                    <i data-lucide="hard-drive" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="rounded-xl p-4 lg:p-6 backdrop-blur-lg border"
         :class="darkMode ? 'bg-white/5 border-white/10' : 'bg-white border-gray-200'">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    البحث
                </label>
                <input type="text"
                       x-model="filters.search"
                       @input.debounce.500ms="loadMedia()"
                       placeholder="ابحث في الملفات..."
                       class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-blue-500 transition-all"
                       :class="darkMode ? 'bg-white/10 border-white/20 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    النوع
                </label>
                <select x-model="filters.type"
                        @change="loadMedia()"
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-blue-500 transition-all"
                        :class="darkMode ? 'bg-white/10 border-white/20 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">الكل</option>
                    <option value="image">صور</option>
                    <option value="video">فيديو</option>
                    <option value="document">مستندات</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    المجموعة
                </label>
                <select x-model="filters.collection"
                        @change="loadMedia()"
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-blue-500 transition-all"
                        :class="darkMode ? 'bg-white/10 border-white/20 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">الكل</option>
                    <option value="default">افتراضي</option>
                    <option value="featured">مميزة</option>
                    <option value="gallery">معرض</option>
                    <option value="thumbnail">مصغرات</option>
                </select>
            </div>

            <div class="flex items-end">
                <button @click="resetFilters()"
                        class="w-full px-4 py-2 rounded-lg font-medium transition-all"
                        :class="darkMode ? 'bg-white/10 hover:bg-white/20 text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-900'">
                    <i data-lucide="x" class="w-4 h-4 inline-block ml-2"></i>
                    إعادة تعيين
                </button>
            </div>
        </div>
    </div>

    <!-- Media Grid -->
    <div class="rounded-xl p-6 backdrop-blur-lg border"
         :class="darkMode ? 'bg-white/5 border-white/10' : 'bg-white border-gray-200'">

        <!-- Loading State -->
        <div x-show="loading" class="text-center py-12">
            <div class="inline-block w-12 h-12 border-4 border-t-blue-500 border-gray-300 rounded-full animate-spin"></div>
            <p class="mt-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">جاري التحميل...</p>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && media.length === 0" class="text-center py-12">
            <i data-lucide="image-off" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
            <p class="text-lg font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">لا توجد ملفات</p>
        </div>

        <!-- Media Grid -->
        <div x-show="!loading && media.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 lg:gap-4">
            <template x-for="item in media" :key="item.id">
                <div class="group relative rounded-xl overflow-hidden border cursor-pointer transition-all hover:shadow-xl"
                     :class="darkMode ? 'bg-white/5 border-white/10' : 'bg-white border-gray-200'"
                     @click="selectedMedia = item; showDetailsModal = true">

                    <!-- Image Preview -->
                    <div class="aspect-square relative overflow-hidden bg-gray-100">
                        <template x-if="item.type === 'image'">
                            <img :src="item.url" :alt="item.alt_text || item.filename"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                        </template>
                        <template x-if="item.type !== 'image'">
                            <div class="w-full h-full flex items-center justify-center"
                                 :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'">
                                <i :data-lucide="item.type === 'video' ? 'video' : 'file'" class="w-12 h-12"
                                   :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                            </div>
                        </template>

                        <!-- Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                            <div class="absolute bottom-2 left-2 right-2">
                                <p class="text-white text-xs font-medium truncate" x-text="item.original_filename"></p>
                            </div>
                        </div>
                    </div>

                    <!-- File Info -->
                    <div class="p-2">
                        <p class="text-xs truncate" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                           x-text="formatBytes(item.size)"></p>
                    </div>
                </div>
            </template>
        </div>

        <!-- Pagination -->
        <div x-show="!loading && pagination.last_page > 1" class="mt-6 flex items-center justify-between">
            <button @click="loadMedia(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    class="px-4 py-2 rounded-lg font-medium transition-all disabled:opacity-50"
                    :class="darkMode ? 'bg-white/10 hover:bg-white/20 text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-900'">
                السابق
            </button>
            <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                صفحة <span x-text="pagination.current_page"></span> من <span x-text="pagination.last_page"></span>
            </span>
            <button @click="loadMedia(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="px-4 py-2 rounded-lg font-medium transition-all disabled:opacity-50"
                    :class="darkMode ? 'bg-white/10 hover:bg-white/20 text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-900'">
                التالي
            </button>
        </div>
    </div>

    <!-- Upload Modal -->
    <div x-show="showUploadModal"
         x-transition
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         @click.self="showUploadModal = false">
        <div class="rounded-2xl p-4 lg:p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto backdrop-blur-lg border shadow-2xl"
             :class="darkMode ? 'bg-gray-900/95 border-white/10' : 'bg-white border-gray-200'"
             @click.stop
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    رفع ملف جديد
                </h3>
                <button @click="showUploadModal = false"
                        class="p-2 rounded-lg transition-colors"
                        :class="darkMode ? 'hover:bg-white/10' : 'hover:bg-gray-100'">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form @submit.prevent="uploadFile()">
                <div class="space-y-6">
                    <!-- Drag & Drop Zone -->
                    <div>
                        <div class="relative"
                             @dragover.prevent="isDragging = true"
                             @dragleave.prevent="isDragging = false"
                             @drop.prevent="handleDrop($event)">

                            <!-- Hidden File Input -->
                            <input type="file"
                                   x-ref="fileInput"
                                   @change="handleFileSelect($event)"
                                   accept="image/*,video/*"
                                   multiple
                                   class="hidden">

                            <!-- Drop Zone -->
                            <div @click="$refs.fileInput.click()"
                                 class="border-2 border-dashed rounded-2xl p-12 text-center cursor-pointer transition-all duration-300"
                                 :class="[
                                     isDragging
                                         ? 'border-blue-500 bg-blue-500/10 scale-105'
                                         : (darkMode ? 'border-white/20 hover:border-white/40 hover:bg-white/5' : 'border-gray-300 hover:border-gray-400 hover:bg-gray-50'),
                                     uploadData.files.length > 0 ? 'border-green-500' : ''
                                 ]">

                                <!-- Upload Icon -->
                                <div class="mb-4 relative">
                                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full transition-all duration-300"
                                         :class="isDragging
                                             ? 'bg-blue-500/20 animate-bounce'
                                             : (darkMode ? 'bg-white/10' : 'bg-gray-100')">
                                        <i data-lucide="upload-cloud"
                                           class="w-10 h-10 transition-colors"
                                           :class="isDragging ? 'text-blue-500' : (darkMode ? 'text-gray-400' : 'text-gray-500')"></i>
                                    </div>

                                    <!-- Animated particles on drag -->
                                    <template x-if="isDragging">
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="w-24 h-24 rounded-full border-4 border-blue-500 border-t-transparent animate-spin"></div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Instructions -->
                                <template x-if="uploadData.files.length === 0">
                                    <div>
                                        <p class="text-lg font-semibold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                            <span x-show="!isDragging">اسحب وأفلت الملفات هنا</span>
                                            <span x-show="isDragging" class="text-blue-500">أفلت الملفات الآن</span>
                                        </p>
                                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                            أو انقر للتصفح واختيار الملفات
                                        </p>
                                        <p class="text-xs mt-3" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                                            PNG, JPG, GIF, WebP حتى 100MB
                                        </p>
                                    </div>
                                </template>

                                <!-- Selected Files Preview -->
                                <template x-if="uploadData.files.length > 0">
                                    <div class="space-y-3">
                                        <p class="text-lg font-semibold text-green-500 mb-4">
                                            <i data-lucide="check-circle" class="w-5 h-5 inline-block ml-2"></i>
                                            تم اختيار <span x-text="uploadData.files.length"></span> ملف
                                        </p>

                                        <div class="max-h-40 overflow-y-auto space-y-2">
                                            <template x-for="(file, index) in uploadData.files" :key="index">
                                                <div class="flex items-center gap-3 p-3 rounded-lg text-right"
                                                     :class="darkMode ? 'bg-white/5' : 'bg-gray-50'">
                                                    <!-- File Icon/Preview -->
                                                    <template x-if="file.type.startsWith('image/')">
                                                        <img :src="file.preview"
                                                             class="w-12 h-12 rounded-lg object-cover">
                                                    </template>
                                                    <template x-if="!file.type.startsWith('image/')">
                                                        <div class="w-12 h-12 rounded-lg flex items-center justify-center"
                                                             :class="darkMode ? 'bg-white/10' : 'bg-gray-200'">
                                                            <i data-lucide="file" class="w-6 h-6" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                                                        </div>
                                                    </template>

                                                    <!-- File Info -->
                                                    <div class="flex-1 min-w-0">
                                                        <p class="font-medium truncate" :class="darkMode ? 'text-white' : 'text-gray-900'"
                                                           x-text="file.name"></p>
                                                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                                                           x-text="formatBytes(file.size)"></p>
                                                    </div>

                                                    <!-- Remove Button -->
                                                    <button type="button"
                                                            @click.stop="removeFile(index)"
                                                            class="p-2 rounded-lg transition-colors"
                                                            :class="darkMode ? 'hover:bg-white/10 text-gray-400 hover:text-red-400' : 'hover:bg-gray-200 text-gray-600 hover:text-red-600'">
                                                        <i data-lucide="x" class="w-5 h-5"></i>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>

                                        <button type="button"
                                                @click="$refs.fileInput.click()"
                                                class="text-sm font-medium text-blue-500 hover:text-blue-600 transition-colors">
                                            <i data-lucide="plus-circle" class="w-4 h-4 inline-block ml-1"></i>
                                            إضافة المزيد من الملفات
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Collection -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            المجموعة
                        </label>
                        <select x-model="uploadData.collection_name"
                                class="w-full px-4 py-2 rounded-lg border"
                                :class="darkMode ? 'bg-white/10 border-white/20 text-white' : 'bg-white border-gray-300'">
                            <option value="default">افتراضي</option>
                            <option value="featured">مميزة</option>
                            <option value="gallery">معرض</option>
                            <option value="thumbnail">مصغرات</option>
                        </select>
                    </div>

                    <!-- Alt Text -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            النص البديل (اختياري)
                        </label>
                        <input type="text"
                               x-model="uploadData.alt_text"
                               class="w-full px-4 py-2 rounded-lg border"
                               :class="darkMode ? 'bg-white/10 border-white/20 text-white' : 'bg-white border-gray-300'">
                    </div>

                    <!-- Caption -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            التعليق (اختياري)
                        </label>
                        <textarea x-model="uploadData.caption"
                                  rows="3"
                                  class="w-full px-4 py-2 rounded-lg border"
                                  :class="darkMode ? 'bg-white/10 border-white/20 text-white' : 'bg-white border-gray-300'"></textarea>
                    </div>

                    <!-- Optimize -->
                    <div class="flex items-center gap-2">
                        <input type="checkbox"
                               x-model="uploadData.optimize"
                               id="optimize"
                               class="w-4 h-4 rounded">
                        <label for="optimize" class="text-sm font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            تحسين الصورة تلقائياً (ImageMagick)
                        </label>
                    </div>

                    <!-- Upload Progress -->
                    <div x-show="uploading"
                         x-transition
                         class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                جاري رفع <span x-text="uploadData.currentFile"></span> من <span x-text="uploadData.totalFiles"></span>
                            </span>
                            <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                <span x-text="uploadProgress"></span>%
                            </span>
                        </div>
                        <div class="relative w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-[#0056b3] to-[#218838] transition-all duration-300 ease-out"
                                 :style="`width: ${uploadProgress}%`">
                                <div class="absolute inset-0 flex items-center justify-end px-2">
                                    <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-center" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                            يرجى عدم إغلاق هذه النافذة حتى اكتمال الرفع
                        </p>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-3 pt-4">
                        <button type="submit"
                                :disabled="uploading || uploadData.files.length === 0"
                                class="flex-1 px-6 py-3 rounded-xl font-bold text-white transition-all disabled:opacity-50 disabled:cursor-not-allowed bg-gradient-to-r from-[#0056b3] to-[#218838] hover:shadow-lg hover:scale-105 active:scale-95">
                            <i data-lucide="upload" class="w-5 h-5 inline-block ml-2"></i>
                            <span x-text="uploading ? 'جاري الرفع...' : `رفع ${uploadData.files.length} ملف`"></span>
                        </button>
                        <button type="button"
                                @click="closeUploadModal()"
                                :disabled="uploading"
                                class="px-6 py-3 rounded-xl font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="darkMode ? 'bg-white/10 hover:bg-white/20 text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-900'">
                            <span x-text="uploading ? 'الرجاء الانتظار' : 'إلغاء'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Details Modal -->
    <div x-show="showDetailsModal"
         x-transition
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         @click.self="showDetailsModal = false">
        <div class="rounded-2xl p-4 lg:p-6 max-w-4xl w-full backdrop-blur-lg border max-h-[90vh] overflow-y-auto custom-scrollbar"
             :class="darkMode ? 'bg-gray-900/95 border-white/10' : 'bg-white border-gray-200'"
             @click.stop>
            <template x-if="selectedMedia">
                <div>
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'"
                            x-text="selectedMedia.original_filename"></h3>
                        <button @click="showDetailsModal = false"
                                class="p-2 rounded-lg transition-colors"
                                :class="darkMode ? 'hover:bg-white/10' : 'hover:bg-gray-100'">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Preview -->
                        <div>
                            <template x-if="selectedMedia.type === 'image'">
                                <img :src="selectedMedia.url" :alt="selectedMedia.alt_text"
                                     class="w-full rounded-xl">
                            </template>
                        </div>

                        <!-- Details -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    المعرف
                                </label>
                                <p :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="selectedMedia.id"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    النوع
                                </label>
                                <p :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="selectedMedia.type"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    الحجم
                                </label>
                                <p :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="formatBytes(selectedMedia.size)"></p>
                            </div>

                            <div x-show="selectedMedia.width && selectedMedia.height">
                                <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    الأبعاد
                                </label>
                                <p :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    <span x-text="selectedMedia.width"></span> × <span x-text="selectedMedia.height"></span>
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    الرابط
                                </label>
                                <div class="flex gap-2">
                                    <input type="text"
                                           :value="selectedMedia.url"
                                           readonly
                                           class="flex-1 px-3 py-2 rounded-lg border text-sm"
                                           :class="darkMode ? 'bg-white/10 border-white/20 text-white' : 'bg-gray-50 border-gray-300'">
                                    <button @click="copyToClipboard(selectedMedia.url)"
                                            class="px-4 py-2 rounded-lg font-medium transition-all"
                                            :class="darkMode ? 'bg-white/10 hover:bg-white/20 text-white' : 'bg-gray-200 hover:bg-gray-300'">
                                        <i data-lucide="copy" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2 pt-4">
                                <button @click="deleteMedia(selectedMedia.id)"
                                        class="px-4 py-2 rounded-lg font-medium text-white bg-red-500 hover:bg-red-600 transition-all">
                                    <i data-lucide="trash-2" class="w-4 h-4 inline-block ml-2"></i>
                                    حذف
                                </button>
                                <template x-if="selectedMedia.type === 'image'">
                                    <button @click="optimizeMedia(selectedMedia.id)"
                                            class="px-4 py-2 rounded-lg font-medium text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:shadow-lg transition-all">
                                        <i data-lucide="zap" class="w-4 h-4 inline-block ml-2"></i>
                                        تحسين
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Metadata (if optimized) -->
                    <template x-if="selectedMedia.metadata && selectedMedia.metadata.optimized">
                        <div class="mt-6 p-4 rounded-xl" :class="darkMode ? 'bg-white/5' : 'bg-gray-50'">
                            <h4 class="font-bold mb-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                معلومات التحسين
                            </h4>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الحجم الأصلي:</span>
                                    <span :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="formatBytes(selectedMedia.metadata.original_size)"></span>
                                </div>
                                <div>
                                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الحجم المحسّن:</span>
                                    <span :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="formatBytes(selectedMedia.metadata.optimized_size)"></span>
                                </div>
                                <div>
                                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">التوفير:</span>
                                    <span class="text-green-500 font-medium" x-text="selectedMedia.metadata.size_reduction"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function mediaLibrary() {
    return {
        loading: false,
        uploading: false,
        uploadProgress: 0,
        showUploadModal: false,
        showDetailsModal: false,
        media: [],
        selectedMedia: null,
        filters: {
            search: '',
            type: '',
            collection: '',
        },
        uploadData: {
            files: [],
            collection_name: 'default',
            alt_text: '',
            caption: '',
            optimize: true,
            currentFile: 0,
            totalFiles: 0,
        },
        isDragging: false,
        stats: {
            total: 0,
            images: 0,
            videos: 0,
            totalSize: 0,
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
        },

        init() {
            this.loadMedia();
            lucide.createIcons();
        },

        async loadMedia(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.pagination.per_page,
                    ...this.filters,
                });

                const response = await fetch(`/su/portal/v1/admin/simple-media?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });
                const data = await response.json();

                this.media = data.data || [];
                this.pagination = {
                    current_page: data.current_page || 1,
                    last_page: data.last_page || 1,
                    per_page: data.per_page || 20,
                    total: data.total || 0,
                };

                this.updateStats();
            } catch (error) {
                console.error('Error loading media:', error);
                this.showToast('خطأ في تحميل الملفات', 'error');
            } finally {
                this.loading = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },

        handleFileSelect(event) {
            this.addFiles(Array.from(event.target.files));
        },

        handleDrop(event) {
            this.isDragging = false;
            const files = Array.from(event.dataTransfer.files);
            this.addFiles(files);
        },

        addFiles(files) {
            files.forEach(file => {
                // Create preview for images
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        file.preview = e.target.result;
                        this.uploadData.files.push(file);
                        this.$nextTick(() => lucide.createIcons());
                    };
                    reader.readAsDataURL(file);
                } else {
                    this.uploadData.files.push(file);
                }
            });
        },

        removeFile(index) {
            this.uploadData.files.splice(index, 1);
            this.$nextTick(() => lucide.createIcons());
        },

        async uploadFile() {
            if (this.uploadData.files.length === 0) return;

            this.uploading = true;
            this.uploadData.totalFiles = this.uploadData.files.length;
            this.uploadData.currentFile = 0;

            let successCount = 0;
            let errorCount = 0;

            for (let i = 0; i < this.uploadData.files.length; i++) {
                this.uploadData.currentFile = i + 1;
                const file = this.uploadData.files[i];

                const formData = new FormData();
                formData.append('file', file);
                formData.append('collection_name', this.uploadData.collection_name);
                formData.append('alt_text', this.uploadData.alt_text);
                formData.append('caption', this.uploadData.caption);
                formData.append('optimize', this.uploadData.optimize ? '1' : '0');

                try {
                    const response = await fetch('/su/portal/v1/admin/simple-media', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: formData,
                    });

                    const result = await response.json();

                    if (result.success) {
                        successCount++;
                    } else {
                        errorCount++;
                        console.error('Upload failed for', file.name, result.message);
                    }

                    // Update progress
                    this.uploadProgress = Math.round(((i + 1) / this.uploadData.files.length) * 100);

                } catch (error) {
                    errorCount++;
                    console.error('Upload error for', file.name, error);
                }
            }

            // Show results
            if (successCount > 0) {
                this.showToast(`تم رفع ${successCount} ملف بنجاح`, 'success');
            }
            if (errorCount > 0) {
                this.showToast(`فشل رفع ${errorCount} ملف`, 'error');
            }

            this.uploading = false;
            this.uploadProgress = 0;
            this.showUploadModal = false;
            this.resetUploadForm();
            this.loadMedia();
        },

        async deleteMedia(id) {
            if (!confirm('هل أنت متأكد من حذف هذا الملف؟')) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/simple-media/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    },
                });

                const result = await response.json();

                if (result.success) {
                    this.showToast('تم حذف الملف بنجاح', 'success');
                    this.showDetailsModal = false;
                    this.loadMedia();
                } else {
                    this.showToast(result.message || 'فشل حذف الملف', 'error');
                }
            } catch (error) {
                console.error('Delete error:', error);
                this.showToast('خطأ في حذف الملف', 'error');
            }
        },

        async optimizeMedia(id) {
            try {
                const response = await fetch(`/su/portal/v1/admin/simple-media/${id}/optimize`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    },
                });

                const result = await response.json();

                if (result.success) {
                    this.showToast('تم تحسين الصورة بنجاح', 'success');
                    this.selectedMedia = result.data;
                    this.loadMedia();
                } else {
                    this.showToast(result.message || 'فشل تحسين الصورة', 'error');
                }
            } catch (error) {
                console.error('Optimize error:', error);
                this.showToast('خطأ في تحسين الصورة', 'error');
            }
        },

        resetFilters() {
            this.filters = {
                search: '',
                type: '',
                collection: '',
            };
            this.loadMedia();
        },

        closeUploadModal() {
            if (this.uploading) {
                if (!confirm('جاري رفع الملفات. هل تريد الإلغاء؟')) {
                    return;
                }
            }
            this.showUploadModal = false;
            this.resetUploadForm();
        },

        resetUploadForm() {
            this.uploadData = {
                files: [],
                collection_name: 'default',
                alt_text: '',
                caption: '',
                optimize: true,
                currentFile: 0,
                totalFiles: 0,
            };
            this.isDragging = false;
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        },

        updateStats() {
            // Use pagination total for accurate count across all pages
            this.stats.total = this.pagination.total || this.media.length;
            this.stats.images = this.media.filter(m => m.type === 'image').length;
            this.stats.videos = this.media.filter(m => m.type === 'video').length;
            this.stats.totalSize = this.media.reduce((sum, m) => sum + (m.size || 0), 0);
        },

        formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            this.showToast('تم نسخ الرابط', 'success');
        },

        showToast(message, type = 'success') {
            // Integrate with your toast notification system
            window.showToast?.(message, type);
        }
    };
}
</script>
@endsection
