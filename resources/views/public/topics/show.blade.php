@extends('layouts.public')

@section('title', __('topics.topic_prefix') . ' ' . $topic['title'])
@section('meta_description', $topic['description'] ?? __('topics.overview'))

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ $topic['title'] }}",
    "description": "{{ $topic['description'] ?? __('topics.overview') }}",
    "url": "{{ $topic['url'] }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@section('content')

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $gradingLabels = [
        'sahih' => __('topics.grading_sahih'),
        'hasan' => __('topics.grading_hasan'),
        'daif' => __('topics.grading_daif'),
        'mawdu' => __('topics.grading_mawdu'),
        'mutawatir' => __('topics.grading_mutawatir'),
        'mursal' => __('topics.grading_mursal'),
    ];
    // Grading badge colour by class (green Sahih / amber Hasan / gray others).
    $gradingClass = [
        'sahih' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
        'mutawatir' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
        'hasan' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    ];
@endphp

<div
    x-data="{
        activeTab: 'verses',
        verseCount: {{ $topic['stats']['verses'] }},
        hadithCount: {{ $topic['stats']['hadiths'] }},
        articleCount: {{ $topic['stats']['articles'] }},
        init() {
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            this.$watch('activeTab', () => this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); }));
        }
    }"
>

    {{-- Breadcrumb --}}
    <section class="border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-[#16213e]">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <x-site.breadcrumb :items="[
                ['label' => __('topics.breadcrumb'), 'url' => route('public.topics')],
                ['label' => $topic['title']],
            ]" />
        </div>
    </section>

    {{-- Hero --}}
    <section class="relative py-14 sm:py-20 overflow-hidden bg-gradient-to-br from-blue-50 via-green-50 to-cyan-50 dark:from-[#1a1a2e] dark:via-[#16213e] dark:to-[#0f3460]">
        <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
            <div class="absolute top-16 -end-20 w-64 h-64 bg-[#0056b3] opacity-10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-20 -start-20 w-64 h-64 bg-[#218838] opacity-10 rounded-full blur-3xl"></div>
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-4xl mx-auto text-center">
                <div class="inline-flex items-center gap-3 glass px-5 py-2.5 rounded-full mb-6">
                    <span class="w-10 h-10 rounded-full flex items-center justify-center text-white bg-gradient-to-br {{ $topic['color'] ?: 'from-[#0056b3] to-[#218838]' }}">
                        <i data-lucide="{{ $topic['icon'] ?: 'bookmark' }}" class="w-5 h-5"></i>
                    </span>
                    <span class="text-sm font-bold gradient-text">{{ __('topics.topic_label') }}</span>
                </div>

                <h1 class="text-4xl sm:text-5xl md:text-6xl font-black mb-5 text-gray-900 dark:text-white">
                    {{ __('topics.topic_prefix') }}
                    <span class="block gradient-text mt-2">{{ $topic['title'] }}</span>
                </h1>

                <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto leading-relaxed" style="word-break: break-word;">
                    {{ $topic['description'] ?? __('topics.overview') }}
                </p>

                {{-- Stats (real counts) --}}
                <div class="grid grid-cols-3 gap-4 sm:gap-6 max-w-2xl mx-auto mt-10">
                    <div class="glass rounded-2xl p-4 sm:p-6">
                        <div class="text-3xl sm:text-4xl font-black gradient-text mb-1">{{ $topic['stats']['verses'] }}</div>
                        <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-300">{{ __('topics.stat_verses') }}</div>
                    </div>
                    <div class="glass rounded-2xl p-4 sm:p-6">
                        <div class="text-3xl sm:text-4xl font-black gradient-text mb-1">{{ $topic['stats']['hadiths'] }}</div>
                        <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-300">{{ __('topics.stat_hadiths') }}</div>
                    </div>
                    <div class="glass rounded-2xl p-4 sm:p-6">
                        <div class="text-3xl sm:text-4xl font-black gradient-text mb-1">{{ $topic['stats']['articles'] }}</div>
                        <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-300">{{ __('topics.stat_articles') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Sticky tabs --}}
    <section class="sticky top-20 z-40 border-b border-gray-200 dark:border-gray-800 bg-white/95 dark:bg-[#16213e]/95" style="backdrop-filter: blur(10px);">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex gap-4 sm:gap-8 overflow-x-auto" style="scrollbar-width: none;">
                <button @click="activeTab = 'verses'"
                        class="relative px-4 sm:px-6 py-4 font-bold text-base sm:text-lg whitespace-nowrap transition-all flex items-center gap-2 border-b-[3px]"
                        :class="activeTab === 'verses' ? 'text-[#0056b3] border-[#0056b3]' : 'text-gray-500 dark:text-gray-400 border-transparent hover:text-gray-900 dark:hover:text-white'">
                    <i data-lucide="book-open" class="w-5 h-5"></i>
                    <span>{{ __('topics.tab_verses') }}</span>
                    <span class="text-xs opacity-70" x-text="'(' + verseCount + ')'"></span>
                </button>

                <button @click="activeTab = 'hadiths'"
                        class="relative px-4 sm:px-6 py-4 font-bold text-base sm:text-lg whitespace-nowrap transition-all flex items-center gap-2 border-b-[3px]"
                        :class="activeTab === 'hadiths' ? 'text-[#0056b3] border-[#0056b3]' : 'text-gray-500 dark:text-gray-400 border-transparent hover:text-gray-900 dark:hover:text-white'">
                    <i data-lucide="scroll-text" class="w-5 h-5"></i>
                    <span>{{ __('topics.tab_hadiths') }}</span>
                    <span class="text-xs opacity-70" x-text="'(' + hadithCount + ')'"></span>
                </button>

                <button @click="activeTab = 'media'"
                        class="relative px-4 sm:px-6 py-4 font-bold text-base sm:text-lg whitespace-nowrap transition-all flex items-center gap-2 border-b-[3px]"
                        :class="activeTab === 'media' ? 'text-[#0056b3] border-[#0056b3]' : 'text-gray-500 dark:text-gray-400 border-transparent hover:text-gray-900 dark:hover:text-white'">
                    <i data-lucide="sparkles" class="w-5 h-5"></i>
                    <span>{{ __('topics.tab_media') }}</span>
                    <span class="text-xs opacity-70" x-text="'(' + articleCount + ')'"></span>
                </button>
            </nav>
        </div>
    </section>

    {{-- Content --}}
    <main class="py-10 sm:py-12 bg-[#F8F9FA] dark:bg-[#1a1a2e]">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Verses tab --}}
            <div x-show="activeTab === 'verses'" x-transition class="space-y-6 max-w-4xl mx-auto">
                @forelse ($topic['verses'] as $verse)
                    <article class="relative rounded-2xl p-6 sm:p-8 bg-white dark:bg-[#16213e] border border-gray-100 dark:border-gray-800 card-premium overflow-hidden">
                        <blockquote class="relative mb-6">
                            <div class="absolute -start-2 top-0 w-1 h-full bg-gradient-to-b from-[#0056b3] to-[#218838] rounded-full"></div>
                            <p class="ps-4 text-2xl sm:text-3xl leading-loose text-gray-900 dark:text-white" style="font-family: 'Amiri', serif;" dir="rtl">{{ $verse['text'] }}</p>
                            @if (! empty($verse['translation']))
                                <p class="ps-4 mt-4 text-base text-gray-600 dark:text-gray-300 leading-relaxed">{{ $verse['translation'] }}</p>
                            @endif
                        </blockquote>

                        <div class="flex flex-wrap items-center justify-between gap-4 pt-5 border-t border-gray-100 dark:border-gray-800">
                            <div class="flex items-center gap-3">
                                <span class="w-11 h-11 bg-gradient-to-br from-[#0056b3] to-[#218838] rounded-full flex items-center justify-center shrink-0">
                                    <i data-lucide="book-open" class="w-5 h-5 text-white"></i>
                                </span>
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $verse['surahName'] }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('topics.verse_ayah', ['number' => $verse['verseNumber']]) }}</p>
                                </div>
                            </div>
                            @if (\Illuminate\Support\Facades\Route::has('public.quran.surah'))
                                <a href="{{ route('public.quran.surah', $verse['surahId']) }}#verse-{{ $verse['verseNumber'] }}"
                                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-[#0056b3] border border-[#0056b3]/30 hover:bg-[#0056b3]/5 transition-colors">
                                    <i data-lucide="book-open-text" class="w-4 h-4"></i>
                                    {{ __('topics.read_surah') }}
                                </a>
                            @endif
                        </div>
                    </article>
                @empty
                    <x-site.empty-state icon="book-open" :title="__('topics.empty_verses_title')" :message="__('topics.empty_verses_message')" />
                @endforelse
            </div>

            {{-- Hadiths tab --}}
            <div x-show="activeTab === 'hadiths'" x-transition x-cloak class="space-y-6 max-w-4xl mx-auto">
                @forelse ($topic['hadiths'] as $hadith)
                    <article class="rounded-2xl p-6 sm:p-8 bg-white dark:bg-[#16213e] border border-gray-100 dark:border-gray-800 card-premium">
                        <div class="relative mb-6">
                            <div class="absolute -start-2 top-0 w-1 h-full bg-gradient-to-b from-[#218838] to-[#64CCC5] rounded-full"></div>
                            <p class="ps-4 text-xl sm:text-2xl leading-loose text-gray-900 dark:text-white" style="font-family: 'Amiri', serif;" dir="rtl">{{ $hadith['text'] }}</p>
                            @if (! empty($hadith['translation']))
                                <p class="ps-4 mt-4 text-base text-gray-600 dark:text-gray-300 leading-relaxed">{{ $hadith['translation'] }}</p>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-4 pt-5 border-t border-gray-100 dark:border-gray-800">
                            <div class="flex items-center gap-3">
                                <span class="w-11 h-11 bg-gradient-to-br from-[#218838] to-[#64CCC5] rounded-full flex items-center justify-center shrink-0">
                                    <i data-lucide="scroll-text" class="w-5 h-5 text-white"></i>
                                </span>
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $hadith['source'] }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('topics.hadith_number', ['number' => $hadith['number']]) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold {{ $gradingClass[$hadith['grading']] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                                    {{ $gradingLabels[$hadith['grading']] ?? __('topics.grading_unknown') }}
                                </span>
                                <a href="{{ $hadith['url'] }}"
                                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-[#218838] border border-[#218838]/30 hover:bg-[#218838]/5 transition-colors">
                                    <i data-lucide="info" class="w-4 h-4"></i>
                                    {{ __('topics.hadith_explanation') }}
                                </a>
                            </div>
                        </div>
                    </article>
                @empty
                    <x-site.empty-state icon="scroll-text" :title="__('topics.empty_hadiths_title')" :message="__('topics.empty_hadiths_message')" />
                @endforelse
            </div>

            {{-- Media (articles) tab --}}
            <div x-show="activeTab === 'media'" x-transition x-cloak>
                @if (count($topic['articles']) === 0)
                    <x-site.empty-state icon="file-text" :title="__('topics.empty_articles_title')" :message="__('topics.empty_articles_message')" />
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($topic['articles'] as $article)
                            <a href="{{ $article['url'] }}"
                               class="card-premium group flex flex-col rounded-2xl overflow-hidden bg-white dark:bg-[#16213e] border border-gray-100 dark:border-gray-800 transition-all hover:-translate-y-1 hover:shadow-xl">
                                <div class="relative aspect-video overflow-hidden bg-gray-100 dark:bg-gray-800">
                                    @if (! empty($article['thumb']))
                                        <img src="{{ $article['thumb'] }}" alt="{{ $article['title'] }}" loading="lazy"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-300">
                                            <i data-lucide="file-text" class="w-10 h-10"></i>
                                        </div>
                                    @endif
                                    <span class="absolute top-3 start-3 glass px-3 py-1 rounded-full text-xs font-semibold text-white">
                                        {{ $article['category'] ?? __('topics.article_badge') }}
                                    </span>
                                </div>
                                <div class="p-5 flex flex-col flex-1">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 line-clamp-2">{{ $article['title'] }}</h3>
                                    @if (! empty($article['excerpt']))
                                        <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-3 flex-1" style="word-break: break-word;">{{ $article['excerpt'] }}</p>
                                    @endif
                                    <span class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-[#0056b3] group-hover:text-[#218838] transition-colors">
                                        {{ __('topics.read_more') }}
                                        <i data-lucide="arrow-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4"></i>
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </main>

</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
@endpush
