{{--
    Article Content Blocks Component

    Displays the main article content and related blocks:
    - Article body with read more/less functionality
    - Sources & References
    - YouTube videos
    - Key points summary
    - Tags

    @param array $article Article data
    @param bool $isRtl RTL direction flag
--}}

@props(['article', 'isRtl' => false])

{{-- Article body (sanitized CMS content) — collapses behind a
     read-more fade when it exceeds maxCollapsed px. --}}
@if (! empty($article['body']))
    <div class="relative">
        <div x-ref="articleBody"
             class="prose-article max-w-none break-words"
             :class="(bodyOverflow && !bodyExpanded) ? 'overflow-hidden' : ''"
             :style="(bodyOverflow && !bodyExpanded) ? `max-height: ${maxCollapsed}px` : ''">
            {!! $article['body'] !!}
        </div>
        <div x-show="bodyOverflow && !bodyExpanded" x-cloak
             class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-white dark:from-[#16213e] to-transparent pointer-events-none"></div>
    </div>
    <div x-show="bodyOverflow" x-cloak class="mt-5 flex justify-center no-print">
        <button @click="toggleBody()"
                class="inline-flex items-center gap-2 px-7 py-3 rounded-full text-sm font-bold bg-gradient-to-l from-primary to-secondary text-white shadow-md hover:shadow-lg hover:scale-105 transition-all cursor-pointer">
            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" :class="bodyExpanded ? 'rotate-180' : ''"></i>
            <span x-text="bodyExpanded ? '{{ __('articles.public.read_less') }}' : '{{ __('articles.public.read_more') }}'"></span>
        </button>
    </div>
@else
    {{-- Not `empty_message`: that is the library's
         "no matching articles, adjust your filters"
         copy, which told a reader standing on one
         article to change a search they never ran. --}}
    <x-site.empty-state icon="file-text" :message="__('articles.public.no_body')" class="!py-12" />
@endif

{{-- Sources & References --}}
@if (! empty($article['references']))
    <div class="mt-12 pt-8 border-t border-gray-100 dark:border-[#0f3460]">
        {{-- Bare accent icon beside the heading, matching the
             prototype — not a tinted square badge. --}}
        <h3 class="text-xl font-bold mb-4 flex items-center gap-3 text-gray-900 dark:text-white">
            <i data-lucide="book-open" class="w-5 h-5 text-accent" aria-hidden="true"></i>{{ __('articles.public.sources') }}
        </h3>
        <ul class="space-y-3">
            @foreach ($article['references'] as $ref)
                <li class="flex items-start gap-3 text-gray-600 dark:text-gray-300">
                    <i data-lucide="book" class="w-4 h-4 mt-1 text-accent shrink-0"></i>
                    <span class="break-words">
                        @if (! empty($ref['url']))
                            <a href="{{ $ref['url'] }}" target="_blank" rel="noopener nofollow"
                               class="font-semibold text-gray-800 dark:text-gray-100 hover:text-primary hover:underline">{{ $ref['name'] }}</a>
                        @else
                            <strong class="text-gray-800 dark:text-gray-100">{{ $ref['name'] }}</strong>
                        @endif
                        @if (! empty($ref['type']))
                            <span class="ms-2 px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 dark:bg-[#0f3460] text-gray-500 dark:text-gray-400">{{ $ref['type'] }}</span>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ── YouTube videos ─────────────────────────────────
     One video renders as a single framed player. Two or
     more render as a playlist: one stage plus a scrolling
     queue, the way YouTube itself presents a series, so
     the reader can see what else is there without
     scrolling past four stacked 16:9 iframes.

     Iframes are created only on demand (the thumbnail is
     a button): four embeds would otherwise each pull
     YouTube's player bundle on page load. --}}
@if (! empty($article['videos']))
    @php($videos = $article['videos'])
    <section class="mt-10"
             aria-labelledby="article-videos-label"
             x-data="{
                 vi: 0,
                 started: false,
                 videos: @js($videos),
                 get current() { return this.videos[this.vi]; },
                 play(i) { this.vi = i; this.started = true; },
             }">
        <h2 id="article-videos-label" class="text-lg font-bold mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
            <i data-lucide="play-circle" class="w-5 h-5 text-accent" aria-hidden="true"></i>
            {{ count($videos) > 1 ? __('articles.public.video_playlist') : __('articles.public.video') }}
            @if (count($videos) > 1)
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    (<span x-text="vi + 1"></span>/{{ count($videos) }})
                </span>
            @endif
        </h2>

        <div @class(['grid gap-4', 'lg:grid-cols-[1fr_20rem]' => count($videos) > 1])>
            {{-- Stage --}}
            <div>
                <div class="relative aspect-video rounded-2xl overflow-hidden bg-black">
                    {{-- Poster until the reader asks for it. --}}
                    <template x-if="!started">
                        <button type="button" @click="started = true"
                                class="group absolute inset-0 w-full h-full cursor-pointer"
                                :aria-label="current.title">
                            <img :src="current.thumb" :alt="current.title" loading="lazy"
                                 class="w-full h-full object-cover">
                            <span class="absolute inset-0 bg-black/25 group-hover:bg-black/10 transition-colors"></span>
                            <span class="absolute inset-0 flex items-center justify-center">
                                <span class="w-16 h-16 rounded-full bg-red-600 text-white flex items-center justify-center shadow-2xl group-hover:scale-110 transition-transform">
                                    <i data-lucide="play" class="w-7 h-7 ms-0.5" aria-hidden="true"></i>
                                </span>
                            </span>
                        </button>
                    </template>

                    <template x-if="started">
                        {{-- :key forces a fresh iframe per video, so choosing
                             another entry actually swaps the player. --}}
                        <iframe :key="current.id"
                                :src="current.embedUrl + '&autoplay=1'"
                                :title="current.title"
                                class="absolute inset-0 w-full h-full"
                                frameborder="0" loading="lazy"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allowfullscreen></iframe>
                    </template>
                </div>

                {{-- Title + share, so the embed is shareable without
                     opening YouTube first. --}}
                <div class="flex items-start justify-between gap-3 mt-3">
                    <p class="font-semibold text-gray-900 dark:text-white min-w-0" x-text="current.title"></p>
                    <div class="flex items-center gap-1 shrink-0 no-print">
                        <button type="button"
                                @click="navigator.clipboard.writeText(current.watchUrl).then(() => $dispatch('toast', { message: '{{ __('articles.public.link_copied') }}', type: 'success' }))"
                                class="w-9 h-9 rounded-full flex items-center justify-center text-gray-500 hover:text-primary hover:bg-gray-100 dark:hover:bg-[#0f3460] transition-colors cursor-pointer"
                                aria-label="{{ __('articles.public.copy_link') }}">
                            <i data-lucide="link" class="w-4 h-4"></i>
                        </button>
                        <a :href="current.watchUrl" target="_blank" rel="noopener"
                           class="w-9 h-9 rounded-full flex items-center justify-center text-gray-500 hover:text-primary hover:bg-gray-100 dark:hover:bg-[#0f3460] transition-colors"
                           aria-label="{{ __('articles.public.watch_on_youtube') }}">
                            <i data-lucide="external-link" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Queue --}}
            @if (count($videos) > 1)
                <ol class="video-queue rounded-2xl border border-gray-200 dark:border-[#0f3460] divide-y divide-gray-100 dark:divide-[#0f3460] overflow-y-auto"
                    aria-label="{{ __('articles.public.video_playlist') }}">
                    <template x-for="(v, i) in videos" :key="v.id">
                        <li>
                            <button type="button" @click="play(i)"
                                    class="w-full flex items-start gap-3 p-3 text-start transition-colors cursor-pointer"
                                    :class="vi === i ? 'bg-primary/10' : 'hover:bg-gray-50 dark:hover:bg-[#0f3460]/60'"
                                    :aria-current="vi === i ? 'true' : 'false'">
                                <span class="relative w-24 aspect-video rounded-lg overflow-hidden shrink-0 bg-gray-100 dark:bg-[#0f3460]">
                                    <img :src="v.thumb" :alt="''" loading="lazy" class="w-full h-full object-cover">
                                    <span x-show="vi === i" class="absolute inset-0 bg-black/45 flex items-center justify-center">
                                        <i data-lucide="play" class="w-4 h-4 text-white" aria-hidden="true"></i>
                                    </span>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5" x-text="i + 1"></span>
                                    <span class="block text-sm font-medium line-clamp-2 text-gray-900 dark:text-white" x-text="v.title"></span>
                                </span>
                            </button>
                        </li>
                    </template>
                </ol>
            @endif
        </div>
    </section>
@endif

{{-- Key points panel, per the design prototype: a tinted
     gradient block with a lightbulb, not the folio card. --}}
@if (! empty($article['summary']))
    <aside class="summary-panel mt-10" aria-labelledby="article-summary-label">
        <h2 id="article-summary-label" class="flex items-center gap-3 text-xl font-bold mb-4 text-gray-900 dark:text-white">
            <i data-lucide="lightbulb" class="w-6 h-6 text-accent" aria-hidden="true"></i>
            {{ __('articles.public.summary') }}
        </h2>
        <div class="summary-panel__body text-gray-700 dark:text-gray-300">
            {!! $article['summary'] !!}
        </div>
    </aside>
@endif

{{-- Tags --}}
@if (! empty($article['tags']))
    <div class="flex flex-wrap items-center gap-2 mt-8 pt-6 border-t border-gray-100 dark:border-[#0f3460]">
        <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 dark:text-gray-400 me-1">
            <i data-lucide="tag" class="w-4 h-4"></i>{{ __('articles.public.tags') }}:
        </span>
        @foreach ($article['tags'] as $tag)
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $tag }}</span>
        @endforeach
    </div>
@endif
