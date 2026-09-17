@props([
    'name' => 'location_id',
    'selected' => null,
    'selectedLabel' => null,
    'label' => null,
    'hint' => null,
])

{{--
    Searchable place picker, backed by admin.locations.search.

    Matches translated names AND registered aliases, so typing "Medina" finds
    المدينة المنورة. Only active places are offered.
--}}
<div x-data="placePicker({
        selected: {{ $selected ? (int) $selected : 'null' }},
        selectedLabel: @js($selectedLabel),
        endpoint: @js(route('admin.locations.search')),
     })"
     x-init="$watch('selected', () => $dispatch('wizard:dirty'))"
     class="relative">

    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
        {{ $label ?? __('locations.singular') }}
    </label>

    <input type="hidden" name="{{ $name }}" :value="selected ?? ''">

    <div class="relative">
        <input type="text"
               x-model="query"
               @input.debounce.300ms="search()"
               @focus="open = true"
               @keydown.escape="open = false"
               :placeholder="selectedLabel || '{{ __('locations.filters.search') }}'"
               class="w-full px-4 py-2.5 pe-10 rounded-xl border focus:ring-2 focus:ring-[#0056b3]"
               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">

        <button type="button" x-show="selected" @click="clear()" x-cloak
                class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-red-500"
                :title="'{{ __('locations.actions.remove_alias') }}'">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>

    <p x-show="selectedLabel" x-cloak class="mt-1.5 text-sm font-semibold flex items-center gap-1.5"
       :class="darkMode ? 'text-[#64CCC5]' : 'text-[#0056b3]'">
        <i data-lucide="map-pin" class="w-4 h-4"></i><span x-text="selectedLabel"></span>
    </p>

    @if($hint)
        <p class="mt-1 text-xs opacity-60">{{ $hint }}</p>
    @endif

    <div x-show="open && results.length" x-cloak @click.outside="open = false"
         class="absolute z-30 mt-1 w-full max-h-64 overflow-y-auto rounded-xl border shadow-lg"
         :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'">
        <template x-for="place in results" :key="place.id">
            <button type="button" @click="choose(place)"
                    class="w-full text-start px-4 py-2.5 flex items-center justify-between gap-3 hover:bg-gray-100 dark:hover:bg-gray-700">
                <span class="flex items-center gap-2">
                    <i data-lucide="map-pin" class="w-4 h-4 opacity-50"></i>
                    <span x-text="place.name"></span>
                    <span class="text-xs opacity-50" x-text="place.parent_name"></span>
                </span>
                <span class="text-xs opacity-50" x-text="place.type_label"></span>
            </button>
        </template>
    </div>
</div>

@once
@push('scripts')
<script>
function placePicker({ selected, selectedLabel, endpoint }) {
    return {
        selected, selectedLabel, endpoint,
        query: '', results: [], open: false,

        async search() {
            if (this.query.trim().length < 2) { this.results = []; return; }
            try {
                const url = `${this.endpoint}?q=${encodeURIComponent(this.query)}&limit=20`;
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) { this.results = []; return; }
                const payload = await response.json();
                this.results = payload.data ?? [];
                this.open = true;
            } catch (e) {
                // A failed lookup must not block saving the rest of the form.
                console.error('[place-picker]', e);
                this.results = [];
            }
        },

        choose(place) {
            this.selected = place.id;
            this.selectedLabel = place.name;
            this.query = '';
            this.results = [];
            this.open = false;
        },

        clear() {
            this.selected = null;
            this.selectedLabel = null;
            this.query = '';
        },
    };
}
</script>
@endpush
@endonce
