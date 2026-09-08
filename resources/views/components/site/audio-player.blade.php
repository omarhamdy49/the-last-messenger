{{--
    Shared public audio player — a persistent bottom bar driven by the global
    `audioPlayer` Alpine store. The Quran reader's floating-rail Play button and
    per-verse play buttons both route through this one surface (no second player).

    Extends the admin player pattern with reciter switching: a surah page calls
    $store.audioPlayer.configure({reciters, reciterId, surah, urlBase}); the bar
    then exposes a reciter picker that re-resolves the audio URL and resumes from
    the same time, plus surah-to-surah transport that navigates the page itself.

    Layout is two rows: the transport/options row over a full-width scrub bar. The
    transport cluster and the scrub bar are forced dir="ltr" so ⏮/⏭ and the
    progress fill keep their universal meaning on RTL pages.

    Theme follows the public `$store.theme.dark`. Hidden until a track is loaded.
--}}
@php
    $iconBtn = 'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 transition-colors disabled:opacity-25 disabled:pointer-events-none';
    $iconBtnTheme = "\$store.theme.dark ? 'text-gray-300 hover:bg-gray-800 hover:text-white' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'";
@endphp
<div x-data x-show="$store.audioPlayer.track" x-cloak
     x-effect="document.body.classList.toggle('site-has-audio', !!$store.audioPlayer.track)"
     class="fixed bottom-0 inset-x-0 z-50 border-t shadow-2xl"
     :class="$store.theme.dark ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'"
     role="region" aria-label="{{ __('quran.public.listen') }}">
    <div class="mx-auto max-w-5xl px-3 sm:px-4 py-2">

        <div class="flex items-center gap-2 sm:gap-3">

            {{-- Track info --}}
            <div class="flex items-center gap-2.5 flex-1 min-w-0">
                <div class="hidden sm:flex w-9 h-9 rounded-lg bg-gradient-to-br from-[#0056b3] to-[#218838] items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M18 3a1 1 0 00-1.196-.98l-8 1.6A1 1 0 008 4.6v7.114A3.5 3.5 0 1010 14.5V8.82l6-1.2v3.894A3.5 3.5 0 1018 14V3z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold truncate font-['Amiri']" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'" x-text="$store.audioPlayer.track?.title"></p>
                    <p class="text-[11px] truncate" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'" x-text="$store.audioPlayer.track?.subtitle"></p>
                </div>
            </div>

            {{-- Transport --}}
            <div class="flex items-center gap-0.5 sm:gap-1 flex-shrink-0" dir="ltr">
                {{-- Previous surah: loads that surah's page and keeps playing --}}
                <button x-show="$store.audioPlayer.hasSurahNav()"
                        @click="$store.audioPlayer.gotoSurah(-1)"
                        :disabled="$store.audioPlayer.surahMeta?.number <= 1"
                        class="{{ $iconBtn }}" :class="{{ $iconBtnTheme }}"
                        title="{{ __('quran.public.previous_surah') }}" aria-label="{{ __('quran.public.previous_surah') }}">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M7 5h2v14H7zM19 5v14l-9.5-7z"/></svg>
                </button>

                {{-- Back 5s --}}
                <button @click="$store.audioPlayer.nudge(-5)" class="{{ $iconBtn }}" :class="{{ $iconBtnTheme }}"
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
                <button @click="$store.audioPlayer.nudge(5)" class="{{ $iconBtn }}" :class="{{ $iconBtnTheme }}"
                        title="{{ __('quran.public.forward_5') }} (→)" aria-label="{{ __('quran.public.forward_5') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" d="M20 12a8 8 0 1 1-8-8"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.6 1.7 12 4 9.6 6.3"/>
                        <text x="12" y="16.2" text-anchor="middle" font-size="9" font-weight="700" fill="currentColor" stroke="none" font-family="ui-sans-serif, system-ui, sans-serif">5</text>
                    </svg>
                </button>

                {{-- Next surah --}}
                <button x-show="$store.audioPlayer.hasSurahNav()"
                        @click="$store.audioPlayer.gotoSurah(1)"
                        :disabled="$store.audioPlayer.surahMeta?.number >= 114"
                        class="{{ $iconBtn }}" :class="{{ $iconBtnTheme }}"
                        title="{{ __('quran.public.next_surah') }}" aria-label="{{ __('quran.public.next_surah') }}">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M15 5h2v14h-2zM5 5v14l9.5-7z"/></svg>
                </button>

                <span class="w-px h-5 mx-0.5 sm:mx-1 flex-shrink-0" :class="$store.theme.dark ? 'bg-gray-700' : 'bg-gray-200'" aria-hidden="true"></span>

                {{-- Play from the beginning --}}
                <button @click="$store.audioPlayer.restart()" class="{{ $iconBtn }}" :class="{{ $iconBtnTheme }}"
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
                {{-- Follow along: chains per-verse audio so the page can highlight
                     the ayah being recited. Only offered where a verse queue is
                     possible (a surah page, reciter with per-verse files). --}}
                {{-- ms-*: the options cluster butts up against the transport, and the
                     start side is the facing one in both LTR and RTL. --}}
                <button x-show="$store.audioPlayer.surahMeta && $store.audioPlayer.currentReciter()?.everyayah_folder"
                        @click="$store.audioPlayer.toggleFollow()"
                        class="{{ $iconBtn }} ms-2 sm:ms-3"
                        :class="$store.audioPlayer.follow
                            ? 'text-white bg-gradient-to-r from-[#0056b3] to-[#218838]'
                            : ({{ $iconBtnTheme }})"
                        :aria-pressed="$store.audioPlayer.follow"
                        title="{{ __('quran.public.follow_verses') }}" aria-label="{{ __('quran.public.follow_verses') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h9M4 12h9M4 18h6"/><circle cx="18" cy="16" r="3.2"/><path stroke-linecap="round" d="m20.4 18.4 1.6 1.6"/></svg>
                </button>

                <button @click="$store.audioPlayer.cycleSpeed()" class="px-2 h-8 rounded-lg text-xs font-bold tabular-nums transition-colors flex-shrink-0"
                        :class="$store.theme.dark ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-100'"
                        x-text="$store.audioPlayer.rate + '×'" title="{{ __('quran.public.playback_speed') }}"
                        aria-label="{{ __('quran.public.playback_speed') }}">1×</button>

                {{-- Volume: phones have hardware keys, so the slider is desktop-only --}}
                <div class="hidden md:flex items-center gap-1.5 flex-shrink-0">
                    <button @click="$store.audioPlayer.toggleMute()" class="{{ $iconBtn }}" :class="{{ $iconBtnTheme }}"
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
                            class="flex items-center gap-1.5 px-2 sm:px-2.5 h-8 rounded-lg text-xs font-semibold transition-colors max-w-[8rem] sm:max-w-[10rem]"
                            :class="$store.theme.dark ? 'text-gray-200 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-100'"
                            title="{{ __('quran.public.choose_reciter') }}" aria-haspopup="listbox" :aria-expanded="$store.audioPlayer.reciterOpen">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14a4 4 0 004-4V6a4 4 0 10-8 0v4a4 4 0 004 4zm6-4a6 6 0 01-12 0m6 6v3"/></svg>
                        <span class="truncate hidden sm:inline" x-text="$store.audioPlayer.reciterName()"></span>
                    </button>
                    <div x-show="$store.audioPlayer.reciterOpen" x-cloak x-transition.opacity
                         class="absolute bottom-full end-0 mb-2 w-56 max-h-72 overflow-y-auto rounded-xl border shadow-2xl py-1"
                         :class="$store.theme.dark ? 'bg-gray-900 border-gray-700' : 'bg-white border-gray-200'" role="listbox">
                        <template x-for="r in $store.audioPlayer.reciters" :key="r.id">
                            <button @click="$store.audioPlayer.selectReciter(r.id)" role="option"
                                    class="w-full text-start px-3 py-2 text-sm transition-colors flex items-center justify-between gap-2"
                                    :class="r.id === $store.audioPlayer.reciterId
                                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                        : ($store.theme.dark ? 'text-gray-200 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-100')">
                                <span class="truncate font-['Amiri']" x-text="r.nameAr || r.nameEn"></span>
                                <svg x-show="r.id === $store.audioPlayer.reciterId" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                </div>

                <button @click="$store.audioPlayer.close()" class="{{ $iconBtn }}" :class="{{ $iconBtnTheme }}"
                        aria-label="{{ __('quran.public.close_player') }}" title="{{ __('quran.public.close_player') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        {{-- Scrub bar (under the transport): click, drag or arrow-key. Own row so it stays grabbable on phones. --}}
        <div class="mt-1 flex items-center gap-2" dir="ltr">
            <span class="text-[11px] tabular-nums w-10 text-right flex-shrink-0"
                  :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'"
                  x-text="$store.audioPlayer.fmt($store.audioPlayer.currentTime)">0:00</span>

            <div class="group flex-1 py-1.5 cursor-pointer touch-none focus:outline-none"
                 @pointerdown.prevent="$store.audioPlayer.scrubStart($event)"
                 @keydown.arrow-right.prevent="$store.audioPlayer.nudge(5)"
                 @keydown.arrow-left.prevent="$store.audioPlayer.nudge(-5)"
                 tabindex="0" role="slider"
                 :aria-valuenow="Math.round($store.audioPlayer.progress)" aria-valuemin="0" aria-valuemax="100"
                 aria-label="{{ __('quran.public.seek') }}">
                <div class="h-1.5 rounded-full transition-[height] group-hover:h-2 group-focus:h-2"
                     :class="$store.theme.dark ? 'bg-gray-700' : 'bg-gray-200'">
                    <div class="relative h-full rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838]"
                         :style="`width: ${$store.audioPlayer.progress}%`">
                        <span class="absolute -right-1.5 top-1/2 -translate-y-1/2 w-3 h-3 rounded-full bg-white shadow ring-1 ring-black/10 opacity-0 transition-opacity group-hover:opacity-100 group-focus:opacity-100"></span>
                    </div>
                </div>
            </div>

            <span class="text-[11px] tabular-nums w-10 flex-shrink-0"
                  :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'"
                  x-text="$store.audioPlayer.fmt($store.audioPlayer.duration)">0:00</span>
        </div>

    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
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

            // Reciter switching (configured per surah page)
            reciters: [],           // [{ id, nameAr, nameEn, surahAudioUrl, everyayah_folder }]
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

            init() {
                try {
                    const saved = JSON.parse(localStorage.getItem('sitePlayerTrack') || 'null');
                    if (saved && saved.url) {
                        this.track = { url: saved.url, title: saved.title, subtitle: saved.subtitle };
                        this._ensureAudio();
                        this.audio.currentTime = saved.currentTime || 0;
                        this.currentTime = saved.currentTime || 0;
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
            configure({ reciters, reciterId, surah, urlBase }) {
                this.reciters = Array.isArray(reciters) ? reciters : [];
                this.surahMeta = surah || null;
                this.surahUrlBase = urlBase || null;
                // A queue belongs to the surah that built it.
                this.queue = null; this.queueIndex = 0; this._preload = null;
                const stored = parseInt(localStorage.getItem('siteReciterId') ?? '', 10);
                const valid = (id) => this.reciters.some((r) => r.id === id);
                this.reciterId = valid(stored) ? stored
                    : (valid(reciterId) ? reciterId : (this.reciters[0]?.id ?? null));
            },

            currentReciter() { return this.reciters.find((r) => r.id === this.reciterId) || null; },
            reciterName() { const r = this.currentReciter(); return r ? (r.nameAr || r.nameEn || '') : ''; },
            surahUrl() { const r = this.currentReciter(); return r ? r.surahAudioUrl : null; },

            // everyayah lays per-verse audio out as {folder}/{SSS}{AAA}.mp3.
            _verseQueue(reciter) {
                const surah = this.surahMeta;
                if (!reciter?.everyayah_folder || !surah?.number || !surah?.versesCount) { return null; }
                const s = String(surah.number).padStart(3, '0');
                return Array.from({ length: surah.versesCount }, (_, i) =>
                    `https://everyayah.com/data/${reciter.everyayah_folder}/${s}${String(i + 1).padStart(3, '0')}.mp3`);
            },

            // Subtitle carries the ayah position, since in queue mode the progress
            // bar only ever describes the current verse.
            _subtitle() {
                if (!this.queue) { return this.reciterName(); }
                return `${this.reciterName()} · {{ __('quran.public.verse_label') }} ${this.queueIndex + 1}/${this.queue.length}`;
            },

            // The ayah being recited, 1-based — only knowable in queue mode, since
            // a whole-surah file carries no verse timings. null = not following.
            get playingAyah() { return this.queue ? this.queueIndex + 1 : null; },

            // Queue mode is forced by follow-along, and is the only option for a
            // reciter with no whole-surah recording.
            _preferQueue() { return this.follow && !!this.currentReciter()?.everyayah_folder; },

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
            // Which ayah a fraction of the recording falls in, and where an ayah starts.
            _ayahAt(ratio) {
                const shares = this._ayahShares();
                if (!shares) { return null; }
                const i = shares.findIndex((share) => share >= ratio);

                return i === -1 ? shares.length : i + 1;
            },
            _ratioAt(ayah) {
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

            // Start the surah at one ayah (the per-verse play buttons on the page).
            // Always queue mode — it's the only mode that can start mid-surah.
            playAyah(number) {
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

            // Play a track ({url,title,subtitle}); re-clicking the same url toggles.
            play(trackData) {
                if (this.track && this.track.url === trackData.url) { this.toggle(); return; }
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

            // Surah transport. The player bar lives on the surah page, so moving a
            // surah means moving the page too; the flag tells the next page's
            // boot() to resume playback with the same reciter.
            hasSurahNav() { return !!(this.surahUrlBase && this.surahMeta?.number); },
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
                localStorage.removeItem('sitePlayerTrack');
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
                a.addEventListener('timeupdate', () => { this.currentTime = a.currentTime; this._persistSoon(); });
                a.addEventListener('play', () => { this.playing = true; });
                a.addEventListener('pause', () => { this.playing = false; this._persist(); });
                a.addEventListener('ended', () => {
                    if (this.audio !== a) { return; }
                    // In queue mode the surah continues into the next ayah.
                    if (this._advance()) { return; }
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
            _teardown() { if (this.audio) { this.audio.pause(); this.audio.src = ''; this.audio = null; } },
            _persist() { if (this.track) { localStorage.setItem('sitePlayerTrack', JSON.stringify({ ...this.track, currentTime: this.currentTime })); } },
            _persistSoon() { if (this._saveTimer) { return; } this._saveTimer = setTimeout(() => { this._saveTimer = null; this._persist(); }, 2000); },
        });
    });
</script>
