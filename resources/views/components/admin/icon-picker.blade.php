{{--
    Icon Picker Component
    Allows selection from popular Lucide icons

    Usage:
    <x-admin.icon-picker name="icon" :value="old('icon', 'folder')" />
--}}

@props([
    'name' => 'icon',
    'value' => 'folder',
    'required' => false,
])

<div x-data="iconPicker()" class="icon-picker">
    <!-- Hidden Input -->
    <input type="hidden"
           name="{{ $name }}"
           x-model="selectedIcon"
           @if($required) required @endif>

    <!-- Selected Icon Display -->
    <button type="button"
            @click="isOpen = !isOpen"
            class="w-full px-4 py-3 rounded-lg border-2 transition-all flex items-center justify-between hover:border-accent"
            :class="darkMode ?
                'bg-gray-800 border-gray-700 text-white' :
                'bg-white border-gray-300 text-gray-900'">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gradient-to-r from-[#0056b3] to-[#218838]">
                <i :data-lucide="selectedIcon" class="w-6 h-6 text-white"></i>
            </div>
            <span class="font-medium" x-text="selectedIcon"></span>
        </div>
        <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="{ 'rotate-180': isOpen }"></i>
    </button>

    <!-- Icon Grid Dropdown -->
    <div x-show="isOpen"
         @click.away="isOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute z-50 w-full mt-2 rounded-xl shadow-2xl border overflow-hidden"
         :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'">

        <!-- Search Input -->
        <div class="p-4 border-b"
             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <div class="relative">
                <i data-lucide="search" class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4"
                   :class="darkMode ? 'text-gray-500' : 'text-gray-400'"></i>
                <input type="text"
                       x-model="searchQuery"
                       @input="filterIcons()"
                       placeholder="{{ __('admin.search') }}"
                       class="w-full ps-10 pe-4 py-2 rounded-lg border transition-all text-base sm:text-sm focus:ring-2 focus:ring-accent/50"
                       :class="darkMode ?
                           'bg-gray-900 border-gray-700 text-white placeholder-gray-500 focus:border-accent' :
                           'bg-gray-50 border-gray-300 text-gray-900 placeholder-gray-400 focus:border-accent'">
            </div>
        </div>

        <!-- Icon Grid -->
        <div class="p-4 max-h-64 sm:max-h-80 overflow-y-auto custom-scrollbar">
            <div class="grid grid-cols-5 sm:grid-cols-6 gap-2">
                <template x-for="icon in filteredIcons" :key="icon">
                    <button type="button"
                            @click="selectIcon(icon)"
                            class="aspect-square rounded-lg transition-all hover:scale-110 flex items-center justify-center group"
                            :class="selectedIcon === icon ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838]' :
                                (darkMode ? 'bg-gray-900 hover:bg-gray-700' : 'bg-gray-50 hover:bg-gray-100')"
                            :title="icon">
                        <i :data-lucide="icon"
                           class="w-5 h-5"
                           :class="selectedIcon === icon ? 'text-white' : (darkMode ? 'text-gray-400 group-hover:text-white' : 'text-gray-600 group-hover:text-gray-900')"></i>
                    </button>
                </template>
            </div>

            <!-- No Results -->
            <div x-show="filteredIcons.length === 0" class="py-8 text-center">
                <i data-lucide="search-x" class="w-8 h-8 mx-auto mb-2"
                   :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('admin.no_results') }}
                </p>
            </div>
        </div>

        <!-- Popular Icons Quick Access -->
        <div class="p-4 border-t"
             :class="darkMode ? 'border-gray-700 bg-gray-900/50' : 'border-gray-200 bg-gray-50'">
            <p class="text-xs font-semibold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Popular:
            </p>
            <div class="flex flex-wrap gap-2">
                <template x-for="icon in popularIcons" :key="icon">
                    <button type="button"
                            @click="selectIcon(icon)"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all hover:scale-105 flex items-center gap-2"
                            :class="selectedIcon === icon ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' :
                                (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-white text-gray-700 hover:bg-gray-100')">
                        <i :data-lucide="icon" class="w-4 h-4"></i>
                        <span x-text="icon"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function iconPicker() {
    return {
        isOpen: false,
        selectedIcon: '{{ $value }}',
        searchQuery: '',
        filteredIcons: [],
        allIcons: [
            'folder', 'folder-open', 'folder-tree', 'folders',
            'file-text', 'file', 'files', 'book', 'book-open',
            'star', 'heart', 'bookmark', 'tag', 'tags',
            'home', 'user', 'users', 'settings', 'search',
            'calendar', 'clock', 'map', 'globe', 'compass',
            'camera', 'image', 'video', 'music', 'mic',
            'shopping-cart', 'shopping-bag', 'gift', 'award',
            'trophy', 'target', 'flag', 'shield', 'lock',
            'key', 'mail', 'message-circle', 'phone', 'bell',
            'alert-circle', 'info', 'help-circle', 'check-circle',
            'x-circle', 'plus-circle', 'minus-circle',
            'trending-up', 'trending-down', 'activity',
            'bar-chart', 'pie-chart', 'layers', 'box',
            'package', 'archive', 'database', 'server',
            'cpu', 'hard-drive', 'wifi', 'bluetooth',
            'zap', 'sun', 'moon', 'cloud', 'umbrella',
            'thermometer', 'wind', 'droplet', 'flame',
            'feather', 'anchor', 'briefcase', 'tool',
            'wrench', 'hammer', 'scissors', 'paperclip',
            'pen-tool', 'edit', 'trash', 'save', 'download',
            'upload', 'printer', 'copy', 'clipboard'
        ],
        popularIcons: [
            'folder', 'book', 'star', 'heart', 'tag',
            'user', 'home', 'calendar', 'image', 'video'
        ],

        init() {
            this.filteredIcons = this.allIcons;
            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        selectIcon(icon) {
            this.selectedIcon = icon;
            this.isOpen = false;
            this.searchQuery = '';
            this.filteredIcons = this.allIcons;
            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        filterIcons() {
            if (!this.searchQuery) {
                this.filteredIcons = this.allIcons;
            } else {
                const query = this.searchQuery.toLowerCase();
                this.filteredIcons = this.allIcons.filter(icon =>
                    icon.toLowerCase().includes(query)
                );
            }
            this.$nextTick(() => {
                lucide.createIcons();
            });
        }
    };
}
</script>
@endpush
