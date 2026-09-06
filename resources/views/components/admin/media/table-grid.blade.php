{{-- Media Library Grid/List View Component --}}
<div class="flex-1 space-y-4">
    {{-- Loading State --}}
    <div x-show="loading"
         class="rounded-2xl p-10 text-center shadow-lg"
         :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
        <div class="inline-flex w-14 h-14 rounded-full border-4 border-[#64CCC5] border-t-transparent animate-spin"></div>
        <p class="mt-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('media.library_page.loading') }}</p>
    </div>

    {{-- Empty State --}}
    <div x-show="!loading && items.length === 0"
         class="rounded-2xl p-12 text-center shadow-lg"
         :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
        <div class="w-20 h-20 mx-auto rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center">
            <i data-lucide="image-off" class="w-10 h-10 text-white"></i>
        </div>
        <h3 class="mt-6 text-2xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('media.library_page.empty_title') }}</h3>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ __('media.library_page.empty_description') }}
        </p>
    </div>

    {{-- Grid View --}}
    <div x-show="!loading && items.length > 0 && viewMode === 'grid'"
         class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        <template x-for="item in items" :key="item.id">
            <article class="rounded-2xl overflow-hidden shadow-lg transition-all hover:-translate-y-1"
                     :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                <div class="relative aspect-video overflow-hidden"
                     :class="darkMode ? 'bg-gray-950' : 'bg-gray-100'">
                    <template x-if="item.is_image">
                        <img :src="item.thumbnail_url || item.src"
                             :alt="item.title"
                             class="w-full h-full object-cover transition-transform duration-500 hover:scale-105">
                    </template>

                    <template x-if="!item.is_image">
                        <div class="w-full h-full flex items-center justify-center">
                            <i :data-lucide="item.type === 'video' ? 'video' : (item.type === 'audio' ? 'music-4' : 'file-text')"
                               class="w-12 h-12"
                               :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                        </div>
                    </template>

                    <div class="absolute top-3 left-3">
                        <label class="w-8 h-8 rounded-full bg-black/45 backdrop-blur text-white flex items-center justify-center cursor-pointer">
                            <input type="checkbox"
                                   class="hidden"
                                   :value="item.id"
                                   x-model="selectedItems">
                            <i data-lucide="check" class="w-4 h-4"></i>
                        </label>
                    </div>

                    <div class="absolute top-3 right-3">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold capitalize"
                              :class="typeBadge(item.type)"
                              x-text="item.type_label || item.type"></span>
                    </div>
                </div>

                <div class="p-5 space-y-4">
                    <div>
                        <h3 class="font-bold truncate"
                            :class="darkMode ? 'text-white' : 'text-gray-900'"
                            x-text="item.title"></h3>
                        <p class="text-xs mt-1 truncate"
                           :class="darkMode ? 'text-gray-500' : 'text-gray-500'"
                           x-text="item.original_filename"></p>
                    </div>

                    <div class="flex items-center justify-between text-xs"
                         :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        <span x-text="item.human_readable_size"></span>
                        <span x-text="item.width && item.height ? `${item.width}×${item.height}` : '-'"></span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button @click="viewMedia(item)"
                                class="px-3 py-2 rounded-lg text-xs font-semibold transition-all"
                                :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                            {{ __('media.actions.view') }}
                        </button>
                        <button @click="openEdit(item)"
                                class="px-3 py-2 rounded-lg text-xs font-semibold transition-all"
                                :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                            {{ __('media.actions.edit') }}
                        </button>
                        <button x-show="item.is_image"
                                @click="openCrop(item)"
                                class="px-3 py-2 rounded-lg text-xs font-semibold transition-all"
                                :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                            {{ __('media.actions.crop') }}
                        </button>
                        <button @click="deleteMedia(item)"
                                class="px-3 py-2 rounded-lg text-xs font-semibold bg-red-500 text-white hover:bg-red-600 transition-all">
                            {{ __('media.actions.delete') }}
                        </button>
                    </div>
                </div>
            </article>
        </template>
    </div>

    {{-- List View --}}
    <div x-show="!loading && items.length > 0 && viewMode === 'list'"
         class="rounded-2xl shadow-lg overflow-hidden"
         :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr :class="darkMode ? 'bg-gray-950 text-gray-300' : 'bg-gray-50 text-gray-700'">
                        <th class="px-4 py-4 text-left">
                            <input type="checkbox"
                                   @change="toggleSelectAll()"
                                   :checked="currentPageSelected()"
                                   class="rounded border-gray-300 text-[#0056b3] focus:ring-[#64CCC5]">
                        </th>
                        <th class="px-4 py-4 text-left text-xs uppercase tracking-[0.18em]">{{ __('media.library_page.table.media') }}</th>
                        <th class="px-4 py-4 text-left text-xs uppercase tracking-[0.18em]">{{ __('media.library_page.table.title') }}</th>
                        <th class="px-4 py-4 text-left text-xs uppercase tracking-[0.18em]">{{ __('media.library_page.table.collection') }}</th>
                        <th class="px-4 py-4 text-left text-xs uppercase tracking-[0.18em]">{{ __('media.library_page.table.size') }}</th>
                        <th class="px-4 py-4 text-left text-xs uppercase tracking-[0.18em]">{{ __('media.library_page.table.uploaded') }}</th>
                        <th class="px-4 py-4 text-right text-xs uppercase tracking-[0.18em]">{{ __('media.library_page.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in items" :key="item.id">
                        <tr class="border-t"
                            :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                            <td class="px-4 py-4">
                                <input type="checkbox"
                                       :value="item.id"
                                       x-model="selectedItems"
                                       class="rounded border-gray-300 text-[#0056b3] focus:ring-[#64CCC5]">
                            </td>
                            <td class="px-4 py-4">
                                <div class="w-16 h-16 rounded-xl overflow-hidden"
                                     :class="darkMode ? 'bg-gray-950' : 'bg-gray-100'">
                                    <template x-if="item.is_image">
                                        <img :src="item.thumbnail_url || item.src" :alt="item.title" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!item.is_image">
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i :data-lucide="item.type === 'video' ? 'video' : (item.type === 'audio' ? 'music-4' : 'file-text')"
                                               class="w-6 h-6"
                                               :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                        </div>
                                    </template>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="item.title"></div>
                                <div class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-500'" x-text="item.original_filename"></div>
                            </td>
                            <td class="px-4 py-4 text-sm capitalize" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" x-text="item.collection_name"></td>
                            <td class="px-4 py-4 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" x-text="item.human_readable_size"></td>
                            <td class="px-4 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="formatDate(item.created_at)"></td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="viewMedia(item)" class="p-2 rounded-lg transition-all" :class="darkMode ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                    <button @click="openEdit(item)" class="p-2 rounded-lg transition-all" :class="darkMode ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <button x-show="item.is_image" @click="openCrop(item)" class="p-2 rounded-lg transition-all" :class="darkMode ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'">
                                        <i data-lucide="crop" class="w-4 h-4"></i>
                                    </button>
                                    <button @click="deleteMedia(item)" class="p-2 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div x-show="!loading && pagination.total > 0"
         class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm"
           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ __('media.library_page.pagination.showing') }} <span class="font-semibold" x-text="pagination.from || 0"></span>
            {{ __('admin.to') }} <span class="font-semibold" x-text="pagination.to || 0"></span>
            {{ __('admin.of') }} <span class="font-semibold" x-text="pagination.total || 0"></span> {{ __('media.library_page.pagination.items') }}
        </p>

        <div class="flex items-center gap-2">
            <button @click="changePage(pagination.current_page - 1)"
                    :disabled="pagination.current_page <= 1"
                    class="px-4 py-2 rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="darkMode ? 'bg-gray-900 border border-gray-800 text-white hover:bg-gray-800' : 'bg-white border border-gray-200 text-gray-900 hover:bg-gray-50'">
                {{ __('admin.previous') }}
            </button>
            <span class="px-4 py-2 rounded-xl"
                  :class="darkMode ? 'bg-gray-900 border border-gray-800 text-gray-300' : 'bg-white border border-gray-200 text-gray-700'">
                {{ __('media.library_page.pagination.page') }} <span x-text="pagination.current_page || 1"></span> / <span x-text="pagination.last_page || 1"></span>
            </span>
            <button @click="changePage(pagination.current_page + 1)"
                    :disabled="pagination.current_page >= pagination.last_page"
                    class="px-4 py-2 rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="darkMode ? 'bg-gray-900 border border-gray-800 text-white hover:bg-gray-800' : 'bg-white border border-gray-200 text-gray-900 hover:bg-gray-50'">
                {{ __('admin.next') }}
            </button>
        </div>
    </div>
</div>
