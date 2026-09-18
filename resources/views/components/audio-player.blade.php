{{--
    THE audio player. One component, one `audioPlayer` Alpine store, included by
    both layouts (public + admin) — there is no second player and no second
    <audio> element anywhere in the app.

    Three levels of context, from least to most:
      • play({url,title,subtitle})            — one track, no prev/next
      • setPlaylist(tracks, startIndex)       — ordered list; prev/next walk it
                                                in-page and tracks roll on
      • configure({reciters,reciterId,surah,urlBase})
                                              — the Quran surah reader: prev/next
                                                navigate to the neighbouring
                                                surah's PAGE, plus the reciter
                                                picker (the line under the track
                                                title) and per-ayah highlighting
                                                from the chapter's timings
    Prev/next resolve in that order (surah wins), and hide when neither applies —
    no dead controls.

    Layout is two rows: the transport/options row over a full-width scrub bar. The
    transport cluster and the scrub bar are forced dir="ltr" so ⏮/⏭ and the
    progress fill keep their universal meaning on RTL pages.

    Theme is plain Tailwind `dark:`. Both layouts put `dark` on <html> (via
    applyDarkMode() in partials/theme-preference), so the bar must NOT reach for
    either layout's private state ($store.theme on public, x-data darkMode on
    admin) — that is what forked this component in two in the first place.

    Hidden until a track is loaded.
--}}
@php
    // bg-[#ffffff], not bg-white: app.css carries an unlayered
    // `.dark .bg-white { ... !important }` that would beat `dark:bg-gray-900`
    // and repaint the bar navy on public pages only.
    $surface = 'bg-[#ffffff] dark:bg-gray-900';
    $iconBtnBase = 'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 transition-colors disabled:opacity-25 disabled:pointer-events-none';
    $iconBtnTheme = 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white';
    $iconBtn = $iconBtnBase.' '.$iconBtnTheme;
