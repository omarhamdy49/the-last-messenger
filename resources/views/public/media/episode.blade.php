@extends('layouts.public')

@section('title', $episode['seo_title'] ?: $episode['title'] . ' — ' . $program['title'])
@section('meta_description', $episode['seo_description'] ?: \Illuminate\Support\Str::limit(strip_tags((string) ($episode['description'] ?? $program['description'])), 160))

@if($episode['seo_keywords'])
@push('head')
    <meta name="keywords" content="{{ $episode['seo_keywords'] }}">
@endpush
@endif

@push('head')
    <style>
        /* The player chrome lies directly on the video, so it is tinted with white
           translucency instead of an sx surface token — the same reason
           .sx-glass-chip exists. Page-scoped because nothing outside this embed
           should reach for it.
           .player-hit  — transparent control, tints on hover
           .player-plate — statically tinted plate (tints further when interactive) */
        .player-hit { transition: background-color .2s; }
        .player-hit:hover { background-color: rgb(255 255 255 / 0.15); }
        .player-plate { background-color: rgb(255 255 255 / 0.1); transition: background-color .2s; }
        a.player-plate:hover, button.player-plate:hover { background-color: rgb(255 255 255 / 0.2); }
        @media (prefers-reduced-motion: reduce) {
            .player-hit, .player-plate { transition: none; }
        }

        /* Seek (±10s) buttons: the arrow arc nudges in its travel direction; the number stays upright. */
        .seek-btn .seek-arc {
            transition: transform .25s cubic-bezier(.34, 1.56, .64, 1);
            transform-origin: center;
            transform-box: view-box;
        }
        .seek-btn:hover .seek-arc-back { transform: rotate(-22deg); }
        .seek-btn:hover .seek-arc-fwd { transform: rotate(22deg); }
        .seek-btn:active .seek-arc-back { transform: rotate(-60deg) scale(.9); }
        .seek-btn:active .seek-arc-fwd { transform: rotate(60deg) scale(.9); }
        @media (prefers-reduced-motion: reduce) {
            .seek-btn .seek-arc { transition: none; }
            .seek-btn:hover .seek-arc, .seek-btn:active .seek-arc { transform: none; }
        }
    </style>
@endpush

