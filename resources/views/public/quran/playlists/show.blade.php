@extends('layouts.public')

@section('title', $playlist->name.' | '.__('quran.playlists.title'))
@section('meta_description', \Illuminate\Support\Str::limit($playlist->description ?: __('quran.playlists.subtitle'), 160))

@push('head')
<style>
    .eq-bars { display: inline-flex; align-items: flex-end; gap: 2px; height: 14px; }
    .eq-bars span { width: 3px; border-radius: 2px; background: currentColor; animation: eqBounce 1s ease-in-out infinite; }
    .eq-bars span:nth-child(2) { animation-delay: .2s; }
    .eq-bars span:nth-child(3) { animation-delay: .4s; }
    @keyframes eqBounce { 0%, 100% { height: 4px; } 50% { height: 14px; } }
    @keyframes plFadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
    .pl-in { animation: plFadeUp .5s cubic-bezier(.16,1,.3,1) both; }
    .pl-in-2 { animation: plFadeUp .5s cubic-bezier(.16,1,.3,1) .12s both; }
    .pl-row { animation: plFadeUp .35s cubic-bezier(.16,1,.3,1) both; animation-delay: calc(var(--i, 0) * 25ms); }
    /* Liquid glass: blur + 1px inner edge + top highlight. */
    .pl-glass { background: rgba(255,255,255,.10); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid rgba(255,255,255,.22); box-shadow: inset 0 1px 0 rgba(255,255,255,.28); }
    .pl-glass-btn { background: rgba(255,255,255,.12); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,.25); }
    .pl-glass-btn:hover { background: rgba(255,255,255,.2); }
    @media (prefers-reduced-motion: reduce) { .eq-bars span { animation: none; height: 10px; } .pl-in, .pl-in-2, .pl-row { animation: none; } }
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
    $hue = 165 + (crc32($playlist->slug) % 60);
    $playableCount = count(array_filter($tracks, fn ($t) => $t['url']));
    $totalSeconds = array_sum(array_map(fn ($t) => (int) ($t['duration'] ?? 0), $tracks));
    $stats = array_values(array_filter([
        ['value' => $num($card['items']), 'label' => __('quran.playlists.surah_unit')],
        ['value' => $num(count($reciters)), 'label' => __('quran.playlists.reciter_unit')],
        $totalSeconds ? ['value' => $fmtDur($totalSeconds), 'label' => __('quran.playlists.total_duration')] : null,
        ['value' => $num($card['plays']), 'label' => __('quran.playlists.plays')],
    ]));
    $statCols = ['grid-cols-3', 'grid-cols-4'][count($stats) - 3] ?? 'grid-cols-3';
@endphp

