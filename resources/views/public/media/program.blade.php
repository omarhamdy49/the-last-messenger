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
     class="min-h-dvh font-['Cairo'] bg-gray-50 dark:bg-gray-950 transition-colors duration-300">

    {{-- ============================================================ --}}
    {{-- HERO — Shahid-style full-bleed backdrop, info + actions      --}}
    {{-- ============================================================ --}}
    {{-- svh: stable small-viewport unit — vh jumps as mobile browser chrome collapses --}}
    <section class="relative min-h-[65svh] md:min-h-[78vh] flex flex-col justify-end overflow-hidden bg-gray-950">
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
        {{-- Scrim: bottom-heavy like Shahid + side gradient toward the content --}}
        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-black/15 dark:from-black/95 dark:via-black/60 dark:to-black/25" aria-hidden="true"></div>
        <div class="absolute inset-0 {{ $isRtl ? 'bg-gradient-to-l' : 'bg-gradient-to-r' }} from-black/55 via-black/15 to-transparent" aria-hidden="true"></div>

        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="absolute top-5 inset-x-0 z-10">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <ol class="inline-flex items-center gap-2 flex-wrap text-sm font-semibold text-white/70">
                    <li>
                        <a href="{{ route('public.media') }}" class="inline-flex items-center gap-1.5 hover:text-white transition-colors">
                            <i data-lucide="{{ $isRtl ? 'arrow-right' : 'arrow-left' }}" class="w-4 h-4" aria-hidden="true"></i>
                            {{ __('media.public.page_title') }}
                        </a>
                    </li>
                    <li class="flex items-center gap-2">
                        <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-3.5 h-3.5" aria-hidden="true"></i>
                        <span class="text-white line-clamp-1">{{ $program['title'] }}</span>
                    </li>
                </ol>
            </div>
        </nav>

        {{-- Program info --}}
        <div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 pt-28 pb-8 md:pb-10">
            <div class="max-w-3xl mx-auto sm:mx-0 text-center sm:text-start">
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-black text-white mb-4 leading-tight break-words">{{ $program['title'] }}</h1>

                @if ($program['is_trending'])
                    <p class="inline-flex items-center gap-2 text-[#64CCC5] text-sm font-bold mb-3">
                        <i data-lucide="trending-up" class="w-4 h-4" aria-hidden="true"></i>{{ __('media.public.trending_now') }}
                    </p>
                @endif

                {{-- Season line (Shahid) --}}
                @if (! empty($seasons))
                    <p class="text-white font-bold mb-3">{{ __('media.public.season_label', ['number' => $seasons[0]]) }}</p>
                @endif

                {{-- Meta line: count • genre | year • age chip --}}
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-x-3 gap-y-2 mb-4 text-sm text-gray-200">
                    <span>{{ __('media.public.episodes_count', ['count' => $program['episodes_count']]) }}</span>
                    <span class="text-white/40" aria-hidden="true">•</span>
                    <span>{{ $program['category_label'] }}</span>
                    @if ($program['year'])
                        <span class="text-white/40" aria-hidden="true">•</span>
                        <span>{{ $program['year'] }}</span>
                    @endif
                    <span class="px-2 py-0.5 border border-gray-400/60 rounded text-xs">{{ $program['maturity_label'] }}</span>
                </div>

                @if ($program['description'])
                    <p class="text-sm md:text-base text-gray-200 leading-relaxed max-w-2xl line-clamp-2 md:line-clamp-3 break-words mb-3 mx-auto sm:mx-0">{{ strip_tags((string) $program['description']) }}</p>
                @endif

                @if ($program['host'])
                    <p class="text-sm text-gray-300 mb-4">
                        <span class="font-semibold text-white">{{ __('media.public.presented_by') }}:</span> {{ $program['host'] }}
                    </p>
                @endif
            </div>

            {{-- Shahid action row: white pill CTA + circular glass buttons --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mt-2 border-t border-white/10 pt-5">
                @if (! empty($episodes))
                    <a href="{{ $episodes[0]['url'] }}"
                       {{-- bg-[#ffffff] (not bg-white): app.css force-darkens .bg-white in dark mode --}}
                       class="inline-flex items-center justify-center gap-2 bg-[#ffffff] text-gray-900 font-bold px-10 py-3.5 rounded-full shadow-2xl hover:bg-gray-100 hover:scale-[1.03] motion-reduce:transform-none transition-all duration-300">
                        <i data-lucide="play" class="w-5 h-5"></i>{{ __('media.public.watch_now') }}
                    </a>
                @endif

                <div class="flex items-center justify-center gap-5 sm:gap-3">
                    <span class="inline-flex flex-col sm:flex-row items-center gap-1.5 sm:gap-2">
                        <button type="button" @click="toggleSave()" :aria-pressed="saved ? 'true' : 'false'"
                                :disabled="savePending"
                                :title="isAuthenticated ? '' : @js(__('media.public.login_to_save'))"
                                class="w-12 h-12 rounded-full glass !bg-white/10 text-white flex items-center justify-center hover:bg-white/20 hover:scale-105 motion-reduce:transform-none transition-all duration-300 disabled:opacity-60">
                            <i data-lucide="loader-2" class="w-5 h-5 animate-spin" x-show="savePending" x-cloak></i>
                            <i data-lucide="plus" class="w-5 h-5" x-show="!savePending && !saved"></i>
                            <i data-lucide="check" class="w-5 h-5 text-[#64CCC5]" x-show="!savePending && saved" x-cloak></i>
                        </button>
                        <span class="text-xs sm:text-sm text-white/90" x-text="saved ? @js(__('media.public.in_my_list')) : @js(__('media.public.my_list'))"></span>
                    </span>

                    <span class="inline-flex flex-col sm:flex-row items-center gap-1.5 sm:gap-2">
                        <button type="button" @click="share()"
                                class="w-12 h-12 rounded-full glass !bg-white/10 text-white flex items-center justify-center hover:bg-white/20 hover:scale-105 motion-reduce:transform-none transition-all duration-300">
                            <i data-lucide="share-2" class="w-5 h-5"></i>
                        </button>
                        <span class="text-xs sm:text-sm text-white/90" x-text="shareCopied ? @js(__('media.public.link_copied')) : @js(__('media.public.share'))"></span>
                    </span>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- TABS — Shahid: episodes (N) / more info, season filter       --}}
    {{-- ============================================================ --}}
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="border-b border-gray-200 dark:border-white/10 mb-6">
            <div class="flex items-center gap-6 overflow-x-auto scrollbar-hide" role="tablist" aria-label="{{ $program['title'] }}">
                <button type="button" role="tab" @click="activeTab = 'episodes'"
                        :aria-selected="activeTab === 'episodes' ? 'true' : 'false'"
                        :class="activeTab === 'episodes' ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="relative pb-3 text-sm sm:text-base font-bold whitespace-nowrap transition-colors">
                    {{ __('media.public.episodes_title') }} ({{ $program['episodes_count'] }})
                    <span x-show="activeTab === 'episodes'" class="absolute bottom-0 inset-x-0 h-0.5 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838]"></span>
                </button>
                <button type="button" role="tab" @click="activeTab = 'about'"
                        :aria-selected="activeTab === 'about' ? 'true' : 'false'"
                        :class="activeTab === 'about' ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="relative pb-3 text-sm sm:text-base font-bold whitespace-nowrap transition-colors">
                    {{ __('media.public.about_program') }}
                    <span x-show="activeTab === 'about'" class="absolute bottom-0 inset-x-0 h-0.5 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838]"></span>
                </button>

                @if (count($seasons) > 1)
                    <div class="ms-auto flex items-center gap-2 pb-2" role="tablist" aria-label="{{ __('media.public.episodes_title') }}">
                        @foreach ($seasons as $season)
                            <button type="button" role="tab"
                                    @click="activeSeason = {{ $season }}"
                                    :aria-selected="activeSeason === {{ $season }} ? 'true' : 'false'"
                                    :class="activeSeason === {{ $season }}
                                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow'
                                        : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-100 dark:bg-white/10 dark:text-gray-200 dark:border-transparent dark:hover:bg-white/20'"
                                    class="whitespace-nowrap shrink-0 px-4 py-1.5 rounded-full text-xs sm:text-sm font-semibold transition-colors">
                                {{ __('media.public.season_label', ['number' => $season]) }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- EPISODES TAB — Shahid grid: in-card label + duration         --}}
        {{-- ============================================================ --}}
        <div x-show="activeTab === 'episodes'" role="tabpanel">
            @if (empty($episodes))
                <div class="rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900/40">
                    <x-site.empty-state icon="list-video"
                        :title="__('media.public.no_episodes_title')"
                        :message="__('media.public.no_episodes_message')" class="py-12" />
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-x-3 gap-y-5">
                    @foreach ($episodes as $episode)
                        <div @if (count($seasons) > 1) x-show="activeSeason === {{ $episode['season'] }}" @endif>
                            <a href="{{ $episode['url'] }}"
                               class="group relative block aspect-video rounded-xl overflow-hidden bg-gradient-to-br {{ $program['gradient'] }} shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-[#64CCC5]">
                                @if ($episode['thumbnail'])
                                    <img src="{{ $episode['thumbnail'] }}" alt="" loading="lazy"
                                         class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 motion-reduce:transform-none transition-transform duration-500">
                                @else
                                    <div class="absolute inset-0 flex items-center justify-center opacity-30" aria-hidden="true">
                                        <i data-lucide="{{ $episode['has_video'] ? 'monitor-play' : 'video-off' }}" class="w-9 h-9 text-white"></i>
                                    </div>
                                @endif

                                {{-- Bottom scrim, episode label (start) + duration (end) — Shahid --}}
                                <div class="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-black/85 via-black/30 to-transparent" aria-hidden="true"></div>
                                <span class="absolute bottom-2.5 start-3 text-white font-bold text-base sm:text-lg">
                                    {{ __('media.public.episode_label', ['number' => $episode['number']]) }}
                                </span>
                                @if ($episode['duration'])
                                    {{-- dir=ltr must be on an inner element: inset-inline-end resolves
                                         against the element's own direction, which would flip end-3 in RTL --}}
                                    <span class="absolute bottom-3 end-3 text-white/90 text-xs font-medium"><span dir="ltr">{{ $episode['duration'] }}</span></span>
                                @endif

                                @unless ($episode['has_video'])
                                    <span class="absolute top-2.5 start-3 glass !bg-black/60 text-white text-[11px] font-semibold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                                        <i data-lucide="video-off" class="w-3 h-3"></i>{{ __('media.public.video_unavailable_title') }}
                                    </span>
                                @endunless

                                {{-- Hover play overlay --}}
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100 transition-opacity duration-300 flex items-center justify-center motion-reduce:transition-none">
                                    <span class="w-12 h-12 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center shadow-xl">
                                        <i data-lucide="play" class="w-6 h-6 text-gray-900 ms-0.5"></i>
                                    </span>
                                </div>

                                @if (! empty($episode['completed']))
                                    <span class="absolute top-2.5 end-3 w-6 h-6 rounded-full bg-[#218838] text-white flex items-center justify-center shadow"
                                          role="img" aria-label="{{ __('media.public.watched') }}">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    </span>
                                @endif

                                {{-- Watch progress (auth visitors with a saved position) --}}
                                @if (($episode['progress_percent'] ?? 0) > 0)
                                    <div class="absolute bottom-0 inset-x-0 h-1 bg-white/20">
                                        <div class="h-full bg-gradient-to-r from-[#0056b3] to-[#218838]" style="width: {{ $episode['progress_percent'] }}%"></div>
                                    </div>
                                @endif
                            </a>

                            @if ($episode['title'])
                                <p class="mt-2 text-center text-xs sm:text-sm text-gray-600 dark:text-gray-300 line-clamp-1 break-words">{{ $episode['title'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ============================================================ --}}
        {{-- ABOUT TAB                                                    --}}
        {{-- ============================================================ --}}
        <div x-show="activeTab === 'about'" x-cloak role="tabpanel">
            <div class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm p-6 max-w-3xl">
                @if ($program['description'])
                    {{-- Admin WYSIWYG content (trusted, same convention as articles) --}}
                    <div class="text-sm leading-relaxed text-gray-600 dark:text-gray-300 break-words mb-4 prose prose-sm dark:prose-invert max-w-none">{!! $program['description'] !!}</div>
                @endif
                @if ($program['host'])
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                        <span class="font-semibold text-gray-900 dark:text-white">{{ __('media.public.presented_by') }}:</span> {{ $program['host'] }}
                    </p>
                @endif
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $program['category_label'] }}</span>
                    @if ($program['year']) · {{ $program['year'] }}@endif
                    · {{ __('media.public.episodes_count', ['count' => $program['episodes_count']]) }}
                </p>
            </div>
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
