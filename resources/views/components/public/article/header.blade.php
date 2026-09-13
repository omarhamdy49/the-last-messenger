{{--
    Article Header Component

    Displays the article header including:
    - Dateline (published date, read time, views)
    - Title and excerpt
    - Reader actions (share, font size controls)
    - Series membership banner
    - Mobile table of contents

    @param array $article Article data
    @param bool $isRtl RTL direction flag
--}}

@props(['article', 'isRtl' => false])

{{-- Dateline above the title, as in the design prototype:
     the reader sees when and how long before committing to
     the headline, and the byline gets its own card below
     instead of being crushed into a stats row. --}}
<div class="flex flex-wrap items-center gap-x-5 gap-y-2 mb-5 text-sm text-gray-600 dark:text-gray-400">
    <span class="inline-flex items-center gap-2">
        <i data-lucide="calendar" class="w-4 h-4 text-accent" aria-hidden="true"></i>{{ $article['hijriDate'] ?: $article['dateLabel'] }}
    </span>
    <span class="inline-flex items-center gap-2">
        <i data-lucide="clock" class="w-4 h-4 text-accent" aria-hidden="true"></i>{{ $article['readTime'] }} {{ __('articles.public.min_read') }}
    </span>
    <span class="inline-flex items-center gap-2">
        <i data-lucide="eye" class="w-4 h-4 text-accent" aria-hidden="true"></i><span x-text="views.toLocaleString('en-US')">{{ number_format($article['views']) }}</span> {{ __('articles.public.views') }}
    </span>
    @if (! empty($article['hijriDate']) && ! empty($article['dateLabel']))
        <span class="inline-flex items-center gap-2">
            <i data-lucide="sun" class="w-4 h-4 text-accent" aria-hidden="true"></i>{{ $article['dateLabel'] }}
        </span>
    @endif
</div>

{{-- Title --}}
<h1 class="text-3xl md:text-4xl xl:text-5xl font-bold mb-5 leading-tight tracking-tight text-gray-900 dark:text-white break-words">
    {{ $article['title'] }}
</h1>

{{-- Lede — marked by a light dash, the site's gradient signature. --}}
@if (! empty($article['excerpt']))
    <div class="mb-7">
        <span class="block w-12 h-[3px] rounded-full bg-gradient-to-l from-primary via-accent to-secondary mb-3" aria-hidden="true"></span>
        <p class="text-lg md:text-xl text-gray-600 dark:text-gray-300 leading-relaxed break-words">
            {{ $article['excerpt'] }}
        </p>
    </div>
@endif

{{-- Reader actions --}}
<div class="flex flex-wrap items-center gap-2 mb-6 pb-6 border-b border-gray-100 dark:border-[#0f3460] no-print">
    <button @click="showShare = true"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-gradient-to-l from-primary to-secondary text-white shadow hover:shadow-lg hover:scale-105 transition-all cursor-pointer">
        <i data-lucide="share-2" class="w-4 h-4"></i>{{ __('articles.public.share') }}
    </button>
    <div class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-[#0f3460] p-1 ms-auto">
        <button @click="decreaseFont()" aria-label="A-" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-[#16213e] transition-colors cursor-pointer">
            <i data-lucide="minus" class="w-4 h-4"></i>
        </button>
        <span class="px-1 text-xs font-semibold text-gray-700 dark:text-gray-200 w-10 text-center tabular-nums" x-text="fontSize + '%'"></span>
        <button @click="increaseFont()" aria-label="A+" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-[#16213e] transition-colors cursor-pointer">
            <i data-lucide="plus" class="w-4 h-4"></i>
        </button>
    </div>
</div>

{{-- Series membership banner --}}
@if (! empty($article['series']))
    <a href="{{ $article['series']['url'] }}"
       class="group flex items-center gap-4 rounded-2xl p-4 mb-8 no-print border border-primary/15 bg-gradient-to-l from-primary/[0.06] to-secondary/[0.06] hover:from-primary/[0.12] hover:to-secondary/[0.12] transition-colors">
        <span class="w-11 h-11 rounded-xl bg-gradient-to-l from-primary to-secondary text-white flex items-center justify-center shrink-0 shadow-md">
            <i data-lucide="layers" class="w-5 h-5"></i>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('series.public.part_of_series') }}</span>
            <span class="block font-bold text-gray-900 dark:text-white truncate group-hover:text-primary transition-colors">{{ $article['series']['title'] }}</span>
        </span>
        <span class="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold text-primary shrink-0">
            {{ __('series.public.view_series') }}
            <i data-lucide="arrow-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
        </span>
    </a>
@endif

{{-- Mobile table of contents.
     The sidebar card is lg-only, so on a phone a fifteen
     heading article offered no way to navigate at all.
     A native <details> carries the disclosure semantics,
     keyboard behaviour and screen-reader state for free —
     an Alpine boolean would have to re-implement all
     three. Collapsed by default so it never pushes the
     opening paragraph below the fold, and it closes on
     pick so the list is not left covering the section the
     reader just jumped to. --}}
@if (! empty($article['toc']))
    <details class="toc-mobile lg:hidden mb-6 rounded-xl border border-gray-200 dark:border-[#0f3460] overflow-hidden no-print">
        <summary class="toc-summary">
            <span class="flex items-center gap-2 font-bold text-gray-900 dark:text-white">
                <i data-lucide="list" class="w-5 h-5 text-accent" aria-hidden="true"></i>
                {{ __('articles.public.table_of_contents') }}
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 tabular-nums">({{ count($article['toc']) }})</span>
            </span>
            <i data-lucide="chevron-down" class="toc-summary__chevron w-5 h-5 text-gray-400 shrink-0" aria-hidden="true"></i>
        </summary>
        <div class="toc-mobile__list p-2 space-y-1 border-t border-gray-100 dark:border-[#0f3460]">
            @foreach ($article['toc'] as $item)
                @php($isSub = ($item['level'] ?? 2) === 3)
                <a href="#{{ $item['id'] }}"
                   @click.prevent="scrollTo('{{ $item['id'] }}'); $el.closest('details').open = false"
                   @class(['toc-link', 'toc-link--sub' => $isSub])
                   :class="active === '{{ $item['id'] }}' ? 'toc-active' : ''"
                   :aria-current="active === '{{ $item['id'] }}' ? 'true' : 'false'">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </details>
@endif
