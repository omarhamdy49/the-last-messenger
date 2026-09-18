{{--
    Wrapped live player — our chrome over any stream type.

    Replaces the raw provider iframe the live pages used to embed: the visitor
    never sees YouTube's own UI, and an HLS/DASH/radio channel gets the same
    controls as a YouTube one.

    The component owns its own outer box, because the right box depends on the
    source: a video stream gets a 16:9 stage, an audio stream gets a compact
    sound player (a radio mount has no picture, so a black 16:9 rectangle would
    be a lie about what is playing).

    @param array|null  $source  LiveChannel::$stream_source — {type, engine, audio_only, url, video_id}
    @param string|null $poster  Artwork: the pre-roll still, and the album art for audio
    @param string      $title   Accessible name for the player region
    @param bool        $isLive  Paints the LIVE badge
    @param bool        $autoplay Start on load (falls back to muted when the browser refuses sound)
--}}
@props([
    'source' => null,
    'poster' => null,
    'title' => '',
    'isLive' => false,
    'autoplay' => true,
])

@php
    // An `iframe` source is embed-only: there is no media element or SDK to
    // drive, so our chrome cannot exist over it. Everything else is wrapped.
    $wrappable = $source !== null && ($source['type'] ?? 'iframe') !== 'iframe';
    $audioOnly = (bool) ($source['audio_only'] ?? false);
    $stage = 'relative aspect-video max-h-[80dvh] w-full overflow-hidden bg-black';
@endphp

@once
@push('head')
<style>
    /* Chrome sits directly on the picture, so it is tinted with white
       translucency rather than an sx surface token — same reason .sx-glass-chip
       exists. Shared with the episode player's .player-hit. */
    .player-hit { transition: background-color .2s; }
    .player-hit:hover { background-color: rgb(255 255 255 / 0.15); }

    /* YouTube paints its own title bar and "more videos" grid in the top and
       bottom strips of the iframe. Overscanning the frame and clipping it with
       the parent's overflow puts that chrome outside the visible box while the
       16:9 picture still fits the width exactly. The SDK REPLACES the node it
       is given, so the rule targets whatever iframe lands inside the crop. */
    .sx-yt-crop { position: absolute; inset: 0; overflow: hidden; }
    .sx-yt-crop > iframe {
        position: absolute;
        top: -60px;
        left: 0;
        width: 100%;
        height: calc(100% + 120px);
        border: 0;
    }

    /* Audio has no picture — the bars stand in for one, so it reads at a glance
       that sound is actually flowing rather than merely loaded. */
    .sx-eq { display: flex; align-items: flex-end; gap: 3px; height: 24px; }
    .sx-eq i { display: block; width: 4px; border-radius: 9999px; background: currentColor; height: 25%; }
    .sx-eq.is-playing i { animation: sx-eq-bounce .9s ease-in-out infinite; }
    .sx-eq i:nth-child(2) { animation-delay: .15s; }
    .sx-eq i:nth-child(3) { animation-delay: .3s; }
    .sx-eq i:nth-child(4) { animation-delay: .45s; }
    .sx-eq i:nth-child(5) { animation-delay: .6s; }
    @keyframes sx-eq-bounce { 0%, 100% { height: 22%; } 50% { height: 100%; } }

    /* Audio player: the artwork is the only visual, so it carries the motion. */
    .sx-audio-art { position: relative; overflow: hidden; border-radius: 1rem; }
    .sx-audio-art::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(160deg, rgb(0 0 0 / 0) 40%, rgb(0 0 0 / 0.55));
    }

    @media (prefers-reduced-motion: reduce) {
        .player-hit { transition: none; }
        .sx-eq.is-playing i { animation: none; height: 55%; }
    }
</style>
@endpush
@endonce

@if ($source === null)
    {{-- No stream URL yet — the brand plate stands in for the picture --}}
    <div class="{{ $stage }}">
        <div class="sx-hero-fallback absolute inset-0 grid place-items-center gap-3 text-white">
            <div class="grid justify-items-center gap-3">
                <i data-lucide="radio" class="h-12 w-12 opacity-80" aria-hidden="true"></i>
                <p class="font-bold">{{ __('media.public.live_status_offline') }}</p>
            </div>
        </div>
    </div>

