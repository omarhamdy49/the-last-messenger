{{-- Media Library Toolbar Component --}}
<div class="rounded-2xl shadow-lg p-4"
     :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        {{-- Search and Filters Toggle --}}
        <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5"
                   :class="darkMode ? 'text-gray-500' : 'text-gray-400'"></i>
                <input type="text"
                       x-model="filters.search"
                       @input="debounceSearch()"
                       placeholder="{{ __('media.library_page.search_placeholder') }}"
                       class="w-full ps-12 pe-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                       :class="darkMode ? 'bg-gray-950 border-gray-800 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
            </div>

            <button @click="showFilters = !showFilters"
                    class="lg:hidden px-4 py-3 rounded-xl font-semibold transition-all"
                    :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                <i data-lucide="filter" class="w-4 h-4 inline me-2"></i>
                {{ __('media.library_page.filters') }}
            </button>
        </div>

        {{-- View Mode, Sort, and Bulk Actions --}}
        <div class="flex flex-wrap items-center gap-3">
            {{-- View Mode Toggle --}}
            <div class="inline-flex p-1 rounded-xl"
                 :class="darkMode ? 'bg-gray-950 border border-gray-800' : 'bg-gray-100 border border-gray-200'">
                <button @click="viewMode = 'grid'"
                        class="px-3 py-2 rounded-lg transition-all"
                        :class="viewMode === 'grid'
                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md'
                            : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                    <i data-lucide="grid-3x3" class="w-4 h-4"></i>
                </button>
                <button @click="viewMode = 'list'"
                        class="px-3 py-2 rounded-lg transition-all"
                        :class="viewMode === 'list'
                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md'
                            : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                    <i data-lucide="list" class="w-4 h-4"></i>
                </button>
            </div>

            {{-- Sort Dropdown --}}
            <select x-model="filters.sort"
                    @change="applyFilters()"
                    class="px-4 py-3 rounded-xl border"
                    :class="darkMode ? 'bg-gray-950 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <option value="created_at">{{ __('media.library_page.sort_newest') }}</option>
                <option value="updated_at">{{ __('media.library_page.sort_recently_updated') }}</option>
                <option value="size">{{ __('media.library_page.sort_largest_size') }}</option>
                <option value="filename">{{ __('media.library_page.sort_stored_filename') }}</option>
                <option value="original_filename">{{ __('media.library_page.sort_original_filename') }}</option>
            </select>

            {{-- Bulk Download Button --}}
            <button x-show="selectedItems.length > 0"
                    @click="bulkDownload()"
                    class="px-4 py-3 rounded-xl font-semibold transition-all"
                    :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                <i data-lucide="download" class="w-4 h-4 inline me-2"></i>
                {{ __('media.actions.download') }}
            </button>

            {{-- Bulk Tag Button --}}
            <button x-show="selectedItems.length > 0"
                    @click="bulkTag()"
                    class="px-4 py-3 rounded-xl font-semibold transition-all"
                    :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                <i data-lucide="tags" class="w-4 h-4 inline me-2"></i>
                {{ __('media.library_page.bulk.tag_selected') }}
            </button>

            {{-- Bulk Delete Button --}}
            <button x-show="selectedItems.length > 0"
                    @click="confirmBulkDelete()"
                    class="px-4 py-3 rounded-xl font-semibold bg-red-500 text-white hover:bg-red-600 transition-all">
                <i data-lucide="trash-2" class="w-4 h-4 inline me-2"></i>
                {{ __('media.actions.delete') }}
            </button>
        </div>
    </div>
</div>
