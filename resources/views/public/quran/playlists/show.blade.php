@extends('layouts.public')

@section('title', $playlist->name.' | '.__('quran.playlists.title'))
@section('meta_description', \Illuminate\Support\Str::limit($playlist->description ?: __('quran.playlists.subtitle'), 160))

@push('head')
<style>
    /* The only page-scoped rule: a playing indicator the system has no glyph for. */
    .eq-bars { display: inline-flex; align-items: flex-end; gap: 2px; height: 14px; }
    .eq-bars span { width: 3px; border-radius: 2px; background: currentColor; animation: eqLevel 1s ease-in-out infinite; }
    .eq-bars span:nth-child(2) { animation-delay: .2s; }
    .eq-bars span:nth-child(3) { animation-delay: .4s; }
    @keyframes eqLevel { 0%, 100% { height: 4px; } 50% { height: 14px; } }
    @media (prefers-reduced-motion: reduce) { .eq-bars span { animation: none; height: 10px; } }
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
    $chipLimit = 4;
@endphp

<div class="sx min-h-[100dvh] pb-20"
     x-data="playlistPage()"
     x-init="init()"
     :style="$store.audioPlayer.track ? 'padding-bottom: 7rem' : ''">

    <x-public.editorial.hero variant="reading" :title="$playlist->name" :lead="$playlist->description ?: null" x-ref="hero">
        <x-slot:top>
            <nav class="mb-5 flex flex-wrap items-center gap-2 text-sm opacity-80" aria-label="breadcrumb">
                <a href="{{ route('public.quran') }}" class="hover:underline underline-offset-4">{{ __('quran.public.page_title') }}</a>
                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4" aria-hidden="true"></i>
                <a href="{{ route('public.quran.playlists.index') }}" class="hover:underline underline-offset-4">{{ __('quran.playlists.title') }}</a>
            </nav>
        </x-slot:top>

        <x-slot:chips>
            <span class="sx-glass-chip">
                @if ($playlist->is_public)
                    <i data-lucide="globe" aria-hidden="true"></i>{{ __('quran.playlists.public') }}
                @else
                    <i data-lucide="lock" aria-hidden="true"></i>{{ __('quran.playlists.private') }}
                @endif
            </span>
            @if ($playlist->is_featured)
                <span class="sx-glass-chip"><i data-lucide="star" aria-hidden="true"></i>{{ __('quran.playlists.featured') }}</span>
            @endif
            <span class="sx-glass-chip">
                <i data-lucide="{{ $playlist->scope === 'full' ? 'book-open' : 'list-checks' }}" aria-hidden="true"></i>{{ __('quran.playlists.scope_'.$playlist->scope) }}
            </span>
            <span class="sx-glass-chip ps-1">
                <span class="sx-avatar w-5 h-5">
                    @if ($card['owner']['avatar'])
                        <img src="{{ $card['owner']['avatar'] }}" alt="" width="20" height="20" onerror="this.remove()">
                    @else
                        <span class="text-[10px] font-bold">{{ mb_substr($card['owner']['name'] ?: '?', 0, 1) }}</span>
                    @endif
                </span>
                {{ $card['owner']['name'] }}
            </span>
        </x-slot:chips>

        @if ($reciters)
            <x-slot:meta>
                {{-- Phones get the same list from the dropdown under the hero, keeping the opener short. --}}
                <ul class="hidden sm:flex flex-wrap gap-2" aria-label="{{ __('quran.public.reciters') }}" x-data="{ all: false }">
                    @foreach ($reciters as $ri => $r)
                        <li @if ($ri >= $chipLimit) x-show="all" x-cloak @endif>
                            <a href="{{ $r['url'] }}" title="{{ $r['name'] }}" class="sx-glass-chip ps-1 transition-colors hover:bg-[rgb(255_255_255/0.22)]">
                                <span class="sx-avatar w-7 h-7">
                                    @if ($r['image'])
                                        <img src="{{ $r['image'] }}" alt="" width="28" height="28" loading="lazy" onerror="this.remove()">
                                    @else
                                        <span class="px-0.5 text-[7px] font-bold leading-tight sx-clamp-1">{{ explode(' ', trim($r['name']))[0] }}</span>
                                    @endif
                                </span>
                                <span class="sx-clamp-1 max-w-[12rem]">{{ $r['name'] }}</span>
                            </a>
                        </li>
                    @endforeach
                    @if (count($reciters) > $chipLimit)
                        <li>
                            <button type="button" @click="all = !all" :aria-expanded="all" class="sx-glass-chip tabular-nums">
                                <span x-show="!all">+{{ $num(count($reciters) - $chipLimit) }}</span>
                                <span x-show="all" x-cloak>{{ __('quran.playlists.show_less') }}</span>
                                <i data-lucide="chevron-down" class="transition-transform" :class="all ? 'rotate-180' : ''" aria-hidden="true"></i>
                            </button>
                        </li>
                    @endif
                </ul>
            </x-slot:meta>
        @endif

        <x-slot:actions>
            <button type="button" @click="toggleAll()" :disabled="!playable.length" class="sx-btn is-light disabled:opacity-40 disabled:cursor-not-allowed">
                <i data-lucide="play" class="{{ $isRtl ? '-scale-x-100' : '' }}" x-show="!listPlaying" aria-hidden="true"></i>
                <i data-lucide="pause" x-show="listPlaying" x-cloak aria-hidden="true"></i>
                <span x-text="listPlaying ? @js(__('quran.public.pause')) : @js(__('quran.playlists.play_all'))"></span>
            </button>

            @if ($playableCount)
                <a href="{{ route('public.quran.playlists.download', $playlist->slug) }}" class="sx-btn is-light"
                   title="{{ __('quran.playlists.download_hint', ['count' => $num($playableCount)]) }}">
                    <i data-lucide="download" aria-hidden="true"></i>
                    <span>{{ __('quran.playlists.download') }}</span>
                </a>
            @endif

            @if ($playlist->is_public)
                <x-site.share-button :url="$shareUrl" :title="$playlist->name" :text="$playlist->description" variant="glass" />
            @endif
        </x-slot:actions>
    </x-public.editorial.hero>

    <div class="sx-wrap mt-8 grid gap-5">

        {{-- Controls that open a menu live outside the hero, which clips its overflow. --}}
        @if ($reciters || $isOwner)
            <div @class(['sx-surface flex flex-wrap items-center gap-2.5 p-3', 'sm:hidden' => ! $isOwner])>
                @if ($reciters)
                    <div class="relative sm:hidden" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                        <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="menu" class="sx-btn">
                            <i data-lucide="mic" aria-hidden="true"></i>
                            <span class="tabular-nums">{{ $num(count($reciters)) }}</span>
                            <i data-lucide="chevron-down" class="transition-transform" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
                        </button>
                        <ul x-show="open" x-cloak x-transition.origin.top role="menu" aria-label="{{ __('quran.public.reciters') }}"
                            class="sx-surface absolute z-20 top-full mt-2 start-0 w-64 max-h-72 overflow-y-auto p-1.5 sx-tile-list">
                            @foreach ($reciters as $r)
                                <li>
                                    <a href="{{ $r['url'] }}" role="menuitem" title="{{ $r['name'] }}" class="sx-tile">
                                        <span class="sx-tile-icon">
                                            @if ($r['image'])
                                                <img src="{{ $r['image'] }}" alt="" width="40" height="40" loading="lazy" onerror="this.remove()">
                                            @else
                                                <i data-lucide="mic" aria-hidden="true"></i>
                                            @endif
                                        </span>
                                        <span class="sx-tile-title sx-clamp-1">{{ $r['name'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($isOwner)
                    <button type="button" @click="toggleVisibility()" :disabled="visibilityPending" class="sx-btn disabled:opacity-50">
                        <i :data-lucide="isPublic ? 'eye-off' : 'globe'" aria-hidden="true"></i>
                        <span x-text="isPublic ? @js(__('quran.playlists.make_private')) : @js(__('quran.playlists.make_public'))"></span>
                    </button>

                    {{-- More: edit / delete. Delete asks inline instead of a blocking confirm(). --}}
                    <div class="relative" x-data="{ open: false, confirming: false }" @click.outside="open = false; confirming = false" @keydown.escape.window="open = false; confirming = false">
                        <button type="button" @click="open = !open; confirming = false" :aria-expanded="open" aria-haspopup="menu"
                                class="sx-btn w-11 px-0" aria-label="{{ __('quran.playlists.more') }}" title="{{ __('quran.playlists.more') }}">
                            <i data-lucide="more-vertical" aria-hidden="true"></i>
                        </button>
                        <div x-show="open" x-cloak x-transition.origin.top role="menu"
                             class="sx-surface absolute z-20 top-full mt-2 start-0 w-64 p-1.5">
                            <template x-if="!confirming">
                                <div class="sx-tile-list">
                                    <a href="{{ route('public.quran.playlists.edit', $playlist->slug) }}" role="menuitem" class="sx-tile">
                                        <span class="sx-tile-icon"><i data-lucide="pencil" aria-hidden="true"></i></span>
                                        <span class="sx-tile-title">{{ __('quran.playlists.edit') }}</span>
                                    </a>
                                    <button type="button" role="menuitem" @click="confirming = true; $nextTick(() => window.lucide?.createIcons())"
                                            class="sx-tile text-start hover:border-[color:var(--sx-line-strong)]">
                                        <span class="sx-tile-icon text-[color:var(--sx-danger)] bg-[color:var(--sx-danger-soft)]"><i data-lucide="trash-2" aria-hidden="true"></i></span>
                                        <span class="sx-tile-title text-[color:var(--sx-danger)]">{{ __('quran.playlists.delete') }}</span>
                                    </button>
                                </div>
                            </template>
                            <template x-if="confirming">
                                <div class="p-2" role="alertdialog" aria-live="polite">
                                    <p class="font-bold text-[color:var(--sx-ink)]">{{ __('quran.playlists.delete_title') }}</p>
                                    <p class="mt-1 text-xs text-[color:var(--sx-muted)]">{{ __('quran.playlists.delete_confirm') }}</p>
                                    <div class="mt-3 grid grid-cols-2 gap-2">
                                        <button type="button" @click="open = false; confirming = false" class="sx-btn">{{ __('quran.playlists.cancel') }}</button>
                                        <button type="button" @click="destroy()" :disabled="deleting"
                                                class="sx-btn text-white border-transparent bg-[color:var(--sx-danger)] hover:text-white disabled:opacity-60">
                                            <i data-lucide="loader-2" class="animate-spin" x-show="deleting" x-cloak aria-hidden="true"></i>
                                            {{ __('quran.playlists.delete_confirm_btn') }}
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Now playing: takes over once the hero scrolls away. --}}
        <div class="sticky z-30 transition-[transform,opacity] duration-300" :style="`top:${stickyTop}px`"
             x-show="heroOut && current" x-cloak
             x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 -translate-y-2"
             role="region" aria-label="{{ __('quran.playlists.now_playing') }}">
            <div class="sx-surface relative overflow-hidden flex items-center gap-3 ps-2 pe-2 sm:pe-3 py-2">
                <span class="sx-avatar is-square w-11 h-11">
                    <template x-if="current?.reciterImage">
                        <img :src="current.reciterImage" alt="">
                    </template>
                    <template x-if="!current?.reciterImage">
                        <span class="px-1 text-center text-[9px] font-bold leading-tight sx-clamp-2" x-text="(current?.reciterName || '').split(' ')[0]"></span>
                    </template>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-[color:var(--sx-muted)]">{{ __('quran.playlists.now_playing') }}</p>
                    <p class="font-bold leading-tight text-[color:var(--sx-ink)] sx-clamp-1"><span x-text="current?.surahName"></span><span class="font-normal text-sm text-[color:var(--sx-muted)]"> · <span x-text="current?.reciterName"></span></span></p>
                </div>
                <div class="flex items-center gap-1 shrink-0" dir="ltr">
                    <button type="button" @click="$store.audioPlayer.step(-1)" :disabled="!$store.audioPlayer.canStep(-1)"
                            class="sx-btn w-11 px-0 disabled:opacity-30"
                            aria-label="{{ __('quran.public.previous_track') }}" title="{{ __('quran.public.previous_track') }}"><i data-lucide="skip-back" aria-hidden="true"></i></button>
                    <button type="button" @click="$store.audioPlayer.toggle()" class="sx-btn is-primary w-11 px-0"
                            :aria-label="$store.audioPlayer.playing ? @js(__('quran.public.pause')) : @js(__('quran.playlists.play'))"
                            :title="$store.audioPlayer.playing ? @js(__('quran.public.pause')) : @js(__('quran.playlists.play'))">
                        <i data-lucide="play" x-show="!$store.audioPlayer.playing" aria-hidden="true"></i>
                        <i data-lucide="pause" x-show="$store.audioPlayer.playing" x-cloak aria-hidden="true"></i>
                    </button>
                    <button type="button" @click="$store.audioPlayer.step(1)" :disabled="!$store.audioPlayer.canStep(1)"
                            class="sx-btn w-11 px-0 disabled:opacity-30"
                            aria-label="{{ __('quran.public.next_track') }}" title="{{ __('quran.public.next_track') }}"><i data-lucide="skip-forward" aria-hidden="true"></i></button>
                </div>
                <span class="absolute bottom-0 start-0 h-[3px] bg-[color:var(--sx-accent)] transition-[width] duration-500 ease-linear" :style="`width:${progress}%`" aria-hidden="true"></span>
            </div>
        </div>

        {{-- Track list --}}
        @if (count($tracks))
            <section class="sx-surface p-4 sm:p-6" aria-labelledby="pl-tracks-title">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 id="pl-tracks-title" class="sx-section-title !text-lg flex items-center gap-2">
                        <i data-lucide="list-music" class="w-5 h-5 text-[color:var(--sx-accent)]" aria-hidden="true"></i>{{ __('quran.playlists.tracks_title') }}
                    </h2>
                    <dl class="flex flex-wrap items-center gap-x-4 sm:gap-x-6 gap-y-1 text-sm tabular-nums">
                        @foreach ($stats as $stat)
                            <div class="flex items-baseline gap-1.5">
                                <dd class="text-lg sm:text-xl font-extrabold leading-none text-[color:var(--sx-ink)]">{{ $stat['value'] }}</dd>
                                <dt class="text-xs text-[color:var(--sx-muted)]">{{ $stat['label'] }}</dt>
                            </div>
                        @endforeach
                        @if ($playableCount < count($tracks))
                            <div class="text-xs font-bold text-[color:var(--sx-danger)]">{{ __('quran.playlists.needs_reciter_count', ['count' => $num(count($tracks) - $playableCount)]) }}</div>
                        @endif
                    </dl>
                </div>

                <ol class="sx-video-queue" role="list">
                    @foreach ($tracks as $i => $t)
                        @php $hasAudio = (bool) $t['url']; @endphp
                        <li class="relative grid grid-cols-[minmax(0,1fr)_auto] sm:grid-cols-[minmax(0,1fr)_minmax(180px,1.1fr)_auto_auto] items-center gap-x-3 sm:gap-x-5 pe-2 sm:pe-3 transition-colors"
                            :aria-current="isActive({{ $i }}) ? 'true' : 'false'"
                            :class="isActive({{ $i }}) && 'bg-[color:var(--sx-accent-soft)]'">
                            <span class="absolute inset-y-2 start-0 w-1 rounded-e-full transition-opacity" style="background: var(--sx-grad)"
                                  :class="isActive({{ $i }}) ? 'opacity-100' : 'opacity-0'" aria-hidden="true"></span>

                            {{-- Artwork + title: one target, the way the queue reads. --}}
                            <button type="button" @click="playAt({{ $i }})" @disabled(! $hasAudio)
                                    class="group/t bg-transparent disabled:cursor-not-allowed"
                                    aria-label="{{ __('quran.playlists.play') }} {{ $t['surahName'] }}">
                                <span class="relative block w-12 h-12 shrink-0 overflow-hidden rounded-xl" style="background: var(--sx-grad)">
                                    @if ($t['recording'])
                                        @if ($t['reciterImage'])
                                            <img src="{{ $t['reciterImage'] }}" alt="" width="48" height="48" loading="lazy" decoding="async" onerror="this.remove()" class="absolute inset-0 h-full w-full object-cover">
                                        @else
                                            <span class="absolute inset-0 flex items-center justify-center px-0.5 text-center text-[8px] font-bold leading-tight text-white">{{ explode(' ', trim((string) ($t['reciterName'] ?? '?')))[0] }}</span>
                                        @endif
                                    @else
                                        <span class="absolute inset-0 flex items-center justify-center bg-[color:var(--sx-surface-2)] text-[color:var(--sx-danger)]"><i data-lucide="mic-off" class="w-5 h-5" aria-hidden="true"></i></span>
                                    @endif
                                    <span class="absolute inset-0 flex items-center justify-center text-white transition-opacity duration-200"
                                          :class="isActive({{ $i }}) ? 'opacity-100' : 'opacity-0 group-hover/t:opacity-100 group-focus-visible/t:opacity-100'"
                                          style="background: var(--sx-grad)">
                                        <span x-show="isActive({{ $i }}) && $store.audioPlayer.playing" x-cloak class="eq-bars" aria-hidden="true"><span></span><span></span><span></span></span>
                                        <i data-lucide="play" class="w-4 h-4 {{ $isRtl ? '-scale-x-100' : '' }}" x-show="!(isActive({{ $i }}) && $store.audioPlayer.playing)" aria-hidden="true"></i>
                                    </span>
                                    <span class="absolute bottom-0 start-0 min-w-[20px] h-[18px] px-1 rounded-te-lg text-[10px] font-bold tabular-nums flex items-center justify-center bg-[color:var(--sx-surface)] text-[color:var(--sx-ink)]">{{ $num($t['surah']) }}</span>
                                </span>
                                <span class="min-w-0">
                                    <span class="block font-bold text-[15px] sm:text-base sx-clamp-1 transition-colors" :class="isActive({{ $i }}) ? 'text-[color:var(--sx-accent)]' : 'text-[color:var(--sx-ink)]'" title="{{ $t['surahName'] }}">{{ $t['surahName'] }}</span>
                                    <span class="block text-xs text-[color:var(--sx-muted)] sx-clamp-1 tabular-nums">
                                        @if ($t['recording'])
                                            <span class="sm:hidden">{{ $t['reciterName'] }} · {{ $t['recordingLabel'] }}</span>
                                        @else
                                            <span class="sm:hidden text-[color:var(--sx-danger)]">{{ __('quran.playlists.needs_reciter') }}</span>
                                        @endif
                                        <span class="hidden sm:inline">{{ __('quran.playlists.verses', ['count' => $num($t['verses'] ?? 0)]) }}</span>
                                    </span>
                                </span>
                            </button>

                            {{-- Reciter (desktop column, linked) --}}
                            <div class="hidden sm:block min-w-0">
                                @if ($t['recording'])
                                    <a href="{{ $t['reciterUrl'] ?? '#' }}" title="{{ $t['reciterName'] }}" class="group/r block min-w-0">
                                        <span class="block text-sm font-semibold sx-clamp-1 text-[color:var(--sx-ink-2)] group-hover/r:text-[color:var(--sx-accent)] transition-colors">{{ $t['reciterName'] }}</span>
                                        <span class="block text-[11px] sx-clamp-1 text-[color:var(--sx-muted)]">{{ $t['recordingLabel'] }}</span>
                                    </a>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-[color:var(--sx-danger)]">
                                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ __('quran.playlists.needs_reciter') }}
                                    </span>
                                @endif
                            </div>

                            {{-- Transport (desktop). Recitations carry no stored duration yet, so a known
                                 duration simply shows beside the control. --}}
                            <div class="hidden sm:flex items-center justify-end gap-2">
                                @if ($t['duration'])
                                    <span class="text-sm text-[color:var(--sx-muted)] tabular-nums">{{ $fmtDur($t['duration']) }}</span>
                                @endif
                                <button type="button" @disabled(! $hasAudio)
                                        @click="isActive({{ $i }}) ? $store.audioPlayer.toggle() : playAt({{ $i }})"
                                        class="sx-btn w-11 px-0 !rounded-full disabled:opacity-30 disabled:cursor-not-allowed"
                                        :class="isActive({{ $i }}) && '!text-white !border-transparent'"
                                        :style="isActive({{ $i }}) ? 'background: var(--sx-grad)' : ''"
                                        :aria-label="(isActive({{ $i }}) && $store.audioPlayer.playing ? @js(__('quran.public.pause')) : @js(__('quran.playlists.play'))) + ' ' + @js($t['surahName'])"
                                        :title="(isActive({{ $i }}) && $store.audioPlayer.playing ? @js(__('quran.public.pause')) : @js(__('quran.playlists.play'))) + ' ' + @js($t['surahName'])">
                                    <i data-lucide="play" class="w-4 h-4 {{ $isRtl ? '-scale-x-100' : '' }}" x-show="!(isActive({{ $i }}) && $store.audioPlayer.playing)" aria-hidden="true"></i>
                                    <i data-lucide="pause" class="w-4 h-4" x-show="isActive({{ $i }}) && $store.audioPlayer.playing" x-cloak aria-hidden="true"></i>
                                </button>
                            </div>

                            {{-- Trailing: duration on phones + reader link --}}
                            <div class="flex items-center gap-1 justify-self-end">
                                @if ($t['duration'])
                                    <span class="sm:hidden text-[11px] text-[color:var(--sx-muted)] tabular-nums">{{ $fmtDur($t['duration']) }}</span>
                                @endif
                                <a href="{{ $t['readerUrl'] }}" class="sx-btn w-11 px-0 !rounded-full"
                                   aria-label="{{ __('quran.playlists.open_reader') }} — {{ $t['surahName'] }}" title="{{ __('quran.playlists.open_reader') }}">
                                    <i data-lucide="book-open" class="w-4 h-4" aria-hidden="true"></i>
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        @else
            <x-public.editorial.empty-state icon="list-music" :title="__('quran.playlists.empty_mine')" :hint="__('quran.playlists.empty_mine_hint')" />
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
            const list = this.playable;
            const idx = list.findIndex(t => t.url === p.track?.url);
            if (idx >= 0) {
                // Loaded from this list: make sure the list is attached, then toggle.
                if (!p.hasPlaylistNav()) { p.adoptPlaylist(list, idx); }
                p.toggle();
                return;
            }
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
            if (p.track?.url === target) {
                if (!p.hasPlaylistNav()) { p.adoptPlaylist(list, idx); }
                if (!p.playing) { p.toggle(); }
                return;
            }
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
