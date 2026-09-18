@extends('layouts.public')

@section('title', $channel['title'].' | '.config('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) ($channel['description'] ?? '')), 160))

@section('content')
@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    // Related channels keep their status and view count in the tile subtitle.
    $relatedItems = collect($related)->map(fn (array $item): array => [
        'title' => $item['title'],
        'url' => $item['url'],
        'image' => $item['thumbnail'],
        'icon' => 'radio',
        'sub' => ($item['is_live'] ? __('media.public.live_now') : __('media.public.live_status_'.$item['status']))
            .' · '.number_format($item['views_count']).' '.__('media.public.views'),
    ])->all();
@endphp

<div class="sx min-h-[100dvh] pb-20" x-data="liveChannelPage()">

    <div class="sx-wrap pt-6">
        <nav aria-label="breadcrumb" class="flex flex-wrap items-center gap-2">
            <a href="{{ route('public.media') }}" class="sx-chip">
                <i data-lucide="{{ $isRtl ? 'arrow-right' : 'arrow-left' }}" aria-hidden="true"></i>{{ __('media.public.page_title') }}
            </a>
            <a href="{{ route('public.media.live.index') }}" class="sx-chip">
                <i data-lucide="radio" aria-hidden="true"></i>{{ __('media.public.live_channels') }}
            </a>
        </nav>
    </div>

    {{-- ============================================================ --}}
    {{-- PLAYER — the channel itself is the page's opening image      --}}
    {{-- ============================================================ --}}
    @php
        // Audio has no picture, so it gets a compact card in the normal page
        // gutter rather than the full-bleed black stage a video stream fills.
        $isAudioChannel = (bool) ($channel['stream_source']['audio_only'] ?? false);
    @endphp
    <section @class(['mt-6', 'bg-black' => ! $isAudioChannel]) aria-label="{{ $channel['title'] }}">
        <div @class(['sx-wrap', '!px-0 lg:!px-8' => ! $isAudioChannel])>
            {{-- Our own chrome over whatever the channel streams — YouTube, HLS,
                 DASH or an audio-only radio mount. The component picks its own
                 shape: a 16:9 stage for video, a sound player for audio. --}}
            <x-public.media.stream-player
                :source="$channel['stream_source']"
                :poster="$channel['thumbnail']"
                :title="$channel['title']"
                :is-live="$channel['is_live']" />
        </div>
    </section>

    <div class="sx-wrap pt-10">
        <div class="sx-reading">
            <article class="sx-surface sx-article grid gap-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <h1 class="sx-section-title min-w-0">{{ $channel['title'] }}</h1>
                    <button type="button" @click="share()" class="sx-btn shrink-0">
                        <i data-lucide="share-2" aria-hidden="true"></i>
                        <span x-text="shareCopied ? @js(__('media.public.link_copied')) : @js(__('media.public.share'))"></span>
                    </button>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span @class(['sx-chip', 'is-danger' => $channel['is_live']])>
                        <i data-lucide="{{ $channel['is_live'] ? 'radio' : 'circle' }}" aria-hidden="true"></i>
                        {{ $channel['is_live'] ? __('media.public.live_now') : __('media.public.live_status_'.$channel['status']) }}
                    </span>
                    <span class="sx-chip">
                        <i data-lucide="eye" aria-hidden="true"></i><span class="tabular-nums">{{ number_format($channel['views_count']) }}</span> {{ __('media.public.views') }}
                    </span>
                </div>

                @if ($channel['description'])
                    <p class="sx-prose border-t border-[color:var(--sx-line)] pt-6">{{ $channel['description'] }}</p>
                @endif
            </article>

            <aside class="sx-aside">
                @if (empty($relatedItems))
                    <div class="sx-surface p-5">
                        <h2 class="mb-4 flex items-center gap-2 font-extrabold text-[color:var(--sx-ink)]">
                            <i data-lucide="radio" class="h-5 w-5 text-[color:var(--sx-accent)]" aria-hidden="true"></i>{{ __('media.public.related_channels') }}
                        </h2>
                        <p class="text-sm text-[color:var(--sx-muted)]">{{ __('media.public.no_live_channels') }}</p>
                    </div>
                @else
                    <x-public.editorial.related :title="__('media.public.related_channels')" icon="radio" :items="$relatedItems" />
                @endif
            </aside>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function liveChannelPage() {
        return {
            shareCopied: false,
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
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush
@endsection
