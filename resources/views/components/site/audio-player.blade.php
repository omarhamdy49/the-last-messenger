{{--
    Shared public audio player — a persistent bottom bar driven by the global
    `audioPlayer` Alpine store. The Quran reader's floating-rail Play button and
    per-verse play buttons both route through this one surface (no second player).

    Extends the admin player pattern with reciter switching: a surah page calls
    $store.audioPlayer.configure({reciters, reciterId, surah}); the bar then exposes
    a reciter picker that re-resolves the audio URL and resumes from the same time.

    Theme follows the public `$store.theme.dark`. Hidden until a track is loaded.
--}}
<div x-data x-show="$store.audioPlayer.track" x-cloak
     x-effect="document.body.classList.toggle('site-has-audio', !!$store.audioPlayer.track)"
     class="fixed bottom-0 inset-x-0 z-50 border-t shadow-2xl"
     :class="$store.theme.dark ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'"
     role="region" aria-label="{{ __('quran.public.listen') }}">
    <div class="mx-auto max-w-5xl px-3 sm:px-4 py-2.5 flex items-center gap-3 sm:gap-4">

        {{-- Track info --}}
        <div class="flex items-center gap-3 w-32 sm:w-52 flex-shrink-0 min-w-0">
            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M18 3a1 1 0 00-1.196-.98l-8 1.6A1 1 0 008 4.6v7.114A3.5 3.5 0 1010 14.5V8.82l6-1.2v3.894A3.5 3.5 0 1018 14V3z"/></svg>
            </div>
            <div class="min-w-0 hidden sm:block">
                <p class="text-sm font-semibold truncate font-['Amiri']" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'" x-text="$store.audioPlayer.track?.title"></p>
                <p class="text-xs truncate" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'" x-text="$store.audioPlayer.track?.subtitle"></p>
            </div>
        </div>

        {{-- Play/pause + seek --}}
        <div class="flex-1 flex items-center gap-2 sm:gap-3 min-w-0">
            <button @click="$store.audioPlayer.toggle()"
                    class="w-9 h-9 rounded-full flex items-center justify-center text-white bg-gradient-to-r from-[#0056b3] to-[#218838] flex-shrink-0 hover:scale-105 active:scale-95 transition-transform"
                    :aria-label="$store.audioPlayer.playing ? '{{ __('quran.public.pause') }}' : '{{ __('quran.public.play') }}'">
                <svg x-show="!$store.audioPlayer.playing" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.84A1 1 0 004 3.7v12.6a1 1 0 001.5.86l11-6.3a1 1 0 000-1.72l-11-6.3z"/></svg>
                <svg x-show="$store.audioPlayer.playing" x-cloak class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 4a1 1 0 011 1v10a1 1 0 11-2 0V5a1 1 0 011-1zm7 0a1 1 0 011 1v10a1 1 0 11-2 0V5a1 1 0 011-1z"/></svg>
            </button>
            <span class="text-xs tabular-nums flex-shrink-0 whitespace-nowrap" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'" x-text="$store.audioPlayer.fmt($store.audioPlayer.currentTime)">0:00</span>
            <div class="flex-1 h-1.5 rounded-full cursor-pointer min-w-[60px]" :class="$store.theme.dark ? 'bg-gray-700' : 'bg-gray-200'"
                 @click="$store.audioPlayer.seekEvent($event)"
                 role="slider" :aria-valuenow="Math.round($store.audioPlayer.progress)" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('quran.public.listen') }}">
                <div class="h-full rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838]" :style="`width: ${$store.audioPlayer.progress}%`"></div>
            </div>
            <span class="text-xs tabular-nums flex-shrink-0 whitespace-nowrap" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'" x-text="$store.audioPlayer.fmt($store.audioPlayer.duration)">0:00</span>
        </div>

        {{-- Speed + reciter + close --}}
        <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
            <button @click="$store.audioPlayer.cycleSpeed()" class="px-2 py-1 rounded-lg text-xs font-bold tabular-nums transition-colors"
                    :class="$store.theme.dark ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-100'"
                    x-text="$store.audioPlayer.rate + '×'" title="{{ __('quran.public.playback_speed') }}">1×</button>

            {{-- Reciter picker (only when reciters are configured) --}}
            <div class="relative" x-show="$store.audioPlayer.reciters.length > 0" @click.outside="$store.audioPlayer.reciterOpen = false">
                <button @click="$store.audioPlayer.reciterOpen = !$store.audioPlayer.reciterOpen"
                        class="flex items-center gap-1.5 px-2.5 h-8 rounded-lg text-xs font-semibold transition-colors max-w-[8rem] sm:max-w-[10rem]"
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

            <button @click="$store.audioPlayer.close()" class="p-2 rounded-lg transition-colors"
                    :class="$store.theme.dark ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-500 hover:text-gray-900'"
                    aria-label="{{ __('quran.public.pause') }}" title="{{ __('quran.public.pause') }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
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
            _saveTimer: null,

            // Reciter switching (configured per surah page)
            reciters: [],           // [{ id, nameAr, nameEn, surahAudioUrl }]
            reciterId: null,
            surahMeta: null,        // { nameAr } — title + URL re-resolution on reciter change
            reciterOpen: false,

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
            },

            // Set up the reciter list for the current surah page. The user's saved
            // reciter (siteReciterId) wins so their choice sticks across surahs;
            // the page default and the first reciter are fallbacks.
            configure({ reciters, reciterId, surah }) {
                this.reciters = Array.isArray(reciters) ? reciters : [];
                this.surahMeta = surah || null;
                const stored = parseInt(localStorage.getItem('siteReciterId') ?? '', 10);
                const valid = (id) => this.reciters.some((r) => r.id === id);
                this.reciterId = valid(stored) ? stored
                    : (valid(reciterId) ? reciterId : (this.reciters[0]?.id ?? null));
            },

            currentReciter() { return this.reciters.find((r) => r.id === this.reciterId) || null; },
            reciterName() { const r = this.currentReciter(); return r ? (r.nameAr || r.nameEn || '') : ''; },
            surahUrl() { const r = this.currentReciter(); return r ? r.surahAudioUrl : null; },

            // Play the configured surah with the selected reciter (rail Play button).
            playSurah() {
                const url = this.surahUrl();
                if (!url) { window.toast?.('{{ __('quran.public.audio_unavailable') }}', 'error'); return; }
                this.play({ url, title: this.surahMeta?.nameAr || '', subtitle: this.reciterName() });
            },

            // Switch reciter and resume the same surah from the current position.
            selectReciter(id) {
                this.reciterId = id;
                this.reciterOpen = false;
                localStorage.setItem('siteReciterId', String(id));
                const url = this.surahUrl();
                if (!url || !this.track) { return; }
                const resumeAt = this.currentTime;
                const wasPlaying = this.playing;
                this._teardown();
                this.track = { url, title: this.surahMeta?.nameAr || this.track.title, subtitle: this.reciterName() };
                this._ensureAudio();
                this.audio.addEventListener('loadedmetadata', () => {
                    this.audio.currentTime = Math.min(resumeAt, this.audio.duration || resumeAt);
                    if (wasPlaying) { this.audio.play().catch(() => {}); }
                }, { once: true });
                this._persist();
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
            seek(pct) { if (this.audio && this.duration) { this.audio.currentTime = Math.max(0, Math.min(1, pct)) * this.duration; } },
            seekEvent(e) {
                const rect = e.currentTarget.getBoundingClientRect();
                let pct = (e.clientX - rect.left) / rect.width;
                if (document.documentElement.dir === 'rtl') { pct = 1 - pct; }
                this.seek(pct);
            },
            cycleSpeed() {
                const steps = [1, 1.25, 1.5, 2, 0.75];
                this.rate = steps[(steps.indexOf(this.rate) + 1) % steps.length];
                if (this.audio) { this.audio.playbackRate = this.rate; }
            },
            setVolume(v) { this.volume = v; if (this.audio) { this.audio.volume = v; } localStorage.setItem('sitePlayerVolume', v); },

            close() {
                this._teardown();
                this.track = null; this.playing = false; this.currentTime = 0; this.duration = 0;
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
                a.addEventListener('ended', () => { this.playing = false; this.currentTime = 0; this._persist(); });
                this.audio = a;
            },
            _teardown() { if (this.audio) { this.audio.pause(); this.audio.src = ''; this.audio = null; } },
            _persist() { if (this.track) { localStorage.setItem('sitePlayerTrack', JSON.stringify({ ...this.track, currentTime: this.currentTime })); } },
            _persistSoon() { if (this._saveTimer) { return; } this._saveTimer = setTimeout(() => { this._saveTimer = null; this._persist(); }, 2000); },
        });
    });
</script>
