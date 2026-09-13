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
    @media (prefers-reduced-motion: reduce) { .eq-bars span { animation: none; height: 10px; } }
    .pl-collage img, .pl-collage .pl-initial { animation: plIn .4s ease-out both; animation-delay: calc(var(--i, 0) * 60ms); }
    @keyframes plIn { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) { .pl-collage img, .pl-collage .pl-initial { animation: none; } }
</style>
@endpush

@section('content')
@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $num = fn ($n) => app()->getLocale() === 'ar' ? arabic_digits($n) : number_format((int) $n);
    $fmtDur = function (?int $s) use ($num): ?string {
        if ($s === null) { return null; }
        $m = intdiv($s, 60); $r = $s % 60;
        return $num($m).':'.$num(str_pad((string) $r, 2, '0', STR_PAD_LEFT));
    };
    $reciters = $card['reciters'];
    $shareUrl = route('public.quran.playlists.show', $playlist->slug);
@endphp

<div class="min-h-dvh font-['Cairo'] bg-gray-50 dark:bg-gray-950 transition-colors duration-300"
     x-data="playlistPage()"
     x-init="init()"
     :style="$store.audioPlayer.track ? 'padding-bottom: 7rem' : ''">
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

    {{-- Breadcrumb --}}
    <nav class="mb-6 text-sm text-zinc-500 dark:text-zinc-400 flex items-center gap-2 flex-wrap" aria-label="breadcrumb">
        <a href="{{ route('public.quran') }}" class="hover:text-primary transition-colors">{{ __('quran.public.title') }}</a>
        <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <a href="{{ route('public.quran.playlists.index') }}" class="hover:text-primary transition-colors">{{ __('quran.playlists.title') }}</a>
        <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span class="text-zinc-900 dark:text-white font-semibold truncate max-w-[18rem]">{{ $playlist->name }}</span>
    </nav>

    {{-- ============================ SPLIT HERO ============================ --}}
    <header class="grid md:grid-cols-[3fr_2fr] gap-8 md:gap-12 items-center mb-10">
        <div class="min-w-0">
            <div class="flex items-center gap-2 mb-3">
                @if ($playlist->is_public)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                        <i data-lucide="globe" class="w-3.5 h-3.5"></i>{{ __('quran.playlists.public') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <i data-lucide="lock" class="w-3.5 h-3.5"></i>{{ __('quran.playlists.private') }}
                    </span>
                @endif
                @if ($playlist->is_featured)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                        <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>{{ __('quran.playlists.featured') }}
                    </span>
                @endif
            </div>

            <h1 class="text-3xl sm:text-5xl font-black tracking-tight text-zinc-900 dark:text-white leading-tight text-start">{{ $playlist->name }}</h1>
            @if ($playlist->description)
                <p class="mt-3 text-base sm:text-lg text-zinc-600 dark:text-zinc-300 leading-relaxed max-w-[60ch]">{{ $playlist->description }}</p>
            @endif

            {{-- Owner + stats --}}
            <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                <span class="inline-flex items-center gap-2">
                    @if ($card['owner']['avatar'])
                        <img src="{{ $card['owner']['avatar'] }}" alt="" width="28" height="28" class="w-7 h-7 rounded-full object-cover">
                    @else
                        <span class="w-7 h-7 rounded-full bg-zinc-200 dark:bg-zinc-700 text-xs font-bold text-zinc-700 dark:text-zinc-100 flex items-center justify-center">{{ mb_substr($card['owner']['name'] ?: '?', 0, 1) }}</span>
                    @endif
                    <span>{{ __('quran.playlists.by') }} <strong class="text-zinc-900 dark:text-white">{{ $card['owner']['name'] }}</strong></span>
                </span>
                <span class="tabular-nums">{{ __('quran.playlists.surahs_count', ['count' => $num($card['items'])]) }}</span>
                <span class="tabular-nums">{{ __('quran.playlists.reciters_count', ['count' => $num(count($reciters))]) }}</span>
                <span class="inline-flex items-center gap-1 tabular-nums"><i data-lucide="headphones" class="w-4 h-4"></i>{{ $num($card['plays']) }} {{ __('quran.playlists.plays') }}</span>
            </div>

            {{-- Reciter names --}}
            @if ($reciters)
                <ul class="mt-4 flex flex-wrap gap-2" aria-label="{{ __('quran.public.reciters') }}">
                    @foreach ($reciters as $r)
                        <li class="inline-flex items-center gap-2 ps-1 pe-3 py-1 rounded-full bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                            @if ($r['image'])
                                <img src="{{ $r['image'] }}" alt="" width="24" height="24" class="w-6 h-6 rounded-full object-cover">
                            @else
                                <span class="w-6 h-6 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] text-white text-[11px] font-bold flex items-center justify-center">{{ mb_substr($r['name'], 0, 1) }}</span>
                            @endif
                            {{ $r['name'] }}
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- Actions --}}
            <div class="mt-7 flex flex-wrap items-center gap-3">
                <button type="button" @click="playAll(0)" :disabled="!playable.length"
                        class="inline-flex items-center gap-2 h-12 px-6 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold shadow-sm transition-transform duration-200 hover:-translate-y-0.5 active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/60">
                    <i data-lucide="play" class="w-5 h-5 fill-current {{ $isRtl ? '-scale-x-100' : '' }}" x-show="!listPlaying"></i>
                    <i data-lucide="pause" class="w-5 h-5 fill-current" x-show="listPlaying" x-cloak></i>
                    {{ __('quran.playlists.play_all') }}
                </button>

                @if ($playlist->is_public)
                    <x-site.share-button :url="$shareUrl" :title="$playlist->name" :text="$playlist->description" variant="surface" />
                @endif

                @if ($isOwner)
                    <a href="{{ route('public.quran.playlists.edit', $playlist->slug) }}"
                       class="inline-flex items-center gap-2 h-11 px-4 rounded-xl text-sm font-semibold border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 hover:border-zinc-400 transition active:scale-[0.98]">
                        <i data-lucide="pencil" class="w-4 h-4"></i>{{ __('quran.playlists.edit') }}
                    </a>
                    <button type="button" @click="toggleVisibility()" :disabled="visibilityPending"
                            class="inline-flex items-center gap-2 h-11 px-4 rounded-xl text-sm font-semibold border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 hover:border-zinc-400 transition active:scale-[0.98] disabled:opacity-50">
                        <i :data-lucide="isPublic ? 'eye-off' : 'globe'" class="w-4 h-4"></i>
                        <span x-text="isPublic ? @js(__('quran.playlists.make_private')) : @js(__('quran.playlists.make_public'))"></span>
                    </button>
                    <form method="POST" action="{{ route('public.quran.playlists.destroy', $playlist->slug) }}"
                          @submit="if (!confirm(@js(__('quran.playlists.delete_confirm')))) $event.preventDefault()">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-2 h-11 px-4 rounded-xl text-sm font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition active:scale-[0.98]">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>{{ __('quran.playlists.delete') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Portrait collage (overlapped tiles) --}}
        <div class="pl-collage relative hidden md:grid grid-cols-2 gap-3 max-w-sm justify-self-end w-full" aria-hidden="true">
            @foreach (array_slice($reciters, 0, 4) as $i => $r)
                <div style="--i: {{ $i }}" class="aspect-square rounded-3xl overflow-hidden bg-zinc-100 dark:bg-zinc-800 shadow-[0_20px_40px_-24px_rgba(0,86,179,0.35)] {{ $i % 2 === 1 ? 'mt-8' : '' }}">
                    @if ($r['image'])
                        <img src="{{ $r['image'] }}" alt="" loading="lazy" class="w-full h-full object-cover">
                    @else
                        <span class="pl-initial w-full h-full flex items-center justify-center text-5xl font-black text-white bg-gradient-to-br from-[#0056b3] to-[#218838]">{{ mb_substr($r['name'], 0, 1) }}</span>
                    @endif
                </div>
            @endforeach
            @if (count($reciters) === 0)
                <div class="col-span-2 aspect-[4/3] rounded-3xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center text-white/80">
                    <i data-lucide="list-music" class="w-16 h-16"></i>
                </div>
            @endif
        </div>
    </header>

    {{-- ============================ TRACK LIST ============================ --}}
    @if (count($tracks))
        <ol class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 divide-y divide-zinc-100 dark:divide-zinc-800" role="list">
            @foreach ($tracks as $i => $t)
                <li class="grid grid-cols-[auto_1fr_auto] sm:grid-cols-[auto_1fr_auto_auto_auto] gap-x-3 sm:gap-x-4 gap-y-1 items-center px-3 sm:px-4 py-3 transition-colors"
                    :class="isActive({{ $i }}) ? 'bg-primary/5 dark:bg-primary/10' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800/50'">
                    <span class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold tabular-nums shrink-0 bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200"
                          :class="isActive({{ $i }}) ? 'text-primary' : ''">
                        <span x-show="!isActive({{ $i }})">{{ $num($t['surah']) }}</span>
                        <span x-show="isActive({{ $i }})" x-cloak class="eq-bars text-primary" aria-hidden="true"><span></span><span></span><span></span></span>
                    </span>

                    <div class="min-w-0">
                        <a href="{{ $t['readerUrl'] }}" class="font-bold text-zinc-900 dark:text-white hover:text-primary transition-colors truncate block"
                           title="{{ __('quran.playlists.open_reader') }}">{{ $t['surahName'] }}</a>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 tabular-nums">{{ __('quran.playlists.verses', ['count' => $num($t['verses'] ?? 0)]) }}</p>
                    </div>

                    <div class="col-span-3 sm:col-span-1 flex items-center gap-2 min-w-0 sm:min-w-[180px]">
                        @if ($t['recording'])
                            @if ($t['reciterImage'])
                                <img src="{{ $t['reciterImage'] }}" alt="" width="32" height="32" loading="lazy" class="w-8 h-8 rounded-full object-cover shrink-0">
                            @else
                                <span class="w-8 h-8 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] text-white text-xs font-bold flex items-center justify-center shrink-0">{{ mb_substr($t['reciterName'] ?? '?', 0, 1) }}</span>
                            @endif
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-zinc-800 dark:text-zinc-100 truncate">{{ $t['reciterName'] }}</span>
                                <span class="block text-[11px] text-zinc-500 dark:text-zinc-400 truncate">{{ $t['recordingLabel'] }}</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700 dark:text-amber-300">
                                <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>{{ __('quran.playlists.needs_reciter') }}
                            </span>
                        @endif
                    </div>

                    <span class="hidden sm:block text-sm text-zinc-500 dark:text-zinc-400 tabular-nums w-12 text-end" dir="ltr">{{ $fmtDur($t['duration']) ?? '—' }}</span>

                    <button type="button" @click="playAll({{ $i }})" @disabled(! $t['url'])
                            class="w-11 h-11 rounded-full flex items-center justify-center text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition active:scale-[0.95] disabled:opacity-30 justify-self-end"
                            aria-label="{{ __('quran.playlists.play') }} {{ $t['surahName'] }}">
                        <i data-lucide="play" class="w-4 h-4 fill-current {{ $isRtl ? '-scale-x-100' : '' }}" x-show="!(isActive({{ $i }}) && $store.audioPlayer.playing)"></i>
                        <i data-lucide="pause" class="w-4 h-4 fill-current" x-show="isActive({{ $i }}) && $store.audioPlayer.playing" x-cloak></i>
                    </button>
                </li>
            @endforeach
        </ol>
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
            return this.tracks.filter(t => t.url).map(t => ({ url: t.url, title: t.surahName, subtitle: t.reciterName || '' }));
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
