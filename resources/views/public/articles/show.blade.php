@extends('layouts.public')

@section('title', $article['title'])
@section('meta_description', $article['seoDescription'] ?? \Illuminate\Support\Str::limit(strip_tags($article['excerpt'] ?? ''), 155))
@section('canonical', $article['canonical'])
@section('og_type', 'article')
@section('og_image', $article['image'])

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $tocJson = json_encode(array_column($article['toc'], 'id'), JSON_UNESCAPED_UNICODE);
@endphp

@push('head')
    @if (! empty($article['datePublishedIso']))
        <meta property="article:published_time" content="{{ $article['datePublishedIso'] }}">
    @endif
    @if (! empty($article['dateModifiedIso']))
        <meta property="article:modified_time" content="{{ $article['dateModifiedIso'] }}">
    @endif
    @if (! empty($article['author']))
        <meta property="article:author" content="{{ $article['author'] }}">
    @endif
    @if (! empty($article['category']))
        <meta property="article:section" content="{{ $article['category'] }}">
    @endif
@endpush

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Article",
    "headline": @json(\Illuminate\Support\Str::limit($article['title'], 110, '')),
    "description": @json($article['seoDescription'] ?? ''),
    "image": @json($article['image']),
    "author": { "@@type": "Person", "name": @json($article['author']) },
    "publisher": {
        "@@type": "Organization",
        "name": @json(__('site.site_name')),
        "logo": { "@@type": "ImageObject", "url": @json(asset('images/site/rasool-allah-seal.webp')) }
    },
@if (! empty($article['datePublishedIso']))
    "datePublished": @json($article['datePublishedIso']),
@endif
@if (! empty($article['dateModifiedIso']))
    "dateModified": @json($article['dateModifiedIso']),
@endif
    "inLanguage": "{{ app()->getLocale() }}",
    "mainEntityOfPage": { "@@type": "WebPage", "@@id": @json($article['canonical']) }
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": @json(__('site.home')), "item": @json(url('/')) },
        { "@@type": "ListItem", "position": 2, "name": @json(__('articles.public.breadcrumb')), "item": @json(route('public.articles')) },
        { "@@type": "ListItem", "position": 3, "name": @json($article['title']) }
    ]
}
</script>
@endpush

@push('head')
@include('partials.module-embed-styles')
@endpush

@php
    /** @var array $article ArticleController::mapDetail */
    $toc = collect($article['toc'] ?? [])->values();
    $hasBody = filled($article['body'] ?? null);
    $sourceItems = collect($article['references'] ?? [])->map(fn ($r) => ['title' => $r['name'], 'url' => $r['url'] ?? null, 'tag' => $r['type'] ?? null])->all();
    $statistics = collect($article['statistics'] ?? [])->mapWithKeys(fn ($s) => [$s['name'] => $s['value']])->filter(fn ($v) => is_scalar($v))->take(4);
    $gallery = $article['gallery'] ?? [];
    $videos = $article['videos'] ?? [];
    $series = $article['series'] ?? null;
    $seriesPath = collect($series['articles'] ?? [])->map(fn ($a) => ['title' => $a['title'], 'url' => $a['url'], 'current' => $a['current'] ?? false])->all();

    $reader = [
        'eventId' => $article['id'],
        'favoriteType' => 'article',
        'title' => $article['title'],
        'saved' => (bool) ($isSaved ?? false),
        'isAuthenticated' => auth()->check(),
        'loginUrl' => route('public.login'),
        'storeUrl' => route('public.favorites.store'),
        'destroyUrl' => route('public.favorites.destroy'),
        'sections' => $toc->pluck('id')->all(),
        'fontScale' => true,
        'viewUrl' => route('public.articles.view', ['id' => $article['id']]),
        'views' => (int) $article['views'],
        'messages' => [
            'added' => __('seerah.public.bookmark_added'),
            'removed' => __('seerah.public.bookmark_removed'),
            'failed' => __('seerah.public.save_failed'),
            'copied' => __('articles.public.link_copied'),
        ],
    ];
@endphp