@elseif (! $wrappable)
    {{-- Embed-only provider: pass it straight through, muted autoplay --}}
    <div class="{{ $stage }}">
        <iframe src="{{ $source['url'] }}"
                title="{{ $title }}"
                class="absolute inset-0 h-full w-full"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen></iframe>
    </div>

@elseif ($audioOnly)
    {{-- ================================================================ --}}
    {{-- SOUND PLAYER — a radio mount has no picture, so it gets a compact --}}
    {{-- card rather than a 16:9 stage standing empty.                     --}}
    {{-- ================================================================ --}}
    <div x-data="streamPlayer(@js($source), { autoplay: {{ $autoplay ? 'true' : 'false' }}, audioOnly: true })"
         x-init="init()"
         x-ref="shell"
         role="region" aria-label="{{ $title }}"
         class="sx-surface relative overflow-hidden">

        {{-- The engine drives a real <audio> element here: same handle contract
             as <video>, but it never asks the browser for a decode surface.
             Hidden by size rather than `display:none` — Chrome deprioritises
             autostart for media it is not rendering at all. preload=auto so the
             mount is already connecting while the chrome paints. --}}
        <audio x-ref="video" preload="{{ $autoplay ? 'auto' : 'none' }}" @if ($autoplay) autoplay @endif
               class="pointer-events-none absolute h-px w-px opacity-0"></audio>

        {{-- Artwork, blurred, as the card's own backdrop --}}
        @if ($poster)
            <img src="{{ $poster }}" alt="" aria-hidden="true"
                 class="pointer-events-none absolute inset-0 h-full w-full scale-110 object-cover opacity-20 blur-2xl">
        @endif

        <div class="relative flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:gap-6 sm:p-6">

            {{-- Album art --}}
            <div class="sx-audio-art mx-auto h-32 w-32 shrink-0 sm:mx-0 sm:h-28 sm:w-28">
                @if ($poster)
                    <img src="{{ $poster }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                @else
                    <div class="sx-hero-fallback absolute inset-0"></div>
                @endif
                <div class="absolute inset-x-0 bottom-0 z-10 flex justify-center pb-2.5 text-white">
                    <div class="sx-eq" :class="playing ? 'is-playing' : ''" aria-hidden="true">
                        <i></i><i></i><i></i><i></i><i></i>
                    </div>
                </div>
            </div>

            {{-- Meta + transport --}}
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                    @if ($isLive)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-600 px-2.5 py-1 text-[11px] font-bold text-white">
                            <span class="h-1.5 w-1.5 rounded-full bg-current motion-safe:animate-pulse" aria-hidden="true"></span>
                            {{ __('media.public.live_now') }}
                        </span>
                    @endif
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-[color:var(--sx-muted)]">
                        <i data-lucide="radio" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        {{ __('media.public.audio_only_stream') }}
                    </span>
                </div>

                <h2 class="mt-2 truncate text-center text-lg font-extrabold text-[color:var(--sx-ink)] sm:text-start">{{ $title }}</h2>

                {{-- Transport row. dir=ltr so the slider always fills left→right. --}}
                <div class="mt-4 flex items-center justify-center gap-3 sm:justify-start" dir="ltr">
                    <button type="button" @click="togglePlay()"
                            :disabled="loading"
                            :aria-label="playing ? @js(__('media.public.player_pause')) : @js(__('media.public.player_play'))"
                            :title="playing ? @js(__('media.public.player_pause')) : @js(__('media.public.player_play'))"
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-white shadow-lg transition-transform hover:scale-105 disabled:cursor-wait motion-reduce:transform-none"
                            style="background: var(--sx-grad)">
                        {{-- While the mount is connecting the button IS the loader:
                             one spot to look at, instead of a play icon that does
                             nothing yet sitting next to a separate spinner. --}}
                        <i data-lucide="loader-2" class="h-6 w-6 animate-spin" x-show="loading" x-cloak aria-hidden="true"></i>
                        <i data-lucide="play" class="ms-0.5 h-6 w-6" x-show="!loading && !playing" aria-hidden="true"></i>
                        <i data-lucide="pause" class="h-6 w-6" x-show="!loading && playing" x-cloak aria-hidden="true"></i>
                    </button>

                    <button type="button" @click="toggleMute()"
                            :aria-label="muted ? @js(__('media.public.player_unmute')) : @js(__('media.public.player_mute'))"
                            :title="muted ? @js(__('media.public.player_unmute')) : @js(__('media.public.player_mute'))"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[color:var(--sx-ink)] transition-colors hover:bg-[color:var(--sx-line)]">
                        <i data-lucide="volume-2" class="h-5 w-5" x-show="!muted" aria-hidden="true"></i>
                        <i data-lucide="volume-x" class="h-5 w-5" x-show="muted" x-cloak aria-hidden="true"></i>
                    </button>

                    <input type="range" min="0" max="100" step="1" :value="muted ? 0 : volume"
                           @input="setVolume($event.target.value)"
                           aria-label="{{ __('media.public.player_volume') }}"
                           class="player-range w-28 sm:w-36"
                           :style="`--progress: ${muted ? 0 : volume}%`">
                </div>

                {{-- Status line: connecting / silent / failed, never all at once --}}
                <p class="mt-3 text-center text-xs font-semibold sm:text-start" x-cloak
                   x-show="loading || needsUnmuteGesture || playerError"
                   :class="playerError ? 'text-red-500' : 'text-[color:var(--sx-muted)]'">
                    {{-- Mutually exclusive on purpose: a failed stream must not
                         also be advertising a sound prompt that does nothing. --}}
                    <span x-show="loading">{{ __('media.public.player_connecting') }}</span>
                    <span x-show="!loading && playerError">{{ __('media.public.player_error_stream') }}</span>
                    <span x-show="!loading && !playerError && needsUnmuteGesture">{{ __('media.public.player_tap_for_sound') }}</span>
                </p>
            </div>

            {{-- Sound unlock / retry, whichever applies --}}
            <div class="flex justify-center sm:block">
                <button type="button" x-show="needsUnmuteGesture && !playerError" x-cloak @click="unmuteNow()"
                        class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-bold text-white shadow-lg"
                        style="background: var(--sx-grad)">
                    <i data-lucide="volume-x" class="h-4 w-4" aria-hidden="true"></i>
                    {{ __('media.public.player_tap_for_sound') }}
                </button>
                <button type="button" x-show="playerError" x-cloak @click="retry()"
                        class="inline-flex items-center gap-2 rounded-full border border-[color:var(--sx-line)] px-4 py-2 text-sm font-bold text-[color:var(--sx-ink)]">
                    <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                    {{ __('media.public.player_retry') }}
                </button>
            </div>
        </div>
    </div>

@else
    {{-- ================================================================ --}}
    {{-- VIDEO STAGE — YouTube / HLS / DASH / progressive                  --}}
    {{-- ================================================================ --}}
    <div class="{{ $stage }}">
        <div x-data="streamPlayer(@js($source), { autoplay: {{ $autoplay ? 'true' : 'false' }}, audioOnly: false })"
             x-init="init()"
             x-ref="shell"
             @mousemove.throttle.250ms="wakeControls()"
             @touchstart.passive="wakeControls()"
             @keydown.space.prevent="togglePlay()"
             @keydown.m="toggleMute()"
             @keydown.f="toggleFullscreen()"
             tabindex="0"
             role="region" aria-label="{{ $title }}"
             class="absolute inset-0 bg-black focus:outline-none">

            @if ($source['engine'] === 'youtube')
                {{-- SDK target: YouTube REPLACES this node with its iframe --}}
                <div class="sx-yt-crop"><div x-ref="ytHost"></div></div>
            @else
                {{-- hls / dash / progressive. playsinline keeps iOS from hijacking
                     it into its own fullscreen player, where our chrome is gone.
                     The autoplay attribute backs up our own play() call: the
                     browser may honour it at a moment the script cannot. --}}
                <video x-ref="video" playsinline
                       preload="{{ $autoplay ? 'auto' : 'none' }}" @if ($autoplay) autoplay @endif
                       class="absolute inset-0 h-full w-full object-contain"></video>
            @endif

            {{-- Artwork: before playback, and afterwards too when the stream turns
                 out to carry no video track (an audio-only HLS mount), so the
                 visitor gets album art rather than a black rectangle. --}}
            <div x-show="!started || !hasPicture" class="absolute inset-0 z-10">
                @if ($poster)
                    <img src="{{ $poster }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                @else
                    <div class="sx-hero-fallback absolute inset-0"></div>
                @endif
                <div class="absolute inset-0 bg-black/45" aria-hidden="true"></div>
            </div>

            {{-- Pre-play affordance: only when autoplay is off or was refused
                 outright. Hidden the moment we start connecting, so it never
                 sits next to the spinner. --}}
            <div x-show="!started && !loading" x-cloak class="absolute inset-0 z-20">
                <button type="button" @click="start()"
                        aria-label="{{ __('media.public.player_play') }}"
                        title="{{ __('media.public.player_play') }}"
                        class="group/start absolute inset-0 flex items-center justify-center">
                    <span class="flex h-16 w-16 items-center justify-center rounded-full shadow-2xl transition-transform duration-300 group-hover/start:scale-110 motion-reduce:transform-none sm:h-20 sm:w-20"
                          style="background: var(--sx-grad)">
                        <i data-lucide="play" class="ms-1 h-8 w-8 text-white sm:h-10 sm:w-10" aria-hidden="true"></i>
                    </span>
                </button>
            </div>

            {{-- Connecting / buffering: the loader owns the stage alone. --}}
            <div x-show="loading" x-cloak class="pointer-events-none absolute inset-0 z-30 flex flex-col items-center justify-center gap-3 bg-black/45">
                <i data-lucide="loader-2" class="h-12 w-12 animate-spin text-white" aria-hidden="true"></i>
                <p class="text-xs font-semibold text-white/75">{{ __('media.public.player_connecting') }}</p>
            </div>

            {{-- Click-capture veil: owns every click over the video so no embed UI
                 is ever reachable, and doubles as the paused overlay. --}}
            <div x-show="started" x-cloak
                 @click="togglePlay()" @dblclick="toggleFullscreen()"
                 class="absolute inset-0 z-20 transition-colors duration-200"
                 :class="!playing && !loading ? 'bg-black/50' : ''">
                <div x-show="!playing && !loading" class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <span class="flex h-16 w-16 items-center justify-center rounded-full shadow-2xl" style="background: var(--sx-grad)">
                        <i data-lucide="play" class="ms-1 h-8 w-8 text-white" aria-hidden="true"></i>
                    </span>
                </div>
            </div>

            {{-- Unmute prompt: the browser refused audible autoplay, so the stream
                 is running silently and one tap brings the sound back. --}}
            <button type="button" x-show="needsUnmuteGesture && !loading" x-cloak @click.stop="unmuteNow()"
                    class="absolute top-4 start-4 z-40 flex items-center gap-2 rounded-full border border-white/20 bg-black/70 px-4 py-2 text-sm font-bold text-white shadow-2xl backdrop-blur-sm hover:bg-black/85">
                <i data-lucide="volume-x" class="h-4 w-4" aria-hidden="true"></i>
                {{ __('media.public.player_tap_for_sound') }}
            </button>

            @if ($isLive)
                <span class="sx-glass-chip is-danger absolute top-4 end-4 z-40">
                    <span class="h-1.5 w-1.5 rounded-full bg-current motion-safe:animate-pulse" aria-hidden="true"></span>
                    {{ __('media.public.live_now') }}
                </span>
            @endif

            {{-- Unrecoverable failure --}}
            <div x-show="playerError" x-cloak class="absolute inset-0 z-50 flex flex-col items-center justify-center gap-3 bg-black/85 px-6 text-center">
                <i data-lucide="alert-triangle" class="h-8 w-8 text-[color:var(--sx-teal)]" aria-hidden="true"></i>
                <p class="text-sm text-white/85">{{ __('media.public.player_error_stream') }}</p>
                <button type="button" @click="retry()" class="rounded-full border border-white/25 px-4 py-1.5 text-xs font-bold text-white hover:bg-white/10">
                    {{ __('media.public.player_retry') }}
                </button>
            </div>

            {{-- Control bar — live: no timeline, no speed, nothing to seek --}}
            <div x-show="started && !playerError" x-cloak dir="ltr"
                 class="absolute inset-x-0 bottom-0 z-40 bg-gradient-to-t from-black/90 via-black/50 to-transparent px-3 pb-2 pt-12 transition-opacity duration-200 motion-reduce:transition-none sm:px-4 sm:pb-3"
                 :class="controlsVisible || !playing ? 'opacity-100' : 'opacity-0 pointer-events-none'">
                <div class="flex items-center gap-1 text-white sm:gap-2">

                    <button type="button" @click="togglePlay()"
                            :aria-label="playing ? @js(__('media.public.player_pause')) : @js(__('media.public.player_play'))"
                            :title="playing ? @js(__('media.public.player_pause')) : @js(__('media.public.player_play'))"
                            class="player-hit flex h-11 w-11 items-center justify-center rounded-full sm:h-10 sm:w-10">
                        <i data-lucide="play" class="ms-0.5 h-5 w-5" x-show="!playing" aria-hidden="true"></i>
                        <i data-lucide="pause" class="h-5 w-5" x-show="playing" x-cloak aria-hidden="true"></i>
                    </button>

                    <button type="button" @click="toggleMute()"
                            :aria-label="muted ? @js(__('media.public.player_unmute')) : @js(__('media.public.player_mute'))"
                            :title="muted ? @js(__('media.public.player_unmute')) : @js(__('media.public.player_mute'))"
                            class="player-hit flex h-11 w-11 items-center justify-center rounded-full sm:h-10 sm:w-10">
                        <i data-lucide="volume-2" class="h-5 w-5" x-show="!muted" aria-hidden="true"></i>
                        <i data-lucide="volume-x" class="h-5 w-5" x-show="muted" x-cloak aria-hidden="true"></i>
                    </button>

                    <input type="range" min="0" max="100" step="1" :value="muted ? 0 : volume"
                           @input="setVolume($event.target.value)"
                           aria-label="{{ __('media.public.player_volume') }}"
                           class="player-range hidden w-20 sm:block"
                           :style="`--progress: ${muted ? 0 : volume}%`">

                    {{-- "LIVE" where a VOD player shows its timecode. Red only while
                         genuinely running; grey when paused or buffering. --}}
                    <span class="ms-1 flex select-none items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide sm:text-xs"
                          :class="playing ? 'text-red-400' : 'text-white/55'">
                        <span class="h-1.5 w-1.5 rounded-full bg-current" :class="playing ? 'motion-safe:animate-pulse' : ''" aria-hidden="true"></span>
                        {{ __('media.public.live_now') }}
                    </span>

                    <button type="button" @click="toggleFullscreen()" class="player-hit ms-auto flex h-11 w-11 items-center justify-center rounded-full sm:h-10 sm:w-10"
                            :aria-label="fullscreen ? @js(__('media.public.player_exit_fullscreen')) : @js(__('media.public.player_fullscreen'))"
                            :title="fullscreen ? @js(__('media.public.player_exit_fullscreen')) : @js(__('media.public.player_fullscreen'))">
                        <i data-lucide="maximize" class="h-5 w-5" x-show="!fullscreen" aria-hidden="true"></i>
                        <i data-lucide="minimize" class="h-5 w-5" x-show="fullscreen" x-cloak aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

@if ($wrappable)
@once
@push('scripts')
<script>
/*
 * Chrome state for the wrapped live player, shared by the video stage and the
 * audio card. The engine (YouTube SDK / hls.js / dash.js / media element) lives
 * in resources/js/stream-player.js and is loaded on demand; this only tracks
 * what the controls need to show.
 */
function streamPlayer(source, options) {
    return {
        source,
        engine: source.engine,
        audioOnly: options.audioOnly,
        autoplay: options.autoplay,

        handle: null,
        started: false,
        booting: false,
        playing: false,
        buffering: false,
        playerError: false,
        muted: false,
        volume: 100,
        hasPicture: true,
        needsUnmuteGesture: false,
        controlsVisible: true,
        fullscreen: false,
        hideControlsTimer: null,
        unmuteProbe: null,

        /*
         * One flag for "there is nothing to look at yet", so the loader and the
         * play button can never be on screen at the same time. Booting covers
         * the engine download and handshake; buffering covers a mid-stream
         * stall. Neither counts once the picture is actually running.
         */
        get loading() {
            return !this.playerError && (this.booting || (this.started && !this.playing && this.buffering));
        },

        init() {
            document.addEventListener('fullscreenchange', () => {
                this.fullscreen = document.fullscreenElement === this.$refs.shell;
            });

            // Remembered across channels — a visitor who turned the sound down
            // should not be shouted at by the next stream.
            try {
                const saved = localStorage.getItem('livePlayerVolume');
                if (saved !== null) this.volume = Math.min(100, Math.max(0, Number(saved)));
            } catch (e) { /* storage blocked */ }

            // An audio-only source has no picture by definition.
            if (this.audioOnly) this.hasPicture = false;

            if (this.autoplay) this.start();
            this.refreshIcons();
        },

        async start() {
            if (this.started || this.booting) return;
            this.booting = true;
            this.started = true;
            this.playerError = false;
            this.refreshIcons();

            try {
                const createStream = await window.loadStreamEngine();
                this.handle = await createStream({
                    container: this.$refs.ytHost,
                    video: this.$refs.video,
                    source: this.source,
                    onState: (state) => this.onState(state),
                    onError: () => this.fail(),
                });
                this.applyVolume();
                await this.attemptAutoplay();
            } catch (e) {
                this.fail();
            } finally {
                this.booting = false;
                this.refreshIcons();
            }
        },

        /*
         * Push our volume onto the engine. Worth doing on every transition into
         * PLAYING and not just once at boot: the YouTube SDK carries its own
         * remembered volume across embeds and re-applies it as the video starts,
         * so a visitor whose last YouTube session was quiet gets a near-silent
         * stream unless we overwrite it. `this.volume` is the slider's value, so
         * re-asserting it never fights a choice the visitor made here.
         */
        applyVolume() {
            if (!this.handle) return;
            this.handle.setVolume(this.volume);
            if (!this.muted) this.handle.unmute();
        },

        /*
         * Autoplay ladder — muted first, on purpose.
         *
         * Muted autoplay is the only kind every browser permits unconditionally,
         * so starting there gets the stream running immediately for all three
         * engines. Asking for sound first instead costs real time: a <video>
         * rejects the promise quickly, but the YouTube SDK's playVideo() never
         * resolves or rejects at all, so an audible-first attempt could only be
         * detected by waiting out a timeout — a dead stall on the one engine
         * where autoplay matters most. We start muted, then restore the sound a
         * beat later wherever the browser allows it.
         */
        async attemptAutoplay() {
            this.handle.mute();
            this.muted = true;

            try {
                await this.handle.play();
            } catch (e) {
                // Even a muted start refused: nothing is broken, the browser
                // just wants a click first (autoplay switched off, or a
                // background tab). Sit paused and unmuted so that click starts
                // it with sound. fail() here would accuse a stream that has not
                // actually been tried.
                this.handle.unmute();
                this.muted = false;
                this.playing = false;
                this.controlsVisible = true;
                this.refreshIcons();
                return;
            }

            this.tryUnmute();
        },

        /*
         * Best-effort sound, immediately after the muted start. Where the site
         * has enough media engagement this simply works; where it does not the
         * browser re-mutes us (or stalls), and we settle for a running muted
         * stream plus the "tap for sound" prompt.
         */
        tryUnmute() {
            if (!this.handle || this.playerError) return;
            this.muted = false;
            this.applyVolume();

            clearTimeout(this.unmuteProbe);
            this.unmuteProbe = setTimeout(() => {
                if (!this.handle || this.playerError) return;
                const refused = this.handle.isMuted() || !this.playing;
                if (!refused) return;

                this.muted = true;
                this.needsUnmuteGesture = true;
                this.handle.mute();
                Promise.resolve(this.handle.play()).catch(() => {});
                this.armGestureUnlock();
                this.refreshIcons();
            }, 600);
        },

        /* A gesture anywhere on the page unlocks audio, so the prompt is a hint
           rather than the only way through. */
        armGestureUnlock() {
            const unlock = () => this.unmuteNow();
            ['pointerdown', 'keydown', 'touchstart'].forEach((evt) =>
                document.addEventListener(evt, unlock, { once: true, capture: true }));
        },

        unmuteNow() {
            if (!this.handle || !this.needsUnmuteGesture) return;
            this.needsUnmuteGesture = false;
            this.muted = false;
            this.applyVolume();
            Promise.resolve(this.handle.play()).catch(() => {});
            this.refreshIcons();
        },

        onState(state) {
            if (state === 'playing') {
                this.playing = true;
                this.buffering = false;
                this.booting = false;
                // Only now can we tell whether the stream carries a picture: an
                // audio-only HLS mount reports videoWidth 0 once it is decoding.
                if (!this.audioOnly && this.engine !== 'youtube') {
                    this.hasPicture = !!(this.$refs.video && this.$refs.video.videoWidth);
                }
                this.applyVolume();
                this.wakeControls();
            } else if (state === 'paused') {
                this.playing = false;
                this.buffering = false;
                this.controlsVisible = true;
            } else if (state === 'buffering') {
                this.buffering = true;
            } else if (state === 'ended') {
                // A live stream that ends has gone off air, not finished.
                this.playing = false;
                this.buffering = false;
                this.controlsVisible = true;
            }
            this.refreshIcons();
        },

        fail() {
            this.playerError = true;
            this.buffering = false;
            this.booting = false;
            this.playing = false;
            this.refreshIcons();
        },

        retry() {
            if (this.handle) this.handle.destroy();
            this.handle = null;
            this.started = false;
            this.playing = false;
            this.playerError = false;
            this.needsUnmuteGesture = false;
            this.start();
        },

        togglePlay() {
            if (this.loading) return;
            if (!this.handle || this.playerError) { if (!this.started) this.start(); return; }
            this.playing ? this.handle.pause() : Promise.resolve(this.handle.play()).catch(() => {});
        },

        toggleMute() {
            if (!this.handle) return;
            this.needsUnmuteGesture = false;
            this.muted = !this.muted;
            this.muted ? this.handle.mute() : this.applyVolume();
            this.refreshIcons();
        },

        setVolume(value) {
            if (!this.handle) return;
            this.volume = Number(value);
            this.muted = this.volume === 0;
            this.needsUnmuteGesture = false;
            this.muted ? this.handle.mute() : this.applyVolume();
            try { localStorage.setItem('livePlayerVolume', String(this.volume)); } catch (e) { /* storage blocked */ }
            this.refreshIcons();
        },

        toggleFullscreen() {
            const shell = this.$refs.shell;
            if (!shell) return;
            if (document.fullscreenElement) document.exitFullscreen();
            else if (shell.requestFullscreen) shell.requestFullscreen();
        },

        wakeControls() {
            this.controlsVisible = true;
            clearTimeout(this.hideControlsTimer);
            if (!this.playing) return;
            this.hideControlsTimer = setTimeout(() => { this.controlsVisible = false; }, 2500);
        },

        refreshIcons() {
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },
    };
}
</script>
@endpush
@endonce
@endif
