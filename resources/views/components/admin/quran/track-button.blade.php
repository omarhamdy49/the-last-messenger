{{-- Play / pause for one track of the page's <x-admin.quran.track-player>. @props id, label --}}
@props(['id', 'label'])

<button type="button" x-data="{ playing: false }"
        @quran-track:state.window="playing = $event.detail.id === {{ (int) $id }} && $event.detail.playing"
        @click="$dispatch('quran-track:toggle', { id: {{ (int) $id }} })"
        :aria-pressed="playing.toString()" aria-label="{{ __('quran.manage.play') }} — {{ $label }}" title="{{ __('quran.manage.play') }}"
        {{ $attributes->class('grid h-11 w-11 shrink-0 place-items-center rounded-full border border-gray-200 text-[#0056b3] transition-colors hover:border-accent hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:border-gray-700 dark:text-[#7cc4ff] dark:hover:bg-gray-800') }}
        :class="playing && '!border-transparent !bg-gradient-to-br from-[#0056b3] to-[#218838] !text-white'">
    <span x-show="!playing"><i data-lucide="play" class="h-4 w-4 fill-current" aria-hidden="true"></i></span>
    <span x-show="playing" x-cloak><i data-lucide="pause" class="h-4 w-4 fill-current" aria-hidden="true"></i></span>
</button>
