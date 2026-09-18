@extends('layouts.public')

{{-- SEO: per-locale overrides from the admin SEO tab; fallback "{title} | {app.name}" --}}
@section('title', $program['seo_title'] ?: $program['title'].' | '.config('app.name'))
@section('meta_description', $program['seo_description'] ?: \Illuminate\Support\Str::limit(strip_tags((string) $program['description']), 160))

@if($program['seo_keywords'])
@push('head')
    <meta name="keywords" content="{{ $program['seo_keywords'] }}">
@endpush
@endif

@push('head')
    @include('partials.module-embed-styles')
@endpush

@section('content')
@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<div class="sx min-h-[100dvh] pb-20" x-data="programPage()">

    {{-- ============================================================ --}}
    {{-- HERO — full-bleed backdrop, programme facts, actions          --}}
    {{-- ============================================================ --}}
    {{-- banner_hq is maxresdefault (1280px) when the banner is a CDN thumbnail;
         the 480px hqdefault visibly pixelates at hero size. --}}
    <x-public.editorial.hero :image="$program['banner_hq'] ?? $program['banner']" variant="story" :title="$program['title']"
        :lead="$program['description'] ? strip_tags((string) $program['description']) : null">
        <x-slot:top>
            <nav aria-label="breadcrumb" class="flex flex-wrap items-center gap-2">
                <a href="{{ route('public.media') }}" class="sx-glass-chip hover:bg-[rgb(255_255_255/0.22)]">
                    <i data-lucide="{{ $isRtl ? 'arrow-right' : 'arrow-left' }}" aria-hidden="true"></i>{{ __('media.public.page_title') }}
                </a>
            </nav>
        </x-slot:top>

        <x-slot:chips>
            <span class="sx-glass-chip sx-clamp-1 max-w-[14rem]" title="{{ $program['category_label'] }}">
                <i data-lucide="folder" aria-hidden="true"></i>{{ $program['category_label'] }}
            </span>
            @if ($program['is_trending'])
                <span class="sx-glass-chip"><i data-lucide="trending-up" aria-hidden="true"></i>{{ __('media.public.trending_now') }}</span>
            @endif
            @if (! empty($seasons))
                <span class="sx-glass-chip"><i data-lucide="layers" aria-hidden="true"></i>{{ __('media.public.season_label', ['number' => $seasons[0]]) }}</span>
            @endif
        </x-slot:chips>

        <x-slot:meta>
            <span class="sx-glass-chip"><i data-lucide="list-video" aria-hidden="true"></i><span class="tabular-nums">{{ __('media.public.episodes_count', ['count' => $program['episodes_count']]) }}</span></span>
            @if ($program['year'])
                <span class="sx-glass-chip"><i data-lucide="calendar" aria-hidden="true"></i><span class="tabular-nums">{{ $program['year'] }}</span></span>
            @endif
            <span class="sx-glass-chip"><i data-lucide="users" aria-hidden="true"></i>{{ $program['maturity_label'] }}</span>
            @if ($program['host'])
                <span class="sx-glass-chip sx-clamp-1 max-w-[16rem]" title="{{ $program['host'] }}">
                    <i data-lucide="mic" aria-hidden="true"></i>{{ __('media.public.presented_by') }}: {{ $program['host'] }}
                </span>
            @endif
        </x-slot:meta>

        <x-slot:actions>
            @if (! empty($episodes))
                <a href="{{ $episodes[0]['url'] }}" class="sx-btn is-light"><i data-lucide="play" aria-hidden="true"></i>{{ __('media.public.watch_now') }}</a>
            @endif

            <button type="button" @click="toggleSave()" :aria-pressed="saved ? 'true' : 'false'" :disabled="savePending"
                    :title="isAuthenticated ? '' : @js(__('media.public.login_to_save'))"
                    class="sx-btn is-glass disabled:opacity-60">
                <i data-lucide="loader-2" class="animate-spin" x-show="savePending" x-cloak aria-hidden="true"></i>
                <i data-lucide="plus" x-show="!savePending && !saved" aria-hidden="true"></i>
                <i data-lucide="check" x-show="!savePending && saved" x-cloak aria-hidden="true"></i>
                <span x-text="saved ? @js(__('media.public.in_my_list')) : @js(__('media.public.my_list'))"></span>
            </button>

            <button type="button" @click="share()" class="sx-btn is-glass">
                <i data-lucide="share-2" aria-hidden="true"></i>
                <span x-text="shareCopied ? @js(__('media.public.link_copied')) : @js(__('media.public.share'))"></span>
            </button>
        </x-slot:actions>
    </x-public.editorial.hero>

    {{-- ============================================================ --}}
    {{-- TABS — episodes (N) / about, with the season switch           --}}
    {{-- ============================================================ --}}
    <div class="sx-wrap grid gap-8 pt-10">
        <div class="flex flex-wrap items-center gap-4">
            <div class="sx-tabs flex-1 min-w-0" role="tablist" aria-label="{{ $program['title'] }}">
                <button type="button" role="tab" class="sx-tab" @click="activeTab = 'episodes'"
                        :aria-selected="activeTab === 'episodes' ? 'true' : 'false'">
                    <i data-lucide="list-video" aria-hidden="true"></i>
                    {{ __('media.public.episodes_title') }} <span class="tabular-nums">({{ $program['episodes_count'] }})</span>
                </button>
                <button type="button" role="tab" class="sx-tab" @click="activeTab = 'about'"
                        :aria-selected="activeTab === 'about' ? 'true' : 'false'">
                    <i data-lucide="info" aria-hidden="true"></i>{{ __('media.public.about_program') }}
                </button>
            </div>

            @if (count($seasons) > 1)
                <div class="sx-scroller shrink-0" role="group" aria-label="{{ __('media.public.episodes_title') }}">
                    @foreach ($seasons as $season)
                        <button type="button" class="sx-chip shrink-0" @click="activeSeason = {{ $season }}"
                                :class="activeSeason === {{ $season }} && 'is-active'"
                                :aria-pressed="(activeSeason === {{ $season }}).toString()">
                            {{ __('media.public.season_label', ['number' => $season]) }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Episodes --}}
        <div x-show="activeTab === 'episodes'" role="tabpanel">
            @if (empty($episodes))
                <x-public.editorial.empty-state icon="list-video"
                    :title="__('media.public.no_episodes_title')" :hint="__('media.public.no_episodes_message')" />
            @else
                <div class="sx-grid">
                    @foreach ($episodes as $episode)
                        <x-public.media.episode-card :episode="$episode" :eager="$loop->index < 3"
                            :x-show="count($seasons) > 1 ? 'activeSeason === '.(int) $episode['season'] : null" />
                    @endforeach
                </div>
            @endif
        </div>

        {{-- About --}}
        <div x-show="activeTab === 'about'" x-cloak role="tabpanel">
            <article class="sx-surface sx-article grid gap-6">
                @if ($program['description'])
                    {{-- Admin WYSIWYG content (trusted, same convention as articles) --}}
                    <div class="sx-prose prose-article max-w-none">{!! $program['description'] !!}</div>
                @endif

                <div class="flex flex-wrap gap-2 border-t border-[color:var(--sx-line)] pt-6">
                    <span class="sx-chip"><i data-lucide="folder" aria-hidden="true"></i>{{ $program['category_label'] }}</span>
                    @if ($program['year'])
                        <span class="sx-chip"><i data-lucide="calendar" aria-hidden="true"></i><span class="tabular-nums">{{ $program['year'] }}</span></span>
                    @endif
                    <span class="sx-chip"><i data-lucide="list-video" aria-hidden="true"></i><span class="tabular-nums">{{ __('media.public.episodes_count', ['count' => $program['episodes_count']]) }}</span></span>
                    <span class="sx-chip"><i data-lucide="users" aria-hidden="true"></i>{{ $program['maturity_label'] }}</span>
                    @if ($program['host'])
                        <span class="sx-chip sx-clamp-1" title="{{ $program['host'] }}">
                            <i data-lucide="mic" aria-hidden="true"></i>{{ __('media.public.presented_by') }}: {{ $program['host'] }}
                        </span>
                    @endif
                </div>
            </article>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function programPage() {
        return {
            isAuthenticated: @json(auth()->check()),
            loginUrl: @js(route('public.login')),
            programId: {{ (int) $program['id'] }},
            storeUrl: @js(route('public.favorites.store')),
            destroyUrl: @js(route('public.favorites.destroy')),
            saved: @json($programIsSaved ?? false),
            savePending: false,
            shareCopied: false,
            activeTab: 'episodes',
            activeSeason: {{ (int) ($seasons[0] ?? 1) }},

            // Saves the program to the visitor's bookmarks (My List).
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

            refreshIcons() {
                this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush
@endsection
