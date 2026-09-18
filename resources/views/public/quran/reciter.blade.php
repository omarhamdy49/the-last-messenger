@extends('layouts.public')

@section('title', $reciter['name'].' | '.__('quran.public.reciters'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) ($reciter['description'] ?? __('quran.public.reciters_page_description'))), 160))

@push('head')
<style>
    /* Equalizer bars for the currently-playing surah tile */
    .eq-bars { display: inline-flex; align-items: flex-end; gap: 2px; height: 14px; }
    .eq-bars span { width: 3px; border-radius: 2px; background: currentColor; animation: eqBounce 1s ease-in-out infinite; }
    .eq-bars span:nth-child(2) { animation-delay: .2s; }
    .eq-bars span:nth-child(3) { animation-delay: .4s; }
    @keyframes eqBounce { 0%, 100% { height: 4px; } 50% { height: 14px; } }
    @media (prefers-reduced-motion: reduce) { .eq-bars span { animation: none; height: 10px; } }
</style>
@endpush

@section('content')
@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<div class="min-h-dvh font-['Cairo'] bg-gray-50 dark:bg-gray-950 transition-colors duration-300"
     x-data
     :style="$store.audioPlayer.track ? 'padding-bottom: 7rem' : ''">

    {{-- ============================================================ --}}
    {{-- PROFILE HERO — brand gradient band, avatar, meta chips        --}}
    {{-- ============================================================ --}}
    <section class="relative overflow-hidden bg-gradient-to-l from-[#0056b3] via-[#116a53] to-[#218838]">
        <div class="absolute inset-0 opacity-[0.12]"
             style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;"></div>

        <div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-12 sm:pt-14 sm:pb-16">
            <a href="{{ route('public.quran.reciters') }}"
               class="inline-flex items-center gap-2 text-white/85 hover:text-white text-sm font-semibold mb-8 transition-colors">
                <i data-lucide="{{ $isRtl ? 'arrow-right' : 'arrow-left' }}" class="w-4 h-4"></i>
                <span>{{ __('quran.public.back_to_reciters') }}</span>
            </a>

            <div class="flex flex-col sm:flex-row items-center gap-6 sm:gap-10 text-center sm:text-start">
                {{-- Avatar --}}
                <div class="relative w-32 h-32 sm:w-40 sm:h-40 shrink-0 rounded-full p-1 bg-white/25 shadow-2xl">
                    @if ($reciter['image'])
                        <img src="{{ $reciter['image'] }}" alt="{{ $reciter['name'] }}"
                             class="w-full h-full rounded-full object-cover">
                    @else
                        <div class="w-full h-full rounded-full bg-white/15 backdrop-blur-sm flex items-center justify-center">
                            <span class="text-white text-5xl sm:text-6xl font-bold">{{ mb_substr($reciter['name'], 0, 1) }}</span>
                        </div>
                    @endif
                    @if ($reciter['is_featured'])
                        <span class="absolute bottom-1 end-1 w-9 h-9 rounded-full bg-white text-[#b8860b] flex items-center justify-center shadow-lg"
                              title="{{ __('quran.public.featured_reciters') }}">
                            <i data-lucide="star" class="w-5 h-5 fill-current"></i>
                        </span>
                    @endif
                </div>

                {{-- Name + chips --}}
                <div class="min-w-0">
                    <div class="flex items-center justify-center sm:justify-start gap-4 mb-4">
                        <h1 class="text-3xl sm:text-5xl font-black text-white leading-tight">{{ $reciter['name'] }}</h1>
                        {{-- Favourite (bookmark) toggle — same optimistic pattern as media pages --}}
                        <button type="button"
                                x-data="reciterSave()"
                                @click="toggleSave()"
                                :aria-pressed="saved"
                                :disabled="savePending"
                                :title="saved ? @js(__('profile.remove_bookmark')) : @js(__('profile.add_bookmark'))"
                                :aria-label="saved ? @js(__('profile.remove_bookmark')) : @js(__('profile.add_bookmark'))"
                                class="shrink-0 w-11 h-11 rounded-full bg-white/15 hover:bg-white/25 backdrop-blur-sm text-white flex items-center justify-center transition-[background-color,transform] duration-200 active:scale-[0.95] disabled:opacity-60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/70">
                            <i data-lucide="heart" class="w-5 h-5 transition-transform duration-200" :class="saved ? 'fill-current scale-110' : ''"></i>
                        </button>
                    </div>
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5">
                        @if ($reciter['country'])
                            <span class="inline-flex items-center gap-1.5 bg-white/15 backdrop-blur-sm text-white text-sm font-semibold px-4 py-1.5 rounded-full">
                                <span aria-hidden="true">{{ $reciter['country_flag'] }}</span>{{ $reciter['country_name'] }}
                            </span>
                        @endif
                        @if ($reciter['style'])
                            <span class="inline-flex items-center gap-1.5 bg-white/15 backdrop-blur-sm text-white text-sm font-semibold px-4 py-1.5 rounded-full">
                                <i data-lucide="mic" class="w-4 h-4" aria-hidden="true"></i>{{ $reciter['styleLabel'] ?? $reciter['style'] }}
                            </span>
                        @endif
                        @if ($reciter['riwayah'])
                            <span class="inline-flex items-center gap-1.5 bg-white/15 backdrop-blur-sm text-white text-sm font-semibold px-4 py-1.5 rounded-full">
                                <i data-lucide="book-open" class="w-4 h-4"></i>{{ $reciter['riwayah'] }}
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-1.5 bg-white/15 backdrop-blur-sm text-white text-sm font-semibold px-4 py-1.5 rounded-full">
                            <i data-lucide="audio-lines" class="w-4 h-4"></i>
                            {{ count($recitations) }} {{ __('quran.public.recording_unit') }}
                        </span>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- About (localized description, else the source's info text) --}}
        @php $about = filled($reciter['description']) ? $reciter['description'] : \App\Domain\Quran\Services\ReaderService::plain($reciter['info'] ?? null); @endphp
        @if (filled($about))
            <section class="mb-10">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                    <span class="w-1 h-7 rounded-full bg-gradient-to-b from-[#0056b3] to-[#218838]"></span>
                    {{ __('quran.public.reciter_about') }}
                </h2>
                <p class="text-gray-700 dark:text-gray-300 leading-relaxed max-w-3xl whitespace-pre-line" dir="auto">{{ $about }}</p>
            </section>
        @endif

        {{-- Recitations --}}
        <section id="recitations" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white {{ empty($recordings) ? 'mb-6' : 'mb-4' }} flex items-center gap-3">
                <span class="w-1 h-7 rounded-full bg-gradient-to-b from-[#0056b3] to-[#218838]"></span>
                {{ __('quran.public.reciter_recitations') }}
            </h2>

            {{-- One tab per recording of this reciter (murattal / mujawwad / another riwayah).
                 Each recording is its own page, so the tabs are links: deep-linkable, no JS,
                 and #recitations lands the reader back on the list they were switching. --}}
            @if (! empty($recordings))
                <nav class="mb-6 -mx-4 px-4 sm:mx-0 sm:px-0 overflow-x-auto border-b border-gray-200 dark:border-gray-800 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                     aria-label="{{ __('quran.public.other_recordings') }}">
                    <ul class="flex gap-1" role="list">
                        @foreach ($recordings as $recording)
                            <li class="shrink-0">
                                <a href="{{ route('public.quran.reciter', $recording['slug']) }}#recitations" title="{{ $recording['name'] }}"
                                   @if ($recording['current']) aria-current="page" @endif
                                   class="relative -mb-px inline-flex min-h-[3rem] items-center gap-2 whitespace-nowrap border-b-2 px-4 text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3] focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-950
                                          {{ $recording['current']
                                              ? 'border-[#0056b3] text-[#0056b3] dark:border-[#64CCC5] dark:text-[#64CCC5]'
                                              : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300 dark:text-gray-400 dark:hover:text-white dark:hover:border-gray-600' }}">
                                    <i data-lucide="disc-3" class="w-4 h-4" aria-hidden="true"></i>
                                    <span>{{ $recording['label'] }}</span>
                                    {{-- `count` is absent from a page cached before it existed; the tab still works. --}}
                                    @isset($recording['count'])
                                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums
                                                     {{ $recording['current'] ? 'bg-[#0056b3]/10 dark:bg-[#64CCC5]/10' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">{{ $recording['count'] }}</span>
                                    @endisset
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            @if (empty($recitations))
                <div class="rounded-3xl border-2 border-dashed border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900/40 py-16 text-center">
                    <i data-lucide="audio-lines" class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600"></i>
                    <p class="text-gray-500 dark:text-gray-400 font-semibold">{{ __('quran.public.reciter_no_recitations') }}</p>
                </div>
            @else
                {{-- Premium surah tiles — clicking routes through the shared
                     bottom-bar player ($store.audioPlayer), same as the Quran
                     reader. The whole list is handed over as the player's
                     playlist, so its ⏮/⏭ walk this reciter's recitations and one
                     surah rolls into the next. The active tile shows equalizer
                     bars / pause. --}}
                @php
                    $playable = collect($recitations)->filter(fn ($r) => filled($r['audio_url']))->values();
                    $tracks = $playable->map(fn ($r) => [
                        'url' => $r['audio_url'],
                        'title' => $r['surah_name'],
                        'subtitle' => $reciter['name'],
                    ])->all();
                @endphp
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3"
                     x-data="{
                        tracks: @js($tracks),
                        isCurrent(url) { return $store.audioPlayer.track?.url === url },
                        isPlaying(url) { return this.isCurrent(url) && $store.audioPlayer.playing },
                        copied: null,
                        // Share the reading page with this reciter preselected, not the
                        // mp3 — the page plays the same file and outlives a moved file.
                        copyLink(number, url) {
                            navigator.clipboard?.writeText(url);
                            this.copied = number;
                            setTimeout(() => { if (this.copied === number) this.copied = null }, 1600);
                        },
                     }">
                    @foreach ($playable as $i => $recitation)
                        <button type="button"
                                {{-- setPlaylist toggles when the tapped track is already loaded --}}
                                @click="$store.audioPlayer.setPlaylist(tracks, {{ $i }})"
                                class="group flex items-center gap-3 rounded-2xl p-3.5 text-start border shadow-sm cursor-pointer transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 motion-reduce:transform-none bg-white dark:bg-gray-900 border-gray-100 dark:border-white/10"
                                :class="isCurrent(@js($recitation['audio_url'])) ? 'ring-2 ring-[#218838]/60 shadow-lg' : ''"
                                :aria-label="isPlaying(@js($recitation['audio_url'])) ? @js(__('quran.public.pause').' — '.$recitation['surah_name']) : @js(__('quran.public.listen_now').' — '.$recitation['surah_name'])">
                            <span class="w-9 h-9 shrink-0 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-white text-xs font-bold flex items-center justify-center tabular-nums">
                                {{ $recitation['surah_number'] }}
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="block font-bold text-sm text-gray-900 dark:text-white truncate">{{ $recitation['surah_name'] }}</span>
                                @if (! empty($recitation['has_timestamps']))
                                    <span class="text-[11px] text-[#218838]" title="{{ __('quran.public.follow_along_available') }}">{{ __('quran.public.follow_along') }}</span>
                                @endif
                            </span>
                            @php($surahLink = route('public.quran.surah', ['surahNumber' => $recitation['surah_number'], 'reciter' => $reciter['id']]))
                            <span class="shrink-0 flex items-center gap-1.5">
                                <a href="{{ $surahLink }}" @click.stop
                                   class="text-gray-400 hover:text-[#0056b3]" title="{{ __('quran.public.read_with_reciter') }}" aria-label="{{ __('quran.public.read_with_reciter') }}">
                                    <i data-lucide="book-open" class="w-4 h-4"></i>
                                </a>

                                {{-- Download. The file is on the reciter's own host, so the
                                     browser decides between saving and opening it. --}}
                                <a href="{{ $recitation['audio_url'] }}" download @click.stop
                                   class="text-gray-400 hover:text-[#0056b3]" title="{{ __('quran.public.download_surah') }}"
                                   aria-label="{{ __('quran.public.download_surah').' — '.$recitation['surah_name'] }}">
                                    <i data-lucide="download" class="w-4 h-4"></i>
                                </a>

                                {{-- A nested <button> inside the row button is invalid, so the
                                     copy control is a span that behaves like one. --}}
                                <span role="button" tabindex="0"
                                      @click.stop="copyLink({{ $recitation['surah_number'] }}, @js($surahLink))"
                                      @keydown.enter.stop.prevent="copyLink({{ $recitation['surah_number'] }}, @js($surahLink))"
                                      class="cursor-pointer text-gray-400 hover:text-[#0056b3]"
                                      :class="copied === {{ $recitation['surah_number'] }} ? 'text-[#218838]' : ''"
                                      :title="copied === {{ $recitation['surah_number'] }} ? @js(__('quran.public.link_copied')) : @js(__('quran.public.copy_surah_link'))"
                                      :aria-label="copied === {{ $recitation['surah_number'] }} ? @js(__('quran.public.link_copied')) : @js(__('quran.public.copy_surah_link'))">
                                    <i data-lucide="link" class="w-4 h-4" x-show="copied !== {{ $recitation['surah_number'] }}"></i>
                                    <i data-lucide="check" class="w-4 h-4" x-show="copied === {{ $recitation['surah_number'] }}" x-cloak></i>
                                </span>
                            </span>

                            {{-- Play / pause / equalizer state --}}
                            <span class="w-9 h-9 shrink-0 rounded-full flex items-center justify-center transition-all duration-300 text-[#0056b3] dark:text-[#64CCC5] bg-[#0056b3]/10 dark:bg-white/10 group-hover:bg-gradient-to-r group-hover:from-[#0056b3] group-hover:to-[#218838] group-hover:text-white">
                                <template x-if="isPlaying(@js($recitation['audio_url']))">
                                    <span class="eq-bars" aria-hidden="true"><span></span><span></span><span></span></span>
                                </template>
                                <template x-if="isCurrent(@js($recitation['audio_url'])) && !$store.audioPlayer.playing">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6 4a1 1 0 011 1v10a1 1 0 11-2 0V5a1 1 0 011-1zm7 0a1 1 0 011 1v10a1 1 0 11-2 0V5a1 1 0 011-1z"/></svg>
                                </template>
                                <template x-if="!isCurrent(@js($recitation['audio_url']))">
                                    <svg class="w-4 h-4 {{ $isRtl ? '-scale-x-100' : '' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.84A1 1 0 004 3.7v12.6a1 1 0 001.5.86l11-6.3a1 1 0 000-1.72l-11-6.3z"/></svg>
                                </template>
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function reciterSave() {
        return {
            isAuthenticated: @json(auth()->check()),
            loginUrl: @js(route('public.login')),
            profileId: {{ (int) $reciter['id'] }},
            storeUrl: @js(route('public.favorites.store')),
            destroyUrl: @js(route('public.favorites.destroy')),
            saved: @json((bool) $reciter['isSaved']),
            savePending: false,

            async toggleSave() {
                if (!this.isAuthenticated) { window.location.href = this.loginUrl; return; }
                if (this.savePending) return;
                const adding = !this.saved;
                this.saved = adding; // optimistic
                this.savePending = true;
                try {
                    const response = await fetch(adding ? this.storeUrl : this.destroyUrl, {
                        method: adding ? 'POST' : 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ type: 'reciter', id: this.profileId }),
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok || payload.success === false) throw new Error('favorite_failed');
                    if (this.$store.toast && payload.message) this.$store.toast.push(payload.message, 'success');
                } catch (e) {
                    this.saved = !adding; // roll back
                    if (this.$store.toast) this.$store.toast.push(@js(__('quran.playlists.error')), 'error');
                } finally {
                    this.savePending = false;
                }
            },
        };
    }
</script>
@endpush
