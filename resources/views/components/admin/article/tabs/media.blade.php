{{--
    Media Tab Component - Article Form
    Features: Media items management and episode picker
--}}

@props([
    'article' => null
])

<div x-show="activeTab === 'media'" x-transition class="space-y-4">
    <div class="flex items-center justify-between">
        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            أضف ملفات وسائط (فيديو، صوت، PDF) لإثراء محتوى المقال.
        </p>
        <button type="button"
                @click="$dispatch('open-media-library', { target: 'media_items', multiple: true })"
                class="px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-all flex items-center gap-2 text-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            إضافة وسائط
        </button>
    </div>

    <div class="space-y-2" x-show="formData.media_items.length > 0">
        <template x-for="(item, index) in formData.media_items" :key="item.id">
            <div class="flex items-center gap-3 p-3 rounded-lg border"
                 :class="darkMode ? 'bg-gray-700/50 border-gray-600' : 'bg-gray-50 border-gray-200'">
                <template x-if="item.thumbnail_url">
                    <img :src="item.thumbnail_url" class="w-12 h-12 rounded-lg object-cover flex-shrink-0" alt="">
                </template>
                <template x-if="!item.thumbnail_url">
                    <i data-lucide="video" class="w-8 h-8 text-gray-400 flex-shrink-0"></i>
                </template>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-sm truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="item.title || item.original_filename"></p>
                    <p class="text-xs text-gray-500" x-text="item.type || item.mime_type"></p>
                </div>
                <button type="button" @click="removeMediaItem(index)"
                        class="p-2 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/20 rounded-lg flex-shrink-0">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
                <input type="hidden" :name="'media_items[' + index + ']'" :value="item.id">
            </div>
        </template>
    </div>

    <div x-show="formData.media_items.length === 0"
         class="text-center py-16 border-2 border-dashed rounded-lg"
         :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
        <i data-lucide="video" class="w-12 h-12 mx-auto mb-3 text-gray-400"></i>
        <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
            لا توجد ملفات وسائط. انقر على "إضافة وسائط" للبدء.
        </p>
    </div>

    {{-- Video queue: linked episodes --}}
    <div class="pt-6 mt-6 border-t" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h4 class="font-semibold text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    حلقات الفيديو المرتبطة
                </h4>
                <p class="text-xs mt-0.5" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    اربط حلقات من برامج الوسائط لعرضها في قائمة تشغيل المقال
                </p>
            </div>
            <button type="button" @click="$dispatch('open-episode-picker')"
                    class="px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-all flex items-center gap-2 text-sm shrink-0">
                <i data-lucide="list-video" class="w-4 h-4"></i>
                تعيين حلقات
            </button>
        </div>

        <div class="space-y-2" x-show="formData.episodes.length > 0">
            <template x-for="(ep, index) in formData.episodes" :key="ep.id">
                <div class="flex items-center gap-3 p-2 rounded-lg border"
                     :class="darkMode ? 'bg-gray-700/50 border-gray-600' : 'bg-gray-50 border-gray-200'">
                    <span class="w-6 text-center text-xs font-bold text-gray-400 shrink-0" x-text="index + 1"></span>
                    <img :src="ep.thumb" x-show="ep.thumb" alt=""
                         class="w-20 h-12 rounded object-cover bg-gray-200 dark:bg-gray-800 shrink-0">
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="ep.title"></p>
                        <p class="text-xs text-gray-500 truncate">
                            <span x-text="ep.program"></span>
                            <span x-show="ep.season">· S<span x-text="ep.season"></span></span>
                            <span x-show="ep.episode">E<span x-text="ep.episode"></span></span>
                        </p>
                    </div>
                    {{-- Reorder: the queue plays in this order --}}
                    <button type="button" @click="moveEpisode(index, -1)" :disabled="index === 0"
                            class="p-1.5 rounded disabled:opacity-30 hover:bg-gray-200 dark:hover:bg-gray-600"
                            aria-label="تحريك لأعلى">
                        <i data-lucide="chevron-up" class="w-4 h-4"></i>
                    </button>
                    <button type="button" @click="moveEpisode(index, 1)" :disabled="index === formData.episodes.length - 1"
                            class="p-1.5 rounded disabled:opacity-30 hover:bg-gray-200 dark:hover:bg-gray-600"
                            aria-label="تحريك لأسفل">
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </button>
                    <button type="button" @click="formData.episodes.splice(index, 1)"
                            class="p-2 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/20 rounded-lg shrink-0"
                            aria-label="إزالة">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                    <input type="hidden" :name="'episodes[' + index + ']'" :value="ep.id">
                </div>
            </template>
        </div>

        <div x-show="formData.episodes.length === 0"
             class="text-center py-10 border-2 border-dashed rounded-lg"
             :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
            <i data-lucide="list-video" class="w-10 h-10 mx-auto mb-2 text-gray-400"></i>
            <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                لا توجد حلقات مرتبطة. انقر على "تعيين حلقات" للبدء.
            </p>
        </div>
    </div>
</div>
