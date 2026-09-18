{{--
    One audio element for a whole page of surah tracks, docked at the bottom.

    Rows do not own <audio> tags (114 of them cost a request each on some browsers
    and cannot hand over to the next surah). A row asks for a track with
        $dispatch('quran-track:toggle', { id: 17 })
    and mirrors the answer it hears back on `quran-track:state` ({ id, playing }).
    `quran-track:load` ({ id, title, url }) plays a URL that is not in the list yet —
    the editor uses it to audition a link before saving it.

    @props tracks: list of { id, title, url } in playing order
--}}
@props(['tracks' => []])

<div x-data="{
        tracks: @js(array_values($tracks)),
        current: null, playing: false, position: 0, duration: 0, failed: false,
        find(id) { return this.tracks.find((t) => t.id === id) || null; },
        toggle(id) {
            if (this.current && this.current.id === id) { this.playing ? this.$refs.audio.pause() : this.$refs.audio.play(); return; }
            const track = this.find(id);
            if (track) { this.load(track); }
        },
        load(track) {
            this.current = track; this.failed = false; this.position = 0; this.duration = 0;
            this.$refs.audio.src = track.url;
            this.$refs.audio.play().catch(() => { this.failed = true; });
        },
        step(by) {
            if (!this.current) { return; }
            const next = this.tracks[this.tracks.findIndex((t) => t.id === this.current.id) + by];
            if (next) { this.load(next); }
        },
        stop() { this.$refs.audio.pause(); this.$refs.audio.removeAttribute('src'); this.current = null; this.announce(); },
        announce() { window.dispatchEvent(new CustomEvent('quran-track:state', { detail: { id: this.current?.id ?? null, playing: this.playing, duration: this.duration } })); },
        clock(seconds) {
            if (!isFinite(seconds)) { return '0:00'; }
            const s = Math.floor(seconds % 60), m = Math.floor(seconds / 60) % 60, h = Math.floor(seconds / 3600);
            return (h ? h + ':' + String(m).padStart(2, '0') : m) + ':' + String(s).padStart(2, '0');
        },
     }"
     @quran-track:toggle.window="toggle($event.detail.id)"
     @quran-track:load.window="load($event.detail)"
     @keydown.escape.window="if (current && !$event.defaultPrevented && document.activeElement === document.body) stop()">

    <audio x-ref="audio" preload="none"
           @play="playing = true; announce()" @pause="playing = false; announce()"
           @timeupdate="position = $el.currentTime"
           @loadedmetadata="duration = $el.duration; announce()"
           @ended="step(1)" x-on:error="if (current) { failed = true; playing = false; announce(); }"></audio>

    <div x-show="current" x-cloak
         x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-4 opacity-0"
         x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="translate-y-4 opacity-0"
         class="fixed inset-x-0 bottom-14 z-30 mx-auto w-[min(46rem,calc(100%-1.5rem))] rounded-2xl border border-gray-200 bg-white/95 p-3 shadow-2xl backdrop-blur dark:border-gray-700 dark:bg-gray-900/95 motion-reduce:transition-none"
         role="region" aria-label="{{ __('quran.manage.play') }}">
        <div class="flex items-center gap-2">
            <button type="button" @click="step(-1)" aria-label="{{ __('quran.manage.previous') }}" title="{{ __('quran.manage.previous') }}"
                    class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:text-gray-300 dark:hover:bg-gray-800">
                <i data-lucide="skip-back" class="h-5 w-5 rtl:-scale-x-100" aria-hidden="true"></i>
            </button>
            <button type="button" @click="toggle(current.id)" :aria-label="playing ? @js(__('quran.manage.pause')) : @js(__('quran.manage.play'))"
                    class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] text-white shadow focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
                <span x-show="!playing"><i data-lucide="play" class="h-5 w-5 fill-current" aria-hidden="true"></i></span>
                <span x-show="playing" x-cloak><i data-lucide="pause" class="h-5 w-5 fill-current" aria-hidden="true"></i></span>
            </button>
            <button type="button" @click="step(1)" aria-label="{{ __('quran.manage.next') }}" title="{{ __('quran.manage.next') }}"
                    class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:text-gray-300 dark:hover:bg-gray-800">
                <i data-lucide="skip-forward" class="h-5 w-5 rtl:-scale-x-100" aria-hidden="true"></i>
            </button>

            <div class="min-w-0 flex-1 px-1">
                <div class="flex items-baseline justify-between gap-3">
                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white" x-text="current?.title"></p>
                    <p class="shrink-0 text-xs tabular-nums text-gray-500 dark:text-gray-400" dir="ltr" x-show="!failed">
                        <span x-text="clock(position)"></span> / <span x-text="clock(duration)"></span>
                    </p>
                    <p class="shrink-0 text-xs font-semibold text-red-600 dark:text-red-400" x-show="failed" x-cloak role="alert">{{ __('quran.manage.audio_failed') }}</p>
                </div>
                {{-- ltr: a seek bar runs with the clock, not with the page. --}}
                <input type="range" dir="ltr" min="0" :max="duration || 0" step="1" :value="position" :disabled="!duration"
                       @input="$refs.audio.currentTime = $event.target.value"
                       aria-label="{{ __('quran.manage.seek') }}"
                       class="mt-1 h-1.5 w-full cursor-pointer accent-[#0056b3] disabled:cursor-not-allowed disabled:opacity-40">
            </div>

            <button type="button" @click="stop()" aria-label="{{ __('quran.manage.close') }}" title="{{ __('quran.manage.close') }}"
                    class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-gray-500 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:text-gray-400 dark:hover:bg-gray-800">
                <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</div>
