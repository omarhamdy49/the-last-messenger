{{--
    Gallery Tab Component - Article Form
    Features: Image gallery management with add/remove functionality
--}}

@props([
    'article' => null
])

<div x-show="activeTab === 'gallery'" x-transition class="space-y-4">
    <div class="flex items-center justify-between">
        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            أضف صوراً إلى معرض المقال. يمكن عرضها كعرض شرائح في صفحة المقال.
        </p>
        <button type="button"
                @click="$dispatch('open-media-library', { target: 'gallery', multiple: true })"
                class="px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-all flex items-center gap-2 text-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            إضافة صورة
        </button>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3" x-show="formData.gallery.length > 0">
        <template x-for="(item, index) in formData.gallery" :key="item.id">
            <div class="relative group rounded-lg overflow-hidden border-2"
                 :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                <img :src="item.url" :alt="item.caption" class="w-full aspect-square object-cover">
                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <button type="button" @click="removeGalleryItem(index)"
                            class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
                <input type="hidden" :name="'gallery[' + index + ']'" :value="item.id">
            </div>
        </template>
    </div>

    <div x-show="formData.gallery.length === 0"
         class="text-center py-16 border-2 border-dashed rounded-lg"
         :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
        <i data-lucide="images" class="w-12 h-12 mx-auto mb-3 text-gray-400"></i>
        <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
            لا توجد صور في المعرض. انقر على "إضافة صورة" للبدء.
        </p>
    </div>
</div>
