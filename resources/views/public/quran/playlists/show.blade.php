@extends('layouts.public')

@section('title', $playlist->name.' | '.__('quran.playlists.title'))
@section('meta_description', \Illuminate\Support\Str::limit($playlist->description ?: __('quran.playlists.subtitle'), 160))

@push('head')
<style>
    .eq-bars { display: inline-flex; align-items: flex-end; gap: 2px; height: 14px; }
    .eq-bars span { width: 3px; border-radius: 2px; background: currentColor; animation: eqLevel 1s ease-in-out infinite; }
    .eq-bars span:nth-child(2) { animation-delay: .2s; }
    .eq-bars span:nth-child(3) { animation-delay: .4s; }
    @keyframes eqLevel { 0%, 100% { height: 4px; } 50% { height: 14px; } }
    @keyframes plFadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
    .pl-in { animation: plFadeUp .5s cubic-bezier(.16,1,.3,1) both; }
    .pl-in-2 { animation: plFadeUp .5s cubic-bezier(.16,1,.3,1) .12s both; }
    .pl-row { animation: plFadeUp .35s cubic-bezier(.16,1,.3,1) both; animation-delay: calc(var(--i, 0) * 25ms); }
    /* Liquid glass: blur + 1px inner edge + top highlight. */
    .pl-glass { background: rgba(255,255,255,.10); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid rgba(255,255,255,.22); box-shadow: inset 0 1px 0 rgba(255,255,255,.28); }
    .pl-glass-btn { background: rgba(255,255,255,.12); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,.25); }
    .pl-glass-btn:hover { background: rgba(255,255,255,.2); }
    /* Living cover: a second brand gradient (accent → primary → secondary) whose opacity breathes
       while this playlist is playing. Opacity only, so it stays on the compositor. */
    .pl-cover-alt { background: linear-gradient(225deg, #64CCC5 0%, #0056b3 50%, #218838 100%); opacity: 0; transition: opacity 1.2s ease; }
    .pl-cover-alt.pl-breathing { animation: plBreathe 12s ease-in-out infinite; }
    @keyframes plBreathe { 0%, 100% { opacity: 0; } 50% { opacity: .78; } }
    /* Now-playing bar: enters from under the site header */
    .pl-now { transition: transform .35s cubic-bezier(.16,1,.3,1), opacity .35s ease; }
    .pl-now[hidden] { display: none; }
    @media (prefers-reduced-motion: reduce) { .eq-bars span { animation: none; height: 10px; } .pl-in, .pl-in-2, .pl-row { animation: none; } .pl-cover-alt.pl-breathing { animation: none; opacity: .4; } .pl-now { transition: none; } }
</style>
@endpush

@section('content')
@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $num = fn ($n) => app()->getLocale() === 'ar' ? arabic_digits($n) : number_format((int) $n);
    $fmtDur = function (?int $s) use ($num): ?string {
        if ($s === null) { return null; }
        $h = intdiv($s, 3600); $m = intdiv($s % 3600, 60); $r = $s % 60;
        $mm = $h ? str_pad((string) $m, 2, '0', STR_PAD_LEFT) : (string) $m;
        return ($h ? $num($h).':' : '').$num($mm).':'.$num(str_pad((string) $r, 2, '0', STR_PAD_LEFT));
    };
    $reciters = $card['reciters'];
    $shareUrl = route('public.quran.playlists.show', $playlist->slug);
    $playableCount = count(array_filter($tracks, fn ($t) => $t['url']));
    $totalSeconds = array_sum(array_map(fn ($t) => (int) ($t['duration'] ?? 0), $tracks));
    $stats = array_values(array_filter([
        ['value' => $num($card['items']), 'label' => __('quran.playlists.surah_unit')],
        ['value' => $num(count($reciters)), 'label' => __('quran.playlists.reciter_unit')],
        $totalSeconds ? ['value' => $fmtDur($totalSeconds), 'label' => __('quran.playlists.total_duration')] : null,
        ['value' => $num($card['plays']), 'label' => __('quran.playlists.plays')],
    ]));
@endphp

<div class="min-h-dvh font-['Cairo'] bg-gray-50 dark:bg-gray-950 transition-colors duration-300"
     x-data="playlistPage()"
     x-init="init()"
     :style="$store.audioPlayer.track ? 'padding-bottom: 7rem' : ''">
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

    {{-- Breadcrumb --}}
    <nav class="mb-5 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2 flex-wrap" aria-label="breadcrumb">
        <a href="{{ route('public.quran') }}" class="hover:text-primary transition-colors">{{ __('quran.public.page_title') }}</a>
        <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <a href="{{ route('public.quran.playlists.index') }}" class="hover:text-primary transition-colors">{{ __('quran.playlists.title') }}</a>
        <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span class="text-gray-900 dark:text-white font-semibold truncate max-w-[18rem]">{{ $playlist->name }}</span>
    </nav>

    {{-- ============================ HERO: the playlist's own tint, copy | glass collage ============================ --}}
    {{-- No overflow clipping on the header itself: the reciters/more dropdowns must escape it.
         Everything decorative is clipped inside its own layer. --}}
    <header class="relative rounded-3xl shadow-2xl mb-6 sm:mb-8 text-white" x-ref="hero">
        <div class="absolute inset-0 rounded-3xl overflow-hidden pointer-events-none" aria-hidden="true" style="background: linear-gradient(135deg, #0056b3 0%, #116a53 55%, #218838 100%)">
            <div class="pl-cover-alt absolute inset-0" :class="listPlaying ? 'pl-breathing' : ''"></div>
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.4\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            <div class="absolute -top-24 -end-24 w-96 h-96 rounded-full bg-white/10 blur-3xl"></div>
            <span class="absolute -bottom-4 start-6 text-[80px] sm:text-[104px] leading-none font-black tabular-nums text-white/[0.07] select-none">{{ $num($card['items']) }}</span>
        </div>

        <div class="relative grid lg:grid-cols-[1fr_auto] gap-6 lg:gap-12 items-center px-6 sm:px-10 lg:px-12 py-6 sm:py-7">
            {{-- Copy --}}
            <div class="pl-in min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold pl-glass">
                        @if ($playlist->is_public)
                            <i data-lucide="globe" class="w-3.5 h-3.5"></i>{{ __('quran.playlists.public') }}
                        @else
                            <i data-lucide="lock" class="w-3.5 h-3.5"></i>{{ __('quran.playlists.private') }}
                        @endif
                    </span>
                    @if ($playlist->is_featured)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-white/90 text-amber-700">
                            <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>{{ __('quran.playlists.featured') }}
                        </span>
                    @endif
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold pl-glass">
                        <i data-lucide="{{ $playlist->scope === 'full' ? 'book-open' : 'list-checks' }}" class="w-3.5 h-3.5"></i>{{ __('quran.playlists.scope_'.$playlist->scope) }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 ps-1 pe-2.5 py-0.5 rounded-full text-xs font-semibold pl-glass text-white/90">
                        @if ($card['owner']['avatar'])
                            <img src="{{ $card['owner']['avatar'] }}" alt="" width="20" height="20" class="w-5 h-5 rounded-full object-cover">
                        @else
                            <span class="w-5 h-5 rounded-full bg-white/25 text-[10px] font-bold flex items-center justify-center">{{ mb_substr($card['owner']['name'] ?: '?', 0, 1) }}</span>
                        @endif
                        {{ $card['owner']['name'] }}
                    </span>
                </div>

                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight leading-[1.2] text-start break-words">{{ $playlist->name }}</h1>
                @if ($playlist->description)
                    <p class="mt-2 text-sm sm:text-base text-white/85 leading-relaxed max-w-[52ch] line-clamp-1">{{ $playlist->description }}</p>
                @endif

                {{-- Reciters (linked chips) --}}
                @if ($reciters)
                    @php $chipLimit = 4; @endphp
                    <ul class="mt-3 hidden sm:flex flex-wrap gap-2" aria-label="{{ __('quran.public.reciters') }}" x-data="{ all: false }">
                        @foreach ($reciters as $ri => $r)
                            <li @if ($ri >= $chipLimit) x-show="all" x-cloak @endif>
                                <a href="{{ $r['url'] }}"
                                   class="inline-flex items-center gap-2 ps-1 pe-3 py-1 rounded-full pl-glass-btn text-sm font-semibold text-white transition-[background-color,transform] duration-200 hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                                    <x-site.reciter-avatar :name="$r['name']" :image="$r['image']" size="w-7 h-7" text="text-[7px]" :compact="true" ring="ring-2 ring-white/70" />
                                    {{ $r['name'] }}
                                </a>
                            </li>
                        @endforeach
                        @if (count($reciters) > $chipLimit)
                            <li>
                                <button type="button" @click="all = !all" :aria-expanded="all"
                                        class="inline-flex items-center gap-1.5 h-9 px-3 rounded-full pl-glass-btn text-sm font-bold text-white tabular-nums transition-[background-color,transform] duration-200 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                                    <span x-show="!all">+{{ $num(count($reciters) - $chipLimit) }}</span>
                                    <span x-show="all" x-cloak>{{ __('quran.playlists.show_less') }}</span>
                                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" :class="all ? 'rotate-180' : ''"></i>
                                </button>
                            </li>
                        @endif
                    </ul>
                @endif

                {{-- Actions: one primary, the rest glass --}}
                <div class="mt-4 flex flex-wrap items-center gap-2.5">
                    <button type="button" @click="toggleAll()" :disabled="!playable.length"
                            class="inline-flex items-center gap-2 h-12 px-6 rounded-2xl bg-white text-[#0056b3] font-bold shadow-lg transition-transform duration-200 hover:-translate-y-0.5 active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                        <i data-lucide="play" class="w-5 h-5 fill-current {{ $isRtl ? '-scale-x-100' : '' }}" x-show="!listPlaying"></i>
                        <i data-lucide="pause" class="w-5 h-5 fill-current" x-show="listPlaying" x-cloak></i>
                        <span x-text="listPlaying ? @js(__('quran.public.pause')) : @js(__('quran.playlists.play_all'))"></span>
                    </button>

                    @if ($playlist->is_public)
                        <x-site.share-button :url="$shareUrl" :title="$playlist->name" :text="$playlist->description" variant="glass" />
                    @endif

                    {{-- Phones: the reciters live behind a button instead of a chip row, keeping the hero short --}}
                    @if ($reciters)
                        <div class="relative sm:hidden" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                            <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="menu"
                                    class="inline-flex items-center gap-1.5 h-11 px-3.5 rounded-xl pl-glass-btn text-sm font-semibold text-white transition-[background-color,transform] duration-200 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                                <i data-lucide="mic" class="w-4 h-4"></i>
                                <span class="tabular-nums">{{ $num(count($reciters)) }}</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            <ul x-show="open" x-cloak x-transition.origin.top role="menu" aria-label="{{ __('quran.public.reciters') }}"
                                class="absolute z-20 top-full mt-2 start-0 w-64 max-h-72 overflow-y-auto rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200/80 dark:border-white/10 p-1.5">
                                @foreach ($reciters as $r)
                                    <li>
                                        <a href="{{ $r['url'] }}" role="menuitem"
                                           class="flex items-center gap-3 h-11 px-2 rounded-xl text-sm font-semibold text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-white/10 transition-colors">
                                            <x-site.reciter-avatar :name="$r['name']" :image="$r['image']" size="w-8 h-8" text="text-[8px]" :compact="true" />
                                            <span class="truncate">{{ $r['name'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($isOwner)
                        <button type="button" @click="toggleVisibility()" :disabled="visibilityPending"
                                class="inline-flex items-center gap-2 h-11 px-4 rounded-xl pl-glass-btn text-sm font-semibold text-white transition-[background-color,transform] duration-200 active:scale-[0.98] disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                            <i :data-lucide="isPublic ? 'eye-off' : 'globe'" class="w-4 h-4"></i>
                            <span x-text="isPublic ? @js(__('quran.playlists.make_private')) : @js(__('quran.playlists.make_public'))"></span>
                        </button>

                        {{-- More: edit / delete. Delete asks inline (a toast-like step) instead of a blocking confirm(). --}}
                        <div class="relative" x-data="{ open: false, confirming: false }" @click.outside="open = false; confirming = false" @keydown.escape.window="open = false; confirming = false">
                            <button type="button" @click="open = !open; confirming = false" :aria-expanded="open" aria-haspopup="menu"
                                    class="w-11 h-11 rounded-xl pl-glass-btn text-white flex items-center justify-center transition-[background-color,transform] duration-200 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80"
                                    aria-label="{{ __('quran.playlists.more') }}" title="{{ __('quran.playlists.more') }}">
                                <i data-lucide="more-vertical" class="w-5 h-5"></i>
                            </button>
                            <div x-show="open" x-cloak x-transition.origin.top
                                 class="absolute z-20 top-full mt-2 start-0 w-64 rounded-2xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-2xl border border-gray-200/80 dark:border-white/10 p-1.5" role="menu">
                                <template x-if="!confirming">
                                    <div>
                                        <a href="{{ route('public.quran.playlists.edit', $playlist->slug) }}" role="menuitem"
                                           class="flex items-center gap-3 h-11 px-3 rounded-xl text-sm font-semibold hover:bg-gray-100 dark:hover:bg-white/10 transition-colors">
                                            <i data-lucide="pencil" class="w-4 h-4 text-primary"></i>{{ __('quran.playlists.edit') }}
                                        </a>
                                        <button type="button" role="menuitem" @click="confirming = true; $nextTick(() => window.lucide?.createIcons())"
                                                class="w-full flex items-center gap-3 h-11 px-3 rounded-xl text-sm font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>{{ __('quran.playlists.delete') }}
                                        </button>
                                    </div>
                                </template>
                                <template x-if="confirming">
                                    <div class="p-2" role="alertdialog" aria-live="polite">
                                        <p class="text-sm font-bold">{{ __('quran.playlists.delete_title') }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('quran.playlists.delete_confirm') }}</p>
                                        <div class="mt-3 grid grid-cols-2 gap-2">
                                            <button type="button" @click="open = false; confirming = false"
                                                    class="h-10 rounded-xl text-sm font-semibold bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/15 transition-colors">{{ __('quran.playlists.cancel') }}</button>
                                            <button type="button" @click="destroy()" :disabled="deleting"
                                                    class="h-10 rounded-xl text-sm font-bold text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-60 transition-colors flex items-center justify-center gap-1.5">
                                                <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="deleting" x-cloak></i>
                                                {{ __('quran.playlists.delete_confirm_btn') }}
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    @endif
                </div>

            </div>

            {{-- Glass collage: reciter portraits, each a link --}}
            <div class="pl-in-2 hidden lg:block justify-self-end w-full max-w-[15rem]">
                <div class="rounded-3xl pl-glass p-3">
                    <div class="grid grid-cols-2 gap-2.5" aria-label="{{ __('quran.public.reciters') }}">
                        @foreach (array_slice($reciters, 0, count($reciters) > 4 ? 3 : 4) as $i => $r)
                            <a href="{{ $r['url'] }}" title="{{ $r['name'] }}"
                               class="group block aspect-square rounded-2xl overflow-hidden ring-1 ring-white/20 transition-transform duration-300 hover:-translate-y-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80 {{ count($reciters) === 1 ? 'col-span-2 aspect-[2/1]' : '' }}">
                                <x-site.reciter-avatar :name="$r['name']" :image="$r['image']" size="w-full h-full" shape="rounded-none" text="text-sm xl:text-base font-bold px-2" class="transition-transform duration-500 group-hover:scale-105" />
                            </a>
                        @endforeach
                        @if (count($reciters) > 4)
                            <div class="aspect-square rounded-2xl bg-white/10 flex flex-col items-center justify-center text-white/90" title="{{ __('quran.playlists.reciters_count', ['count' => $num(count($reciters))]) }}">
                                <span class="text-3xl font-black tabular-nums leading-none">+{{ $num(count($reciters) - 3) }}</span>
                                <span class="mt-1 text-xs">{{ __('quran.playlists.reciter_unit') }}</span>
                            </div>
                        @elseif (count($reciters) === 0)
                            <div class="col-span-2 aspect-[2/1] rounded-2xl bg-white/10 flex items-center justify-center text-white/70">
                                <i data-lucide="list-music" class="w-14 h-14"></i>
                            </div>
                        @elseif (count($reciters) === 3)
                            <div class="aspect-square rounded-2xl bg-white/10 flex flex-col items-center justify-center text-white/80">
                                <span class="text-3xl font-black tabular-nums leading-none">{{ $num($card['items']) }}</span>
                                <span class="mt-1 text-xs">{{ __('quran.playlists.surah_unit') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- ============================ NOW PLAYING (sticky once the hero scrolls away) ============================ --}}
    <div class="pl-now sticky z-30 mb-4" :style="`top:${stickyTop}px`"
         x-show="heroOut && current" x-cloak
         x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 -translate-y-2"
         role="region" aria-label="{{ __('quran.playlists.now_playing') }}">
        <div class="relative overflow-hidden rounded-2xl border border-gray-200/80 dark:border-white/10 bg-white/85 dark:bg-gray-900/85 backdrop-blur-xl shadow-[0_12px_32px_-16px_rgba(0,86,179,0.35)] ps-2 pe-2 sm:pe-3 py-2 flex items-center gap-3">
            <template x-if="current?.reciterImage">
                <img :src="current.reciterImage" alt="" class="w-11 h-11 rounded-xl object-cover shrink-0">
            </template>
            <template x-if="!current?.reciterImage">
                <span class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-white text-[9px] font-bold flex items-center justify-center text-center px-1 shrink-0 leading-tight" x-text="(current?.reciterName || '').split(' ')[0]"></span>
            </template>
            <div class="min-w-0 flex-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('quran.playlists.now_playing') }}</p>
                <p class="font-bold text-gray-900 dark:text-white truncate leading-tight"><span x-text="current?.surahName"></span><span class="font-normal text-gray-500 dark:text-gray-400 text-sm"> · <span x-text="current?.reciterName"></span></span></p>
            </div>
            <div class="flex items-center gap-1 shrink-0" dir="ltr">
                <button type="button" @click="$store.audioPlayer.step(-1)" :disabled="!$store.audioPlayer.canStep(-1)"
                        class="w-10 h-10 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 disabled:opacity-30 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                        aria-label="{{ __('quran.public.previous_track') }}"><i data-lucide="skip-back" class="w-4 h-4 fill-current"></i></button>
                <button type="button" @click="$store.audioPlayer.toggle()"
                        class="w-11 h-11 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center justify-center shadow-md transition-transform active:scale-[0.95] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                        :aria-label="$store.audioPlayer.playing ? @js(__('quran.public.pause')) : @js(__('quran.playlists.play'))">
                    <i data-lucide="play" class="w-5 h-5 fill-current" x-show="!$store.audioPlayer.playing"></i>
                    <i data-lucide="pause" class="w-5 h-5 fill-current" x-show="$store.audioPlayer.playing" x-cloak></i>
                </button>
                <button type="button" @click="$store.audioPlayer.step(1)" :disabled="!$store.audioPlayer.canStep(1)"
                        class="w-10 h-10 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 disabled:opacity-30 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                        aria-label="{{ __('quran.public.next_track') }}"><i data-lucide="skip-forward" class="w-4 h-4 fill-current"></i></button>
            </div>
            {{-- accent progress line --}}
            <span class="absolute bottom-0 start-0 h-[3px] bg-accent transition-[width] duration-500 ease-linear" :style="`width:${progress}%`" aria-hidden="true"></span>
        </div>
    </div>

    {{-- ============================ TRACK LIST ============================ --}}
    @if (count($tracks))
        <section class="rounded-3xl border border-gray-200/80 dark:border-white/10 bg-white dark:bg-gray-900 overflow-hidden" aria-labelledby="pl-tracks-title">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100 dark:border-white/10 flex flex-wrap items-center justify-between gap-3">
                <h2 id="pl-tracks-title" class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="list-music" class="w-5 h-5 text-primary"></i>{{ __('quran.playlists.tracks_title') }}
                </h2>
                <dl class="flex items-center gap-x-4 sm:gap-x-6 text-sm tabular-nums">
                    @foreach ($stats as $stat)
                        <div class="flex items-baseline gap-1.5">
                            <dd class="text-lg sm:text-xl font-black text-gray-900 dark:text-white leading-none">{{ $stat['value'] }}</dd>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</dt>
                        </div>
                    @endforeach
                    @if ($playableCount < count($tracks))
                        <div class="text-xs text-amber-700 dark:text-amber-300">{{ __('quran.playlists.needs_reciter_count', ['count' => $num(count($tracks) - $playableCount)]) }}</div>
                    @endif
                </dl>
            </div>

            <ol class="divide-y divide-gray-100 dark:divide-white/10" role="list">
                @foreach ($tracks as $i => $t)
                    @php $hasAudio = (bool) $t['url']; @endphp
                    <li style="--i: {{ $i }}"
                        class="pl-row relative grid grid-cols-[auto_1fr_auto] sm:grid-cols-[auto_1fr_minmax(200px,1.2fr)_auto_auto] gap-x-3 sm:gap-x-5 items-center px-3 sm:px-5 py-2.5 sm:py-3 transition-colors"
                        :class="isActive({{ $i }}) ? 'bg-primary/[0.06] dark:bg-primary/10' : 'hover:bg-gray-50 dark:hover:bg-white/[0.03]'">
                        <span class="absolute inset-y-2 start-0 w-1 rounded-e-full bg-gradient-to-b from-[#0056b3] to-[#218838] transition-opacity" :class="isActive({{ $i }}) ? 'opacity-100' : 'opacity-0'" aria-hidden="true"></span>

                        {{-- Artwork tile: the reciter's portrait, surah number badge, play/equalizer overlay --}}
                        <button type="button" @click="playAt({{ $i }})" @disabled(! $hasAudio)
                                class="group/t relative w-12 h-12 rounded-xl overflow-hidden shrink-0 bg-gray-100 dark:bg-gray-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent disabled:cursor-not-allowed"
                                aria-label="{{ __('quran.playlists.play') }} {{ $t['surahName'] }}">
                            @if ($t['recording'])
                                <x-site.reciter-avatar :name="$t['reciterName'] ?? '?'" :image="$t['reciterImage']" size="w-full h-full" shape="rounded-none" text="text-[8px]" :compact="true" />
                            @else
                                <span class="w-full h-full flex items-center justify-center text-amber-600 dark:text-amber-300"><i data-lucide="mic-off" class="w-5 h-5"></i></span>
                            @endif
                            <span class="absolute inset-0 flex items-center justify-center text-white transition-opacity duration-200"
                                  :class="isActive({{ $i }}) ? 'bg-gradient-to-br from-[#0056b3]/80 to-[#218838]/80 opacity-100' : 'bg-black/35 opacity-0 group-hover/t:opacity-100 group-focus-visible/t:opacity-100'">
                                <span x-show="isActive({{ $i }}) && $store.audioPlayer.playing" x-cloak class="eq-bars text-accent" aria-hidden="true"><span></span><span></span><span></span></span>
                                <i data-lucide="play" class="w-4 h-4 fill-current {{ $isRtl ? '-scale-x-100' : '' }}" x-show="!(isActive({{ $i }}) && $store.audioPlayer.playing)"></i>
                            </span>
                            <span class="absolute bottom-0 start-0 min-w-[20px] h-[18px] px-1 rounded-te-lg text-[10px] font-bold tabular-nums flex items-center justify-center transition-colors"
                                  :class="isActive({{ $i }}) ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : 'bg-white/95 text-gray-800 dark:bg-gray-900/95 dark:text-gray-100'">{{ $num($t['surah']) }}</span>
                        </button>

                        {{-- Title + subtitle: tap plays. Phones show the reciter here; desktop has its own column. --}}
                        <button type="button" @click="playAt({{ $i }})" @disabled(! $hasAudio)
                                class="min-w-0 text-start rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent disabled:cursor-default">
                            <span class="block font-bold text-[15px] sm:text-base truncate transition-colors" :class="isActive({{ $i }}) ? 'text-primary' : 'text-gray-900 dark:text-white'">{{ $t['surahName'] }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400 truncate tabular-nums">
                                @if ($t['recording'])
                                    <span class="sm:hidden">{{ $t['reciterName'] }}<span class="text-gray-400 dark:text-gray-500"> · {{ $t['recordingLabel'] }}</span></span>
                                @else
                                    <span class="sm:hidden text-amber-700 dark:text-amber-300">{{ __('quran.playlists.needs_reciter') }}</span>
                                @endif
                                <span class="hidden sm:inline">{{ __('quran.playlists.verses', ['count' => $num($t['verses'] ?? 0)]) }}</span>
                            </span>
                        </button>

                        {{-- Reciter (desktop column, linked) --}}
                        <div class="hidden sm:block min-w-0">
                            @if ($t['recording'])
                                <a href="{{ $t['reciterUrl'] ?? '#' }}" class="group/r inline-flex items-center gap-2.5 max-w-full rounded-xl -m-1 p-1 hover:bg-white dark:hover:bg-gray-800 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-gray-800 dark:text-gray-100 truncate group-hover/r:text-primary transition-colors">{{ $t['reciterName'] }}</span>
                                        <span class="block text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ $t['recordingLabel'] }}</span>
                                    </span>
                                </a>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700 dark:text-amber-300">
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>{{ __('quran.playlists.needs_reciter') }}
                                </span>
                            @endif
                        </div>

                        {{-- Duration (desktop column) --}}
                        <span class="hidden sm:block text-sm text-gray-500 dark:text-gray-400 tabular-nums w-14 text-end">{{ $fmtDur($t['duration']) ?? '—' }}</span>

                        {{-- Trailing: duration on phones + reader link --}}
                        <div class="flex items-center gap-1 justify-self-end">
                            @if ($t['duration'])
                                <span class="sm:hidden text-[11px] text-gray-400 dark:text-gray-500 tabular-nums">{{ $fmtDur($t['duration']) }}</span>
                            @endif
                            <a href="{{ $t['readerUrl'] }}"
                               class="w-10 h-10 sm:w-11 sm:h-11 rounded-full flex items-center justify-center text-gray-400 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-primary transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                               aria-label="{{ __('quran.playlists.open_reader') }} — {{ $t['surahName'] }}" title="{{ __('quran.playlists.open_reader') }}">
                                <i data-lucide="book-open" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @else
        <x-site.empty-state icon="list-music" :title="__('quran.playlists.empty_mine')" :message="__('quran.playlists.empty_mine_hint')" />
    @endif
</div>
</div>
@endsection

@push('scripts')
<script>
function playlistPage() {
    return {
        tracks: @js($tracks),
        isPublic: @json($playlist->is_public),
        visibilityUrl: @js(route('public.quran.playlists.visibility', $playlist->slug)),
        playsUrl: @js(route('public.quran.playlists.plays', $playlist->slug)),
        counted: false,
        visibilityPending: false,
        deleting: false,
        heroOut: false,
        stickyTop: 80,
        destroyUrl: @js(route('public.quran.playlists.destroy', $playlist->slug)),

        init() {
            this.$watch('isPublic', () => this.$nextTick(() => window.lucide?.createIcons()));
            // The now-playing bar takes over when the hero leaves the viewport; it sits under the site header.
            const header = document.querySelector('header'); // the site header precedes the hero in the DOM
            this.stickyTop = (header?.getBoundingClientRect().height || 72) + 8;
            if ('IntersectionObserver' in window && this.$refs.hero) {
                new IntersectionObserver(([e]) => { this.heroOut = !e.isIntersecting; }, { rootMargin: `-${this.stickyTop}px 0px 0px 0px`, threshold: 0 }).observe(this.$refs.hero);
            }
            this.$watch('heroOut', () => this.$nextTick(() => window.lucide?.createIcons()));
            this.$watch('current', () => this.$nextTick(() => window.lucide?.createIcons()));
        },
        // The list's track that is loaded in the shared player, if any.
        get current() {
            const url = this.$store.audioPlayer.track?.url;
            return url ? (this.tracks.find(t => t.url === url) || null) : null;
        },
        get progress() {
            const p = this.$store.audioPlayer;
            return p.duration ? Math.min(100, (p.currentTime / p.duration) * 100) : 0;
        },
        get playable() {
            return this.tracks.filter(t => t.url).map(t => ({ url: t.url, title: t.surahName, subtitle: t.reciterName || '', readerUrl: t.readerUrl }));
        },
        get listPlaying() {
            const url = this.$store.audioPlayer.track?.url;
            return !!url && this.$store.audioPlayer.playing && this.tracks.some(t => t.url === url);
        },
        isActive(i) {
            const t = this.tracks[i];
            return !!t?.url && this.$store.audioPlayer.track?.url === t.url;
        },
        // Hero button: starts the list, or pauses/resumes it once it is the loaded list.
        toggleAll() {
            const p = this.$store.audioPlayer;
            const loaded = !!p.track?.url && this.tracks.some(t => t.url === p.track.url);
            if (loaded) { p.toggle(); return; }
            this.playAt(0);
        },
        // Row: always (re)starts that surah — never a toggle, so the first row is not special.
        playAt(i) {
            const list = this.playable;
            if (!list.length) return;
            const p = this.$store.audioPlayer;
            const target = this.tracks[i]?.url;
            const idx = Math.max(0, list.findIndex(t => t.url === target));
            // setPlaylist() toggles when the tapped track is the loaded one; a tap on a row means "play it".
            if (p.track?.url === target) { if (!p.playing) { p.toggle(); } return; }
            p.setPlaylist(list, idx);
            if (!this.counted) {
                this.counted = true;
                fetch(this.playsUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                }).catch(() => {});
            }
        },
        async destroy() {
            if (this.deleting) return;
            this.deleting = true;
            try {
                const res = await fetch(this.destroyUrl, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error('delete_failed');
                this.$store.toast?.push(@js(__('quran.playlists.deleted')), 'success');
                window.location.href = data.redirect || @js(route('public.profile').'#playlists');
            } catch (e) {
                this.deleting = false;
                this.$store.toast?.push(@js(__('quran.playlists.error')), 'error');
            }
        },
        async toggleVisibility() {
            if (this.visibilityPending) return;
            this.visibilityPending = true;
            const next = !this.isPublic;
            try {
                const res = await fetch(this.visibilityUrl, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ is_public: next }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error('visibility_failed');
                this.isPublic = data.is_public;
                if (data.message) this.$store.toast?.push(data.message, 'success');
                // Badge, share button and collage are server-rendered: refresh to keep them honest.
                window.location.reload();
            } catch (e) {
                this.$store.toast?.push(@js(__('quran.playlists.error')), 'error');
            } finally {
                this.visibilityPending = false;
            }
        },
    };
}
</script>
@endpush
