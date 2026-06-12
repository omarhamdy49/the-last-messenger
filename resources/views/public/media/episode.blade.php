@extends('layouts.public')

@section('title', $episode['seo_title'] ?: $episode['title'] . ' — ' . $program['title'])
@section('meta_description', $episode['seo_description'] ?: \Illuminate\Support\Str::limit(strip_tags((string) ($episode['description'] ?? $program['description'])), 160))

@if($episode['seo_keywords'])
@push('head')
    <meta name="keywords" content="{{ $episode['seo_keywords'] }}">
@endpush
@endif

@section('content')
@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<div x-data="episodePlayer()" x-init="init()"
     class="min-h-screen font-['Cairo'] bg-gray-50 dark:bg-gray-950 transition-colors duration-300">

    {{-- ============================================================ --}}
    {{-- PLAYER — responsive 16:9 (or unavailable state)              --}}
    {{-- ============================================================ --}}
    <section class="bg-black">
        <div class="container mx-auto lg:px-8">
            <div class="relative w-full aspect-video max-h-[80vh] overflow-hidden">
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

                        {{-- Poster facade (pre-play): episode thumbnail + brand play button --}}
                        <div x-show="!started" class="absolute inset-0 z-20">
                            @if ($episode['thumbnail'])
                                <img src="{{ $episode['thumbnail'] }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                            @else
                                <div class="absolute inset-0 bg-gradient-to-br {{ $program['gradient'] }}"></div>
                            @endif
                            <div class="absolute inset-0 bg-black/40" aria-hidden="true"></div>
                            <button type="button" @click="startPlayback()"
                                    aria-label="{{ __('media.public.player_play') }}"
                                    class="absolute inset-0 flex items-center justify-center group/start">
                                <span class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-2xl flex items-center justify-center group-hover/start:scale-110 motion-reduce:transform-none transition-transform duration-300">
                                    <i data-lucide="play" class="w-8 h-8 sm:w-10 sm:h-10 text-white ms-1"></i>
                                </span>
                            </button>
                        </div>

                        {{-- Click-capture veil: owns every click over the video, doubles
                             as the pause/buffering overlay so no embed UI ever shows --}}
                        <div x-show="started" x-cloak
                             @click="togglePlay()" @dblclick="toggleFullscreen()"
                             class="absolute inset-0 z-10 transition-colors duration-200"
                             :class="!playing && !buffering ? 'bg-black/60' : ''">
                            <div x-show="buffering" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <i data-lucide="loader-2" class="w-12 h-12 text-white animate-spin"></i>
                            </div>
                            <div x-show="!playing && !buffering && !overlayVisible" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <span class="w-16 h-16 rounded-full bg-white/90 flex items-center justify-center shadow-2xl">
                                    <i data-lucide="play" class="w-8 h-8 text-gray-900 ms-1"></i>
                                </span>
                            </div>
                            <div x-show="playerError" class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-black/80 pointer-events-none px-6 text-center">
                                <i data-lucide="alert-triangle" class="w-8 h-8 text-amber-400"></i>
                                <p class="text-sm text-gray-200">{{ __('media.public.player_error') }}</p>
                            </div>
                        </div>

                        {{-- Custom control bar --}}
                        <div x-show="started && !playerError" x-cloak dir="ltr"
                             class="absolute inset-x-0 bottom-0 z-30 px-3 sm:px-4 pb-2 sm:pb-3 pt-12 bg-gradient-to-t from-black/90 via-black/50 to-transparent transition-opacity duration-200 motion-reduce:transition-none"
                             :class="controlsVisible || !playing ? 'opacity-100' : 'opacity-0 pointer-events-none'">

                            {{-- Seek bar --}}
                            <input type="range" min="0" :max="duration || 0" step="0.5" :value="current"
                                   @input="seek($event.target.value)"
                                   aria-label="{{ __('media.public.player_seek') }}"
                                   class="player-range w-full block"
                                   :style="`--progress: ${duration ? (current / duration * 100) : 0}%`">

                            <div class="flex items-center gap-1 sm:gap-2 mt-1.5 text-white">
                                {{-- Rewind 10s --}}
                                <button type="button" @click="skip(-10)"
                                        aria-label="{{ __('media.public.player_rewind') }}"
                                        class="relative w-11 h-11 sm:w-10 sm:h-10 rounded-full hover:bg-white/15 flex items-center justify-center transition-colors">
                                    <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                                    <span class="absolute inset-0 flex items-center justify-center text-[8px] font-bold pt-0.5 pointer-events-none select-none">10</span>
                                </button>

                                {{-- Play / pause --}}
                                <button type="button" @click="togglePlay()"
                                        :aria-label="playing ? @js(__('media.public.player_pause')) : @js(__('media.public.player_play'))"
                                        class="w-11 h-11 sm:w-10 sm:h-10 rounded-full hover:bg-white/15 flex items-center justify-center transition-colors">
                                    <i data-lucide="play" class="w-5 h-5 ms-0.5" x-show="!playing"></i>
                                    <i data-lucide="pause" class="w-5 h-5" x-show="playing" x-cloak></i>
                                </button>

                                {{-- Forward 10s --}}
                                <button type="button" @click="skip(10)"
                                        aria-label="{{ __('media.public.player_forward') }}"
                                        class="relative w-11 h-11 sm:w-10 sm:h-10 rounded-full hover:bg-white/15 flex items-center justify-center transition-colors">
                                    <i data-lucide="rotate-cw" class="w-5 h-5"></i>
                                    <span class="absolute inset-0 flex items-center justify-center text-[8px] font-bold pt-0.5 pointer-events-none select-none">10</span>
                                </button>

                                {{-- Mute + volume --}}
                                <button type="button" @click="toggleMute()"
                                        :aria-label="muted ? @js(__('media.public.player_unmute')) : @js(__('media.public.player_mute'))"
                                        class="w-11 h-11 sm:w-10 sm:h-10 rounded-full hover:bg-white/15 flex items-center justify-center transition-colors">
                                    <i data-lucide="volume-2" class="w-5 h-5" x-show="!muted"></i>
                                    <i data-lucide="volume-x" class="w-5 h-5" x-show="muted" x-cloak></i>
                                </button>
                                <input type="range" min="0" max="100" step="1" :value="muted ? 0 : volume"
                                       @input="setVolume($event.target.value)"
                                       aria-label="{{ __('media.public.player_volume') }}"
                                       class="player-range hidden sm:block w-20"
                                       :style="`--progress: ${muted ? 0 : volume}%`">

                                {{-- Time --}}
                                <span class="text-[11px] sm:text-xs font-medium tabular-nums text-gray-200 select-none">
                                    <span x-text="fmt(current)">0:00</span>
                                    <span class="text-gray-400"> / </span>
                                    <span x-text="fmt(duration)">0:00</span>
                                </span>

                                {{-- Playback speed --}}
                                <div class="relative ml-auto" @click.away="speedOpen = false">
                                    <button type="button" @click="speedOpen = !speedOpen; refreshIcons()"
                                            :aria-expanded="speedOpen ? 'true' : 'false'"
                                            aria-label="{{ __('media.public.player_speed') }}"
                                            class="h-11 sm:h-10 px-2.5 sm:px-3 rounded-full hover:bg-white/15 flex items-center justify-center gap-1 transition-colors">
                                        <i data-lucide="gauge" class="w-4 h-4 hidden sm:block"></i>
                                        <span class="text-xs font-bold tabular-nums" x-text="rate === 1 ? '1x' : rate + 'x'">1x</span>
                                    </button>
                                    <div x-show="speedOpen" x-cloak
                                         x-transition:enter="transition ease-out duration-150 motion-reduce:duration-0"
                                         x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                         class="absolute bottom-full right-0 mb-2 w-36 rounded-xl bg-black/90 backdrop-blur-sm border border-white/10 py-1.5 shadow-2xl z-50">
                                        <p class="px-3 py-1 text-[11px] font-semibold text-gray-400">{{ __('media.public.player_speed') }}</p>
                                        <template x-for="option in [0.5, 0.75, 1, 1.25, 1.5, 2]" :key="option">
                                            <button type="button" @click="setRate(option)"
                                                    class="w-full flex items-center justify-between px-3 py-1.5 text-xs font-semibold hover:bg-white/10 transition-colors"
                                                    :class="rate === option ? 'text-[#64CCC5]' : 'text-gray-200'">
                                                <span x-text="option === 1 ? @js(__('media.public.player_speed_normal')) : option + 'x'"></span>
                                                <span x-show="rate === option" aria-hidden="true">✓</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                {{-- Fullscreen --}}
                                <button type="button" @click="toggleFullscreen()"
                                        :aria-label="fullscreen ? @js(__('media.public.player_exit_fullscreen')) : @js(__('media.public.player_fullscreen'))"
                                        class="w-11 h-11 sm:w-10 sm:h-10 rounded-full hover:bg-white/15 flex items-center justify-center transition-colors">
                                    <i data-lucide="maximize" class="w-5 h-5" x-show="!fullscreen"></i>
                                    <i data-lucide="minimize" class="w-5 h-5" x-show="fullscreen" x-cloak></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @elseif ($episode['has_video'])
                    {{-- Non-YouTube URL — plain embed fallback --}}
                    <iframe src="{{ $episode['video_url'] }}"
                            title="{{ $episode['title'] }}"
                            class="absolute inset-0 w-full h-full"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen></iframe>
                @else
                    {{-- Clean unavailable state --}}
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-6 bg-gradient-to-br {{ $program['gradient'] }}">
                        <div class="absolute inset-0 bg-black/60" aria-hidden="true"></div>
                        <div class="relative">
                            <div class="w-16 h-16 mx-auto rounded-full bg-white/10 backdrop-blur-sm flex items-center justify-center mb-4">
                                <i data-lucide="video-off" class="w-8 h-8 text-white"></i>
                            </div>
                            <h2 class="text-xl md:text-2xl font-bold text-white mb-2">{{ __('media.public.video_unavailable_title') }}</h2>
                            <p class="text-sm text-gray-300 max-w-md mx-auto">{{ __('media.public.video_unavailable_message') }}</p>
                        </div>
                    </div>
                @endif

                {{-- Next-episode overlay with SVG countdown ring --}}
                @if ($nextEpisode)
                    <div x-show="overlayVisible" x-cloak
                         x-transition:enter="transition ease-out duration-300 motion-reduce:duration-0"
                         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         class="absolute inset-0 z-40 bg-black/90 backdrop-blur-sm flex items-center justify-center">
                        <button type="button" @click="cancelAutoplay()"
                                aria-label="{{ __('media.public.cancel') }}"
                                class="absolute top-4 end-4 w-10 h-10 glass rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>

                        <div class="max-w-md mx-auto px-6 text-center">
                            <h3 class="text-xl md:text-2xl font-bold text-white mb-4">{{ __('media.public.up_next') }}</h3>

                            <div class="mb-5 flex items-center justify-center" x-show="!reducedMotion">
                                <div class="relative w-20 h-20">
                                    <svg class="w-full h-full -rotate-90" viewBox="0 0 96 96" aria-hidden="true">
                                        <circle cx="48" cy="48" r="44" stroke="rgba(255,255,255,0.15)" stroke-width="4" fill="none"/>
                                        <circle cx="48" cy="48" r="44" stroke="url(#countdown-gradient)" stroke-width="4" fill="none"
                                                stroke-linecap="round" stroke-dasharray="276"
                                                :stroke-dashoffset="276 - (276 * (10 - countdown) / 10)"
                                                class="transition-all duration-1000"/>
                                        <defs>
                                            <linearGradient id="countdown-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#0056b3"/>
                                                <stop offset="100%" style="stop-color:#218838"/>
                                            </linearGradient>
                                        </defs>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-3xl font-black text-white" x-text="countdown"></span>
                                    </div>
                                </div>
                            </div>

                            <p class="text-sm text-gray-300 mb-5" x-show="!reducedMotion">{{ __('media.public.next_episode_starts_in') }}</p>

                            <p class="text-base font-bold text-white mb-1 line-clamp-2">{{ $nextEpisode['title'] }}</p>
                            <p class="text-xs text-gray-400 mb-6">{{ $program['title'] }} · {{ __('media.public.episode_label', ['number' => $nextEpisode['number']]) }}</p>

                            <div class="flex flex-wrap gap-3 justify-center">
                                <a href="{{ $nextEpisode['url'] }}"
                                   class="inline-flex items-center gap-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold px-7 py-3 rounded-full hover:scale-105 motion-reduce:transform-none transition-all shadow-lg">
                                    <i data-lucide="play" class="w-4 h-4"></i>{{ __('media.public.play_next') }}
                                </a>
                                <button type="button" @click="cancelAutoplay()"
                                        class="glass text-white font-bold px-7 py-3 rounded-full hover:bg-white/20 transition-all">
                                    {{ __('media.public.cancel') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- EPISODE INFO + SIDEBAR                                       --}}
    {{-- ============================================================ --}}
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 min-w-0">
                {{-- Title & meta --}}
                <nav class="mb-4" aria-label="breadcrumb">
                    <a href="{{ $program['url'] }}"
                       class="inline-flex items-center gap-2 text-sm font-semibold text-[#0056b3] dark:text-[#64CCC5] hover:underline">
                        <i data-lucide="{{ $isRtl ? 'arrow-right' : 'arrow-left' }}" class="w-4 h-4"></i>
                        {{ __('media.public.back_to_program') }}
                    </a>
                </nav>

                {{-- Title (YouTube-style) --}}
                <div class="opacity-0 animate-fade-in-up">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mb-1 break-words leading-snug">{{ $episode['title'] }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        {{ __('media.public.episode_label', ['number' => $episode['number']]) }}
                        @if ($episode['season'] > 1) · {{ __('media.public.season_label', ['number' => $episode['season']]) }} @endif
                    </p>
                </div>

                {{-- Channel-style program row + actions (YouTube-style) --}}
                <div class="flex flex-wrap items-center gap-x-4 gap-y-3 mb-5 opacity-0 animate-fade-in-up stagger-1">
                    <a href="{{ $program['url'] }}" class="flex items-center gap-3 min-w-0 group">
                        <span class="w-10 h-10 sm:w-11 sm:h-11 shrink-0 bg-gradient-to-br {{ $program['gradient'] }} rounded-full flex items-center justify-center overflow-hidden ring-1 ring-black/5 dark:ring-white/10">
                            @if ($program['poster'])
                                <img src="{{ $program['poster'] }}" alt="" class="w-full h-full object-cover">
                            @else
                                <i data-lucide="monitor-play" class="w-5 h-5 text-white"></i>
                            @endif
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-bold text-gray-900 dark:text-white truncate group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors">{{ $program['title'] }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ __('media.public.episodes_count', ['count' => $program['episodes_count']]) }}
                                @if ($program['host']) · {{ $program['host'] }} @endif
                            </span>
                        </span>
                    </a>

                    {{-- Save (My List) — subscribe-style pill --}}
                    <button type="button" @click="toggleSave()" :aria-pressed="saved ? 'true' : 'false'"
                            :disabled="savePending"
                            :title="isAuthenticated ? '' : @js(__('media.public.login_to_save'))"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-semibold transition-all disabled:opacity-60
                                   bg-gray-900 text-white hover:bg-gray-700 dark:bg-[#ffffff] dark:text-gray-900 dark:hover:bg-gray-200">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="savePending" x-cloak></i>
                        <i data-lucide="bookmark" class="w-4 h-4" x-show="!savePending"></i>
                        <span x-text="saved ? @js(__('media.public.saved')) : @js(__('media.public.save'))"></span>
                    </button>

                    <div class="flex flex-wrap items-center gap-2 ms-auto">
                        <button type="button" @click="toggleLike()" :aria-pressed="liked ? 'true' : 'false'"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold text-gray-800 dark:text-gray-200 bg-gray-100 hover:bg-gray-200 dark:bg-white/10 dark:hover:bg-white/20 transition-colors">
                            <i data-lucide="thumbs-up" class="w-4 h-4" :class="liked ? 'text-[#64CCC5]' : ''"></i>
                            <span x-text="liked ? @js(__('media.public.liked')) : @js(__('media.public.like'))"></span>
                        </button>
                        <button type="button" @click="share()"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold text-gray-800 dark:text-gray-200 bg-gray-100 hover:bg-gray-200 dark:bg-white/10 dark:hover:bg-white/20 transition-colors">
                            <i data-lucide="share-2" class="w-4 h-4"></i>
                            <span x-text="shareCopied ? @js(__('media.public.link_copied')) : @js(__('media.public.share'))"></span>
                        </button>
                    </div>
                </div>

                {{-- Description box (YouTube-style): views/meta line + collapsible text --}}
                <div class="rounded-xl bg-gray-100 dark:bg-white/10 p-3 sm:p-4 mb-5 opacity-0 animate-fade-in-up stagger-2"
                     :class="showMore ? '' : 'cursor-pointer hover:bg-gray-200/70 dark:hover:bg-white/15 transition-colors'"
                     @click="if (!showMore) { showMore = true; }">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                        <span>{{ number_format($episode['views_count']) }} {{ __('media.public.views') }}</span>
                        @if ($program['year'])<span aria-hidden="true">·</span><span>{{ $program['year'] }}</span>@endif
                        @if ($episode['duration'])<span aria-hidden="true">·</span><span>{{ $episode['duration'] }}</span>@endif
                        <span aria-hidden="true">·</span><span>{{ $program['maturity_label'] }}</span>
                        @if ($program['rating'] > 0)
                            <span aria-hidden="true">·</span>
                            <span class="inline-flex items-center gap-1"><i data-lucide="star" class="w-3.5 h-3.5 text-yellow-500"></i>{{ number_format($program['rating'], 1) }}</span>
                        @endif
                    </p>

                    <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 break-words whitespace-pre-line"
                       :class="showMore ? '' : 'line-clamp-2'">{{ $episode['description'] ?? $program['description'] ?? __('media.public.no_description') }}</p>

                    <button type="button" @click.stop="showMore = !showMore"
                            class="mt-2 text-sm font-semibold text-gray-900 dark:text-white hover:underline">
                        <span x-text="showMore ? @js(__('media.public.show_less')) : @js(__('media.public.show_more'))"></span>
                    </button>
                </div>

                {{-- Transcript (YouTube-style, collapsible) --}}
                @if (! empty($episode['transcript']))
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm mb-6 opacity-0 animate-fade-in-up stagger-3">
                        <button type="button" @click="transcriptOpen = !transcriptOpen; refreshIcons()"
                                :aria-expanded="transcriptOpen ? 'true' : 'false'"
                                class="w-full flex items-center justify-between gap-3 p-4 text-start">
                            <span class="flex items-center gap-2 text-base font-bold text-gray-900 dark:text-white">
                                <i data-lucide="align-left" class="w-5 h-5 text-[#0056b3] dark:text-[#64CCC5]"></i>
                                {{ __('media.public.transcript') }}
                            </span>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-gray-500 transition-transform duration-200 motion-reduce:transition-none"
                               :class="transcriptOpen ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="transcriptOpen" x-cloak
                             x-transition:enter="transition ease-out duration-200 motion-reduce:duration-0"
                             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                             class="px-4 pb-4 border-t border-gray-100 dark:border-gray-800">
                            <div class="max-h-96 overflow-y-auto custom-scrollbar space-y-2.5 pt-4 pe-2">
                                @foreach ($episode['transcript'] as $line)
                                    <div class="flex items-start gap-3">
                                        @if ($line['time'])
                                            <span class="shrink-0 text-xs font-semibold tabular-nums text-[#0056b3] dark:text-[#64CCC5] bg-[#0056b3]/10 dark:bg-[#64CCC5]/10 px-2 py-0.5 rounded-md" dir="ltr">{{ $line['time'] }}</span>
                                        @endif
                                        <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 break-words">{{ $line['text'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Sticky "more episodes" sidebar --}}
            <aside class="lg:col-span-1">
                <div class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm p-5 lg:sticky lg:top-20 opacity-0 animate-fade-in-up stagger-2">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i data-lucide="list-video" class="w-5 h-5 text-[#0056b3] dark:text-[#64CCC5]"></i>
                        {{ __('media.public.more_episodes') }}
                    </h3>

                    @if (empty($moreEpisodes))
                        <x-site.empty-state icon="list-video"
                            :title="__('media.public.no_episodes_title')"
                            :message="__('media.public.no_episodes_message')" class="!py-8" />
                    @else
                        <div class="space-y-2 max-h-[600px] overflow-y-auto scrollbar-hide">
                            @foreach ($moreEpisodes as $other)
                                <a href="{{ $other['url'] }}"
                                   class="flex gap-3 hover:bg-gray-50 dark:hover:bg-white/5 rounded-xl p-2 transition-colors group">
                                    <div class="relative w-2/5 shrink-0 rounded-lg overflow-hidden aspect-video bg-gradient-to-br {{ $program['gradient'] }}">
                                        @if ($other['thumbnail'])
                                            <img src="{{ $other['thumbnail'] }}" alt="" loading="lazy"
                                                 class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 motion-reduce:transform-none transition-transform duration-300">
                                        @else
                                            <div class="absolute inset-0 flex items-center justify-center opacity-40">
                                                <i data-lucide="video-off" class="w-5 h-5 text-white"></i>
                                            </div>
                                        @endif
                                        @if ($other['duration'])
                                            <span class="absolute bottom-1 end-1 bg-black/80 text-white text-[10px] px-1.5 py-0.5 rounded">{{ $other['duration'] }}</span>
                                        @endif
                                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/30">
                                            <i data-lucide="play-circle" class="w-7 h-7 text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0 py-0.5">
                                        <h4 class="text-xs font-bold text-gray-900 dark:text-white line-clamp-2 mb-1 group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors">
                                            {{ $other['title'] }}
                                        </h4>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-300">{{ __('media.public.episode_label', ['number' => $other['number']]) }}</p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
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
            isAuthenticated: @json(auth()->check()),
            loginUrl: @js(route('public.login')),
            programId: {{ (int) $program['id'] }},
            storeUrl: @js(route('public.favorites.store')),
            destroyUrl: @js(route('public.favorites.destroy')),
            liked: false,
            saved: @json($programIsSaved ?? false),
            savePending: false,
            shareCopied: false,
            showMore: false,
            transcriptOpen: false,
            overlayVisible: false,
            countdown: 10,
            countdownInterval: null,
            reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            hasNext: @json($nextEpisode !== null),
            nextUrl: @js($nextEpisode['url'] ?? null),

            // ---- Custom player state (YouTube IFrame SDK behind our own UI) ----
            videoId: @js($episode['video_id'] ?? null),
            yt: null,
            started: false,
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
            },

            // ---- Playback bootstrap: nothing YouTube loads before this click ----
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
                                e.target.playVideo();
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
                } else if (e.data === YT.PlayerState.BUFFERING) {
                    this.buffering = true;
                } else if (e.data === YT.PlayerState.ENDED) {
                    this.playing = false;
                    this.buffering = false;
                    this.stopTick();
                    this.current = this.duration;
                    if (this.hasNext) this.showOverlay();
                }
                this.refreshIcons();
            },

            startTick() {
                this.stopTick();
                this.tickTimer = setInterval(() => {
                    if (!this.yt || typeof this.yt.getCurrentTime !== 'function') return;
                    this.current = this.yt.getCurrentTime() || 0;
                    if (!this.duration) this.duration = this.yt.getDuration() || 0;
                }, 500);
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
                if (this.reducedMotion) return; // no auto-countdown — user clicks explicitly
                this.countdown = 10;
                this.countdownInterval = setInterval(() => {
                    this.countdown--;
                    if (this.countdown <= 0) {
                        clearInterval(this.countdownInterval);
                        window.location.href = this.nextUrl;
                    }
                }, 1000);
            },

            cancelAutoplay() {
                clearInterval(this.countdownInterval);
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
