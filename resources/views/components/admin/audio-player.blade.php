{{--
    Global Spotify-style audio player bar.

    State lives in the Alpine `audioPlayer` store, so the bar persists across admin
    pages: the current track + position are saved to localStorage and restored on
    each page load. Note: a full page navigation stops the <audio> element and
    browser autoplay policy blocks silent resume, so the bar reappears paused and
    the user resumes with one click — true gapless playback across pages needs SPA
    navigation (Turbo/Inertia), which is out of scope here.
--}}
<div x-data x-show="$store.audioPlayer.track" x-cloak
     class="fixed bottom-0 inset-x-0 z-40 border-t shadow-2xl"
     :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'"
     role="region" aria-label="{{ __('quran.recitations.player_label') }}">
    <div class="mx-auto max-w-7xl px-3 sm:px-4 py-2.5 flex items-center gap-3 sm:gap-4">

        {{-- Track info --}}
        <div class="flex items-center gap-3 w-32 sm:w-56 flex-shrink-0 min-w-0">
            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                <i data-lucide="music" class="w-4 h-4 text-white"></i>
            </div>
            <div class="min-w-0 hidden sm:block">
                <p class="text-sm font-semibold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="$store.audioPlayer.track?.title"></p>
                <p class="text-xs truncate" :class="darkMode ? 'text-gray-400' : 'text-gray-500'" x-text="$store.audioPlayer.track?.subtitle"></p>
            </div>
        </div>

        {{-- Play/pause + seek --}}
        <div class="flex-1 flex items-center gap-2 sm:gap-3 min-w-0">
            <button @click="$store.audioPlayer.toggle()"
                    class="w-9 h-9 rounded-full flex items-center justify-center text-white bg-gradient-to-r from-[#0056b3] to-[#218838] flex-shrink-0 hover:scale-105 active:scale-95 transition-transform"
                    :aria-label="$store.audioPlayer.playing ? '{{ __('quran.recitations.player_pause') }}' : '{{ __('quran.recitations.player_play') }}'">
                <svg x-show="!$store.audioPlayer.playing" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.84A1 1 0 004 3.7v12.6a1 1 0 001.5.86l11-6.3a1 1 0 000-1.72l-11-6.3z"/></svg>
                <svg x-show="$store.audioPlayer.playing" x-cloak class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 4a1 1 0 011 1v10a1 1 0 11-2 0V5a1 1 0 011-1zm7 0a1 1 0 011 1v10a1 1 0 11-2 0V5a1 1 0 011-1z"/></svg>
            </button>
            <span class="text-xs tabular-nums text-end flex-shrink-0 whitespace-nowrap" :class="darkMode ? 'text-gray-400' : 'text-gray-500'" x-text="$store.audioPlayer.fmt($store.audioPlayer.currentTime)">0:00</span>
            <div class="flex-1 h-1.5 rounded-full cursor-pointer min-w-[60px]" :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
                 @click="$store.audioPlayer.seekEvent($event)"
                 role="slider" :aria-valuenow="Math.round($store.audioPlayer.progress)" aria-valuemin="0" aria-valuemax="100">
                <div class="h-full rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838]" :style="`width: ${$store.audioPlayer.progress}%`"></div>
            </div>
            <span class="text-xs tabular-nums flex-shrink-0 whitespace-nowrap" :class="darkMode ? 'text-gray-400' : 'text-gray-500'" x-text="$store.audioPlayer.fmt($store.audioPlayer.duration)">0:00</span>
        </div>

        {{-- Volume + close --}}
        <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
            {{-- Theme class on the wrapper, not the <i>: Lucide replaces each
                 <i data-lucide> with a generated <svg>, copying the attributes onto
                 a fresh node that Alpine re-evaluates outside this scope — which
                 threw a ReferenceError on every admin page load. --}}
            <span class="hidden sm:block" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                <i data-lucide="volume-2" class="w-4 h-4"></i>
            </span>
            <input type="range" min="0" max="1" step="0.05" :value="$store.audioPlayer.volume"
                   @input="$store.audioPlayer.setVolume(parseFloat($event.target.value))"
                   class="w-16 lg:w-24 accent-[#218838] hidden sm:block" aria-label="{{ __('quran.recitations.player_volume') }}">
            <button @click="$store.audioPlayer.close()" class="p-2 rounded-lg transition-colors"
                    :class="darkMode ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-500 hover:text-gray-900'"
                    aria-label="{{ __('quran.recitations.player_close') }}" title="{{ __('quran.recitations.player_close') }}">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('audioPlayer', {
            track: null,          // { url, title, subtitle }
            audio: null,
            playing: false,
            currentTime: 0,
            duration: 0,
            volume: parseFloat(localStorage.getItem('adminPlayerVolume') ?? '1'),
            _saveTimer: null,

            // Restore the bar (track + position) after a page navigation. Autoplay is
            // blocked without a user gesture, so it stays paused until the user clicks.
            init() {
                try {
                    const saved = JSON.parse(localStorage.getItem('adminPlayerTrack') || 'null');
                    if (saved && saved.url) {
                        this.track = { url: saved.url, title: saved.title, subtitle: saved.subtitle };
                        this._ensureAudio();
                        this.audio.currentTime = saved.currentTime || 0;
                        this.currentTime = saved.currentTime || 0;
                    }
                } catch (e) { /* ignore corrupt state */ }
            },

            // Load (and play) a track. Re-clicking the same track just toggles.
            play(track) {
                if (this.track && this.track.url === track.url) { this.toggle(); return; }
                this._teardown();
                this.track = { url: track.url, title: track.title || '', subtitle: track.subtitle || '' };
                this.currentTime = 0; this.duration = 0;
                this._ensureAudio();
                this._persist();
                this.audio.play().catch(() => {});
            },

            toggle() {
                if (!this.audio) { return; }
                if (this.playing) { this.audio.pause(); } else { this.audio.play().catch(() => {}); }
            },

            seek(pct) {
                if (this.audio && this.duration) {
                    this.audio.currentTime = Math.max(0, Math.min(1, pct)) * this.duration;
                }
            },
            seekEvent(e) {
                const rect = e.currentTarget.getBoundingClientRect();
                let pct = (e.clientX - rect.left) / rect.width;
                if (document.documentElement.dir === 'rtl') { pct = 1 - pct; }
                this.seek(pct);
            },
            setVolume(v) {
                this.volume = v;
                if (this.audio) { this.audio.volume = v; }
                localStorage.setItem('adminPlayerVolume', v);
            },

            close() {
                this._teardown();
                this.track = null; this.playing = false; this.currentTime = 0; this.duration = 0;
                localStorage.removeItem('adminPlayerTrack');
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
                a.addEventListener('loadedmetadata', () => { this.duration = a.duration || 0; });
                a.addEventListener('timeupdate', () => { this.currentTime = a.currentTime; this._persistSoon(); });
                a.addEventListener('play', () => { this.playing = true; });
                a.addEventListener('pause', () => { this.playing = false; this._persist(); });
                a.addEventListener('ended', () => { this.playing = false; this.currentTime = 0; this._persist(); });
                this.audio = a;
            },
            _teardown() {
                if (this.audio) { this.audio.pause(); this.audio.src = ''; this.audio = null; }
            },
            _persist() {
                if (!this.track) { return; }
                localStorage.setItem('adminPlayerTrack', JSON.stringify({ ...this.track, currentTime: this.currentTime }));
            },
            _persistSoon() {
                if (this._saveTimer) { return; }
                this._saveTimer = setTimeout(() => { this._saveTimer = null; this._persist(); }, 2000);
            },
        });
    });
</script>
