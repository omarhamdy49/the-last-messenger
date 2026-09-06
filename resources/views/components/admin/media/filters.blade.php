{{-- Media Library Filters Component --}}
<aside x-show="showFilters"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0 -translate-x-full"
       x-transition:enter-end="opacity-100 translate-x-0"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="opacity-100 translate-x-0"
       x-transition:leave-end="opacity-0 -translate-x-full"
       class="w-full lg:w-80 shrink-0">

    <div class="rounded-2xl shadow-lg p-6 sticky top-6"
         :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center">
                    <i data-lucide="sliders-horizontal" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h3 class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('media.filter_panel.title') }}</h3>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('media.filter_panel.subtitle') }}</p>
                </div>
            </div>

            <button @click="clearFilters()"
                    class="text-sm font-semibold text-[#0056b3] hover:text-[#218838] transition-colors">
                {{ __('media.filter_panel.reset') }}
            </button>
        </div>

        <div class="space-y-5">
            {{-- Media Type Filter --}}
            <div>
                <label class="block text-sm font-semibold mb-2"
                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('media.filter_panel.type') }}
                </label>
                <select x-model="filters.type"
                        @change="applyFilters()"
                        class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('media.filter_panel.all_types') }}</option>
                    <option value="image">{{ __('media.filter_panel.images') }}</option>
                    <option value="video">{{ __('media.filter_panel.videos') }}</option>
                    <option value="audio">{{ __('media.filter_panel.audio') }}</option>
                    <option value="document">{{ __('media.filter_panel.documents') }}</option>
                </select>
            </div>

            {{-- Collection Filter --}}
            <div>
                <label class="block text-sm font-semibold mb-2"
                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('media.filter_panel.collection') }}
                </label>
                <select x-model="filters.collection"
                        @change="applyFilters()"
                        class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('media.filter_panel.all_collections') }}</option>
                    <template x-for="collection in collections" :key="collection">
                        <option :value="collection" x-text="collection"></option>
                    </template>
                </select>
            </div>

            {{-- Date Range Filter --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold mb-2"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('media.filter_panel.from') }}
                    </label>
                    <input type="date"
                           x-model="filters.date_from"
                           @change="applyFilters()"
                           class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('media.filter_panel.to') }}
                    </label>
                    <input type="date"
                           x-model="filters.date_to"
                           @change="applyFilters()"
                           class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                </div>
            </div>

            {{-- File Size Filter --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold mb-2"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('media.filter_panel.min_size_kb') }}
                    </label>
                    <input type="number"
                           min="0"
                           x-model="filters.size_min_kb"
                           @change="applyFilters()"
                           class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('media.filter_panel.max_size_kb') }}
                    </label>
                    <input type="number"
                           min="0"
                           x-model="filters.size_max_kb"
                           @change="applyFilters()"
                           class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                </div>
            </div>

            {{-- Unused Media Filter --}}
            <label class="flex items-center gap-3 p-4 rounded-xl border cursor-pointer"
                   :class="darkMode ? 'border-gray-800 bg-gray-800/40 text-gray-200' : 'border-gray-200 bg-gray-50 text-gray-700'">
                <input type="checkbox"
                       x-model="filters.unused"
                       @change="applyFilters()"
                       class="rounded border-gray-300 text-[#0056b3] focus:ring-[#64CCC5]">
                <div>
                    <div class="font-semibold text-sm">{{ __('media.filter_panel.only_unused') }}</div>
                    <div class="text-xs opacity-75">{{ __('media.filter_panel.only_unused_help') }}</div>
                </div>
            </label>

            {{-- Tags Filter --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-semibold"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('media.filter_panel.tags') }}
                    </label>
                    <button x-show="filters.tag"
                            @click="filters.tag = ''; applyFilters()"
                            class="text-xs text-[#0056b3] hover:text-[#218838]">
                        {{ __('media.filter_panel.clear_tag') }}
                    </button>
                </div>

                <div class="flex flex-wrap gap-2 max-h-40 overflow-y-auto custom-scrollbar">
                    <template x-for="tag in tags" :key="tag">
                        <button type="button"
                                @click="filters.tag = filters.tag === tag ? '' : tag; applyFilters()"
                                class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all"
                                :class="filters.tag === tag
                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md'
                                    : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')"
                                x-text="tag">
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</aside>
