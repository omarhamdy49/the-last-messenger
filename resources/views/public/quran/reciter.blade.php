@extends('layouts.public')

@section('title', $reciter['name'].' | '.__('quran.public.reciters'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) ($reciter['description'] ?? __('quran.public.reciters_page_description'))), 160))

@push('head')
<style>
    /* The only page-scoped rule: a playing indicator the system has no glyph for. */
    .eq-bars { display: inline-flex; align-items: flex-end; gap: 2px; height: 14px; }
    .eq-bars span { width: 3px; border-radius: 2px; background: currentColor; animation: eqBounce 1s ease-in-out infinite; }
    .eq-bars span:nth-child(2) { animation-delay: .2s; }
    .eq-bars span:nth-child(3) { animation-delay: .4s; }
    @keyframes eqBounce { 0%, 100% { height: 4px; } 50% { height: 14px; } }
    @media (prefers-reduced-motion: reduce) { .eq-bars span { animation: none; height: 10px; } }
</style>
@endpush

@section('content')
@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $playable = collect($recitations)->filter(fn ($r) => filled($r['audio_url']))->values();
    $tracks = $playable->map(fn ($r) => [
        'url' => $r['audio_url'],
        'title' => $r['surah_name'],
        'subtitle' => $reciter['name'],
    ])->all();
@endphp

<div class="sx min-h-[100dvh] pb-20"
     x-data
     :style="$store.audioPlayer.track ? 'padding-bottom: 7rem' : ''">

    <x-public.editorial.hero variant="reading" :image="$reciter['image'] ?: null" :title="$reciter['name']">
        <x-slot:top>
            <nav class="mb-5 flex flex-wrap items-center gap-2 text-sm opacity-80" aria-label="{{ __('site.breadcrumb') }}">
                <a href="{{ route('public.quran') }}" class="hover:underline underline-offset-4">{{ __('site.quran') }}</a>
                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4" aria-hidden="true"></i>
                <a href="{{ route('public.quran.reciters') }}" class="hover:underline underline-offset-4">{{ __('quran.public.back_to_reciters') }}</a>
            </nav>
        </x-slot:top>

        <x-slot:chips>
            @if ($reciter['is_featured'])
                <span class="sx-glass-chip"><i data-lucide="star" aria-hidden="true"></i>{{ __('site.featured') }}</span>
            @endif
            @if ($reciter['country'])
                <span class="sx-glass-chip">
                    <span aria-hidden="true">{{ $reciter['country_flag'] }}</span><span class="sx-clamp-1 max-w-[12rem]">{{ $reciter['country_name'] }}</span>
                </span>
            @endif
            @if ($reciter['style'])
                <span class="sx-glass-chip"><i data-lucide="mic" aria-hidden="true"></i>{{ $reciter['style'] }}</span>
            @endif
            @if ($reciter['riwayah'])
                <span class="sx-glass-chip"><i data-lucide="book-open" aria-hidden="true"></i><span class="sx-clamp-1 max-w-[12rem]" dir="auto">{{ $reciter['riwayah'] }}</span></span>
            @endif
            <span class="sx-glass-chip tabular-nums">
                <i data-lucide="audio-lines" aria-hidden="true"></i>{{ count($recitations) }} {{ __('quran.public.recording_unit') }}
            </span>
        </x-slot:chips>

        <x-slot:actions>
            {{-- Favourite (bookmark) toggle — same optimistic pattern as media pages --}}
            <button type="button"
                    x-data="reciterSave()"
                    @click="toggleSave()"
                    :aria-pressed="saved"
                    :disabled="savePending"
                    :title="saved ? @js(__('profile.remove_bookmark')) : @js(__('profile.add_bookmark'))"
                    :aria-label="saved ? @js(__('profile.remove_bookmark')) : @js(__('profile.add_bookmark'))"
                    class="sx-btn is-glass w-11 px-0 disabled:opacity-60">
                <i data-lucide="heart" class="transition-transform duration-200" :class="saved ? 'fill-current scale-110' : ''" aria-hidden="true"></i>
            </button>
        </x-slot:actions>
    </x-public.editorial.hero>

    <div class="sx-wrap mt-8 grid gap-10">

        @if (filled($reciter['description']))
            <x-public.editorial.section id="about" icon="user-round" :title="__('quran.public.reciter_about')">
                <p class="sx-prose">{{ $reciter['description'] }}</p>
            </x-public.editorial.section>
        @endif

        <x-quran.recording-switcher :variants="$recordingVariants" :selected-id="$selectedRecordingId" :slug="$canonicalSlug" />

        <x-public.editorial.section id="recitations" icon="audio-lines" :title="__('quran.public.reciter_recitations')">
            @if (empty($recitations))
                <x-public.editorial.empty-state icon="audio-lines" :title="__('quran.public.reciter_no_recitations')" />
            @else
                {{-- Tapping a surah routes through the shared bottom-bar player
                     ($store.audioPlayer), same as the reader. The whole list is handed
                     over as the player's playlist, so its ⏮/⏭ walk this reciter's
                     recitations and one surah rolls into the next. --}}
                <div class="sx-grid is-tight"
                     x-data="{
                        tracks: @js($tracks),
                        isCurrent(url) { return $store.audioPlayer.track?.url === url },
                        isPlaying(url) { return this.isCurrent(url) && $store.audioPlayer.playing },
                     }">
                    @foreach ($playable as $i => $recitation)
                        <button type="button"
                                {{-- setPlaylist toggles when the tapped track is already loaded --}}
                                @click="$store.audioPlayer.setPlaylist(tracks, {{ $i }})"
                                class="sx-tile group text-start hover:border-[color:var(--sx-line-strong)]"
                                :class="isCurrent(@js($recitation['audio_url'])) && '!border-[color:var(--sx-accent)] !bg-[color:var(--sx-accent-soft)]'"
                                :aria-pressed="isPlaying(@js($recitation['audio_url']))"
                                :aria-label="isPlaying(@js($recitation['audio_url'])) ? @js(__('quran.public.pause').' — '.$recitation['surah_name']) : @js(__('quran.public.listen_now').' — '.$recitation['surah_name'])">
                            <span class="sx-tile-icon text-sm font-extrabold tabular-nums">{{ $recitation['surah_number'] }}</span>
                            <span class="sx-tile-title sx-clamp-1 text-sm" title="{{ $recitation['surah_name'] }}">{{ $recitation['surah_name'] }}</span>
                            <span class="grid h-9 w-9 place-items-center rounded-full bg-[color:var(--sx-accent-soft)] text-[color:var(--sx-accent)] transition-colors group-hover:bg-[color:var(--sx-accent)] group-hover:text-white"
                                  :class="isCurrent(@js($recitation['audio_url'])) && '!text-white'"
                                  :style="isCurrent(@js($recitation['audio_url'])) ? 'background: var(--sx-grad)' : ''">
                                <span class="eq-bars" x-show="isPlaying(@js($recitation['audio_url']))" x-cloak aria-hidden="true"><span></span><span></span><span></span></span>
                                <i data-lucide="pause" class="h-4 w-4" x-show="isCurrent(@js($recitation['audio_url'])) && !$store.audioPlayer.playing" x-cloak aria-hidden="true"></i>
                                <i data-lucide="play" class="h-4 w-4 {{ $isRtl ? '-scale-x-100' : '' }}" x-show="!isCurrent(@js($recitation['audio_url']))" aria-hidden="true"></i>
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif
        </x-public.editorial.section>
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
