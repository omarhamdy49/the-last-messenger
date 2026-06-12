@extends('layouts.public')

@section('title', $article['title'])
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($article['excerpt'] ?? ''), 155))
@section('og_type', 'article')
@section('og_image', $article['image'])

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Article",
    "headline": @json($article['title']),
    "image": @json($article['image']),
    "author": { "@@type": "Person", "name": @json($article['author']) },
    "datePublished": @json($article['dateLabel']),
    "inLanguage": "{{ app()->getLocale() }}",
    "mainEntityOfPage": @json($article['url'])
}
</script>
@endpush

@section('content')

@php
    $tocJson = json_encode(array_column($article['toc'], 'id'), JSON_UNESCAPED_UNICODE);
@endphp

<div
    x-data="articleReader({ tocIds: {{ $tocJson }} })"
    x-init="init()"
    class="min-h-screen"
>
    {{-- Reading progress bar --}}
    <div class="fixed top-0 inset-x-0 z-[60] h-1 bg-transparent">
        <div class="h-full bg-gradient-to-l from-primary via-secondary to-accent transition-[width] duration-150 ease-out"
             :style="`width: ${progress}%`"></div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <x-site.breadcrumb
            :items="[
                ['label' => __('articles.public.breadcrumb'), 'url' => route('public.articles')],
                ['label' => $article['title']],
            ]"
            class="mb-8"
        />

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">

            {{-- ============================================================ --}}
            {{-- ARTICLE --}}
            {{-- ============================================================ --}}
            <article class="lg:col-span-8 min-w-0">

                {{-- Header --}}
                <header class="mb-8">
                    <div class="flex items-center gap-3 flex-wrap mb-4 text-sm">
                        @if (! empty($article['category']))
                            <x-site.badge variant="accent" icon="folder">{{ $article['category'] }}</x-site.badge>
                        @endif
                        <span class="inline-flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            {{ $article['dateLabel'] }}
                        </span>
                        @if (! empty($article['hijriDate']))
                            <span class="inline-flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                                <i data-lucide="moon" class="w-4 h-4"></i>
                                {{ $article['hijriDate'] }}
                            </span>
                        @endif
                    </div>

                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white leading-snug mb-5 break-words">
                        {{ $article['title'] }}
                    </h1>

                    @if (! empty($article['excerpt']))
                        <p class="text-lg text-gray-600 dark:text-gray-300 leading-relaxed mb-6 break-words">
                            {{ $article['excerpt'] }}
                        </p>
                    @endif

                    {{-- Author card + meta --}}
                    <div class="flex items-center justify-between flex-wrap gap-4 py-4 border-y border-gray-100 dark:border-[#0f3460]">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-full bg-gradient-to-l from-primary to-secondary flex items-center justify-center text-white font-bold shrink-0">
                                {{ \Illuminate\Support\Str::substr($article['author'], 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('articles.public.by_author') }}</p>
                                <p class="font-bold text-gray-900 dark:text-white truncate">{{ $article['author'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 flex-wrap">
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="clock" class="w-4 h-4"></i>
                                {{ $article['readTime'] }} {{ __('articles.public.min_read') }}
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                                {{ number_format($article['views']) }} {{ __('articles.public.views') }}
                            </span>
                        </div>
                    </div>
                </header>

                {{-- Hero image --}}
                @if (! empty($article['image']))
                    <div class="rounded-2xl overflow-hidden mb-8 aspect-[16/9] bg-gray-100 dark:bg-[#0f3460]">
                        <img src="{{ $article['image'] }}" alt="{{ $article['title'] }}" class="w-full h-full object-cover">
                    </div>
                @endif

                {{-- TOC (mobile, collapsible) --}}
                @if (! empty($article['toc']))
                    <details class="lg:hidden card-premium p-4 mb-8" open>
                        <summary class="cursor-pointer font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="list" class="w-4 h-4"></i>
                            {{ __('articles.public.table_of_contents') }}
                        </summary>
                        <ul class="mt-3 space-y-1.5">
                            @foreach ($article['toc'] as $item)
                                <li @class(['ps-3' => ($item['level'] ?? 2) === 3])>
                                    <a href="#{{ $item['id'] }}" class="text-sm text-gray-600 dark:text-gray-300 hover:text-primary transition-colors">
                                        {{ $item['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif

                {{-- Body (trusted CMS content) --}}
                @if (! empty($article['body']))
                    <div class="prose-article max-w-none break-words text-lg">
                        {!! $article['body'] !!}
                    </div>
                @else
                    <x-site.empty-state icon="file-text" :message="__('articles.public.empty_message')" class="!py-12" />
                @endif

                {{-- Tags --}}
                @if (! empty($article['tags']))
                    <div class="flex flex-wrap gap-2 mt-8 pt-6 border-t border-gray-100 dark:border-[#0f3460]">
                        <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 dark:text-gray-400 me-1">
                            <i data-lucide="tag" class="w-4 h-4"></i>
                            {{ __('articles.public.tags') }}:
                        </span>
                        @foreach ($article['tags'] as $tag)
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif

                {{-- Share + bookmark --}}
                <div class="flex items-center justify-between flex-wrap gap-3 mt-8 p-5 card-premium">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-200 me-1">
                            <i data-lucide="share-2" class="w-4 h-4 text-accent"></i>
                            {{ __('articles.public.share') }}
                        </span>
                        <button @click="shareTwitter()" aria-label="{{ __('articles.public.share_twitter') }}"
                                class="w-10 h-10 rounded-full flex items-center justify-center bg-[#1DA1F2] text-white hover:scale-110 transition-transform">
                            <i data-lucide="twitter" class="w-4 h-4"></i>
                        </button>
                        <button @click="shareFacebook()" aria-label="{{ __('articles.public.share_facebook') }}"
                                class="w-10 h-10 rounded-full flex items-center justify-center bg-[#1877F2] text-white hover:scale-110 transition-transform">
                            <i data-lucide="facebook" class="w-4 h-4"></i>
                        </button>
                        <button @click="shareWhatsapp()" aria-label="{{ __('articles.public.share_whatsapp') }}"
                                class="w-10 h-10 rounded-full flex items-center justify-center bg-[#25D366] text-white hover:scale-110 transition-transform">
                            <i data-lucide="message-circle" class="w-4 h-4"></i>
                        </button>
                        <button @click="copyLink()" aria-label="{{ __('articles.public.copy_link') }}"
                                class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200 hover:scale-110 transition-transform">
                            <i data-lucide="link" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <button @click="toggleBookmark()"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-semibold transition-all"
                            :class="bookmarked
                                ? 'bg-gradient-to-l from-primary to-secondary text-white shadow'
                                : 'bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200 hover:bg-primary/10'">
                        <i data-lucide="bookmark" class="w-4 h-4" :class="bookmarked ? 'fill-current' : ''"></i>
                        <span x-text="bookmarked ? '{{ __('articles.public.bookmarked') }}' : '{{ __('articles.public.bookmark') }}'"></span>
                    </button>
                </div>
            </article>

            {{-- ============================================================ --}}
            {{-- SIDEBAR (TOC scroll-spy) --}}
            {{-- ============================================================ --}}
            <aside class="lg:col-span-4">
                <div class="lg:sticky lg:top-24 space-y-6">
                    @if (! empty($article['toc']))
                        <nav class="card-premium p-5 hidden lg:block" aria-label="{{ __('articles.public.table_of_contents') }}">
                            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                                <i data-lucide="list" class="w-4 h-4"></i>
                                {{ __('articles.public.table_of_contents') }}
                            </h2>
                            <ul class="space-y-1 border-s-2 border-gray-100 dark:border-[#0f3460]">
                                @foreach ($article['toc'] as $item)
                                    <li @class(['ps-2' => ($item['level'] ?? 2) === 3])>
                                        <a href="#{{ $item['id'] }}"
                                           @click.prevent="scrollTo('{{ $item['id'] }}')"
                                           class="block ps-4 -ms-0.5 py-1.5 text-sm border-s-2 -ms-[2px] transition-colors"
                                           :class="active === '{{ $item['id'] }}'
                                               ? 'border-primary text-primary font-semibold'
                                               : 'border-transparent text-gray-600 dark:text-gray-300 hover:text-primary'">
                                            {{ $item['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </nav>
                    @endif

                    {{-- Back to articles --}}
                    <a href="{{ route('public.articles') }}"
                       class="card-premium p-4 flex items-center gap-3 hover:bg-primary/5 transition-colors group">
                        <span class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                            <i data-lucide="chevron-{{ in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'left' : 'right' }}" class="w-4 h-4"></i>
                        </span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ __('articles.public.back_to_articles') }}</span>
                    </a>
                </div>
            </aside>
        </div>

        {{-- ============================================================ --}}
        {{-- RELATED ARTICLES CAROUSEL --}}
        {{-- ============================================================ --}}
        <section class="mt-16">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <i data-lucide="layers" class="w-6 h-6 text-accent"></i>
                {{ __('articles.public.related_articles') }}
            </h2>

            @if (! empty($related))
                <div class="flex gap-5 overflow-x-auto pb-4 snap-x scroll-smooth -mx-1 px-1">
                    @foreach ($related as $item)
                        <a href="{{ $item['url'] }}"
                           class="group card-premium overflow-hidden flex flex-col w-72 shrink-0 snap-start hover-lift">
                            <div class="relative aspect-[16/10] overflow-hidden bg-gray-100 dark:bg-[#0f3460]">
                                <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" loading="lazy"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                                @if (! empty($item['category']))
                                    <span class="absolute bottom-3 start-3 px-2.5 py-1 rounded-full text-xs font-semibold bg-white/90 dark:bg-[#16213e]/90 text-primary backdrop-blur">{{ $item['category'] }}</span>
                                @endif
                            </div>
                            <div class="p-4 flex flex-col flex-1">
                                <h3 class="font-bold text-gray-900 dark:text-white line-clamp-2 mb-2 group-hover:text-primary transition-colors">{{ $item['title'] }}</h3>
                                <div class="mt-auto flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                        {{ $item['readTime'] }} {{ __('articles.public.min_read') }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        {{ $item['views'] }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <x-site.empty-state icon="layers" :message="__('articles.public.no_related')" class="!py-12" />
            @endif
        </section>
    </div>
</div>

@push('scripts')
<script>
    function articleReader(config) {
        return {
            tocIds: config.tocIds || [],
            progress: 0,
            active: '',
            bookmarked: false,
            bookmarkKey: 'bookmarkedArticles',
            articleId: @json($article['id']),

            init() {
                this.bookmarked = this.savedIds().includes(this.articleId);
                this.onScroll();
                window.addEventListener('scroll', () => this.onScroll(), { passive: true });
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },

            onScroll() {
                const doc = document.documentElement;
                const scrollable = doc.scrollHeight - doc.clientHeight;
                this.progress = scrollable > 0 ? Math.min(100, Math.round((doc.scrollTop / scrollable) * 100)) : 0;
                this.updateActive();
            },

            updateActive() {
                let current = '';
                for (const id of this.tocIds) {
                    const el = document.getElementById(id);
                    if (el && el.getBoundingClientRect().top <= 120) {
                        current = id;
                    }
                }
                if (current) this.active = current;
            },

            scrollTo(id) {
                const el = document.getElementById(id);
                if (!el) return;
                const top = el.getBoundingClientRect().top + window.scrollY - 90;
                window.scrollTo({ top, behavior: 'smooth' });
                this.active = id;
            },

            savedIds() {
                try { return JSON.parse(localStorage.getItem(this.bookmarkKey) || '[]'); }
                catch (e) { return []; }
            },

            toggleBookmark() {
                @auth
                    const ids = this.savedIds();
                    const i = ids.indexOf(this.articleId);
                    if (i > -1) ids.splice(i, 1); else ids.push(this.articleId);
                    localStorage.setItem(this.bookmarkKey, JSON.stringify(ids));
                    this.bookmarked = ids.includes(this.articleId);
                    if (this.$store.toast) {
                        this.$store.toast.push(this.bookmarked
                            ? '{{ __('articles.public.bookmarked') }}'
                            : '{{ __('articles.public.bookmark') }}', 'success');
                    }
                @else
                    if (this.$store.toast) {
                        this.$store.toast.push('{{ __('articles.public.login_to_bookmark') }}', 'error');
                    }
                @endauth
            },

            shareTwitter() {
                const url = encodeURIComponent(window.location.href);
                const text = encodeURIComponent(@json($article['title']));
                window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank', 'noopener');
            },
            shareFacebook() {
                const url = encodeURIComponent(window.location.href);
                window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'noopener');
            },
            shareWhatsapp() {
                const text = encodeURIComponent(@json($article['title']) + ' ' + window.location.href);
                window.open(`https://wa.me/?text=${text}`, '_blank', 'noopener');
            },
            copyLink() {
                navigator.clipboard?.writeText(window.location.href).then(() => {
                    if (this.$store.toast) this.$store.toast.push('{{ __('articles.public.link_copied') }}', 'success');
                });
            },
        };
    }
</script>
@endpush

@endsection
