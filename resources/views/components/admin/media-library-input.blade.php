@props([
    'name',
    'label' => __('media.title'),
    'multiple' => false,
    'type' => 'all',
    'initial' => [],
    'helper' => null,
])

@php
    $initialItems = $multiple
        ? collect($initial)->values()->all()
        : (filled($initial) ? [is_array($initial) ? $initial : (array) $initial] : []);
@endphp

<div x-data="mediaLibraryInputComponent({
        multiple: @js($multiple),
        type: @js($type),
        initialItems: @js($initialItems),
        fieldName: @js($name),
    })"
     class="space-y-4">
    @once('admin-media-picker-modal')
        <x-admin.media-picker-modal />
    @endonce

    <div class="flex items-center justify-between gap-4">
        <div>
            <label class="block text-sm font-semibold mb-1"
                   :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                {{ $label }}
            </label>
            @if($helper)
                <p class="text-xs"
                   :class="darkMode ? 'text-gray-500' : 'text-gray-600'">
                    {{ $helper }}
                </p>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <button type="button"
                    @click="chooseMedia()"
                    class="px-4 py-2 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold shadow-lg">
                <i data-lucide="image-plus" class="w-4 h-4 inline me-2"></i>
                {{ __('media.input.choose') }}
            </button>

            <button type="button"
                    x-show="selectedItems.length > 0"
                    @click="clearSelection()"
                    class="px-4 py-2 rounded-xl font-semibold transition-all"
                    :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                {{ __('media.input.clear') }}
            </button>
        </div>
    </div>

    <template x-if="!multiple">
        <input type="hidden" :name="fieldName" :value="selectedItems[0]?.id || ''">
    </template>

    <template x-if="multiple">
        <template x-for="item in selectedItems" :key="item.id">
            <input type="hidden" :name="`${fieldName}[]`" :value="item.id">
        </template>
    </template>

    <div x-show="selectedItems.length === 0"
         class="rounded-2xl border border-dashed p-8 text-center"
         :class="darkMode ? 'border-gray-800 bg-gray-950 text-gray-500' : 'border-gray-300 bg-gray-50 text-gray-600'">
        {{ __('media.input.empty') }}
    </div>

    <div x-show="selectedItems.length > 0"
         class="grid gap-4"
         :class="multiple ? 'sm:grid-cols-2 xl:grid-cols-3' : 'grid-cols-1'">
        <template x-for="item in selectedItems" :key="item.id">
            <div class="rounded-2xl overflow-hidden border shadow-sm"
                 :class="darkMode ? 'border-gray-800 bg-gray-950' : 'border-gray-200 bg-white'">
                <div class="aspect-video overflow-hidden"
                     :class="darkMode ? 'bg-gray-900' : 'bg-gray-100'">
                    <template x-if="item.is_image">
                        <img :src="item.thumbnail_url || item.src" :alt="item.title" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!item.is_image">
                        <div class="w-full h-full flex items-center justify-center">
                            <i :data-lucide="item.type === 'video' ? 'video' : (item.type === 'audio' ? 'music-4' : 'file-text')"
                               class="w-8 h-8"
                               :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                        </div>
                    </template>
                </div>

                <div class="p-4 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-semibold truncate"
                             :class="darkMode ? 'text-white' : 'text-gray-900'"
                             x-text="item.title"></div>
                        <div class="text-xs mt-1 truncate"
                             :class="darkMode ? 'text-gray-500' : 'text-gray-500'"
                             x-text="item.original_filename"></div>
                    </div>

                    <button type="button"
                            @click="removeItem(item.id)"
                            class="p-2 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function mediaLibraryInputComponent(config) {
                return {
                    multiple: config.multiple,
                    type: config.type,
                    fieldName: config.fieldName,
                    selectedItems: config.initialItems || [],

                    init() {
                        this.$nextTick(() => lucide.createIcons());
                    },

                    chooseMedia() {
                        MediaPicker.open({
                            mode: this.multiple ? 'multiple' : 'single',
                            type: this.type,
                            onSelect: (selected) => {
                                this.selectedItems = this.multiple ? selected : [selected];
                                this.$nextTick(() => lucide.createIcons());
                            }
                        });
                    },

                    removeItem(id) {
                        this.selectedItems = this.selectedItems.filter((item) => item.id !== id);
                    },

                    clearSelection() {
                        this.selectedItems = [];
                    }
                };
            }
        </script>
    @endpush
@endonce
