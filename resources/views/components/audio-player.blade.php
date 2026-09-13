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
                                                picker, per-ayah highlighting from
                                                the mp3quran timings, and the
                                                verse-by-verse audio toggle
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
                    <p class="text-[11px] truncate text-gray-500 dark:text-gray-400" x-text="$store.audioPlayer.track?.subtitle"></p>
                </div>
            </div>

            {{-- Transport --}}
            <div class="flex items-center gap-0.5 sm:gap-1 flex-shrink-0" dir="ltr">
                {{-- Previous: the previous surah's page, or the previous playlist track --}}
                <button x-show="$store.audioPlayer.hasStepNav()"
                        @click="$store.audioPlayer.step(-1)"
                        :disabled="!$store.audioPlayer.canStep(-1)"
                        class="{{ $iconBtn }}"
                        :title="$store.audioPlayer.hasSurahNav() ? '{{ __('quran.public.previous_surah') }}' : '{{ __('quran.public.previous_track') }}'"
                        :aria-label="$store.audioPlayer.hasSurahNav() ? '{{ __('quran.public.previous_surah') }}' : '{{ __('quran.public.previous_track') }}'">
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
                <button x-show="$store.audioPlayer.hasStepNav()"
                        @click="$store.audioPlayer.step(1)"
                        :disabled="!$store.audioPlayer.canStep(1)"
                        class="{{ $iconBtn }}"
                        :title="$store.audioPlayer.hasSurahNav() ? '{{ __('quran.public.next_surah') }}' : '{{ __('quran.public.next_track') }}'"
                        :aria-label="$store.audioPlayer.hasSurahNav() ? '{{ __('quran.public.next_surah') }}' : '{{ __('quran.public.next_track') }}'">
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
                {{-- Verse-by-verse audio: plays the surah as chained per-verse files
                     instead of the one whole-surah recording, so every ayah ends in a
                     pause. It used to be the only way the page could know which ayah
                     was being recited; mp3quran timings now do that on the single
                     file, so this is a pacing choice (and still the only mode for the
                     reciters with no timings). Offered wherever a verse queue is
                     possible: a surah page, reciter with per-verse files. --}}
                {{-- ms-*: the options cluster butts up against the transport, and the
                     start side is the facing one in both LTR and RTL. --}}
                <button x-show="$store.audioPlayer.surahMeta && $store.audioPlayer.currentReciter()?.verse_audio_template"
                        @click="$store.audioPlayer.toggleFollow()"
                        class="{{ $iconBtnBase }} ms-2 sm:ms-3"
                        :class="$store.audioPlayer.follow
                            ? 'text-white bg-gradient-to-r from-[#0056b3] to-[#218838]'
                            : '{{ $iconBtnTheme }}'"
                        :aria-pressed="$store.audioPlayer.follow"
                        title="{{ __('quran.public.follow_verses') }}" aria-label="{{ __('quran.public.follow_verses') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h9M4 12h9M4 18h6"/><circle cx="18" cy="16" r="3.2"/><path stroke-linecap="round" d="m20.4 18.4 1.6 1.6"/></svg>
                </button>

                {{-- Playlist mode: the reader (verse following, reciter switching) is one tap away --}}
                <a x-show="$store.audioPlayer.playlist && $store.audioPlayer.track?.readerUrl" x-cloak
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

                {{-- Reciter picker (only when reciters are configured) --}}
                <div class="relative flex-shrink-0" x-show="$store.audioPlayer.reciters.length > 0" @click.outside="$store.audioPlayer.reciterOpen = false">
                    <button @click="$store.audioPlayer.reciterOpen = !$store.audioPlayer.reciterOpen"
                            class="flex items-center gap-1.5 px-2 sm:px-2.5 h-8 rounded-lg text-xs font-semibold transition-colors max-w-[8rem] sm:max-w-[10rem] text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
                            title="{{ __('quran.public.choose_reciter') }}" aria-haspopup="listbox" :aria-expanded="$store.audioPlayer.reciterOpen">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14a4 4 0 004-4V6a4 4 0 10-8 0v4a4 4 0 004 4zm6-4a6 6 0 01-12 0m6 6v3"/></svg>
                        <span class="truncate hidden sm:inline" x-text="$store.audioPlayer.reciterName()"></span>
                    </button>
                    <div x-show="$store.audioPlayer.reciterOpen" x-cloak x-transition.opacity
                         class="absolute bottom-full end-0 mb-2 w-56 max-h-72 overflow-y-auto rounded-xl border shadow-2xl py-1 {{ $surface }} border-gray-200 dark:border-gray-700" role="listbox">
                        <label class="block p-2 text-sm">{{ __('quran.public.reciter') }}
                            <select class="w-full rounded border-gray-300 dark:bg-gray-800" :value="$store.audioPlayer.currentReciter()?.profileId || $store.audioPlayer.reciterId" @change="$store.audioPlayer.selectPerson(Number($event.target.value))">
                                <template x-for="person in $store.audioPlayer.personOptions()" :key="person.id"><option :value="person.id" x-text="person.name"></option></template>
                            </select>
                        </label>
                        <label class="block p-2 text-sm" x-show="$store.audioPlayer.recordingOptions().length > 1">{{ __('quran.recordings.choose') }}
                            <select class="w-full rounded border-gray-300 dark:bg-gray-800" :value="$store.audioPlayer.reciterId" @change="$store.audioPlayer.selectReciter(Number($event.target.value))">
                                <template x-for="r in $store.audioPlayer.recordingOptions()" :key="r.id"><option :value="r.id" x-text="r.recordingLabel || r.nameAr || r.nameEn"></option></template>
                            </select>
                        </label>
                    </div>
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

            // Reciter switching (configured per surah page)
            reciters: [],           // [{ id, nameAr, nameEn, surahAudioUrl, verse_audio_template }]
            reciterId: null,
            surahMeta: null,        // { nameAr, number, versesCount }
            surahUrlBase: null,     // e.g. https://site/quran/surah — enables surah transport
            reciterOpen: false,
            // Follow-along: play the surah as chained per-verse files even when a
            // whole-surah recording exists, so the page can highlight the ayah
            // being recited. Off by default — the single file is gapless.
            follow: localStorage.getItem('siteFollowVerses') === '1',

            // Verse-chaining fallback. Only a fraction of reciters have a
            // whole-surah recording; the rest play a surah by queueing their
            // per-verse files. Non-null queue means we are in that mode.
            queue: null,            // [url, ...] indexed by ayah - 1
            queueIndex: 0,
            _preload: null,

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
                        this.track = { url: saved.url, title: saved.title, subtitle: saved.subtitle, readerUrl: saved.readerUrl || null };
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
                // A queue belongs to the surah that built it, and so do timings.
                this.queue = null; this.queueIndex = 0; this._preload = null;
                this.timings = null; this.ayahAtTime = null; this._timingsReq++;
                this.playlist = null; this.playlistIndex = 0;
                const stored = parseInt(localStorage.getItem('siteReciterId') ?? '', 10);
                const valid = (id) => this.reciters.some((r) => r.id === id);
                // `force` (a playlist hand-off via ?reciter=) wins over the stored
                // choice for this page only; the stored preference is not touched.
                this.reciterId = (force && valid(reciterId)) ? reciterId
                    : (valid(stored) ? stored
                        : (valid(reciterId) ? reciterId : (this.reciters[0]?.id ?? null)));
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
                this.queue = null; this.queueIndex = 0; this._preload = null;
                if (this.track?.url === list[this.playlistIndex].url) { this.toggle(); return; }
                this._playListed(true);
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

            personOptions() {
                return [...new Map(this.reciters.map(r => [r.profileId || r.id, { id: r.profileId || r.id, name: r.profileName || r.nameAr || r.nameEn }])).values()];
            },
            recordingOptions() {
                const current = this.currentReciter();
                return this.reciters.filter(r => (r.profileId || r.id) === (current?.profileId || current?.id));
            },
            selectPerson(id) {
                const choices = this.reciters.filter(r => (r.profileId || r.id) === id);
                const chosen = choices.find(r => r.isDefault) || choices[0];
                if (chosen) { this.selectReciter(chosen.id); }
            },
            currentReciter() { return this.reciters.find((r) => r.id === this.reciterId) || null; },
            reciterName() { const r = this.currentReciter(); return r ? (r.nameAr || r.nameEn || '') : ''; },
            surahUrl() { const r = this.currentReciter(); return r ? r.surahAudioUrl : null; },

            // everyayah lays per-verse audio out as {folder}/{SSS}{AAA}.mp3.
            _verseQueue(reciter) {
                const surah = this.surahMeta;
                if (!reciter?.verse_audio_template || !surah?.number || !surah?.versesCount) { return null; }
                const s = String(surah.number).padStart(3, '0');
                return Array.from({ length: surah.versesCount }, (_, i) =>
                    reciter.verse_audio_template.replace('{surah}', s).replace('{ayah}', String(i + 1).padStart(3, '0')));
            },

            // Subtitle carries the ayah position, since in queue mode the progress
            // bar only ever describes the current verse.
            _subtitle() {
                if (!this.queue) { return this.reciterName(); }
                return `${this.reciterName()} · {{ __('quran.public.verse_label') }} ${this.queueIndex + 1}/${this.queue.length}`;
            },

            // The ayah being recited, 1-based. Three sources, in order:
            //   queue mode          -> the queued ayah, known by construction
            //   whole file + timings-> the playhead, resolved in _syncTimedAyah
            //   neither             -> null
            // null means "highlight nothing", and so does ayah 0 (the bismillah);
            // syncPlayingVerse() on the surah page treats any falsy value that way.
            get playingAyah() {
                if (this.queue) { return this.queueIndex + 1; }

                return this.ayahAtTime || null;
            },

            // Queue mode is forced by follow-along, and is the only option for a
            // reciter with no whole-surah recording.
            _preferQueue() { return this.follow && !!this.currentReciter()?.verse_audio_template; },

            // Load the whole-surah file and start at at(duration), once the
            // duration is actually known.
            _playWhole(url, at, autoplay) {
                this.queue = null;
                this._teardown();
                this.track = { url, title: this.surahMeta?.nameAr || this.track?.title || '', subtitle: this.reciterName() };
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
                // Timings describe mp3quran's own whole-surah audio. No recording,
                // nothing to time — and the per-verse queue never needs them.
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
                const n = this.queue ? null : this._ayahAtMs(seconds * 1000);
                if (n !== this.ayahAtTime) { this.ayahAtTime = n; }
            },

            // Cumulative share of the surah's letters at the end of each ayah.
            // Recitation time tracks letters, not ayah count — ayah 282 of
            // al-Baqarah is not one 286th of the recording. This is the only way
            // to carry a listener's place between a whole-surah file (no verse
            // timings anywhere in this project) and the per-verse queue, so it is
            // an estimate; it always lands on an ayah boundary, so the error stops
            // there instead of compounding.
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
            // is only for the reciters mp3quran publishes none for. Ratios are
            // taken against the timings' own total, not audio.duration — the file
            // loaded at the moment of a mode switch is one ayah, not the surah.
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

            toggleFollow() {
                this.follow = !this.follow;
                localStorage.setItem('siteFollowVerses', this.follow ? '1' : '0');
                if (!this.track || !this.surahMeta) { return; }

                const wasPlaying = this.playing;

                if (this.follow) {
                    if (this.queue) { return; }   // already chaining verses
                    const queue = this._verseQueue(this.currentReciter());
                    if (!queue) { return; }       // no per-verse audio: nothing to switch to
                    // Resume at the ayah the current offset falls in, from its start.
                    const ratio = this.duration ? this.currentTime / this.duration : 0;
                    this.queue = queue;
                    this.queueIndex = Math.min((this._ayahAt(ratio) || 1) - 1, queue.length - 1);
                    this._playQueued(wasPlaying);

                    return;
                }

                const url = this.surahUrl();
                if (!url || !this.queue) { return; }   // this reciter only has verses
                const ratio = this._ratioAt(this.queueIndex + 1);
                this._playWhole(url, (duration) => ratio * duration, wasPlaying);
            },

            // Play the configured surah with the selected reciter (rail Play button).
            // Prefers the single whole-surah file, unless the reader is following
            // along; falls back to chaining verses either way.
            playSurah(restart = false) {
                const url = this._preferQueue() ? null : this.surahUrl();
                if (url) {
                    this.queue = null;
                    this._loadTimings();
                    if (restart && this.track?.url === url) { this.restart(); return; }
                    this.play({ url, title: this.surahMeta?.nameAr || '', subtitle: this.reciterName() });
                    return;
                }

                const queue = this._verseQueue(this.currentReciter());
                if (!queue) {
                    // Follow-along asked for verses this reciter can't supply.
                    const whole = this.surahUrl();
                    if (whole) { this.queue = null; this.play({ url: whole, title: this.surahMeta?.nameAr || '', subtitle: this.reciterName() }); return; }
                    window.toast?.('{{ __('quran.public.audio_unavailable') }}', 'error');

                    return;
                }

                this.queue = queue;
                this.queueIndex = 0;
                this._playQueued(true);
            },

            // Start the surah at one ayah — the per-verse play buttons in verse view
            // and "listen from here" in reading view both land here. With timings the
            // whole-surah recording can start mid-surah on its own, so seek and keep
            // the single gapless file; only a reciter without them needs the queue.
            playAyah(number) {
                const whole = this._preferQueue() ? null : this.surahUrl();
                if (!whole) { this._playAyahQueued(number); return; }

                // Returned so a caller can await the seek; nothing in the app does.
                return this._loadTimings().then(() => {
                    const ms = this._ayahStartMs(number);
                    if (ms === null) { this._playAyahQueued(number); return; }

                    if (!this.queue && this.track?.url === whole && this.audio) {
                        // Re-tapping the ayah already under the playhead toggles it,
                        // same as the queue path — the card shows a pause icon.
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

            // Chained per-verse files: the only way to start mid-surah for a reciter
            // with no timings, and what follow-along asks for explicitly.
            _playAyahQueued(number) {
                const queue = this.queue || this._verseQueue(this.currentReciter());
                if (!queue) { window.toast?.('{{ __('quran.public.audio_unavailable') }}', 'error'); return; }

                const idx = Math.max(0, Math.min(number - 1, queue.length - 1));
                // Re-tapping the ayah that's already loaded toggles it.
                if (this.queue && this.queueIndex === idx && this.track) { this.toggle(); return; }
                this.queue = queue;
                this.queueIndex = idx;
                this._playQueued(true);
            },

            // Load the queued ayah. autoplay=false is used when only re-pointing
            // the source (e.g. a reciter switch while paused).
            _playQueued(autoplay) {
                this._teardown();
                this.track = { url: this.queue[this.queueIndex], title: this.surahMeta?.nameAr || '', subtitle: this._subtitle() };
                this.currentTime = 0; this.duration = 0;
                this._ensureAudio();
                this._persist();
                if (autoplay) { this.audio.play().catch(() => {}); }
                this._preloadNext();
            },

            // Warm the next ayah so the gap between verses stays short.
            _preloadNext() {
                const next = this.queue?.[this.queueIndex + 1];
                if (!next) { this._preload = null; return; }
                const a = new Audio();
                a.preload = 'auto';
                a.src = next;
                this._preload = a;
            },

            // Advance within the queue. Returns false at the end of the surah.
            _advance(autoplay = true) {
                if (!this.queue || this.queueIndex >= this.queue.length - 1) { return false; }
                this.queueIndex++;
                this._playQueued(autoplay);
                return true;
            },

            // Switch reciter and resume the same surah. In queue mode that means
            // the same ayah — a wall-clock offset does not carry across reciters.
            selectReciter(id) {
                this.reciterId = id;
                this.reciterOpen = false;
                localStorage.setItem('siteReciterId', String(id));
                // Timings belong to one recording, so the switch invalidates them
                // whether or not anything is playing right now.
                this._loadTimings();
                if (!this.track) { return; }

                const wasPlaying = this.playing;
                const url = this._preferQueue() ? null : this.surahUrl();

                if (!url) {
                    const queue = this._verseQueue(this.currentReciter());
                    if (!queue) { window.toast?.('{{ __('quran.public.audio_unavailable') }}', 'error'); return; }
                    // Keep the listener's place in the surah across the switch.
                    this.queueIndex = Math.min(this.queue ? this.queueIndex : 0, queue.length - 1);
                    this.queue = queue;
                    this._playQueued(wasPlaying);
                    return;
                }

                const resumeAt = this.queue ? 0 : this.currentTime;
                this._playWhole(url, () => resumeAt, wasPlaying);
            },

            // Play a standalone track ({url,title,subtitle}); re-clicking the same
            // url toggles. Drops any playlist — a bare track is not part of a list.
            play(trackData) {
                if (this.track && this.track.url === trackData.url) { this.toggle(); return; }
                this.playlist = null; this.playlistIndex = 0;
                this._teardown();
                this.track = { url: trackData.url, title: trackData.title || '', subtitle: trackData.subtitle || '' };
                this.currentTime = 0; this.duration = 0;
                this._ensureAudio();
                this._persist();
                this.audio.play().catch(() => {});
            },

            toggle() {
                if (!this.audio) { return; }
                if (this.playing) { this.audio.pause(); } else { this.audio.play().catch(() => {}); }
            },

            // Skip by ±seconds. In queue mode a nudge past either edge of the
            // current ayah steps to the neighbouring one, so the surah — not the
            // single verse file — behaves like one continuous track.
            nudge(sec) {
                if (!this.audio) { return; }
                // Read the element, not the mirrored currentTime: `timeupdate`
                // fires ~4x a second, so two quick taps off the mirror would both
                // compute from the same stale base and land one step, not two.
                const t = this.audio.currentTime + sec;
                if (t < 0 && this.queue && this.queueIndex > 0) {
                    this.queueIndex--;
                    this._playQueued(this.playing);
                    return;
                }
                const end = this.audio.duration || this.duration || 0;
                if (end && t > end && this.queue && this._advance(this.playing)) { return; }
                const at = Math.max(0, end ? Math.min(t, end) : Math.max(t, 0));
                this.audio.currentTime = at;
                this.currentTime = at;
            },

            // Restart the surah from its very beginning (ayah 1 in queue mode).
            restart() {
                if (this.queue) { this.queueIndex = 0; this._playQueued(true); return; }
                if (!this.audio) { return; }
                this.audio.currentTime = 0;
                this.currentTime = 0;
                this.audio.play().catch(() => {});
            },

            // Prev/next. Surah context wins — the player bar lives on the surah
            // page, so moving a surah means moving the page too. Otherwise a
            // playlist walks in place. Neither: the buttons are hidden.
            hasSurahNav() { return !!(this.surahUrlBase && this.surahMeta?.number); },
            hasPlaylistNav() { return (this.playlist?.length || 0) > 1; },
            hasStepNav() { return this.hasSurahNav() || this.hasPlaylistNav(); },
            canStep(delta) {
                if (this.hasSurahNav()) {
                    const n = (this.surahMeta?.number || 0) + delta;
                    return n >= 1 && n <= 114;
                }
                const i = this.playlistIndex + delta;
                return this.hasPlaylistNav() && i >= 0 && i < this.playlist.length;
            },
            step(delta) {
                if (!this.canStep(delta)) { return; }
                if (this.hasSurahNav()) { this.gotoSurah(delta); return; }
                this.playlistIndex += delta;
                this._playListed(true);
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
                this.queue = null; this.queueIndex = 0; this._preload = null;
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
                a.addEventListener('loadedmetadata', () => { this.duration = a.duration || 0; });
                a.addEventListener('timeupdate', () => { this.currentTime = a.currentTime; this._syncTimedAyah(a.currentTime); this._persistSoon(); });
                a.addEventListener('play', () => { this.playing = true; });
                a.addEventListener('pause', () => { this.playing = false; this._persist(); });
                a.addEventListener('ended', () => {
                    if (this.audio !== a) { return; }
                    // In queue mode the surah continues into the next ayah; in a
                    // playlist it rolls into the next track. A surah page does NOT
                    // auto-navigate — that would move the page under the listener.
                    if (this._advance()) { return; }
                    if (this.hasPlaylistNav() && this.canStep(1)) { this.step(1); return; }
                    this.playing = false; this.currentTime = 0; this._persist();
                });
                // everyayah is missing the odd ayah file for some reciters; skip
                // past it rather than stalling the surah on a dead request.
                // The identity check matters: _teardown() clears src, which fires
                // `error` on the element being discarded — without it, advancing
                // one ayah tears down the old audio and cascades through the surah.
                a.addEventListener('error', () => {
                    if (this.audio !== a) { return; }
                    if (!this._advance()) { this.playing = false; }
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