<div class="min-h-dvh font-['Cairo'] bg-gray-50 dark:bg-gray-950 transition-colors duration-300"
     x-data="playlistPage()"
     x-init="init()"
     :style="$store.audioPlayer.track ? 'padding-bottom: 7rem' : ''">
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

    {{-- Breadcrumb --}}
    <nav class="mb-5 text-sm text-zinc-500 dark:text-zinc-400 flex items-center gap-2 flex-wrap" aria-label="breadcrumb">
        <a href="{{ route('public.quran') }}" class="hover:text-primary transition-colors">{{ __('quran.public.page_title') }}</a>
        <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <a href="{{ route('public.quran.playlists.index') }}" class="hover:text-primary transition-colors">{{ __('quran.playlists.title') }}</a>
        <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span class="text-zinc-900 dark:text-white font-semibold truncate max-w-[18rem]">{{ $playlist->name }}</span>
    </nav>

    {{-- ============================ HERO: the playlist's own tint, copy | glass collage ============================ --}}
    <header class="relative rounded-3xl overflow-hidden shadow-2xl mb-8 sm:mb-10 text-white"
            style="background: linear-gradient(135deg, hsl({{ $hue }} 62% 34%) 0%, hsl({{ $hue + 28 }} 58% 24%) 100%)">
        <div class="absolute inset-0 opacity-10 pointer-events-none">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.4\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>
        <div class="absolute -top-24 -end-24 w-96 h-96 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
        <span aria-hidden="true" class="absolute -bottom-6 start-6 text-[120px] sm:text-[160px] leading-none font-black tabular-nums text-white/[0.07] select-none pointer-events-none">{{ $num($card['items']) }}</span>

        <div class="relative grid lg:grid-cols-[1.15fr_0.85fr] gap-8 lg:gap-14 items-center px-6 sm:px-10 lg:px-14 py-10 sm:py-14">
            {{-- Copy --}}
            <div class="pl-in min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-4">
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
                </div>

                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-[1.2] text-start break-words">{{ $playlist->name }}</h1>
                @if ($playlist->description)
                    <p class="mt-4 text-base sm:text-lg text-white/85 leading-relaxed max-w-[52ch]">{{ $playlist->description }}</p>
                @endif

                {{-- Owner --}}
                <div class="mt-5 inline-flex items-center gap-2.5 text-sm text-white/85">
                    @if ($card['owner']['avatar'])
                        <img src="{{ $card['owner']['avatar'] }}" alt="" width="32" height="32" class="w-8 h-8 rounded-full object-cover ring-2 ring-white/60">
                    @else
                        <span class="w-8 h-8 rounded-full pl-glass text-xs font-bold flex items-center justify-center">{{ mb_substr($card['owner']['name'] ?: '?', 0, 1) }}</span>
                    @endif
                    <span>{{ __('quran.playlists.by') }} <strong class="text-white">{{ $card['owner']['name'] }}</strong></span>
                </div>

                {{-- Reciters (linked chips) --}}
                @if ($reciters)
                    @php $chipLimit = 8; @endphp
                    <ul class="mt-5 flex flex-wrap gap-2" aria-label="{{ __('quran.public.reciters') }}" x-data="{ all: false }">
                        @foreach ($reciters as $ri => $r)
                            <li @if ($ri >= $chipLimit) x-show="all" x-cloak x-transition.opacity @endif>
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
                <div class="mt-7 flex flex-wrap items-center gap-3">
                    <button type="button" @click="playAll(0)" :disabled="!playable.length"
                            class="inline-flex items-center gap-2 h-12 px-6 rounded-2xl bg-white text-[#0056b3] font-bold shadow-lg transition-transform duration-200 hover:-translate-y-0.5 active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                        <i data-lucide="play" class="w-5 h-5 fill-current {{ $isRtl ? '-scale-x-100' : '' }}" x-show="!listPlaying"></i>
                        <i data-lucide="pause" class="w-5 h-5 fill-current" x-show="listPlaying" x-cloak></i>
                        <span x-text="listPlaying ? @js(__('quran.public.pause')) : @js(__('quran.playlists.play_all'))"></span>
                    </button>

                    @if ($playlist->is_public)
                        <x-site.share-button :url="$shareUrl" :title="$playlist->name" :text="$playlist->description" variant="glass" />
                    @endif

                    @if ($isOwner)
                        <a href="{{ route('public.quran.playlists.edit', $playlist->slug) }}"
                           class="inline-flex items-center gap-2 h-11 px-4 rounded-xl pl-glass-btn text-sm font-semibold text-white transition-[background-color,transform] duration-200 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                            <i data-lucide="pencil" class="w-4 h-4"></i>{{ __('quran.playlists.edit') }}
                        </a>
                        <button type="button" @click="toggleVisibility()" :disabled="visibilityPending"
                                class="inline-flex items-center gap-2 h-11 px-4 rounded-xl pl-glass-btn text-sm font-semibold text-white transition-[background-color,transform] duration-200 active:scale-[0.98] disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                            <i :data-lucide="isPublic ? 'eye-off' : 'globe'" class="w-4 h-4"></i>
                            <span x-text="isPublic ? @js(__('quran.playlists.make_private')) : @js(__('quran.playlists.make_public'))"></span>
                        </button>
                        <form method="POST" action="{{ route('public.quran.playlists.destroy', $playlist->slug) }}"
                              @submit="if (!confirm(@js(__('quran.playlists.delete_confirm')))) $event.preventDefault()">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center gap-2 h-11 px-3 rounded-xl text-sm font-semibold text-white/80 hover:text-white hover:bg-rose-500/30 transition-colors active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>{{ __('quran.playlists.delete') }}
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Bare divided figures --}}
                <dl class="mt-8 grid {{ $statCols }} max-w-lg divide-x divide-white/25 {{ $isRtl ? 'divide-x-reverse' : '' }}">
                    @foreach ($stats as $i => $stat)
                        <div class="{{ $i === 0 ? '' : 'ps-4 sm:ps-5' }} {{ $i === count($stats) - 1 ? '' : 'pe-4 sm:pe-5' }} min-w-0">
                            <dd class="text-2xl sm:text-3xl font-black tabular-nums leading-none truncate">{{ $stat['value'] }}</dd>
                            <dt class="mt-1.5 text-xs sm:text-sm text-white/75">{{ $stat['label'] }}</dt>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Glass collage: reciter portraits, each a link --}}
            <div class="pl-in-2 hidden lg:block justify-self-end w-full max-w-sm">
                <div class="rounded-3xl pl-glass p-4">
                    <div class="grid grid-cols-2 gap-3" aria-label="{{ __('quran.public.reciters') }}">
                        @foreach (array_slice($reciters, 0, count($reciters) > 4 ? 3 : 4) as $i => $r)
                            <a href="{{ $r['url'] }}" title="{{ $r['name'] }}"
                               class="group block aspect-square rounded-2xl overflow-hidden ring-1 ring-white/20 transition-transform duration-300 hover:-translate-y-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80 {{ count($reciters) === 1 ? 'col-span-2 aspect-[2/1]' : '' }}">
                                <x-site.reciter-avatar :name="$r['name']" :image="$r['image']" size="w-full h-full" shape="rounded-none" text="text-xl xl:text-2xl font-black px-3" class="transition-transform duration-500 group-hover:scale-105" />
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

    {{-- ============================ TRACK LIST ============================ --}}
    @if (count($tracks))
        <section class="rounded-3xl border border-zinc-200/80 dark:border-white/10 bg-white dark:bg-zinc-900 overflow-hidden" aria-labelledby="pl-tracks-title">
            <div class="px-4 sm:px-6 py-4 border-b border-zinc-100 dark:border-white/10 flex flex-wrap items-center justify-between gap-3">
                <h2 id="pl-tracks-title" class="text-lg font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="list-music" class="w-5 h-5 text-primary"></i>{{ __('quran.playlists.tracks_title') }}
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 tabular-nums">
                    {{ __('quran.playlists.surahs_count', ['count' => $num(count($tracks))]) }}
                    @if ($playableCount < count($tracks))
                        <span aria-hidden="true" class="mx-1">·</span>
                        <span class="text-amber-700 dark:text-amber-300">{{ __('quran.playlists.needs_reciter_count', ['count' => $num(count($tracks) - $playableCount)]) }}</span>
                    @endif
                </p>
            </div>

            <ol class="divide-y divide-zinc-100 dark:divide-white/10" role="list">
                @foreach ($tracks as $i => $t)
                    <li style="--i: {{ $i }}"
                        class="pl-row relative grid grid-cols-[auto_1fr_auto] sm:grid-cols-[auto_1fr_minmax(200px,1.2fr)_auto_auto] gap-x-3 sm:gap-x-5 gap-y-2 items-center px-3 sm:px-5 py-3 transition-colors"
                        :class="isActive({{ $i }}) ? 'bg-primary/[0.06] dark:bg-primary/10' : 'hover:bg-zinc-50 dark:hover:bg-white/[0.03]'">
                        {{-- active accent bar --}}
                        <span class="absolute inset-y-2 start-0 w-1 rounded-e-full bg-gradient-to-b from-[#0056b3] to-[#218838] transition-opacity" :class="isActive({{ $i }}) ? 'opacity-100' : 'opacity-0'" aria-hidden="true"></span>

                        {{-- number / equalizer --}}
                        <button type="button" @click="playAll({{ $i }})" @disabled(! $t['url'])
                                class="w-11 h-11 rounded-2xl flex items-center justify-center text-sm font-bold tabular-nums shrink-0 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/60 disabled:cursor-not-allowed"
                                :class="isActive({{ $i }}) ? 'bg-primary text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 hover:bg-primary/10 hover:text-primary'"
                                aria-label="{{ __('quran.playlists.play') }} {{ $t['surahName'] }}">
                            <span x-show="!isActive({{ $i }})">{{ $num($t['surah']) }}</span>
                            <span x-show="isActive({{ $i }}) && $store.audioPlayer.playing" x-cloak class="eq-bars" aria-hidden="true"><span></span><span></span><span></span></span>
                            <i data-lucide="play" class="w-4 h-4 fill-current {{ $isRtl ? '-scale-x-100' : '' }}" x-show="isActive({{ $i }}) && !$store.audioPlayer.playing" x-cloak></i>
                        </button>

                        {{-- surah --}}
                        <div class="min-w-0">
                            <a href="{{ $t['readerUrl'] }}" class="font-bold text-zinc-900 dark:text-white hover:text-primary transition-colors truncate block"
                               title="{{ __('quran.playlists.open_reader') }}">{{ $t['surahName'] }}</a>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 tabular-nums">
                                {{ __('quran.playlists.verses', ['count' => $num($t['verses'] ?? 0)]) }}
                                @if ($t['duration'])
                                    <span class="sm:hidden"><span aria-hidden="true">·</span> {{ $fmtDur($t['duration']) }}</span>
                                @endif
                            </p>
                        </div>

                        {{-- reciter (mobile: second line) --}}
                        <div class="col-span-3 sm:col-span-1 ps-14 sm:ps-0 min-w-0">
                            @if ($t['recording'])
                                <a href="{{ $t['reciterUrl'] ?? '#' }}" class="group/r inline-flex items-center gap-2.5 max-w-full rounded-xl -m-1 p-1 hover:bg-white dark:hover:bg-zinc-800 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/60">
                                    <x-site.reciter-avatar :name="$t['reciterName'] ?? '?'" :image="$t['reciterImage']" size="w-9 h-9" text="text-[8px]" :compact="true" class="shrink-0" />
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-zinc-800 dark:text-zinc-100 truncate group-hover/r:text-primary transition-colors">{{ $t['reciterName'] }}</span>
                                        <span class="block text-[11px] text-zinc-500 dark:text-zinc-400 truncate">{{ $t['recordingLabel'] }}</span>
                                    </span>
                                </a>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700 dark:text-amber-300">
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>{{ __('quran.playlists.needs_reciter') }}
                                </span>
                            @endif
                        </div>

                        {{-- duration (desktop) --}}
                        <span class="hidden sm:block text-sm text-zinc-500 dark:text-zinc-400 tabular-nums w-14 text-end">{{ $fmtDur($t['duration']) ?? '—' }}</span>

                        {{-- reader link --}}
                        <a href="{{ $t['readerUrl'] }}"
                           class="w-11 h-11 rounded-full flex items-center justify-center text-zinc-500 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-primary transition-colors justify-self-end focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/60"
                           aria-label="{{ __('quran.playlists.open_reader') }} — {{ $t['surahName'] }}" title="{{ __('quran.playlists.open_reader') }}">
                            <i data-lucide="book-open" class="w-4 h-4"></i>
                        </a>
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

        init() {
            this.$watch('isPublic', () => this.$nextTick(() => window.lucide?.createIcons()));
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
        playAll(i) {
            const list = this.playable;
            if (!list.length) return;
            // "Play all" while this list is already playing pauses it, like a transport button.
            if (i === 0 && this.listPlaying) { this.$store.audioPlayer.toggle(); return; }
            const target = this.tracks[i]?.url;
            const idx = Math.max(0, list.findIndex(t => t.url === target));
            this.$store.audioPlayer.setPlaylist(list, idx);
            if (!this.counted) {
                this.counted = true;
                fetch(this.playsUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                }).catch(() => {});
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
