<!-- Media Library Modal -->
<div x-show="isOpen"
     x-cloak
     @keydown.escape.window="isOpen = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">

    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
         @click="isOpen = false"></div>

    <!-- Modal -->
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden"
             @click.stop>

            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">مكتبة الوسائط</h2>
                <button @click="isOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Filters -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <input type="text"
                               x-model="search"
                               @input="filterMedia()"
                               placeholder="بحث بالاسم..."
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-right"
                               dir="rtl">
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <select x-model="typeFilter"
                                @change="filterMedia()"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-right"
                                dir="rtl">
                            <option value="all">جميع الأنواع</option>
                            <option value="image">صور</option>
                            <option value="video">فيديو</option>
                            <option value="document">مستندات</option>
                        </select>
                    </div>

                    <!-- Date Filter -->
                    <div>
                        <select x-model="dateFilter"
                                @change="filterMedia()"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-right"
                                dir="rtl">
                            <option value="all">جميع التواريخ</option>
                            <option value="today">اليوم</option>
                            <option value="week">هذا الأسبوع</option>
                            <option value="month">هذا الشهر</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Media Grid -->
            <div class="p-6 overflow-y-auto" style="max-height: 500px;">
                <!-- Loading State -->
                <div x-show="loading" class="flex items-center justify-center py-20">
                    <div class="text-center">
                        <svg class="animate-spin h-12 w-12 mx-auto text-blue-500 mb-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400">جاري التحميل...</p>
                    </div>
                </div>

                <!-- Media Grid -->
                <div x-show="!loading" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <template x-for="media in filteredItems" :key="media.id">
                        <div @click="selectMedia(media)"
                             :class="isMediaSelected(media) ? 'ring-4 ring-blue-500' : ''"
                             class="relative group cursor-pointer rounded-lg overflow-hidden border-2 border-transparent hover:border-blue-300 transition-all">
                            <div class="aspect-square bg-gray-100 dark:bg-gray-800">
                                <img :src="media.url"
                                     :alt="media.name"
                                     class="w-full h-full object-cover">
                            </div>

                            <!-- Selected Badge -->
                            <div x-show="isMediaSelected(media)"
                                 class="absolute top-2 right-2 bg-blue-500 text-white rounded-full p-1">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>

                            <!-- Info Overlay -->
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <p class="text-white text-xs truncate" x-text="media.name"></p>
                                <p class="text-white text-xs" x-text="formatFileSize(media.size)"></p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Empty State -->
                <div x-show="!loading && filteredItems.length === 0" class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">لا توجد وسائط</p>
                </div>
            </div>

            <!-- Pagination -->
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        عرض <span x-text="pagination.from"></span> -
                        <span x-text="pagination.to"></span> من
                        <span x-text="pagination.total"></span>
                    </div>

                    <div class="flex gap-2">
                        <button @click="previousPage()"
                                :disabled="pagination.currentPage === 1"
                                :class="pagination.currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-700'"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300">
                            السابق
                        </button>

                        <div class="flex gap-1">
                            <template x-for="page in pageNumbers()" :key="page">
                                <button @click="goToPage(page)"
                                        :class="page === pagination.currentPage ? 'bg-blue-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                                        x-text="page">
                                </button>
                            </template>
                        </div>

                        <button @click="nextPage()"
                                :disabled="pagination.currentPage === pagination.lastPage"
                                :class="pagination.currentPage === pagination.lastPage ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-700'"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300">
                            التالي
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span x-show="pickerMode === 'multiple'">
                        <span x-text="tempSelected.length"></span> عنصر محدد
                    </span>
                </div>
                <div class="flex gap-3">
                    <button @click="isOpen = false"
                            class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                        إلغاء
                    </button>
                    <button @click="confirmSelection()"
                            class="px-6 py-2 gradient-button text-white rounded-lg">
                        تأكيد الاختيار
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .gradient-button {
        background: linear-gradient(135deg, #0056b3, #218838);
        transition: all 0.2s ease;
    }

    .gradient-button:hover {
        box-shadow: 0 4px 12px rgba(0, 86, 179, 0.3);
        transform: translateY(-1px);
    }
</style>