@endphp
<div x-data x-show="$store.audioPlayer.track" x-cloak
     x-effect="document.body.classList.toggle('site-has-audio', !!$store.audioPlayer.track)"
     class="fixed bottom-0 inset-x-0 z-50 border-t shadow-2xl {{ $surface }} border-gray-200 dark:border-gray-800"
     role="region" aria-label="{{ __('quran.public.listen') }}">
    <div class="mx-auto max-w-5xl px-3 sm:px-4 py-2">

        <div class="flex items-center gap-2 sm:gap-3">

            {{-- Track info --}}
            <div class="flex items-center gap-2.5 flex-1 min-w-0">
                <div class="hidden sm:flex w-9 h-9 rounded-lg bg-gradient-to-br from-[#0056b3] to-[#218838] items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M18 3a1 1 0 00-1.196-.98l-8 1.6A1 1 0 008 4.6v7.114A3.5 3.5 0 1010 14.5V8.82l6-1.2v3.894A3.5 3.5 0 1018 14V3z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold truncate font-['Amiri'] text-gray-900 dark:text-white" x-text="$store.audioPlayer.track?.title"></p>

                    {{-- The reciter line IS the picker. It used to be a dead label here and
                         a second button over on the right, which named the same reciter
                         twice and cost the transport room it did not have. --}}
                    <div class="relative" x-show="$store.audioPlayer.reciters.length > 0"
                         @click.outside="$store.audioPlayer.reciterOpen = false">
                        <button @click="$store.audioPlayer.reciterOpen = !$store.audioPlayer.reciterOpen"
                                class="flex items-center gap-1 max-w-full rounded text-[11px] text-gray-500 dark:text-gray-400 hover:text-[#0056b3] dark:hover:text-[#64CCC5] transition-colors"
                                title="{{ __('quran.public.choose_reciter') }}" aria-haspopup="listbox"
                                :aria-expanded="$store.audioPlayer.reciterOpen">
                            <span class="truncate" x-text="$store.audioPlayer.reciterName()"></span>
                            <svg class="w-3 h-3 flex-shrink-0 transition-transform" :class="$store.audioPlayer.reciterOpen ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="m6 9 6 6 6-6"/></svg>
                        </button>
                        {{-- One tap, one list: every recording is a row, so choosing a
                             reciter never means opening a select inside a popover. --}}
                        <div x-show="$store.audioPlayer.reciterOpen" x-cloak x-transition.opacity
                             class="absolute bottom-full start-0 mb-2 w-64 max-h-80 overflow-y-auto rounded-xl border shadow-2xl py-1 {{ $surface }} border-gray-200 dark:border-gray-700" role="listbox">
                            <div class="sticky top-0 p-2 {{ $surface }}">
                                    <input type="search" x-model="$store.audioPlayer.reciterQuery"
                                           @click.stop @keydown.escape.stop="$store.audioPlayer.reciterOpen = false"
                                           placeholder="{{ __('quran.public.reciter_search') }}"
                                           aria-label="{{ __('quran.public.reciter_search') }}"
                                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-xs py-1.5">
                                </div>
                                <p x-show="$store.audioPlayer.filteredReciters().length === 0" x-cloak
                                   class="px-3 py-3 text-xs text-gray-500 dark:text-gray-400">{{ __('site.no_results') }}</p>
                                <template x-for="r in $store.audioPlayer.filteredReciters()" :key="r.id">
                                    <button type="button" role="option" @click="$store.audioPlayer.selectReciter(r.id)"
                                            :aria-selected="r.id === $store.audioPlayer.reciterId ? 'true' : 'false'"
                                            class="w-full text-start px-3 py-2 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-gray-800"
                                            :class="r.id === $store.audioPlayer.reciterId ? 'bg-gray-100 dark:bg-gray-800 font-bold' : ''">
                                        <span class="block truncate" x-text="r.nameAr || r.nameEn"></span>
                                        <span class="block truncate text-[11px] text-gray-500 dark:text-gray-400"
                                              x-text="[r.recordingLabel, r.kindLabel].filter(Boolean).join(' · ')"></span>
                                    </button>
                                </template>
                        </div>
                    </div>

                    {{-- Anything that is not a surah (a playlist track) keeps its own line. --}}
                    <p x-show="$store.audioPlayer.reciters.length === 0" class="text-[11px] truncate text-gray-500 dark:text-gray-400"
                       x-text="$store.audioPlayer.track?.subtitle"></p>
                </div>
            </div>

            {{-- Transport --}}
            <div class="flex items-center gap-0.5 sm:gap-1 flex-shrink-0" dir="ltr">
                {{-- Previous: the previous surah's page, or the previous playlist track --}}
                <button x-show="$store.audioPlayer.hasStepNav()" x-cloak
                        @click="$store.audioPlayer.step(-1)"
                        :disabled="!$store.audioPlayer.canStep(-1)"
                        class="{{ $iconBtn }}"
                        :title="$store.audioPlayer.navMode() !== 'playlist' ? '{{ __('quran.public.previous_surah') }}' : '{{ __('quran.public.previous_track') }}'"
                        :aria-label="$store.audioPlayer.navMode() !== 'playlist' ? '{{ __('quran.public.previous_surah') }}' : '{{ __('quran.public.previous_track') }}'">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M7 5h2v14H7zM19 5v14l-9.5-7z"/></svg>
                </button>

                {{-- Back 5s --}}
                <button @click="$store.audioPlayer.nudge(-5)" class="{{ $iconBtn }}"
                        title="{{ __('quran.public.rewind_5') }} (←)" aria-label="{{ __('quran.public.rewind_5') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" d="M4 12a8 8 0 1 0 8-8"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.4 1.7 12 4l2.4 2.3"/>
                        <text x="12" y="16.2" text-anchor="middle" font-size="9" font-weight="700" fill="currentColor" stroke="none" font-family="ui-sans-serif, system-ui, sans-serif">5</text>
                    </svg>
                </button>

                {{-- Play / pause --}}
                <button @click="$store.audioPlayer.toggle()"
                        class="w-10 h-10 rounded-full flex items-center justify-center text-white bg-gradient-to-r from-[#0056b3] to-[#218838] flex-shrink-0 hover:scale-105 active:scale-95 transition-transform"
                        :aria-label="$store.audioPlayer.playing ? '{{ __('quran.public.pause') }}' : '{{ __('quran.public.play') }}'">
                    <svg x-show="!$store.audioPlayer.playing" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.84A1 1 0 004 3.7v12.6a1 1 0 001.5.86l11-6.3a1 1 0 000-1.72l-11-6.3z"/></svg>
                    <svg x-show="$store.audioPlayer.playing" x-cloak class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 4a1 1 0 011 1v10a1 1 0 11-2 0V5a1 1 0 011-1zm7 0a1 1 0 011 1v10a1 1 0 11-2 0V5a1 1 0 011-1z"/></svg>
                </button>

                {{-- Forward 5s --}}
                <button @click="$store.audioPlayer.nudge(5)" class="{{ $iconBtn }}"
                        title="{{ __('quran.public.forward_5') }} (→)" aria-label="{{ __('quran.public.forward_5') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" d="M20 12a8 8 0 1 1-8-8"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.6 1.7 12 4 9.6 6.3"/>
                        <text x="12" y="16.2" text-anchor="middle" font-size="9" font-weight="700" fill="currentColor" stroke="none" font-family="ui-sans-serif, system-ui, sans-serif">5</text>
                    </svg>
                </button>

                {{-- Next --}}
                <button x-show="$store.audioPlayer.hasStepNav()" x-cloak
                        @click="$store.audioPlayer.step(1)"
                        :disabled="!$store.audioPlayer.canStep(1)"
                        class="{{ $iconBtn }}"
                        :title="$store.audioPlayer.navMode() !== 'playlist' ? '{{ __('quran.public.next_surah') }}' : '{{ __('quran.public.next_track') }}'"
                        :aria-label="$store.audioPlayer.navMode() !== 'playlist' ? '{{ __('quran.public.next_surah') }}' : '{{ __('quran.public.next_track') }}'">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M15 5h2v14h-2zM5 5v14l9.5-7z"/></svg>
                </button>

                <span class="w-px h-5 mx-0.5 sm:mx-1 flex-shrink-0 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>

                {{-- Play from the beginning --}}
                <button @click="$store.audioPlayer.restart()" class="{{ $iconBtn }}"
                        title="{{ __('quran.public.play_from_start') }}" aria-label="{{ __('quran.public.play_from_start') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" d="M4 12a8 8 0 1 0 8-8"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.4 1.7 12 4l2.4 2.3"/>
                        <path d="M10.3 9.2 15 12l-4.7 2.8z" fill="currentColor" stroke="none"/>
                    </svg>
                </button>
            </div>

            {{-- Speed + volume + reciter + close. min-w-fit, not min-w-0: this
                 cluster must never shrink below its buttons — it did, and then
                 overflowed onto the transport. The track title absorbs the
                 squeeze instead, which is what truncate is there for. --}}
            <div class="flex-1 min-w-fit flex items-center justify-end gap-1 sm:gap-2">
{{-- Follow along: scroll the reader to the ayah being recited. The
                     highlight follows the playhead either way; this only decides
                     whether the page moves under the listener. --}}
                {{-- ms-*: the options cluster butts up against the transport, and the
                     start side is the facing one in both LTR and RTL. --}}
                <button x-show="$store.audioPlayer.surahMeta"
                        @click="$store.audioPlayer.toggleFollow()"
                        class="{{ $iconBtnBase }} ms-2 sm:ms-3"
                        :class="$store.audioPlayer.follow
                            ? 'text-white bg-gradient-to-r from-[#0056b3] to-[#218838]'
                            : '{{ $iconBtnTheme }}'"
                        :aria-pressed="$store.audioPlayer.follow"
                        title="{{ __('quran.public.follow_verses') }}" aria-label="{{ __('quran.public.follow_verses') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h9M4 12h9M4 18h6"/><circle cx="18" cy="16" r="3.2"/><path stroke-linecap="round" d="m20.4 18.4 1.6 1.6"/></svg>
                </button>

                {{-- Auto-play next (surah or playlist track) — shown wherever there is a next --}}
                <button x-show="$store.audioPlayer.hasStepNav()"
                        @click="$store.audioPlayer.toggleAutoNext()"
                        class="{{ $iconBtnBase }} ms-2 sm:ms-3"
                        :class="$store.audioPlayer.autoNext
                            ? 'text-white bg-gradient-to-r from-[#0056b3] to-[#218838]'
                            : '{{ $iconBtnTheme }}'"
                        :aria-pressed="$store.audioPlayer.autoNext"
                        title="{{ __('quran.public.auto_next') }}" aria-label="{{ __('quran.public.auto_next') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 2l4 4-4 4"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 11v-1a4 4 0 0 1 4-4h14M7 22l-4-4 4-4"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 13v1a4 4 0 0 1-4 4H3"/></svg>
                </button>

                {{-- Off the reader: verse following and reciter switching are one tap away --}}
                <a x-show="$store.audioPlayer.track?.readerUrl && !$store.audioPlayer.surahMeta" x-cloak
                   :href="$store.audioPlayer.track?.readerUrl"
                   class="{{ $iconBtn }} ms-2 sm:ms-3"
                   title="{{ __('quran.playlists.open_reader') }}" aria-label="{{ __('quran.playlists.open_reader') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v16H6.5A2.5 2.5 0 0 0 4 21.5zM20 5.5A2.5 2.5 0 0 0 17.5 3H13v16h4.5a2.5 2.5 0 0 1 2.5 2.5z"/></svg>
                </a>

                <button @click="$store.audioPlayer.cycleSpeed()"
                        class="px-2 h-8 rounded-lg text-xs font-bold tabular-nums transition-colors flex-shrink-0 text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                        x-text="$store.audioPlayer.rate + '×'" title="{{ __('quran.public.playback_speed') }}"
                        aria-label="{{ __('quran.public.playback_speed') }}">1×</button>

                {{-- Volume: phones have hardware keys, so the slider is desktop-only --}}
                <div class="hidden md:flex items-center gap-1.5 flex-shrink-0">
                    <button @click="$store.audioPlayer.toggleMute()" class="{{ $iconBtn }}"
                            :title="$store.audioPlayer.volume > 0 ? '{{ __('quran.public.mute') }}' : '{{ __('quran.public.unmute') }}'"
                            :aria-label="$store.audioPlayer.volume > 0 ? '{{ __('quran.public.mute') }}' : '{{ __('quran.public.unmute') }}'">
                        <svg x-show="$store.audioPlayer.volume > 0" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5 6 9H3v6h3l5 4V5z"/><path stroke-linecap="round" d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/></svg>
                        <svg x-show="$store.audioPlayer.volume === 0" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5 6 9H3v6h3l5 4V5z"/><path stroke-linecap="round" d="m16 9.5 5 5m0-5-5 5"/></svg>
                    </button>
                    <input type="range" min="0" max="1" step="0.05" :value="$store.audioPlayer.volume"
                           @input="$store.audioPlayer.setVolume(parseFloat($event.target.value))"
                           class="w-16 lg:w-20 accent-[#0056b3] cursor-pointer" aria-label="{{ __('quran.public.volume') }}">
                </div>

                <button @click="$store.audioPlayer.close()" class="{{ $iconBtn }}"
                        aria-label="{{ __('quran.public.close_player') }}" title="{{ __('quran.public.close_player') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        {{-- Scrub bar (under the transport): click, drag or arrow-key. Own row so it stays grabbable on phones. --}}
        <div class="mt-1 flex items-center gap-2" dir="ltr">
            <span class="text-[11px] tabular-nums w-10 text-right flex-shrink-0 text-gray-500 dark:text-gray-400"
                  x-text="$store.audioPlayer.fmt($store.audioPlayer.currentTime)">0:00</span>

            <div class="group flex-1 py-1.5 cursor-pointer touch-none focus:outline-none"
                 @pointerdown.prevent="$store.audioPlayer.scrubStart($event)"
                 @keydown.arrow-right.prevent="$store.audioPlayer.nudge(5)"
                 @keydown.arrow-left.prevent="$store.audioPlayer.nudge(-5)"
                 tabindex="0" role="slider"
                 :aria-valuenow="Math.round($store.audioPlayer.progress)" aria-valuemin="0" aria-valuemax="100"
                 aria-label="{{ __('quran.public.seek') }}">
                <div class="h-1.5 rounded-full transition-[height] group-hover:h-2 group-focus:h-2 bg-gray-200 dark:bg-gray-700">
                    <div class="relative h-full rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838]"
                         :style="`width: ${$store.audioPlayer.progress}%`">
                        <span class="absolute -right-1.5 top-1/2 -translate-y-1/2 w-3 h-3 rounded-full bg-white shadow ring-1 ring-black/10 opacity-0 transition-opacity group-hover:opacity-100 group-focus:opacity-100"></span>
                    </div>
                </div>
            </div>

            <span class="text-[11px] tabular-nums w-10 flex-shrink-0 text-gray-500 dark:text-gray-400"
                  x-text="$store.audioPlayer.fmt($store.audioPlayer.duration)">0:00</span>
        </div>

    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        // Guard: the component is included by both layouts, and a page that
        // somehow renders both must still end up with exactly one store.
        if (Alpine.store('audioPlayer')) { return; }

        Alpine.store('audioPlayer', {
            track: null,            // { url, title, subtitle }
            audio: null,
            playing: false,
            currentTime: 0,
            duration: 0,
            rate: 1,
            volume: parseFloat(localStorage.getItem('sitePlayerVolume') ?? '1'),
            _lastVolume: 1,         // restored by unmute
            _saveTimer: null,

            // Playlist context: an ordered [{url,title,subtitle}] the transport
            // walks in place. Null = single-track or surah context.
            playlist: null,
            playlistIndex: 0,

            // Surah names for in-place surah stepping off the reader: [number] -> nameAr.
            // Loaded once from the site's surah list and kept for the session.
            _surahNames: null,

            // Reciter switching (configured per surah page)
            reciters: [],           // [{ id, nameAr, nameEn, surahAudioUrl, hasTimestamps }]
            reciterId: null,
            surahMeta: null,        // { nameAr, number, versesCount }
            surahUrlBase: null,     // e.g. https://site/quran/surah — enables surah transport
            reciterOpen: false,
            reciterQuery: '',       // filter box in the reciter list
            // Follow-along: scroll the reader to the ayah being recited. Highlighting
            // happens either way; this only decides whether the page moves under the
            // listener. Default on — reading along with the recitation is what the
            // reader is for, and now that it only moves the page (never the audio),
            // there is nothing for it to interrupt. Switching it off is remembered.
            follow: localStorage.getItem('siteFollowVerses') !== '0',
            // Auto-play the next surah/track when one ends. Default on; the
            // listener can switch it off from the player on any page.
            autoNext: localStorage.getItem('sitePlayerAutoNext') !== '0',

            // Per-ayah timings for the whole-surah recording of the selected
            // (surah, reciter): [[ayah, startMs, endMs], ...] ascending, with
            // ayah 0 covering the bismillah. Null means this pair has none —
            // mp3quran publishes them for 34 of our 56 reciters — and everything
            // below falls back to what it did before they existed.
            timings: null,
            ayahAtTime: null,       // ayah under the playhead, derived from `timings`
            _timingsCache: {},      // "surah:reciterId" -> array (empty array = none, never refetched)
            _timingsPending: {},    // "surah:reciterId" -> in-flight promise, so two callers share one request
            _timingsReq: 0,         // request generation; a late response with an old one is dropped

            init() {
                try {
                    const saved = JSON.parse(localStorage.getItem('sitePlayerTrack') || 'null');
                    if (saved && saved.url) {
                        this.track = { url: saved.url, title: saved.title, subtitle: saved.subtitle, readerUrl: saved.readerUrl || null, surah: saved.surah || null };
                        this._ensureAudio();
                        this.audio.currentTime = saved.currentTime || 0;
                        this.currentTime = saved.currentTime || 0;
                        // A playlist queue is restored too, so prev/next keep working on
                        // the next page — but only while its current entry is the saved track.
                        const q = JSON.parse(localStorage.getItem('sitePlayerPlaylist') || 'null');
                        if (q && Array.isArray(q.list) && q.list[q.index]?.url === saved.url) {
                            this.playlist = q.list; this.playlistIndex = q.index;
                        }
                    }
                } catch (e) { /* ignore corrupt state */ }
                if (this.volume > 0) { this._lastVolume = this.volume; }
                this._bindKeys();
            },

            // Arrow keys seek ±5s while something is actually playing. Typing
            // fields keep their arrows, Alt/Cmd combos stay with the browser
            // (Alt+← is Back), and a keypress the scrub bar already handled is
            // left alone so a focused bar doesn't seek twice.
            _bindKeys() {
                window.addEventListener('keydown', (e) => {
                    if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') { return; }
                    if (!this.track || !this.playing) { return; }
                    if (e.defaultPrevented || e.ctrlKey || e.metaKey || e.altKey || e.shiftKey) { return; }
                    if (e.target?.closest?.('input, textarea, select, [contenteditable=""], [contenteditable="true"]')) { return; }
                    e.preventDefault();
                    this.nudge(e.key === 'ArrowRight' ? 5 : -5);
                });
            },

            // Set up the reciter list for the current surah page. The user's saved
            // reciter (siteReciterId) wins so their choice sticks across surahs;
            // the page default and the first reciter are fallbacks.
            configure({ reciters, reciterId, surah, urlBase, force = false }) {
                this.reciters = Array.isArray(reciters) ? reciters : [];
                this.surahMeta = surah || null;
                this.surahUrlBase = urlBase || null;
                // Timings belong to the surah that loaded them.
                this.timings = null; this.ayahAtTime = null; this._timingsReq++;
                // An active playlist survives opening the reader (e.g. "open in reader"
                // from a playlist): it is only replaced once this page plays its own surah.
                const stored = parseInt(localStorage.getItem('siteReciterId') ?? '', 10);
                const valid = (id) => this.reciters.some((r) => r.id === id);
                // `force` (a playlist hand-off via ?reciter=) wins over the stored
                // choice for this page only; the stored preference is not touched.
                // With nothing chosen yet, prefer a reciter that has the whole-surah
                // recording: that is the one where "play from here" is a seek inside a
                // single file, which is how the reader is meant to sound. A stored
                // choice still wins — the listener's pick is not second-guessed.
                const firstWhole = this.reciters.find((r) => r.surahAudioUrl) || this.reciters[0];
                this.reciterId = (force && valid(reciterId)) ? reciterId
                    : (valid(stored) ? stored
                        : (valid(reciterId) ? reciterId : (firstWhole?.id ?? null)));
                // init() may have restored a paused whole-surah track from a previous
                // visit. Resuming it goes straight through toggle(), never through
                // playSurah(), so this is the only place those timings get asked for.
                if (this.track?.url && this.track.url === this.surahUrl()) { this._loadTimings(); }
            },

            // Load an ordered list and start at startIndex. Re-selecting the track
            // that is already loaded toggles it, same as play(). Mutually exclusive
            // with the surah context: whichever was configured last owns prev/next.
            setPlaylist(tracks, startIndex = 0) {
                const list = (Array.isArray(tracks) ? tracks : []).filter((t) => t?.url);
                if (!list.length) { return; }
                this.playlist = list;
                this.playlistIndex = Math.max(0, Math.min(startIndex, list.length - 1));
                this.surahMeta = null; this.surahUrlBase = null;
                if (this.track?.url === list[this.playlistIndex].url) { this.toggle(); return; }
                this._playListed(true);
            },

            // Attach a list to the track that is already loaded (a resume from a
            // playlist page after the saved queue was lost) without touching playback.
            // Re-establishes prev/next, auto-next and the reader link.
            adoptPlaylist(tracks, index) {
                const list = (Array.isArray(tracks) ? tracks : []).filter((t) => t?.url);
                if (!list.length || !this.track || list[index]?.url !== this.track.url) { return false; }
                this.playlist = list; this.playlistIndex = index;
                this.surahMeta = null; this.surahUrlBase = null;
                this.track = { ...this.track, title: list[index].title || this.track.title, subtitle: list[index].subtitle || this.track.subtitle, readerUrl: list[index].readerUrl || this.track.readerUrl || null };
                this._persist();
                return true;
            },

            _playListed(autoplay) {
                const t = this.playlist[this.playlistIndex];
                this._teardown();
                this.track = { url: t.url, title: t.title || '', subtitle: t.subtitle || '', readerUrl: t.readerUrl || null };
                this.currentTime = 0; this.duration = 0;
                this._ensureAudio();
                this._persist();
                if (autoplay) { this.audio.play().catch(() => {}); }
            },

            // The catalogue runs to 33 recordings, so the list needs a filter. Matches
            // either name and the style/kind line, case-insensitively.
            filteredReciters() {
                const q = this.reciterQuery.trim().toLowerCase();
                if (!q) { return this.reciters; }

                return this.reciters.filter((r) => [r.nameAr, r.nameEn, r.recordingLabel, r.kindLabel]
                    .filter(Boolean).join(' ').toLowerCase().includes(q));
            },
            currentReciter() { return this.reciters.find((r) => r.id === this.reciterId) || null; },
            // What a reader-played track remembers about itself, so prev/next and
            // auto-next keep working after the listener leaves the reader.
            _surahRef() {
                const number = this.surahMeta?.number;
                if (!number || !this.reciterId) { return null; }
                return { number, reciterId: this.reciterId };
            },
            _readerUrl() {
                const ref = this._surahRef();
                return ref && this.surahUrlBase ? `${this.surahUrlBase}/${ref.number}?reciter=${ref.reciterId}` : null;
            },
            reciterName() { const r = this.currentReciter(); return r ? (r.nameAr || r.nameEn || '') : ''; },
            surahUrl() { const r = this.currentReciter(); return r ? r.surahAudioUrl : null; },

            // The ayah being recited, 1-based, resolved from the playhead against the
            // chapter's timings in _syncTimedAyah. null means "highlight nothing", and
            // so does ayah 0 (the bismillah); syncPlayingVerse() on the surah page
            // treats any falsy value that way.
            get playingAyah() { return this.ayahAtTime || null; },

            // Load the whole-surah file and start at at(duration), once the
            // duration is actually known.
            _playWhole(url, at, autoplay) {
                this.playlist = null; this.playlistIndex = 0;
                this._teardown();
                this.track = { url, title: this.surahMeta?.nameAr || this.track?.title || '', subtitle: this.reciterName(), surah: this._surahRef(), readerUrl: this._readerUrl() };
                this.currentTime = 0; this.duration = 0;
                this._ensureAudio();
                this.audio.addEventListener('loadedmetadata', () => {
                    const seconds = at(this.audio.duration || 0);
                    if (seconds > 0) { this.audio.currentTime = Math.min(seconds, this.audio.duration || seconds); }
                    if (autoplay) { this.audio.play().catch(() => {}); }
                }, { once: true });
                this._persist();
                this._loadTimings();
            },

            // Fetch the timings for the selected (surah, reciter), once. Lazy: only
            // a whole-surah recording can use them, so nothing is fetched until one
            // starts or the reciter changes. A pair with no timings caches the empty
            // list, so the second look costs no request.
            _loadTimings() {
                // Newest call wins. A response tagged with an older token belongs to
                // a reciter or surah the reader has already left, and must not land
                // on top of the current one.
                const token = ++this._timingsReq;
                const surah = this.surahMeta?.number;
                const id = this.reciterId;
                // Timings describe this reciter's whole-surah recording. No recording,
                // nothing to time.
                if (!surah || !id || !this.surahUrlBase || !this.surahUrl()) {
                    this.timings = null; this.ayahAtTime = null;

                    return Promise.resolve();
                }

                const source = this.currentReciter()?.sourceId;
                const revision = this.currentReciter()?.sourceRevision;
                const key = `${surah}:${id}:${source}:${revision}`;
                const cached = this._timingsCache[key];
                if (cached) { this._useTimings(cached, token); return Promise.resolve(); }

                this.timings = null; this.ayahAtTime = null;

                // One request per pair even when two call sites ask at once —
                // selectReciter() asks, and the _playWhole() it goes on to call
                // asks again for the same reciter before the first has landed.
                let req = this._timingsPending[key];
                if (!req) {
                    req = fetch(`${this.surahUrlBase}/${surah}/timings/${id}${source ? `?source=${source}&revision=${revision}` : ''}`, { headers: { Accept: 'application/json' } })
                        .then((r) => (r.ok ? r.json() : null))
                        .then((data) => (Array.isArray(data?.ayahs) ? data.ayahs : []))
                        // Highlighting is progressive enhancement: a failed fetch
                        // costs the reader the highlight and nothing else. An empty
                        // list is cached like any other answer, so a reciter with no
                        // timings is asked for exactly once.
                        .catch(() => [])
                        .then((ayahs) => {
                            this._timingsCache[key] = ayahs;
                            delete this._timingsPending[key];

                            return ayahs;
                        });
                    this._timingsPending[key] = req;
                }

                return req.then((ayahs) => this._useTimings(ayahs, token));
            },

            _useTimings(ayahs, token) {
                if (token !== this._timingsReq) { return; }
                this.timings = ayahs.length ? ayahs : null;
                this._syncTimedAyah(this.audio?.currentTime || 0);
            },

            // Which ayah covers an offset in the whole-surah file. Binary search,
            // not a scan: this runs on every `timeupdate` (~4x a second) and
            // al-Baqarah has 287 triples. Sticky through any gap between triples,
            // so a silence does not blink the highlight off. Returns 0 for the
            // bismillah, null before the first entry.
            _ayahAtMs(ms) {
                const t = this.timings;
                if (!t || !t.length || ms < t[0][1]) { return null; }
                let lo = 0, hi = t.length - 1;
                while (lo < hi) {
                    const mid = (lo + hi + 1) >> 1;
                    if (t[mid][1] <= ms) { lo = mid; } else { hi = mid - 1; }
                }

                return t[lo][0];
            },

            // Where an ayah starts in the whole-surah file, in ms. Null = unknown.
            _ayahStartMs(ayah) {
                const t = this.timings?.find((x) => x[0] === ayah);

                return t ? t[1] : null;
            },

            _timingsTotalMs() {
                const last = this.timings?.[this.timings.length - 1];

                return last ? last[2] : 0;
            },

            // Assign only when the ayah actually changes. `playingAyah` feeds a
            // class binding on every verse card and an x-effect that scrolls the
            // page; writing it four times a second would re-run all of that for
            // nothing, most of the time to the same value.
            _syncTimedAyah(seconds) {
                const n = this._ayahAtMs(seconds * 1000);
                if (n !== this.ayahAtTime) { this.ayahAtTime = n; }
            },

            // Cumulative share of the surah's letters at the end of each ayah.
            // Recitation time tracks letters, not ayah count — ayah 282 of
            // al-Baqarah is not one 286th of the recording. This is the only way
            // to carry a listener's place between a whole-surah file (no verse
            // timings anywhere in this project), so it is an estimate; it always
            // lands on an ayah boundary, so the error stops there instead of
            // compounding.
            _ayahShares() {
                const weights = this.surahMeta?.weights;
                if (!Array.isArray(weights) || weights.length === 0) { return null; }
                const total = weights.reduce((sum, n) => sum + n, 0);
                if (!total) { return null; }
                let acc = 0;

                return weights.map((n) => (acc += n) / total);
            },
            // Which ayah a fraction of the recording falls in, and where an ayah
            // starts. Real timings win wherever we have them; the letter estimate
            // is only for the reciters mp3quran publishes none for.
            _ayahAt(ratio) {
                const total = this._timingsTotalMs();
                // Ayah 0 is the bismillah; resuming from it means resuming at ayah 1.
                if (total) { return this._ayahAtMs(ratio * total) || 1; }

                const shares = this._ayahShares();
                if (!shares) { return null; }
                const i = shares.findIndex((share) => share >= ratio);

                return i === -1 ? shares.length : i + 1;
            },
            _ratioAt(ayah) {
                const total = this._timingsTotalMs();
                if (total) {
                    const ms = this._ayahStartMs(ayah);
                    if (ms !== null) { return ms / total; }
                }

                const shares = this._ayahShares();
                if (!shares || ayah <= 1) { return 0; }

                return shares[ayah - 2] ?? 0;
            },

            toggleAutoNext() {
                this.autoNext = !this.autoNext;
                localStorage.setItem('sitePlayerAutoNext', this.autoNext ? '1' : '0');
            },

            // Auto-scroll only: nothing about playback changes, so this never
            // interrupts the recording.
            toggleFollow() {
                this.follow = !this.follow;
                localStorage.setItem('siteFollowVerses', this.follow ? '1' : '0');
            },

            // Play the configured surah with the selected reciter (rail Play button).
            playSurah(restart = false) {
                const url = this.surahUrl();
                if (!url) { window.toast?.('{{ __('quran.public.audio_unavailable') }}', 'error'); return; }

                this._loadTimings();
                if (restart && this.track?.url === url) { this.restart(); return; }
                this.play({ url, title: this.surahMeta?.nameAr || '', subtitle: this.reciterName(), surah: this._surahRef(), readerUrl: this._readerUrl() });
            },

            // Start the surah at one ayah — the per-verse play buttons in verse view
            // and "listen from here" in reading view both land here. It is always a
            // seek inside the one gapless recording: with the chapter's timings the
            // position is exact, and without them it is estimated, but the listener
            // never gets a different file.
            playAyah(number) {
                const whole = this.surahUrl();
                if (!whole) { window.toast?.('{{ __('quran.public.audio_unavailable') }}', 'error'); return; }

                // Returned so a caller can await the seek; nothing in the app does.
                return this._loadTimings().then(() => {
                    const ms = this._ayahStartMs(number);
                    if (ms === null) {
                        // ponytail: no timings for this recording — seek by the letter-share
                        // estimate rather than refuse; exact once it gets real timings.
                        const ratio = this._ratioAt(number);
                        this._playWhole(whole, (duration) => ratio * duration, true);

                        return;
                    }

                    if (this.track?.url === whole && this.audio) {
                        // Re-tapping the ayah already under the playhead toggles it:
                        // the card shows a pause icon.
                        if (this.ayahAtTime === number) { this.toggle(); return; }
                        this.audio.currentTime = ms / 1000;
                        this.currentTime = ms / 1000;
                        this._syncTimedAyah(ms / 1000);
                        if (!this.playing) { this.audio.play().catch(() => {}); }

                        return;
                    }

                    this._playWhole(whole, () => ms / 1000, true);
                });
            },



            // Switch reciter and resume the same surah at the same ayah.
            selectReciter(id) {
                this.reciterId = id;
                this.reciterOpen = false;
                this.reciterQuery = '';
                localStorage.setItem('siteReciterId', String(id));
                // Timings belong to one recording, so the switch invalidates them
                // whether or not anything is playing right now.
                this._loadTimings();
                if (!this.track) { return; }

                const wasPlaying = this.playing;
                const url = this.surahUrl();
                if (!url) { window.toast?.('{{ __('quran.public.audio_unavailable') }}', 'error'); return; }

                // Resume at the same AYAH, not the same wall-clock offset: two reciters
                // read at different speeds, so 3:41 into one is a different verse in
                // the other. Falls back to the offset only when timings are missing.
                const ayah = this.ayahAtTime;
                const offset = this.currentTime;

                this._loadTimings().then(() => {
                    const ms = ayah ? this._ayahStartMs(ayah) : null;
                    this._playWhole(url, () => (ms === null ? offset : ms / 1000), wasPlaying);
                });
            },

            // Play a standalone track ({url,title,subtitle}); re-clicking the same
            // url toggles. Drops any playlist — a bare track is not part of a list.
            play(trackData) {
                if (this.track && this.track.url === trackData.url) { this.toggle(); return; }
                this.playlist = null; this.playlistIndex = 0;
                this._teardown();
                this.track = { url: trackData.url, title: trackData.title || '', subtitle: trackData.subtitle || '', surah: trackData.surah || null, readerUrl: trackData.readerUrl || null };
                this.currentTime = 0; this.duration = 0;
                this._ensureAudio();
                this._persist();
                this.audio.play().catch(() => {});
            },

            toggle() {
                if (!this.audio) { return; }
                if (this.playing) { this.audio.pause(); } else { this.audio.play().catch(() => {}); }
            },

            // Skip by ±seconds within the recording.
            nudge(sec) {
                if (!this.audio) { return; }
                // Read the element, not the mirrored currentTime: `timeupdate`
                // fires ~4x a second, so two quick taps off the mirror would both
                // compute from the same stale base and land one step, not two.
                const t = this.audio.currentTime + sec;
                const end = this.audio.duration || this.duration || 0;
                const at = Math.max(0, end ? Math.min(t, end) : Math.max(t, 0));
                this.audio.currentTime = at;
                this.currentTime = at;
            },

            // Restart the surah from its very beginning.
            restart() {
                if (!this.audio) { return; }
                this.audio.currentTime = 0;
                this.currentTime = 0;
                this.audio.play().catch(() => {});
            },

            // Prev/next follow the audio that is loaded, not the page it was started on:
            //   'playlist'      the loaded track is the current entry of a playlist -> walk it in place
            //   'surahPage'     on a surah reader                                 -> move the page
            //   'surahInPlace'  a reader-played surah, now on any other page      -> next surah, same recording, in place
            //   null            a standalone track                                -> no prev/next
            navMode() {
                if (!this.track) { return null; }
                if ((this.playlist?.length || 0) > 1 && this.playlist[this.playlistIndex]?.url === this.track.url) { return 'playlist'; }
                if (this.hasSurahNav()) { return 'surahPage'; }
                if (this.track.surah?.number && this.track.surah?.reciterId) { return 'surahInPlace'; }
                return null;
            },
            hasSurahNav() { return !!(this.surahUrlBase && this.surahMeta?.number); },
            hasPlaylistNav() { return this.navMode() === 'playlist'; },
            hasStepNav() { return this.navMode() !== null; },
            canStep(delta) {
                switch (this.navMode()) {
                    case 'playlist': {
                        const i = this.playlistIndex + delta;
                        return i >= 0 && i < this.playlist.length;
                    }
                    case 'surahPage': {
                        const n = this.surahMeta.number + delta;
                        return n >= 1 && n <= 114;
                    }
                    case 'surahInPlace': {
                        const n = this.track.surah.number + delta;
                        return n >= 1 && n <= 114;
                    }
                    default: return false;
                }
            },
            step(delta) {
                if (!this.canStep(delta)) { return; }
                switch (this.navMode()) {
                    case 'playlist': this.playlistIndex += delta; this._playListed(true); return;
                    case 'surahPage': this.gotoSurah(delta); return;
                    case 'surahInPlace': this._playSurahInPlace(this.track.surah.number + delta, this.track.surah.reciterId, delta); return;
                }
            },

            // Next/previous surah with the same recording, without leaving the page.
            // The server resolves the playable file (lowest-priority active source); a
            // surah that recording lacks is skipped in the direction of travel.
            async _playSurahInPlace(number, reciterId, delta, skips = 0) {
                if (number < 1 || number > 114 || skips > 10) { this.audio?.pause(); return; }
                const subtitle = this.track?.subtitle || '';
                let url = null;
                try {
                    const res = await fetch(`{{ url('/quran/playlists/audio') }}/${number}/${reciterId}`, { headers: { Accept: 'application/json' } });
                    url = res.ok ? (await res.json()).url : null;
                } catch (e) { url = null; }
                if (!url) { return this._playSurahInPlace(number + delta, reciterId, delta, skips + 1); }

                const names = await this._loadSurahNames();
                this._teardown();
                this.track = {
                    url,
                    title: names?.[number] || `{{ __('quran.nav.surah') }} ${number}`,
                    subtitle,
                    surah: { number, reciterId },
                    readerUrl: `{{ url('/quran/surah') }}/${number}?reciter=${reciterId}`,
                };
                this.currentTime = 0; this.duration = 0;
                this._ensureAudio();
                this._persist();
                this.audio.play().catch(() => {});
            },

            async _loadSurahNames() {
                if (this._surahNames) { return this._surahNames; }
                try {
                    const cached = JSON.parse(sessionStorage.getItem('siteSurahNames') || 'null');
                    if (cached) { return (this._surahNames = cached); }
                    const res = await fetch(`{{ route('public.quran.surahs.json') }}`, { headers: { Accept: 'application/json' } });
                    const data = res.ok ? await res.json() : null;
                    const names = {};
                    (data?.surahs || []).forEach((x) => { names[x.number] = x.nameAr; });
                    sessionStorage.setItem('siteSurahNames', JSON.stringify(names));
                    return (this._surahNames = names);
                } catch (e) { return null; }
            },

            // The flag tells the next page's boot() to resume playback with the
            // same reciter.
            gotoSurah(delta) {
                const n = (this.surahMeta?.number || 0) + delta;
                if (!this.hasSurahNav() || n < 1 || n > 114) { return; }
                this.audio?.pause();
                try {
                    sessionStorage.setItem('siteAutoplaySurah', '1');
                    // Don't let the outgoing surah's track flash back on the new page.
                    localStorage.removeItem('sitePlayerTrack');
                } catch (e) { /* private mode: playback just won't resume */ }
                window.location.href = `${this.surahUrlBase}/${n}`;
            },

            seek(pct) { if (this.audio && this.duration) { this.audio.currentTime = Math.max(0, Math.min(1, pct)) * this.duration; } },
            seekEvent(e) {
                const el = e.currentTarget;
                const rect = el.getBoundingClientRect();
                let pct = (e.clientX - rect.left) / rect.width;
                // Read the direction off the element: the bar is forced LTR, on an
                // RTL page as well.
                if (getComputedStyle(el).direction === 'rtl') { pct = 1 - pct; }
                this.seek(pct);
            },
            // Drag-scrub. Pointer capture keeps the moves coming when the cursor
            // leaves the bar, and releases the listeners on its own.
            scrubStart(e) {
                const el = e.currentTarget;
                const move = (ev) => this.seekEvent({ currentTarget: el, clientX: ev.clientX });
                const end = () => {
                    el.removeEventListener('pointermove', move);
                    el.removeEventListener('pointerup', end);
                    el.removeEventListener('pointercancel', end);
                };
                try { el.setPointerCapture(e.pointerId); } catch (err) { /* mouse fallback */ }
                el.addEventListener('pointermove', move);
                el.addEventListener('pointerup', end);
                el.addEventListener('pointercancel', end);
                move(e);
                el.focus();
            },
            cycleSpeed() {
                const steps = [1, 1.25, 1.5, 2, 0.75];
                this.rate = steps[(steps.indexOf(this.rate) + 1) % steps.length];
                if (this.audio) { this.audio.playbackRate = this.rate; }
            },
            setVolume(v) {
                this.volume = v;
                if (v > 0) { this._lastVolume = v; }
                if (this.audio) { this.audio.volume = v; }
                localStorage.setItem('sitePlayerVolume', v);
            },
            toggleMute() { this.setVolume(this.volume > 0 ? 0 : (this._lastVolume || 1)); },

            close() {
                this._teardown();
                this.track = null; this.playing = false; this.currentTime = 0; this.duration = 0;
                this.playlist = null; this.playlistIndex = 0;
                localStorage.removeItem('sitePlayerTrack');
                localStorage.removeItem('sitePlayerPlaylist');
            },

            get progress() { return this.duration ? (this.currentTime / this.duration) * 100 : 0; },
            fmt(s) {
                s = Math.floor(s || 0);
                const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
                const pad = (n) => String(n).padStart(2, '0');
                return h > 0 ? `${h}:${pad(m)}:${pad(sec)}` : `${m}:${pad(sec)}`;
            },

            _ensureAudio() {
                if (this.audio) { return; }
                const a = new Audio(this.track.url);
                a.volume = this.volume;
                a.playbackRate = this.rate;
                // Every listener checks it still owns the store: _teardown() pauses and
                // blanks the outgoing element, and its `pause` lands asynchronously —
                // after the next track's `play` — so without the guard a track change
                // shows "paused" (and hides the row equalizer) while audio is playing.
                a.addEventListener('loadedmetadata', () => { if (this.audio !== a) { return; } this.duration = a.duration || 0; });
                a.addEventListener('timeupdate', () => { if (this.audio !== a) { return; } this.currentTime = a.currentTime; this._syncTimedAyah(a.currentTime); this._persistSoon(); });
                a.addEventListener('play', () => { if (this.audio !== a) { return; } this.playing = true; });
                a.addEventListener('pause', () => { if (this.audio !== a) { return; } this.playing = false; this._persist(); });
                a.addEventListener('ended', () => {
                    if (this.audio !== a) { return; }
                    // With auto-next on, a playlist rolls into its next track and a
                    // surah page moves on to the next surah (the flag makes the next
                    // page resume with the same reciter).
                    if (this.autoNext && this.hasStepNav() && this.canStep(1)) { this.step(1); return; }
                    this.playing = false; this.currentTime = 0; this._persist();
                });
                // The identity check matters: _teardown() clears src, which fires
                // `error` on the element being discarded.
                a.addEventListener('error', () => {
                    if (this.audio !== a) { return; }
                    this.playing = false;
                });
                this.audio = a;
            },
            _teardown() { this.ayahAtTime = null; if (this.audio) { this.audio.pause(); this.audio.src = ''; this.audio = null; } },
            _persist() {
                if (this.track) { localStorage.setItem('sitePlayerTrack', JSON.stringify({ ...this.track, currentTime: this.currentTime })); }
                if (this.playlist) { localStorage.setItem('sitePlayerPlaylist', JSON.stringify({ list: this.playlist, index: this.playlistIndex })); }
                else { localStorage.removeItem('sitePlayerPlaylist'); }
            },
            _persistSoon() { if (this._saveTimer) { return; } this._saveTimer = setTimeout(() => { this._saveTimer = null; this._persist(); }, 2000); },
        });
    });
</script>
