{{--
    Gradient Picker Component
    Visual selector for a preset Tailwind gradient (stored as the class string, e.g. "from-green-400 to-emerald-500").
    Presets are curated to the platform brand palette (blue→green brand, teal accent) plus a spread of
    warm / cool / neutral options for event-type colour coding.

    Usage:
    <x-admin.gradient-picker name="gradient" :value="old('gradient', $type?->gradient)" />
--}}

@props([
    'name' => 'gradient',
    'value' => '',
    'required' => false,
])

@once
<script>
function gradientPicker() {
    return {
        isOpen: false,
        selected: '',
        gradients: [
            // Brand / accent
            { c: 'from-[#0056b3] to-[#218838]', label: 'Brand' },
            { c: 'from-[#64CCC5] to-[#218838]', label: 'Accent' },
            { c: 'from-[#0056b3] to-[#64CCC5]', label: 'Brand Teal' },
            // Cool
            { c: 'from-blue-500 to-cyan-500', label: 'Ocean' },
            { c: 'from-sky-500 to-indigo-500', label: 'Sky' },
            { c: 'from-cyan-500 to-blue-600', label: 'Deep Sea' },
            { c: 'from-indigo-500 to-purple-600', label: 'Twilight' },
            { c: 'from-violet-500 to-fuchsia-500', label: 'Violet' },
            { c: 'from-purple-500 to-pink-500', label: 'Orchid' },
            // Green / teal
            { c: 'from-green-400 to-emerald-500', label: 'Meadow' },
            { c: 'from-emerald-500 to-teal-500', label: 'Emerald' },
            { c: 'from-teal-400 to-cyan-500', label: 'Lagoon' },
            { c: 'from-lime-400 to-green-500', label: 'Spring' },
            // Warm
            { c: 'from-yellow-400 to-amber-500', label: 'Sunbeam' },
            { c: 'from-amber-400 to-orange-500', label: 'Amber' },
            { c: 'from-orange-500 to-red-500', label: 'Sunset' },
            { c: 'from-red-500 to-orange-500', label: 'Ember' },
            { c: 'from-rose-500 to-pink-500', label: 'Rose' },
            { c: 'from-pink-400 to-rose-500', label: 'Blossom' },
            // Neutral
            { c: 'from-gray-500 to-slate-600', label: 'Slate' },
            { c: 'from-slate-400 to-slate-500', label: 'Stone' },
            { c: 'from-stone-500 to-neutral-600', label: 'Earth' },
        ],

        labelFor(value) {
            const g = this.gradients.find(x => x.c === value);
            return g ? g.label : (value || '—');
        },

        select(value) {
            this.selected = value;
            this.isOpen = false;
        },
    };
}
</script>
@endonce

<div x-data="gradientPicker()" x-init="selected = '{{ $value }}'" class="gradient-picker relative">
    <input type="hidden" name="{{ $name }}" x-model="selected" @if($required) required @endif>

    <!-- Selected gradient display -->
    <button type="button"
            @click="isOpen = !isOpen"
            class="w-full px-4 py-3 rounded-lg border-2 transition-all flex items-center justify-between hover:border-accent"
            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-12 h-8 rounded-lg flex-shrink-0 bg-gradient-to-r"
                 :class="selected || 'from-gray-300 to-gray-400'"></div>
            <span class="font-medium truncate" x-text="labelFor(selected)"></span>
        </div>
        <i data-lucide="chevron-down" class="w-5 h-5 transition-transform flex-shrink-0" :class="{ 'rotate-180': isOpen }"></i>
    </button>

    <!-- Gradient grid dropdown -->
    <div x-show="isOpen"
         x-cloak
         @click.away="isOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute z-50 w-full mt-2 rounded-xl shadow-2xl border overflow-hidden"
         :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'">

        <div class="p-4 max-h-72 overflow-y-auto custom-scrollbar">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                <template x-for="g in gradients" :key="g.c">
                    <button type="button"
                            @click="select(g.c)"
                            class="rounded-lg p-1.5 transition-all hover:scale-[1.03] border-2"
                            :class="selected === g.c ? 'border-accent' : (darkMode ? 'border-gray-700 hover:border-gray-600' : 'border-gray-200 hover:border-gray-300')"
                            :title="g.label">
                        <div class="h-9 rounded-md bg-gradient-to-r" :class="g.c"></div>
                        <span class="block mt-1 text-[11px] font-medium truncate"
                              :class="darkMode ? 'text-gray-300' : 'text-gray-600'"
                              x-text="g.label"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Clear option -->
        <div class="p-3 border-t" :class="darkMode ? 'border-gray-700 bg-gray-900/50' : 'border-gray-200 bg-gray-50'">
            <button type="button" @click="select('')"
                    class="text-sm font-medium transition-colors"
                    :class="darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900'">
                {{ __('admin.clear') }}
            </button>
        </div>
    </div>
</div>
