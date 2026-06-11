@extends('layouts.public')

{{-- SEO: per-locale overrides from the admin SEO tab; fallback "{title} | {app.name}" --}}
@section('title', $program['seo_title'] ?: $program['title'].' | '.config('app.name'))
@section('meta_description', $program['seo_description'] ?: \Illuminate\Support\Str::limit(strip_tags((string) $program['description']), 160))

@if($program['seo_keywords'])
@push('head')
    <meta name="keywords" content="{{ $program['seo_keywords'] }}">
@endpush
@endif

@section('content')
@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<div x-data="programPage()"
     class="min-h-screen font-['Cairo'] bg-gray-50 dark:bg-gray-950 transition-colors duration-300">

    {{-- ============================================================ --}}
    {{-- HERO — full-bleed banner with poster (Shahid-style)          --}}
    {{-- ============================================================ --}}
    <section class="relative min-h-[60vh] md:min-h-[70vh] flex flex-col justify-end overflow-hidden bg-gray-950">
        {{-- Backdrop --}}
        @if ($program['banner'])
            <img src="{{ $program['banner'] }}" alt=""
                 class="absolute inset-0 w-full h-full object-cover scale-105">
        @else
            <div class="absolute inset-0 bg-gradient-to-br {{ $program['gradient'] }}">
                <div class="absolute inset-0 opacity-10" aria-hidden="true">
                    <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                        <defs><pattern id="program-grid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="1"/>
                        </pattern></defs>
                        <rect width="100%" height="100%" fill="url(#program-grid)"/>
                    </svg>
                </div>
            </div>
        @endif
        {{-- Scrim: lighter in light mode so the scheme toggle is visible in the hero --}}
        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/40 to-black/15 dark:from-black/95 dark:via-black/60 dark:to-black/25" aria-hidden="true"></div>

        {{-- Blend the hero bottom into the page background (light/dark aware); kept
             short so it never washes out the bottom-aligned action buttons --}}
        <div class="absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-gray-50 to-transparent dark:from-gray-950 pointer-events-none" aria-hidden="true"></div>

        {{-- Breadcrumb --}}
        <nav class="absolute top-6 start-0 w-full z-10" aria-label="breadcrumb">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <a href="{{ route('public.media') }}"
                   class="inline-flex items-center gap-2 text-sm font-semibold text-white/80 hover:text-white transition-colors">
                    <i data-lucide="{{ $isRtl ? 'arrow-right' : 'arrow-left' }}" class="w-4 h-4"></i>
                    {{ __('media.public.back_to_programs') }}
                </a>
            </div>
        </nav>

        {{-- Program info --}}
        <div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-10 md:pb-14">
            <div class="flex flex-col sm:flex-row items-start sm:items-end gap-5 md:gap-8">
                {{-- Poster --}}
                <div class="w-28 sm:w-36 md:w-44 shrink-0 rounded-2xl overflow-hidden shadow-2xl ring-1 ring-white/20 animate-fade-in-up motion-reduce:animate-none">
                    <div class="relative aspect-[2/3] bg-gradient-to-br {{ $program['gradient'] }}">
                        @if ($program['poster'])
                            <img src="{{ $program['poster'] }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                        @else
                            <div class="absolute inset-0 flex items-center justify-center opacity-30" aria-hidden="true">
                                <i data-lucide="{{ ['seerah' => 'map-pin', 'quran' => 'book-open', 'hadith' => 'scroll-text', 'kids' => 'baby', 'documentary' => 'film'][$program['category']] ?? 'monitor-play' }}"
                                   class="w-12 h-12 text-white"></i>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="inline-flex items-center gap-2 glass !bg-white/10 text-white text-xs font-bold px-3 py-1.5 rounded-full uppercase tracking-wide">
                            {{ $program['category_label'] }}
                        </span>
                        @if ($program['is_trending'])
                            <span class="inline-flex items-center gap-1.5 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-xs font-bold px-3 py-1.5 rounded-full">
                                <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>{{ __('media.public.trending_now') }}
                            </span>
                        @endif
                    </div>

                    <h1 class="text-3xl sm:text-4xl md:text-6xl font-black text-white mb-4 leading-tight break-words">{{ $program['title'] }}</h1>

                    <div class="flex flex-wrap items-center gap-3 mb-4 text-sm text-gray-200">
                        @if ($program['rating'] > 0)
                            <span class="flex items-center gap-1 text-yellow-400 font-semibold">
                                <i data-lucide="star" class="w-4 h-4"></i>{{ number_format($program['rating'], 1) }}
                            </span>
                        @endif
                        @if ($program['year'])<span>{{ $program['year'] }}</span>@endif
                        <span class="px-2.5 py-0.5 border border-gray-400/60 rounded text-xs">{{ $program['maturity_label'] }}</span>
                        <span>{{ __('media.public.episodes_count', ['count' => $program['episodes_count']]) }}</span>
                        <span class="flex items-center gap-1">
                            <i data-lucide="eye" class="w-4 h-4"></i>{{ number_format($program['views_count']) }} {{ __('media.public.views') }}
                        </span>
                    </div>

                    @if ($program['host'])
                        <p class="text-sm text-gray-300 mb-3">
                            <span class="font-semibold text-white">{{ __('media.public.presented_by') }}:</span> {{ $program['host'] }}
                        </p>
                    @endif

                    @if ($program['description'])
                        <p class="text-sm md:text-base text-gray-200 leading-relaxed max-w-3xl line-clamp-2 md:line-clamp-3 break-words mb-6">{{ strip_tags((string) $program['description']) }}</p>
                    @endif

                    <div class="flex flex-wrap items-center gap-3">
                        @if (! empty($episodes))
                            <a href="{{ $episodes[0]['url'] }}"
                               {{-- bg-[#ffffff] (not bg-white): app.css force-darkens .bg-white in dark mode --}}
                               class="inline-flex items-center gap-2 bg-[#ffffff] text-gray-900 font-bold px-7 py-3.5 rounded-full shadow-2xl hover:bg-gray-100 hover:scale-105 motion-reduce:transform-none transition-all duration-300">
                                <i data-lucide="play" class="w-5 h-5"></i>{{ __('media.public.watch_now') }}
                            </a>
                        @endif

                        <button type="button" @click="toggleSave()" :aria-pressed="saved ? 'true' : 'false'"
                                :disabled="savePending"
                                :title="isAuthenticated ? '' : @js(__('media.public.login_to_save'))"
                                class="inline-flex items-center gap-2 glass !bg-white/10 text-white font-bold px-7 py-3.5 rounded-full hover:bg-white/20 transition-all duration-300 disabled:opacity-60">
                            <i data-lucide="loader-2" class="w-5 h-5 animate-spin" x-show="savePending" x-cloak></i>
                            <i data-lucide="plus" class="w-5 h-5" x-show="!savePending && !saved"></i>
                            <i data-lucide="check" class="w-5 h-5 text-[#64CCC5]" x-show="!savePending && saved" x-cloak></i>
                            <span x-text="saved ? @js(__('media.public.in_my_list')) : @js(__('media.public.my_list'))"></span>
                        </button>

                        <button type="button" @click="share()"
                                class="inline-flex items-center gap-2 glass !bg-white/10 text-white font-bold px-5 py-3.5 rounded-full hover:bg-white/20 transition-all duration-300">
                            <i data-lucide="share-2" class="w-5 h-5"></i>
                            <span x-text="shareCopied ? @js(__('media.public.link_copied')) : @js(__('media.public.share'))"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- EPISODES — season selector + responsive grid                 --}}
    {{-- ============================================================ --}}
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="w-1 h-8 bg-gradient-to-b from-[#0056b3] to-[#218838] rounded-full" aria-hidden="true"></span>
                <i data-lucide="list-video" class="w-6 h-6 text-[#0056b3] dark:text-[#64CCC5]" aria-hidden="true"></i>
                {{ __('media.public.episodes_title') }}
            </h2>

            @if (count($seasons) > 1)
                <div class="flex items-center gap-2 overflow-x-auto scrollbar-hide" role="tablist" aria-label="{{ __('media.public.episodes_title') }}">
                    @foreach ($seasons as $season)
                        <button type="button" role="tab"
                                @click="activeSeason = {{ $season }}"
                                :aria-selected="activeSeason === {{ $season }} ? 'true' : 'false'"
                                :class="activeSeason === {{ $season }}
                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg'
                                    : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-100 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-800 dark:hover:text-white'"
                                class="whitespace-nowrap shrink-0 px-5 py-2 rounded-full text-sm font-semibold transition-all">
                            {{ __('media.public.season_label', ['number' => $season]) }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        @if (empty($episodes))
            <div class="rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900/40">
                <x-site.empty-state icon="list-video"
                    :title="__('media.public.no_episodes_title')"
                    :message="__('media.public.no_episodes_message')" class="py-12" />
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5">
                @foreach ($episodes as $episode)
                    <a href="{{ $episode['url'] }}"
                       @if (count($seasons) > 1) x-show="activeSeason === {{ $episode['season'] }}" @endif
                       class="premium-card group block rounded-2xl overflow-hidden shadow-lg bg-white dark:bg-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#64CCC5]">
                        <div class="relative aspect-video bg-gradient-to-br {{ $program['gradient'] }}">
                            @if ($episode['thumbnail'])
                                <img src="{{ $episode['thumbnail'] }}" alt="" loading="lazy"
                                     class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 motion-reduce:transform-none transition-transform duration-500">
                            @else
                                <div class="absolute inset-0 flex items-center justify-center opacity-30" aria-hidden="true">
                                    <i data-lucide="{{ $episode['has_video'] ? 'monitor-play' : 'video-off' }}" class="w-9 h-9 text-white"></i>
                                </div>
                            @endif

                            <span class="absolute top-2 start-2 glass !bg-black/50 text-white text-[11px] font-bold px-2.5 py-1 rounded-full">
                                {{ __('media.public.episode_label', ['number' => $episode['number']]) }}
                            </span>

                            @if ($episode['duration'])
                                <span class="absolute bottom-2 end-2 bg-black/80 text-white text-[11px] px-2 py-0.5 rounded">{{ $episode['duration'] }}</span>
                            @endif

                            <div class="card-overlay absolute inset-0 bg-black/45 opacity-0 transition-opacity duration-300 flex items-center justify-center motion-reduce:transition-none">
                                <span class="w-12 h-12 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center shadow-xl">
                                    <i data-lucide="play" class="w-6 h-6 text-gray-900 ms-0.5"></i>
                                </span>
                            </div>
                        </div>

                        <div class="p-4">
                            <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white line-clamp-2 break-words group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors">
                                {{ $episode['title'] }}
                            </h3>
                            @unless ($episode['has_video'])
                                <span class="inline-flex items-center gap-1 mt-2 text-[11px] text-gray-500 dark:text-gray-300">
                                    <i data-lucide="video-off" class="w-3.5 h-3.5"></i>{{ __('media.public.video_unavailable_title') }}
                                </span>
                            @endunless
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- ============================================================ --}}
        {{-- ABOUT                                                        --}}
        {{-- ============================================================ --}}
        @if ($program['description'] || $program['host'])
            <div class="mt-10 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <i data-lucide="info" class="w-5 h-5 text-[#0056b3] dark:text-[#64CCC5]"></i>
                    {{ __('media.public.about_program') }}
                </h3>
                @if ($program['description'])
                    {{-- Admin WYSIWYG content (trusted, same convention as articles) --}}
                    <div class="text-sm leading-relaxed text-gray-600 dark:text-gray-300 break-words mb-3 prose prose-sm dark:prose-invert max-w-none">{!! $program['description'] !!}</div>
                @endif
                @if ($program['host'])
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        <span class="font-semibold text-gray-900 dark:text-white">{{ __('media.public.presented_by') }}:</span> {{ $program['host'] }}
                    </p>
                @endif
            </div>
        @endif
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
