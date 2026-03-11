{{--
    Color Picker Component
    Preset color palette with custom color option

    Usage:
    <x-admin.color-picker name="color" :value="old('color', '#0056b3')" />
--}}

@props([
    'name' => 'color',
    'value' => '#0056b3',
    'required' => false,
])

{{-- Define colorPicker() BEFORE the element so Alpine finds it when its MutationObserver fires --}}
@once
<script>
function colorPicker() {
    return {
        isOpen: false,
        selectedColor: '',
        brandColors: [
            { name: 'Primary Blue', value: '#0056b3' },
            { name: 'Secondary Green', value: '#218838' },
            { name: 'Accent Teal', value: '#64CCC5' },
            { name: 'Light Blue', value: '#3385ff' },
            { name: 'Dark Green', value: '#1b5e20' }
        ],
        standardColors: [
            // Reds
            '#ef4444', '#dc2626', '#b91c1c', '#991b1b',
            // Oranges
            '#f97316', '#ea580c', '#c2410c', '#9a3412',
            // Yellows
            '#eab308', '#ca8a04', '#a16207', '#854d0e',
            // Greens
            '#22c55e', '#16a34a', '#15803d', '#166534',
            // Blues
            '#3b82f6', '#2563eb', '#1d4ed8', '#1e40af',
            // Purples
            '#a855f7', '#9333ea', '#7e22ce', '#6b21a8',
            // Pinks
            '#ec4899', '#db2777', '#be185d', '#9f1239',
            // Grays
            '#6b7280', '#4b5563', '#374151', '#1f2937'
        ],

        init() {
            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        selectColor(color) {
            this.selectedColor = color.toUpperCase();
            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        validateColor() {
            // Ensure # prefix
            if (!this.selectedColor.startsWith('#')) {
                this.selectedColor = '#' + this.selectedColor;
            }

            // Convert to uppercase
            this.selectedColor = this.selectedColor.toUpperCase();

            // Validate hex format
            const hexPattern = /^#[0-9A-F]{6}$/;
            if (!hexPattern.test(this.selectedColor)) {
                // If invalid, don't update (keep previous valid value)
                return;
            }

            this.$nextTick(() => {
                lucide.createIcons();
            });
        }
    };
}
</script>
@endonce

<div x-data="colorPicker()" x-init="selectedColor = '{{ $value }}'" class="color-picker">
    <!-- Hidden Input -->
    <input type="hidden"
           name="{{ $name }}"
           x-model="selectedColor"
           @if($required) required @endif>

    <!-- Selected Color Display -->
    <div class="flex items-center gap-3">
        <!-- Color Preview Circle -->
        <div class="relative">
            <button type="button"
                    @click="isOpen = !isOpen"
                    class="w-16 h-16 rounded-xl border-4 border-white shadow-lg hover:scale-110 transition-all ring-2 ring-offset-2"
                    :style="`background: ${selectedColor};`"
                    :class="darkMode ? 'ring-gray-800 ring-offset-gray-900' : 'ring-gray-200 ring-offset-white'">
            </button>
        </div>

        <!-- Color Value -->
        <div class="flex-1">
            <label class="text-sm font-semibold block mb-1"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Selected Color
            </label>
            <div class="flex items-center gap-2">
                <input type="text"
                       x-model="selectedColor"
                       @input="validateColor()"
                       pattern="^#[0-9A-Fa-f]{6}$"
                       placeholder="#0056b3"
                       class="flex-1 px-3 py-2 rounded-lg border text-sm font-mono uppercase transition-all focus:ring-2 focus:ring-accent/50"
                       :class="darkMode ?
                           'bg-gray-800 border-gray-700 text-white focus:border-accent' :
                           'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                <button type="button"
                        @click="isOpen = !isOpen"
                        class="px-3 py-2 rounded-lg border transition-all hover:scale-105"
                        :class="darkMode ?
                            'bg-gray-800 border-gray-700 text-white hover:bg-gray-700' :
                            'bg-white border-gray-300 text-gray-900 hover:bg-gray-50'">
                    <i data-lucide="palette" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Color Palette Dropdown -->
    <div x-show="isOpen"
         @click.away="isOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute z-50 w-full mt-2 rounded-xl shadow-2xl border overflow-hidden max-h-[80vh] overflow-y-auto custom-scrollbar"
         :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'">

        <!-- Brand Colors Section -->
        <div class="p-4 border-b"
             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <p class="text-xs font-semibold mb-3" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                BRAND COLORS
            </p>
            <div class="grid grid-cols-5 gap-3">
                <template x-for="color in brandColors" :key="color.value">
                    <button type="button"
                            @click="selectColor(color.value)"
                            class="group relative aspect-square rounded-lg shadow-md hover:scale-110 transition-all ring-2 ring-offset-2"
                            :style="`background: ${color.value};`"
                            :class="[
                                selectedColor === color.value ? 'ring-accent scale-110' : 'ring-transparent',
                                darkMode ? 'ring-offset-gray-800' : 'ring-offset-white'
                            ]"
                            :title="color.name">
                        <i data-lucide="check"
                           x-show="selectedColor === color.value"
                           class="absolute inset-0 m-auto w-6 h-6 text-white"></i>
                    </button>
                </template>
            </div>
        </div>

        <!-- Standard Colors Section -->
        <div class="p-4 border-b"
             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <p class="text-xs font-semibold mb-3" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                STANDARD COLORS
            </p>
            <div class="grid grid-cols-8 gap-2">
                <template x-for="color in standardColors" :key="color">
                    <button type="button"
                            @click="selectColor(color)"
                            class="group relative aspect-square rounded-lg shadow-md hover:scale-110 transition-all ring-2 ring-offset-2"
                            :style="`background: ${color};`"
                            :class="[
                                selectedColor === color ? 'ring-accent scale-110' : 'ring-transparent',
                                darkMode ? 'ring-offset-gray-800' : 'ring-offset-white'
                            ]">
                        <i data-lucide="check"
                           x-show="selectedColor === color"
                           class="absolute inset-0 m-auto w-4 h-4 text-white"></i>
                    </button>
                </template>
            </div>
        </div>

        <!-- Custom Color Section -->
        <div class="p-4"
             :class="darkMode ? 'bg-gray-900/50' : 'bg-gray-50'">
            <p class="text-xs font-semibold mb-3" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                CUSTOM COLOR
            </p>
            <div class="flex items-center gap-3">
                <input type="color"
                       x-model="selectedColor"
                       @input="validateColor()"
                       class="w-12 h-12 rounded-lg border-2 cursor-pointer"
                       :class="darkMode ? 'border-gray-700 bg-gray-800' : 'border-gray-300 bg-white'">
                <div class="flex-1">
                    <input type="text"
                           x-model="selectedColor"
                           @input="validateColor()"
                           pattern="^#[0-9A-Fa-f]{6}$"
                           placeholder="#0056b3"
                           class="w-full px-3 py-2 rounded-lg border text-sm font-mono uppercase transition-all focus:ring-2 focus:ring-accent/50"
                           :class="darkMode ?
                               'bg-gray-800 border-gray-700 text-white focus:border-accent' :
                               'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                </div>
            </div>
        </div>
    </div>
</div>