@section('content')
@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<div class="sx min-h-[100dvh] pb-20" x-data="episodePlayer()" x-init="init()">

    {{-- ============================================================ --}}
    {{-- PLAYER — responsive 16:9 (or unavailable state)              --}}
    {{-- ============================================================ --}}
    <section class="bg-black">
        <div class="sx-wrap !px-0 lg:!px-8">
            <div class="relative aspect-video max-h-[80dvh] w-full overflow-hidden">
                @if ($episode['has_video'] && $episode['video_id'])
                    {{-- Custom player: YouTube IFrame SDK behind a branded shell.
                         Nothing YouTube loads until the visitor presses play; while
                         playing, a capture layer keeps every YouTube UI element
                         unreachable and all controls are our own. --}}
                    <div x-ref="shell"
                         @mousemove.throttle.250ms="wakeControls()"
                         @touchstart.passive="wakeControls()"
                         @keydown.space.prevent="started && togglePlay()"
                         @keydown.m="started && toggleMute()"
                         @keydown.f="started && toggleFullscreen()"
                         @keydown.arrow-left.prevent="started && skip(-10)"
                         @keydown.arrow-right.prevent="started && skip(10)"
                         tabindex="0"
                         role="region" aria-label="{{ $episode['title'] }}"
                         class="absolute inset-0 bg-black focus:outline-none">

                        {{-- The SDK replaces this node with the iframe; the shell crops
                             YouTube's own chrome (see #episode-player in app.css) --}}
                        <div class="absolute inset-0 overflow-hidden">
                            <div id="episode-player"></div>
                        </div>

                        {{-- Poster facade (pre-play): episode thumbnail + brand play button.
                             It carries the hero view-transition name, so the card the
                             visitor clicked morphs straight into the player picture.
                             thumbnail_hq is maxresdefault (1280px) when the thumbnail is a
                             CDN fallback; onerror drops back to hqdefault when maxres 404s. --}}
                        <div x-show="!started" class="absolute inset-0 z-20">
                            @if ($episode['thumbnail'])
                                <img src="{{ $episode['thumbnail_hq'] ?? $episode['thumbnail'] }}" alt=""
                                     @if (! empty($episode['thumbnail_hq'])) onerror="this.onerror=null;this.src='{{ $episode['thumbnail'] }}'" @endif
                                     style="view-transition-name: sx-hero"
                                     class="absolute inset-0 h-full w-full object-cover">
                            @else
                                <div class="sx-hero-fallback"></div>
                            @endif
                            <div class="absolute inset-0 bg-black/40" aria-hidden="true"></div>
                            <button type="button" @click="startPlayback()"
                                    aria-label="{{ __('media.public.player_play') }}"
                                    title="{{ __('media.public.player_play') }}"
                                    class="group/start absolute inset-0 flex items-center justify-center">
                                <span class="flex h-16 w-16 items-center justify-center rounded-full shadow-2xl transition-transform duration-300 group-hover/start:scale-110 motion-reduce:transform-none sm:h-20 sm:w-20"
                                      style="background: var(--sx-grad)">
                                    <i data-lucide="play" class="ms-1 h-8 w-8 text-white sm:h-10 sm:w-10" aria-hidden="true"></i>
                                </span>
                            </button>
                        </div>

                        {{-- Click-capture veil: owns every click over the video, doubles
                             as the pause/buffering overlay so no embed UI ever shows --}}
                        <div x-show="started" x-cloak
                             @click="togglePlay()" @dblclick="toggleFullscreen()"
                             class="absolute inset-0 z-10 transition-colors duration-200"
                             :class="!playing && !buffering ? 'bg-black/60' : ''">
                            <div x-show="buffering" class="pointer-events-none absolute inset-0 flex items-center justify-center">
                                <i data-lucide="loader-2" class="h-12 w-12 animate-spin text-white" aria-hidden="true"></i>
                            </div>
                            <div x-show="!playing && !buffering && !overlayVisible" class="pointer-events-none absolute inset-0 flex items-center justify-center">
                                {{-- Same brand plate as the poster facade above, so pausing
                                     and pressing play read as one control. --}}
                                <span class="flex h-16 w-16 items-center justify-center rounded-full shadow-2xl"
                                      style="background: var(--sx-grad)">
                                    <i data-lucide="play" class="ms-1 h-8 w-8 text-white" aria-hidden="true"></i>
                                </span>
                            </div>
                            <div x-show="playerError" class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-3 bg-black/80 px-6 text-center">
                                <i data-lucide="alert-triangle" class="h-8 w-8 text-[color:var(--sx-teal)]" aria-hidden="true"></i>
                                <p class="text-sm text-white/85">{{ __('media.public.player_error') }}</p>
                            </div>
                        </div>

                        {{-- Custom control bar --}}
                        <div x-show="started && !playerError" x-cloak dir="ltr"
                             class="absolute inset-x-0 bottom-0 z-30 bg-gradient-to-t from-black/90 via-black/50 to-transparent px-3 pb-2 pt-12 transition-opacity duration-200 motion-reduce:transition-none sm:px-4 sm:pb-3"
                             :class="controlsVisible || !playing ? 'opacity-100' : 'opacity-0 pointer-events-none'">

                            {{-- Seek bar --}}
                            <input type="range" min="0" :max="duration || 0" step="0.5" :value="current"
                                   @input="seek($event.target.value)"
                                   aria-label="{{ __('media.public.player_seek') }}"
                                   class="player-range block w-full"
                                   :style="`--progress: ${duration ? (current / duration * 100) : 0}%`">

                            <div class="mt-1.5 flex items-center gap-1 text-white sm:gap-2">
                                {{-- Rewind 10s --}}
                                <button type="button" @click="skip(-10)"
                                        aria-label="{{ __('media.public.player_rewind') }}"
                                        title="{{ __('media.public.player_rewind') }}"
                                        class="player-hit seek-btn flex h-11 w-11 items-center justify-center rounded-full sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <g class="seek-arc seek-arc-back">
                                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                            <path d="M3 3v5h5"/>
                                        </g>
                                        <text x="12" y="15.5" text-anchor="middle" font-size="7.5" font-weight="800"
                                              fill="currentColor" stroke="none" style="font-family: inherit;">10</text>
                                    </svg>
                                </button>

                                {{-- Play / pause --}}
                                <button type="button" @click="togglePlay()"
                                        :aria-label="playing ? @js(__('media.public.player_pause')) : @js(__('media.public.player_play'))"
                                        :title="playing ? @js(__('media.public.player_pause')) : @js(__('media.public.player_play'))"
                                        class="player-hit flex h-11 w-11 items-center justify-center rounded-full sm:h-10 sm:w-10">
                                    <i data-lucide="play" class="ms-0.5 h-5 w-5" x-show="!playing" aria-hidden="true"></i>
                                    <i data-lucide="pause" class="h-5 w-5" x-show="playing" x-cloak aria-hidden="true"></i>
                                </button>

                                {{-- Forward 10s --}}
                                <button type="button" @click="skip(10)"
                                        aria-label="{{ __('media.public.player_forward') }}"
                                        title="{{ __('media.public.player_forward') }}"
                                        class="player-hit seek-btn flex h-11 w-11 items-center justify-center rounded-full sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <g class="seek-arc seek-arc-fwd">
                                            <path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/>
                                            <path d="M21 3v5h-5"/>
                                        </g>
                                        <text x="12" y="15.5" text-anchor="middle" font-size="7.5" font-weight="800"
                                              fill="currentColor" stroke="none" style="font-family: inherit;">10</text>
                                    </svg>
                                </button>

                                {{-- Mute + volume --}}
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

                                {{-- Time — total duration hidden on the narrowest screens:
                                     with hh:mm:ss durations the full "cur / total" readout
                                     pushes the fullscreen button off a 360-390px viewport --}}
                                <span class="select-none whitespace-nowrap text-[11px] font-medium tabular-nums text-white/85 sm:text-xs">
                                    <span x-text="fmt(current)">0:00</span>
                                    <span class="hidden text-white/50 min-[480px]:inline"> / </span>
                                    <span x-text="fmt(duration)" class="hidden min-[480px]:inline">0:00</span>
                                </span>

                                {{-- Playback speed --}}
                                <div class="relative ms-auto" @click.away="speedOpen = false">
                                    <button type="button" @click="speedOpen = !speedOpen; refreshIcons()"
                                            :aria-expanded="speedOpen ? 'true' : 'false'"
                                            aria-label="{{ __('media.public.player_speed') }}"
                                            title="{{ __('media.public.player_speed') }}"
                                            class="player-hit flex h-11 items-center justify-center gap-1 rounded-full px-2.5 sm:h-10 sm:px-3">
                                        <i data-lucide="gauge" class="hidden h-4 w-4 sm:block" aria-hidden="true"></i>
                                        <span class="text-xs font-bold tabular-nums" x-text="rate === 1 ? '1x' : rate + 'x'">1x</span>
                                    </button>
                                    <div x-show="speedOpen" x-cloak
                                         x-transition:enter="transition ease-out duration-150 motion-reduce:duration-0"
                                         x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                         class="absolute bottom-full end-0 z-50 mb-2 w-36 rounded-xl border border-white/10 bg-black/90 py-1.5 shadow-2xl backdrop-blur-sm">
                                        <p class="px-3 py-1 text-[11px] font-semibold text-white/60">{{ __('media.public.player_speed') }}</p>
                                        <template x-for="option in [0.5, 0.75, 1, 1.25, 1.5, 2]" :key="option">
                                            <button type="button" @click="setRate(option)"
                                                    class="player-hit flex w-full items-center justify-between px-3 py-1.5 text-xs font-semibold"
                                                    :class="rate === option ? 'text-[color:var(--sx-teal)]' : 'text-white/85'">
                                                <span x-text="option === 1 ? @js(__('media.public.player_speed_normal')) : option + 'x'"></span>
                                                <span x-show="rate === option" aria-hidden="true">✓</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                {{-- Fullscreen --}}
                                <button type="button" @click="toggleFullscreen()"
                                        :aria-label="fullscreen ? @js(__('media.public.player_exit_fullscreen')) : @js(__('media.public.player_fullscreen'))"
                                        :title="fullscreen ? @js(__('media.public.player_exit_fullscreen')) : @js(__('media.public.player_fullscreen'))"
                                        class="player-hit flex h-11 w-11 items-center justify-center rounded-full sm:h-10 sm:w-10">
                                    <i data-lucide="maximize" class="h-5 w-5" x-show="!fullscreen" aria-hidden="true"></i>
                                    <i data-lucide="minimize" class="h-5 w-5" x-show="fullscreen" x-cloak aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @elseif ($episode['has_video'])
                    {{-- Non-YouTube URL — plain embed fallback --}}
                    <iframe src="{{ $episode['video_url'] }}"
                            title="{{ $episode['title'] }}"
                            class="absolute inset-0 h-full w-full"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen></iframe>
                @else
                    {{-- Clean unavailable state --}}
                    <div class="absolute inset-0 flex flex-col items-center justify-center px-6 text-center">
                        <div class="sx-hero-fallback" aria-hidden="true"></div>
                        <div class="absolute inset-0 bg-black/60" aria-hidden="true"></div>
                        <div class="relative">
                            <div class="player-plate mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full backdrop-blur-sm">
                                <i data-lucide="video-off" class="h-8 w-8 text-white" aria-hidden="true"></i>
                            </div>
                            <h2 class="mb-2 text-xl font-extrabold text-white md:text-2xl">{{ __('media.public.video_unavailable_title') }}</h2>
                            <p class="mx-auto max-w-md text-sm text-white/80">{{ __('media.public.video_unavailable_message') }}</p>
                        </div>
                    </div>
                @endif

                {{-- Next-episode overlay with SVG countdown ring --}}
                @if ($nextEpisode)
                    <div x-show="overlayVisible" x-cloak
                         x-transition:enter="transition ease-out duration-300 motion-reduce:duration-0"
                         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         class="absolute inset-0 z-40 flex items-center justify-center bg-black/90 backdrop-blur-sm">
                        <button type="button" @click="cancelAutoplay()"
                                aria-label="{{ __('media.public.cancel') }}" title="{{ __('media.public.cancel') }}"
                                class="player-plate absolute top-4 end-4 flex h-11 w-11 items-center justify-center rounded-full border border-white/20 text-white backdrop-blur-sm">
                            <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
                        </button>

                        <div class="mx-auto max-w-md px-6 text-center">
                            <h3 class="mb-4 text-xl font-extrabold text-white md:text-2xl">{{ __('media.public.up_next') }}</h3>

                            <div class="mb-5 flex items-center justify-center" x-show="!reducedMotion">
                                <div class="relative h-20 w-20">
                                    <svg class="h-full w-full -rotate-90" viewBox="0 0 96 96" aria-hidden="true">
                                        <circle cx="48" cy="48" r="44" stroke="rgba(255,255,255,0.15)" stroke-width="4" fill="none"/>
                                        <circle cx="48" cy="48" r="44" stroke="url(#countdown-gradient)" stroke-width="4" fill="none"
                                                stroke-linecap="round" stroke-dasharray="276"
                                                :stroke-dashoffset="276 * countdown / countdownTotal"
                                                class="transition-all duration-1000"/>
                                        <defs>
                                            <linearGradient id="countdown-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color: var(--sx-accent)"/>
                                                <stop offset="100%" style="stop-color: var(--sx-green)"/>
                                            </linearGradient>
                                        </defs>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-3xl font-black tabular-nums text-white" x-text="countdown"></span>
                                    </div>
                                </div>
                            </div>

                            <p class="mb-5 text-sm text-white/80" x-show="!reducedMotion">{{ __('media.public.next_episode_starts_in') }}</p>

                            {{-- Next episode card (thumbnail + play) --}}
                            <a href="{{ $nextEpisode['url'] }}" title="{{ $nextEpisode['title'] }}"
                               class="player-plate group/next mb-6 block overflow-hidden rounded-2xl border border-white/15 text-start shadow-2xl backdrop-blur-sm">
                                <div class="relative h-40 sm:h-44">
                                    @if ($nextEpisode['thumbnail'])
                                        <img src="{{ $nextEpisode['thumbnail'] }}" alt="" loading="lazy" decoding="async"
                                             class="absolute inset-0 h-full w-full object-cover">
                                    @else
                                        <div class="sx-hero-fallback"></div>
                                    @endif
                                    <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent" aria-hidden="true"></div>

                                    <span class="absolute inset-0 flex items-center justify-center">
                                        <span class="flex h-14 w-14 items-center justify-center rounded-full shadow-2xl transition-transform duration-300 group-hover/next:scale-110 motion-reduce:transform-none"
                                              style="background: var(--sx-grad)">
                                            <i data-lucide="play" class="ms-1 h-7 w-7 text-white" aria-hidden="true"></i>
                                        </span>
                                    </span>

                                    <span class="absolute bottom-0 start-0 w-full p-4">
                                        <span class="sx-clamp-1 mb-0.5 block text-base font-bold text-white">{{ $nextEpisode['title'] }}</span>
                                        <span class="mb-1 block text-xs text-white/80">{{ $program['title'] }} · {{ __('media.public.episode_label', ['number' => $nextEpisode['number']]) }}</span>
                                        @if ($nextEpisode['duration'])
                                            <span class="inline-flex items-center gap-1 text-xs text-white/70">
                                                <i data-lucide="clock" class="h-3.5 w-3.5" aria-hidden="true"></i><span dir="ltr" class="tabular-nums">{{ $nextEpisode['duration'] }}</span>
                                            </span>
                                        @endif
                                    </span>
                                </div>
                            </a>

                            <div class="flex flex-wrap justify-center gap-3">
                                <a href="{{ $nextEpisode['url'] }}" class="sx-btn is-primary"><i data-lucide="play" aria-hidden="true"></i>{{ __('media.public.play_next') }}</a>
                                <button type="button" @click="cancelAutoplay()" class="sx-btn is-glass">{{ __('media.public.cancel') }}</button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- EPISODE INFO + MORE EPISODES                                 --}}
    {{-- ============================================================ --}}
    <div class="sx-wrap pt-8">
        <div class="sx-reading">
            <div class="min-w-0 grid gap-5 sx-rise">
                <nav aria-label="breadcrumb" class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('public.media') }}" class="sx-chip"><i data-lucide="clapperboard" aria-hidden="true"></i>{{ __('media.public.page_title') }}</a>
                    <a href="{{ $program['url'] }}" class="sx-chip sx-clamp-1" title="{{ $program['title'] }}">{{ $program['title'] }}</a>
                    <span class="sx-chip is-active">{{ __('media.public.episode_label', ['number' => $episode['number']]) }}</span>
                </nav>

                <div>
                    <h1 class="sx-section-title">{{ $episode['title'] }}</h1>
                    <p class="mt-1 text-sm text-[color:var(--sx-muted)]">
                        {{ __('media.public.episode_label', ['number' => $episode['number']]) }}
                        @if ($episode['season'] > 1) · {{ __('media.public.season_label', ['number' => $episode['season']]) }} @endif
                    </p>
                </div>

                {{-- Programme row + actions --}}
                <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
                    <a href="{{ $program['url'] }}" class="group flex min-w-0 items-center gap-3" title="{{ $program['title'] }}">
                        <span class="sx-avatar !h-11 !w-11">
                            @if ($program['poster'])
                                <img src="{{ $program['poster'] }}" alt="" decoding="async" onerror="this.remove()">
                            @endif
                            <i data-lucide="monitor-play" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="sx-clamp-1 block font-bold text-[color:var(--sx-ink)] transition-colors group-hover:text-[color:var(--sx-accent)]">{{ $program['title'] }}</span>
                            <span class="sx-clamp-1 block text-xs text-[color:var(--sx-muted)]">
                                {{ __('media.public.episodes_count', ['count' => $program['episodes_count']]) }}
                                @if ($program['host']) · {{ $program['host'] }} @endif
                            </span>
                        </span>
                    </a>

                    {{-- Save (My List) --}}
                    <button type="button" @click="toggleSave()" :aria-pressed="saved ? 'true' : 'false'" :disabled="savePending"
                            :title="isAuthenticated ? '' : @js(__('media.public.login_to_save'))"
                            class="sx-btn is-primary disabled:opacity-60">
                        <i data-lucide="loader-2" class="animate-spin" x-show="savePending" x-cloak aria-hidden="true"></i>
                        <i data-lucide="bookmark" x-show="!savePending" aria-hidden="true"></i>
                        <span x-text="saved ? @js(__('media.public.saved')) : @js(__('media.public.save'))"></span>
                    </button>

                    <div class="flex flex-wrap items-center gap-2 ms-auto">
                        <button type="button" @click="toggleLike()" :aria-pressed="liked ? 'true' : 'false'" class="sx-btn">
                            <i data-lucide="thumbs-up" :class="liked && 'text-[color:var(--sx-accent)]'" aria-hidden="true"></i>
                            <span x-text="liked ? @js(__('media.public.liked')) : @js(__('media.public.like'))"></span>
                        </button>
                        <button type="button" @click="share()" class="sx-btn">
                            <i data-lucide="share-2" aria-hidden="true"></i>
                            <span x-text="shareCopied ? @js(__('media.public.link_copied')) : @js(__('media.public.share'))"></span>
                        </button>
                    </div>
                </div>

                {{-- Description: facts line + collapsible text --}}
                <div class="sx-panel p-4"
                     :class="showMore ? '' : 'cursor-pointer transition-colors hover:border-[color:var(--sx-line-strong)]'"
                     @click="if (!showMore) { showMore = true; }">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="sx-chip"><i data-lucide="eye" aria-hidden="true"></i><span class="tabular-nums">{{ number_format($episode['views_count']) }}</span> {{ __('media.public.views') }}</span>
                        @if ($program['year'])
                            <span class="sx-chip tabular-nums">{{ $program['year'] }}</span>
                        @endif
                        @if ($episode['duration'])
                            <span class="sx-chip"><i data-lucide="clock" aria-hidden="true"></i><span dir="ltr" class="tabular-nums">{{ $episode['duration'] }}</span></span>
                        @endif
                        <span class="sx-chip">{{ $program['maturity_label'] }}</span>
                        @if ($program['rating'] > 0)
                            <span class="sx-chip"><i data-lucide="star" aria-hidden="true"></i><span class="tabular-nums">{{ number_format($program['rating'], 1) }}</span></span>
                        @endif
                    </div>

                    <p class="whitespace-pre-line leading-loose text-[color:var(--sx-ink-2)]"
                       :class="showMore ? '' : 'sx-clamp-2'">{{ $episode['description'] ?? $program['description'] ?? __('media.public.no_description') }}</p>

                    <button type="button" @click.stop="showMore = !showMore" class="mt-2 text-sm font-bold text-[color:var(--sx-accent)] hover:underline">
                        <span x-text="showMore ? @js(__('media.public.show_less')) : @js(__('media.public.show_more'))"></span>
                    </button>
                </div>

                {{-- Transcript (native disclosure, styled by the system) --}}
                @if (! empty($episode['transcript']))
                    <details class="sx-disclosure">
                        <summary>
                            <span class="flex items-center gap-2"><i data-lucide="align-left" aria-hidden="true"></i>{{ __('media.public.transcript') }}</span>
                            <i data-lucide="chevron-down" class="sx-disclosure-chevron" aria-hidden="true"></i>
                        </summary>
                        <div class="custom-scrollbar max-h-96 space-y-2.5 overflow-y-auto overscroll-contain px-2 pb-3 pt-3">
                            @foreach ($episode['transcript'] as $line)
                                <div class="flex items-start gap-3">
                                    @if ($line['time'])
                                        <span class="sx-chip !min-h-0 !py-0.5 shrink-0 tabular-nums" dir="ltr">{{ $line['time'] }}</span>
                                    @endif
                                    <p class="leading-loose text-[color:var(--sx-ink-2)]">{{ $line['text'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>

            {{-- Sticky "more episodes" queue --}}
            <aside class="sx-aside">
                <div class="sx-surface p-5">
                    <h2 class="mb-4 flex items-center gap-2 font-extrabold text-[color:var(--sx-ink)]">
                        <i data-lucide="list-video" class="h-5 w-5 text-[color:var(--sx-accent)]" aria-hidden="true"></i>{{ __('media.public.more_episodes') }}
                    </h2>

                    @if (empty($moreEpisodes))
                        <p class="text-sm text-[color:var(--sx-muted)]">{{ __('media.public.no_episodes_message') }}</p>
                    @else
                        <ol class="sx-video-queue" aria-label="{{ __('media.public.more_episodes') }}">
                            @foreach ($moreEpisodes as $other)
                                <li>
                                    <a href="{{ $other['url'] }}" title="{{ $other['title'] }}"
                                       class="group grid grid-cols-[auto_minmax(0,1fr)] items-start gap-3 p-2.5 transition-colors hover:bg-[color:var(--sx-surface-2)]">
                                        <span class="relative aspect-video w-24 shrink-0 overflow-hidden rounded-lg" style="background: var(--sx-grad)">
                                            @if ($other['thumbnail'])
                                                <img src="{{ $other['thumbnail'] }}" alt="" loading="lazy" decoding="async"
                                                     class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105 motion-reduce:transform-none">
                                            @else
                                                <span class="absolute inset-0 grid place-items-center opacity-70">
                                                    <i data-lucide="video-off" class="h-5 w-5 text-white" aria-hidden="true"></i>
                                                </span>
                                            @endif
                                            @if ($other['duration'])
                                                <span class="absolute bottom-1 end-1 rounded bg-black/80 px-1.5 py-0.5 text-[10px] text-white">
                                                    <span dir="ltr" class="tabular-nums">{{ $other['duration'] }}</span>
                                                </span>
                                            @endif
                                            @if (($other['progress_percent'] ?? 0) > 0)
                                                {{-- Track reads on any thumbnail; matches the duration pill above. --}}
                                                <span class="absolute inset-x-0 bottom-0 h-0.5 bg-black/50"
                                                      role="img" aria-label="{{ $other['progress_percent'] }}% {{ __('media.public.complete') }}">
                                                    <span class="block h-full" style="width: {{ $other['progress_percent'] }}%; background: var(--sx-grad)"></span>
                                                </span>
                                            @endif
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-xs font-bold tabular-nums text-[color:var(--sx-muted)]">{{ __('media.public.episode_label', ['number' => $other['number']]) }}</span>
                                            <span class="sx-clamp-2 block text-sm font-bold text-[color:var(--sx-ink)] transition-colors group-hover:text-[color:var(--sx-accent)]">{{ $other['title'] }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function episodePlayer() {
        return {
            episodeKey: 'media_ep_{{ $episode['id'] }}',
            episodeId: {{ (int) $episode['id'] }},
            isAuthenticated: @json(auth()->check()),
            loginUrl: @js(route('public.login')),
            progressUrl: @js(route('public.media.progress')),
            csrf: @js(csrf_token()),
            resumeSeconds: {{ (int) ($resumeSeconds ?? 0) }},
            lastBeat: 0,
            completedSent: false,
            programId: {{ (int) $program['id'] }},
            storeUrl: @js(route('public.favorites.store')),
            destroyUrl: @js(route('public.favorites.destroy')),
            liked: false,
            saved: @json($programIsSaved ?? false),
            savePending: false,
            shareCopied: false,
            showMore: false,
            overlayVisible: false,
            // "Up next" opens during the closing seconds of playback; the countdown
            // follows the video clock, so pausing the video pauses the countdown too.
            countdownTotal: 5,
            countdown: 5,
            overlayDismissed: false,
            reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            hasNext: @json($nextEpisode !== null),
            nextUrl: @js($nextEpisode['url'] ?? null),

            // ---- Custom player state (YouTube IFrame SDK behind our own UI) ----
            videoId: @js($episode['video_id'] ?? null),
            yt: null,
            started: false,
            _autoUnmute: false,
            playing: false,
            buffering: false,
            playerError: false,
            current: 0,
            duration: 0,
            volume: 100,
            muted: false,
            rate: 1,
            speedOpen: false,
            fullscreen: false,
            controlsVisible: true,
            hideControlsTimer: null,
            tickTimer: null,

            init() {
                try {
                    this.liked = localStorage.getItem(this.episodeKey + '_liked') === '1';
                } catch (e) { /* storage unavailable */ }
                document.addEventListener('fullscreenchange', () => {
                    this.fullscreen = document.fullscreenElement !== null;
                    this.refreshIcons();
                });
                // Warm the YouTube API while the visitor looks at the poster, so
                // the play click boots the player in one hop instead of first
                // waiting on the SDK download.
                if (this.videoId) {
                    this.loadYouTubeApi().catch(() => { /* retried on click */ });
                }
                // Last-chance beat when the visitor leaves mid-episode.
                window.addEventListener('pagehide', () => this.sendProgress(false, true));
            },

            // ---- Playback bootstrap: the player itself loads on this click ----
            async startPlayback() {
                if (!this.videoId || this.started) return;
                this.started = true;
                this.buffering = true;
                this.refreshIcons();

                try {
                    await this.loadYouTubeApi();
                    this.yt = new YT.Player('episode-player', {
                        videoId: this.videoId,
                        playerVars: {
                            // The player only exists because the visitor clicked the
                            // poster's play button — start immediately, don't make
                            // them click YouTube's own play button a second time.
                            autoplay: 1,
                            controls: 0,       // our control bar only
                            rel: 0,
                            fs: 0,
                            disablekb: 1,
                            playsinline: 1,
                            iv_load_policy: 3,
                            modestbranding: 1,
                            origin: window.location.origin,
                        },
                        events: {
                            onReady: (e) => {
                                this.duration = e.target.getDuration() || 0;
                                this.volume = e.target.getVolume();
                                this.muted = e.target.isMuted();
                                // Resume from the saved position — but not for the
                                // opening seconds or the closing stretch, where a
                                // restart is what the visitor actually wants.
                                if (this.resumeSeconds > 10
                                    && this.duration > 0
                                    && this.resumeSeconds < this.duration * 0.95) {
                                    e.target.seekTo(this.resumeSeconds, true);
                                }
                                // Belt & braces with autoplay=1: some browsers ignore the
                                // playerVar even for gesture-initiated embeds.
                                e.target.playVideo();
                                // Strict autoplay policies can swallow that play call.
                                // Fall back to a muted start (always allowed) and
                                // restore the sound once PLAYING arrives.
                                setTimeout(() => {
                                    const st = e.target.getPlayerState();
                                    if (st !== YT.PlayerState.PLAYING && st !== YT.PlayerState.BUFFERING) {
                                        this._autoUnmute = true;
                                        this.muted = true;
                                        e.target.mute();
                                        e.target.playVideo();
                                    }
                                }, 1500);
                                // buffering stays true until PLAYING — the spinner,
                                // not a paused overlay, covers the boot gap.
                                this.refreshIcons();
                            },
                            onStateChange: (e) => this.onPlayerState(e),
                            onError: () => { this.playerError = true; this.buffering = false; this.refreshIcons(); },
                        },
                    });
                } catch (e) {
                    this.playerError = true;
                    this.buffering = false;
                    this.refreshIcons();
                }
            },

            loadYouTubeApi() {
                return new Promise((resolve, reject) => {
                    if (window.YT && window.YT.Player) { resolve(); return; }
                    const timeout = setTimeout(() => reject(new Error('yt_api_timeout')), 8000);
                    const previous = window.onYouTubeIframeAPIReady;
                    window.onYouTubeIframeAPIReady = () => {
                        if (previous) previous();
                        clearTimeout(timeout);
                        resolve();
                    };
                    if (!document.querySelector('script[src*="youtube.com/iframe_api"]')) {
                        const tag = document.createElement('script');
                        tag.src = 'https://www.youtube.com/iframe_api';
                        document.head.appendChild(tag);
                    }
                });
            },

            onPlayerState(e) {
                if (e.data === YT.PlayerState.PLAYING) {
                    if (this._autoUnmute) {
                        // We only muted to get past the autoplay policy — the
                        // visitor's click asked for sound.
                        this._autoUnmute = false;
                        this.yt.unMute();
                        this.muted = false;
                    }
                    this.playing = true;
                    this.buffering = false;
                    this.duration = this.yt.getDuration() || this.duration;
                    this.startTick();
                    this.wakeControls();
                } else if (e.data === YT.PlayerState.PAUSED) {
                    this.playing = false;
                    this.buffering = false;
                    this.stopTick();
                    this.controlsVisible = true;
                    this.sendProgress();
                } else if (e.data === YT.PlayerState.BUFFERING) {
                    this.buffering = true;
                } else if (e.data === YT.PlayerState.ENDED) {
                    this.playing = false;
                    this.buffering = false;
                    this.stopTick();
                    this.current = this.duration;
                    // Beacon so the completed flag survives the auto-advance navigation.
                    this.sendProgress(true, true);
                    if (this.hasNext && !this.overlayDismissed) {
                        if (this.reducedMotion) {
                            // No auto-advance — the overlay stays for an explicit click.
                            this.countdown = 0;
                            this.showOverlay();
                        } else {
                            window.location.href = this.nextUrl;
                        }
                    }
                }
                this.refreshIcons();
            },

            startTick() {
                this.stopTick();
                this.tickTimer = setInterval(() => {
                    if (!this.yt || typeof this.yt.getCurrentTime !== 'function') return;
                    this.current = this.yt.getCurrentTime() || 0;
                    if (!this.duration) this.duration = this.yt.getDuration() || 0;
                    this.syncUpNextOverlay();
                    // Progress beat: at most one POST per 15s of active playback;
                    // the closing 5% counts as watched (Netflix-style completion).
                    if (this.playing && this.duration && this.current / this.duration >= 0.95) {
                        this.sendProgress(true);
                    } else if (this.playing && Date.now() - this.lastBeat > 15000) {
                        this.sendProgress();
                    }
                }, 500);
            },

            // Persist the watch position (guests: no-op). Once completed has been
            // sent, plain beats stop — they would flip the row back to in-progress.
            sendProgress(completed = false, useBeacon = false) {
                if (!this.isAuthenticated || !this.started || this.playerError) return;
                if (this.completedSent && !completed) return;
                if (completed) {
                    if (this.completedSent) return;
                    this.completedSent = true;
                }
                this.lastBeat = Date.now();

                const body = new URLSearchParams({
                    _token: this.csrf,
                    episode_id: this.episodeId,
                    seconds: Math.max(0, Math.floor(completed ? (this.duration || this.current) : this.current)),
                    completed: completed ? 1 : 0,
                });
                // sendBeacon can't set headers — CSRF travels as the _token field.
                if (useBeacon && navigator.sendBeacon) {
                    navigator.sendBeacon(this.progressUrl, body);
                    return;
                }
                fetch(this.progressUrl, { method: 'POST', body, keepalive: true }).catch(() => {});
            },

            // Show "up next" during the last seconds of the episode; hide it again
            // if the visitor seeks back out of the closing window.
            syncUpNextOverlay() {
                if (!this.hasNext || this.overlayDismissed || !this.duration) return;
                const remaining = this.duration - this.current;

                if (remaining <= this.countdownTotal && remaining >= 0) {
                    this.countdown = Math.max(0, Math.ceil(remaining));
                    if (!this.overlayVisible) this.showOverlay();
                } else if (this.overlayVisible) {
                    this.overlayVisible = false;
                    this.countdown = this.countdownTotal;
                }
            },
            stopTick() { clearInterval(this.tickTimer); this.tickTimer = null; },

            togglePlay() {
                if (!this.yt || this.playerError) return;
                this.playing ? this.yt.pauseVideo() : this.yt.playVideo();
            },

            seek(value) {
                if (!this.yt) return;
                this.current = Number(value);
                this.yt.seekTo(this.current, true);
            },

            skip(seconds) {
                if (!this.yt) return;
                const target = Math.min(Math.max(0, this.current + seconds), this.duration || Infinity);
                this.seek(target);
                this.wakeControls();
            },

            setRate(rate) {
                if (!this.yt) return;
                this.rate = rate;
                this.yt.setPlaybackRate(rate);
                this.speedOpen = false;
                this.wakeControls();
            },

            toggleMute() {
                if (!this.yt) return;
                this.muted = !this.muted;
                this.muted ? this.yt.mute() : this.yt.unMute();
                this.refreshIcons();
            },

            setVolume(value) {
                if (!this.yt) return;
                this.volume = Number(value);
                this.muted = this.volume === 0;
                this.yt.setVolume(this.volume);
                if (!this.muted) this.yt.unMute();
                this.refreshIcons();
            },

            toggleFullscreen() {
                const shell = this.$refs.shell;
                if (!shell) return;
                if (document.fullscreenElement) {
                    document.exitFullscreen();
                } else if (shell.requestFullscreen) {
                    shell.requestFullscreen();
                }
            },

            wakeControls() {
                this.controlsVisible = true;
                clearTimeout(this.hideControlsTimer);
                if (!this.playing) return;
                this.hideControlsTimer = setTimeout(() => { this.controlsVisible = false; }, 2500);
            },

            fmt(seconds) {
                seconds = Math.max(0, Math.floor(seconds || 0));
                const h = Math.floor(seconds / 3600);
                const m = Math.floor((seconds % 3600) / 60);
                const s = String(seconds % 60).padStart(2, '0');
                return h > 0 ? `${h}:${String(m).padStart(2, '0')}:${s}` : `${m}:${s}`;
            },

            toggleLike() {
                this.liked = !this.liked;
                try { localStorage.setItem(this.episodeKey + '_liked', this.liked ? '1' : '0'); } catch (e) {}
            },

            // Saves the parent program to the visitor's bookmarks (My List).
            async toggleSave() {
                if (!this.isAuthenticated) {
                    window.location.href = this.loginUrl;
                    return;
                }
                if (this.savePending) return;

                const adding = !this.saved;
                this.saved = adding; // optimistic
                this.savePending = true;
                this.refreshIcons();

                try {
                    const response = await fetch(adding ? this.storeUrl : this.destroyUrl, {
                        method: adding ? 'POST' : 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ type: 'media', id: this.programId }),
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok || payload.success === false) throw new Error('favorite_failed');
                    if (this.$store.toast && payload.message) this.$store.toast.push(payload.message, 'success');
                } catch (e) {
                    this.saved = !adding; // roll back
                    if (this.$store.toast) this.$store.toast.push(@js(__('media.public.my_list_error')), 'error');
                } finally {
                    this.savePending = false;
                    this.refreshIcons();
                }
            },

            refreshIcons() {
                this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
            },

            async share() {
                const payload = { title: document.title, url: window.location.href };
                if (navigator.share) {
                    try { await navigator.share(payload); return; } catch (e) { /* dismissed */ }
                }
                try {
                    await navigator.clipboard.writeText(window.location.href);
                    this.shareCopied = true;
                    setTimeout(() => { this.shareCopied = false; }, 2000);
                } catch (e) { /* clipboard unavailable */ }
            },

            showOverlay() {
                this.overlayVisible = true;
                this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
            },

            cancelAutoplay() {
                this.overlayDismissed = true; // don't re-open or auto-advance for this view
                this.overlayVisible = false;
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush
@endsection