@section('content')
<div class="sx pb-28" x-data="editorialReader(@js($reader))">

    <x-public.editorial.reader-dock :font-scale="true"
        :save-label="__('articles.public.bookmark')" :unsave-label="__('seerah.public.unsave_event')" />

    <x-public.editorial.hero :image="$article['cover'] ?? $article['image'] ?? null" variant="reading" :title="$article['title']" :lead="$article['excerpt'] ?: null">
        <x-slot:top>
            <nav aria-label="breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-white/80">
                <a href="{{ route('public.articles') }}" class="sx-glass-chip"><i data-lucide="newspaper" aria-hidden="true"></i>{{ __('articles.public.breadcrumb') }}</a>
            </nav>
        </x-slot:top>
        <x-slot:chips>
            @if (! empty($article['category']))
                <span class="sx-glass-chip sx-clamp-1 max-w-[16rem]" title="{{ $article['category'] }}"><i data-lucide="folder" aria-hidden="true"></i>{{ $article['category'] }}</span>
            @endif
            @if ($series)
                <a href="{{ $series['url'] }}" class="sx-glass-chip sx-clamp-1 max-w-[18rem]" title="{{ $series['title'] }}"><i data-lucide="layers" aria-hidden="true"></i>{{ $series['title'] }}</a>
            @endif
        </x-slot:chips>
        <x-slot:meta>
            <span class="sx-glass-chip"><i data-lucide="pen-line" aria-hidden="true"></i>{{ $article['author'] }}</span>
            @if ($article['hijriDate'] ?: $article['dateLabel'])
                <span class="sx-glass-chip"><i data-lucide="calendar" aria-hidden="true"></i>{{ $article['hijriDate'] ?: $article['dateLabel'] }}</span>
            @endif
            @if ($article['readTime'])
                <span class="sx-glass-chip"><i data-lucide="clock" aria-hidden="true"></i>{{ $article['readTime'] }} {{ __('articles.public.min_read') }}</span>
            @endif
            <span class="sx-glass-chip"><i data-lucide="eye" aria-hidden="true"></i><span x-text="(views ?? 0).toLocaleString('en-US')">{{ number_format($article['views']) }}</span> {{ __('articles.public.views') }}</span>
        </x-slot:meta>
        <x-slot:actions>
            <a href="#article-body" class="sx-btn is-light"><i data-lucide="book-open" aria-hidden="true"></i>{{ __('seerah.public.start_reading') }}</a>
            @if ($series)
                <a href="{{ $series['url'] }}" class="sx-btn is-glass"><i data-lucide="layers" aria-hidden="true"></i>{{ __('series.public.view_series') }}</a>
            @endif
        </x-slot:actions>
    </x-public.editorial.hero>

    <div class="sx-wrap grid gap-12 pt-10">
        <div class="sx-reading">
            <article class="sx-surface sx-article min-w-0 grid gap-10">
                @if ($toc->isNotEmpty())
                    <details class="sx-disclosure min-[1100px]:hidden">
                        <summary><span class="flex items-center gap-2"><i data-lucide="list" aria-hidden="true"></i>{{ __('articles.public.table_of_contents') }}</span><i data-lucide="chevron-down" class="sx-disclosure-chevron" aria-hidden="true"></i></summary>
                        <div class="sx-toc sx-scroll mt-2 max-h-[60vh] overflow-y-auto" @click="$el.closest('details').open = false">
                            @foreach ($toc as $item)
                                <a href="#{{ $item['id'] }}" @class(['ps-6' => ($item['level'] ?? 2) > 2]) title="{{ $item['label'] }}"><span class="sx-clamp-1">{{ $item['label'] }}</span></a>
                            @endforeach
                        </div>
                    </details>
                @endif

                <div id="article-body" class="scroll-mt-24">
                    @if ($hasBody)
                        <div class="sx-prose prose-article max-w-none" :style="`font-size: ${fontSize}%`">{!! $article['body'] !!}</div>
                    @else
                        <x-public.editorial.empty-state icon="file-text" :title="__('articles.public.no_body')" class="!shadow-none" />
                    @endif
                </div>

                <x-public.editorial.stats :statistics="$statistics" :title="__('articles.public.quick_stats')" />

                @if (! empty($article['summary']))
                    <x-public.editorial.section id="summary" icon="lightbulb" :title="__('articles.public.summary')">
                        <div class="sx-summary sx-prose">{!! $article['summary'] !!}</div>
                    </x-public.editorial.section>
                @endif

                @if ($videos !== [])
                    @include('public.articles._videos', ['videos' => $videos])
                @endif

                <x-public.editorial.media :title="__('articles.public.attached_media')" :items="$article['media'] ?? []" />
                <x-public.editorial.gallery :title="__('articles.public.gallery')" :alt="$article['title']" :images="$gallery" />
                <x-public.editorial.sources :title="__('articles.public.sources')" :items="$sourceItems" />

                @if (! empty($article['tags']))
                    <ul class="flex flex-wrap gap-2 border-t border-[color:var(--sx-line)] pt-6" aria-label="{{ __('articles.public.tags') }}">
                        @foreach ($article['tags'] as $tag)
                            <li><span class="sx-chip sx-clamp-1" title="{{ $tag }}"><i data-lucide="hash" class="inline align-[-2px]" aria-hidden="true"></i> {{ $tag }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </article>

            <aside class="sx-aside">
                <x-public.editorial.toc :items="$toc" :title="__('articles.public.table_of_contents')" class="max-[1099px]:hidden" />
                @if ($series)
                    <x-public.editorial.related :title="$series['title']" icon="layers" :items="$seriesPath" :numbered="true" />
                @endif
                <x-public.editorial.related :title="__('articles.public.related_articles')" icon="newspaper"
                    :items="collect($related ?? [])->take(4)->map(fn ($r) => ['title' => $r['title'], 'url' => $r['url'], 'image' => $r['image'] ?? null, 'sub' => $r['category'] ?? null])->all()" />
            </aside>
        </div>

        <x-public.editorial.pager :prev="$prev ?? null" :next="$next ?? null"
            :prev-label="__('articles.public.previous_article')" :next-label="__('articles.public.next_article')" :label="__('articles.public.more_articles')" />

        @if (count($related ?? []) > 4)
            <section class="grid gap-6" aria-labelledby="more-articles">
                <h2 id="more-articles" class="sx-section-title">{{ __('articles.public.more_articles') }}</h2>
                <div class="sx-grid">
                    @foreach (array_slice($related, 4) as $card)
                        <x-public.articles.card :article="$card" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
@endsection
