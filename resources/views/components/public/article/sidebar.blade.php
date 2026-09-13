{{--
    Article Sidebar Component

    Displays sidebar widgets:
    - Table of contents with reading progress
    - Series reading path
    - Quick statistics
    - Tags widget
    - Back to articles link

    @param array $article Article data
    @param bool $isRtl RTL direction flag
--}}

@props(['article', 'isRtl' => false])

<aside class="lg:col-span-4 xl:col-span-3 mt-8 lg:mt-0 no-print">
    <div class="lg:sticky lg:top-24 space-y-6">

        {{-- Table of contents — the prototype's card: a titled panel
             of pill links, with the active section filled rather than
             marked by a travelling rail. --}}
        @if (! empty($article['toc']))
            <nav class="card-premium p-6 hidden lg:block animate-fade-in-up" aria-label="{{ __('articles.public.table_of_contents') }}">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                    <i data-lucide="list" class="w-5 h-5 text-accent" aria-hidden="true"></i>
                    {{ __('articles.public.table_of_contents') }}
                </h3>
                <div class="toc-scroll">
                    <div class="relative space-y-1">
                        {{-- lit trail: rail + gradient fill follow the reading position --}}
                        <div class="toc-rail-track" aria-hidden="true"></div>
                        <div class="toc-rail-fill" aria-hidden="true"
                             :style="`--tp: ${active ? Math.min(1, (tocIds.indexOf(active) + 0.5) / tocIds.length) : 0}`"></div>
                        @foreach ($article['toc'] as $item)
                            @php($isSub = ($item['level'] ?? 2) === 3)
                            <a href="#{{ $item['id'] }}"
                               @click.prevent="scrollTo('{{ $item['id'] }}')"
                               data-toc-link="{{ $item['id'] }}"
                               @class(['toc-link', 'toc-link--sub' => $isSub])
                               :class="active === '{{ $item['id'] }}' ? 'toc-active' : ''"
                               :aria-current="active === '{{ $item['id'] }}' ? 'true' : 'false'">
                                <span class="toc-dot"
                                      :class="active && tocIds.indexOf('{{ $item['id'] }}') <= tocIds.indexOf(active) ? 'is-on' : ''"
                                      aria-hidden="true"></span>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </nav>
        @endif

        {{-- Series reading path — the article's series siblings in
             order, with the current article highlighted. --}}
        @if (! empty($article['series']['articles']))
            <div class="card-premium p-5 animate-fade-in-up">
                <h3 class="text-lg font-bold mb-1 flex items-center gap-2 text-gray-900 dark:text-white">
                    <i data-lucide="layers" class="w-5 h-5 text-accent" aria-hidden="true"></i>
                    {{ __('series.public.articles_in_series') }}
                </h3>
                <a href="{{ $article['series']['url'] }}"
                   class="block text-sm font-semibold text-primary hover:underline mb-4 truncate">
                    {{ $article['series']['title'] }}
                </a>
                <ol class="space-y-1">
                    @foreach ($article['series']['articles'] as $i => $sibling)
                        <li>
                            @if ($sibling['current'])
                                <span class="flex items-start gap-2.5 px-3 py-2 rounded-lg text-sm font-bold bg-primary/10 text-primary"
                                      aria-current="page">
                                    <span class="w-5 h-5 rounded-full bg-primary text-white text-[11px] font-bold flex items-center justify-center shrink-0 mt-0.5">{{ $i + 1 }}</span>
                                    <span class="min-w-0">{{ $sibling['title'] }}</span>
                                </span>
                            @else
                                <a href="{{ $sibling['url'] }}"
                                   class="flex items-start gap-2.5 px-3 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    <span class="w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-[11px] font-bold flex items-center justify-center shrink-0 mt-0.5">{{ $i + 1 }}</span>
                                    <span class="min-w-0">{{ $sibling['title'] }}</span>
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        {{-- Quick stats (real statistics) --}}
        @if (! empty($article['statistics']))
            <div class="card-premium p-5 animate-fade-in-up stagger-1">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                    <i data-lucide="bar-chart-2" class="w-5 h-5 text-accent" aria-hidden="true"></i>{{ __('articles.public.quick_stats') }}
                </h3>
                <div class="space-y-3">
                    @foreach ($article['statistics'] as $stat)
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400 break-words">{{ $stat['name'] }}</span>
                            <span class="font-semibold text-gray-900 dark:text-white shrink-0">{{ $stat['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Tags widget --}}
        @if (! empty($article['tags']))
            <div class="card-premium p-5 animate-fade-in-up stagger-2">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                    <i data-lucide="tag" class="w-5 h-5 text-accent" aria-hidden="true"></i>{{ __('articles.public.tags') }}
                </h3>
                <div class="flex flex-wrap gap-2">
                    @foreach ($article['tags'] as $tag)
                        <span class="px-4 py-2 rounded-full text-sm font-medium bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300 transition-transform hover:scale-105">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Back to articles --}}
        <a href="{{ route('public.articles') }}"
           class="card-premium p-4 flex items-center gap-3 hover:bg-primary/5 transition-colors group">
            <span class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4"></i>
            </span>
            <span class="font-semibold text-gray-900 dark:text-white">{{ __('articles.public.back_to_articles') }}</span>
        </a>
    </div>
</aside>
