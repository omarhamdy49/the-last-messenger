{{--
    Article card — "Manuscript Leaf" premium component.

    Currently drives the library index (grid and list view). The reader's
    related rail, the series page and the home feed still carry their own
    lookalike markup — migrate them onto this component when they are next
    touched rather than growing a fourth variant.

    Props:
        $article  — card array as produced by Site\ArticleController::mapCard():
                    id, title, excerpt, image, url, category, tags, author,
                    readTime, views, isFeatured, dateLabel, hijriDate
        $variant  — 'grid' (default, portrait leaf) | 'row' (horizontal leaf)
        $aspect   — media aspect ratio utility for the grid variant
        $eager    — true to load the image eagerly (use for above-the-fold cards)

    Alignment contract: the root <a> is a full-height flex column and the footer
    is pinned with `margin-block-start:auto`, so the meta row lines up across
    every card in a row whatever the title or excerpt length. Give the grid
    container `items-stretch` (the CSS grid default) and cards match heights.

    Accessibility: the whole leaf is one link, so the "read more" cue is
    decorative and hidden from assistive tech — the title carries the label.
--}}
@props([
    'article',
    'variant' => 'grid',
    'aspect'  => 'aspect-[16/10]',
    'eager'   => false,
])
@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $isRow = $variant === 'row';

    // The card carries one date. Hijri is the calendar this material is dated
    // in, so it wins; the Gregorian label is the fallback when it is absent.
    $dateText = $article['hijriDate'] ?: ($article['dateLabel'] ?? null);

    $tags = array_slice($article['tags'] ?? [], 0, 3);
@endphp

<x-site.article-card-styles />

<a href="{{ $article['url'] }}"
   {{ $attributes->class(['art-card group', 'art-card--row' => $isRow]) }}>

    {{-- Media plate --}}
    <div class="art-card-media {{ $isRow ? 'aspect-[16/10] sm:aspect-auto' : $aspect }}">
        <img src="{{ $article['image'] }}"
             alt="{{ $article['title'] }}"
             loading="{{ $eager ? 'eager' : 'lazy' }}"
             decoding="async"
             onerror="this.style.display='none';this.parentElement.classList.add('img-gradient-fallback')"
             onload="this.style.display='';this.parentElement.classList.remove('img-gradient-fallback')">

        @if (! empty($article['isFeatured']))
            <span class="art-badge art-badge--featured absolute top-3 start-3 z-[2]">
                <i data-lucide="star" class="w-3 h-3" aria-hidden="true"></i>
                {{ __('articles.public.featured') }}
            </span>
        @endif

        @if (! empty($article['category']))
            <span class="art-badge art-badge--category absolute bottom-3 start-3 z-[2] max-w-[calc(100%-1.5rem)] truncate">
                {{ $article['category'] }}
            </span>
        @endif
    </div>

    {{-- Text block --}}
    <div class="art-card-body">
        <h3 class="art-card-title text-[1.15rem] sm:text-xl font-bold text-gray-900 dark:text-white mb-2 line-clamp-2">
            {{ $article['title'] }}
        </h3>

        <p class="art-card-excerpt line-clamp-2 mb-4">{{ $article['excerpt'] }}</p>

        @if ($tags)
            <div class="flex flex-wrap gap-1.5 mb-4">
                @foreach ($tags as $tag)
                    <span class="art-tag">{{ $tag }}</span>
                @endforeach
            </div>
        @endif

        <div class="art-card-foot">
            <span class="art-meta">
                @if ($dateText)
                    <span class="truncate">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 shrink-0" aria-hidden="true"></i>
                        <span class="truncate">{{ $dateText }}</span>
                    </span>
                @endif
                @if (! empty($article['readTime']))
                    <span class="shrink-0">
                        <i data-lucide="clock" class="w-3.5 h-3.5" aria-hidden="true"></i>
                        {{ $article['readTime'] }} {{ __('articles.public.min_read') }}
                    </span>
                @endif
            </span>

            <span class="art-cue" aria-hidden="true">
                {{ __('articles.public.read_more') }}
                <i data-lucide="arrow-{{ $isRtl ? 'left' : 'right' }}" class="w-3.5 h-3.5"></i>
            </span>
        </div>
    </div>
</a>
