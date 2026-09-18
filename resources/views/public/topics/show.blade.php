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

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $icon = $topic['icon'] ?: 'bookmark';

    $gradingLabels = [
        'sahih' => __('topics.grading_sahih'),
        'hasan' => __('topics.grading_hasan'),
        'daif' => __('topics.grading_daif'),
        'mawdu' => __('topics.grading_mawdu'),
        'mutawatir' => __('topics.grading_mutawatir'),
        'mursal' => __('topics.grading_mursal'),
    ];
    // The grade is written out; the tint only repeats what the label says.
    // Weak and fabricated read as a warning, everything else stays neutral.
    $gradingTone = ['daif' => 'is-danger', 'mawdu' => 'is-danger', 'sahih' => 'is-active', 'mutawatir' => 'is-active'];

    $tabs = [
        ['key' => 'verses', 'icon' => 'book-open', 'label' => __('topics.tab_verses'), 'count' => $topic['stats']['verses']],
        ['key' => 'hadiths', 'icon' => 'scroll-text', 'label' => __('topics.tab_hadiths'), 'count' => $topic['stats']['hadiths']],
        ['key' => 'media', 'icon' => 'sparkles', 'label' => __('topics.tab_media'), 'count' => $topic['stats']['articles']],
    ];
@endphp

@section('content')
<div class="sx min-h-[100dvh] pb-20"
     x-data="{
        activeTab: 'verses',
        init() {
            this.$watch('activeTab', () => this.$nextTick(() => window.lucide?.createIcons()));
        },
     }">

    <x-public.editorial.hero variant="reading" :title="$topic['title']" :lead="$topic['description'] ?? __('topics.overview')">
        <x-slot:top>
            <nav aria-label="breadcrumb" class="mb-4 flex flex-wrap items-center gap-2 text-sm text-white/80">
                <a href="{{ url('/') }}" class="hover:underline underline-offset-4">{{ __('site.home') }}</a>
                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-3.5 h-3.5" aria-hidden="true"></i>
                <a href="{{ route('public.topics') }}" class="hover:underline underline-offset-4">{{ __('topics.breadcrumb') }}</a>
            </nav>
        </x-slot:top>
        <x-slot:chips>
            <span class="sx-glass-chip"><i data-lucide="{{ $icon }}" aria-hidden="true"></i>{{ __('topics.topic_label') }}</span>
        </x-slot:chips>
        <x-slot:meta>
            @foreach ($tabs as $tab)
                @continue($tab['count'] === 0)
                <span class="sx-glass-chip"><i data-lucide="{{ $tab['icon'] }}" aria-hidden="true"></i><span class="tabular-nums">{{ $tab['count'] }}</span> {{ $tab['label'] }}</span>
            @endforeach
        </x-slot:meta>
    </x-public.editorial.hero>

    <div class="sx-wrap mt-10 grid gap-8">
        <x-public.editorial.stats :title="__('topics.topic_label')" :statistics="[
            __('topics.stat_verses') => $topic['stats']['verses'],
            __('topics.stat_hadiths') => $topic['stats']['hadiths'],
            __('topics.stat_articles') => $topic['stats']['articles'],
        ]" />

        <div class="sx-tabs" role="tablist" aria-label="{{ __('topics.topic_label') }}">
            @foreach ($tabs as $tab)
                <button type="button" role="tab" class="sx-tab" :aria-selected="(activeTab === '{{ $tab['key'] }}').toString()"
                        aria-controls="tab-{{ $tab['key'] }}" @click="activeTab = '{{ $tab['key'] }}'">
                    <i data-lucide="{{ $tab['icon'] }}" aria-hidden="true"></i>{{ $tab['label'] }}
                    <span class="sx-count tabular-nums">{{ $tab['count'] }}</span>
                </button>
            @endforeach
        </div>

        {{-- Verses --}}
        <div id="tab-verses" role="tabpanel" x-show="activeTab === 'verses'" class="mx-auto grid w-full max-w-4xl gap-5">
            @forelse ($topic['verses'] as $verse)
                <article class="sx-surface grid gap-5 p-6 sm:p-8">
                    <blockquote class="border-s-[3px] border-[color:var(--sx-accent)] ps-4">
                        <p class="sx-scripture" dir="rtl">{{ $verse['text'] }}</p>
                        @if (! empty($verse['translation']))
                            <p class="mt-4 leading-loose text-[color:var(--sx-ink-2)]">{{ $verse['translation'] }}</p>
                        @endif
                    </blockquote>

                    <footer class="flex flex-wrap items-center justify-between gap-4 border-t border-[color:var(--sx-line)] pt-5">
                        <div class="flex items-center gap-3">
                            <span class="sx-avatar" aria-hidden="true"><i data-lucide="book-open"></i></span>
                            <div class="min-w-0">
                                <p class="font-bold text-[color:var(--sx-ink)] sx-clamp-1" title="{{ $verse['surahName'] }}">{{ $verse['surahName'] }}</p>
                                <p class="text-sm text-[color:var(--sx-muted)] tabular-nums">{{ __('topics.verse_ayah', ['number' => $verse['verseNumber']]) }}</p>
                            </div>
                        </div>
                        @if (\Illuminate\Support\Facades\Route::has('public.quran.surah'))
                            <a href="{{ route('public.quran.surah', $verse['surahId']) }}#verse-{{ $verse['verseNumber'] }}" class="sx-btn">
                                <i data-lucide="book-open-text" aria-hidden="true"></i>{{ __('topics.read_surah') }}
                            </a>
                        @endif
                    </footer>
                </article>
            @empty
                <x-public.editorial.empty-state icon="book-open" :title="__('topics.empty_verses_title')" :hint="__('topics.empty_verses_message')" />
            @endforelse
        </div>

        {{-- Hadiths --}}
        <div id="tab-hadiths" role="tabpanel" x-show="activeTab === 'hadiths'" x-cloak class="mx-auto grid w-full max-w-4xl gap-5">
            @forelse ($topic['hadiths'] as $hadith)
                <article class="sx-surface grid gap-5 p-6 sm:p-8">
                    <blockquote class="border-s-[3px] border-[color:var(--sx-teal)] ps-4">
                        <p class="sx-scripture !text-[1.35rem]" dir="rtl">{{ $hadith['text'] }}</p>
                        @if (! empty($hadith['translation']))
                            <p class="mt-4 leading-loose text-[color:var(--sx-ink-2)]">{{ $hadith['translation'] }}</p>
                        @endif
                    </blockquote>

                    <footer class="flex flex-wrap items-center justify-between gap-4 border-t border-[color:var(--sx-line)] pt-5">
                        <div class="flex items-center gap-3">
                            <span class="sx-avatar" aria-hidden="true"><i data-lucide="scroll-text"></i></span>
                            <div class="min-w-0">
                                <p class="font-bold text-[color:var(--sx-ink)] sx-clamp-1" title="{{ $hadith['source'] }}">{{ $hadith['source'] }}</p>
                                <p class="text-sm text-[color:var(--sx-muted)] tabular-nums">{{ __('topics.hadith_number', ['number' => $hadith['number']]) }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <span @class(['sx-chip', $gradingTone[$hadith['grading']] ?? ''])>
                                {{ $gradingLabels[$hadith['grading']] ?? __('topics.grading_unknown') }}
                            </span>
                            <a href="{{ $hadith['url'] }}" class="sx-btn">
                                <i data-lucide="info" aria-hidden="true"></i>{{ __('topics.hadith_explanation') }}
                            </a>
                        </div>
                    </footer>
                </article>
            @empty
                <x-public.editorial.empty-state icon="scroll-text" :title="__('topics.empty_hadiths_title')" :hint="__('topics.empty_hadiths_message')" />
            @endforelse
        </div>

        {{-- Media (articles) --}}
        <div id="tab-media" role="tabpanel" x-show="activeTab === 'media'" x-cloak>
            @if (count($topic['articles']) === 0)
                <x-public.editorial.empty-state icon="file-text" :title="__('topics.empty_articles_title')" :hint="__('topics.empty_articles_message')" />
            @else
                <div class="sx-grid">
                    @foreach ($topic['articles'] as $article)
                        <x-public.editorial.card
                            :url="$article['url']" :title="$article['title']" :excerpt="$article['excerpt'] ?: null"
                            :image="$article['thumb'] ?: null" icon="newspaper"
                            :badge="$article['category'] ?: __('topics.article_badge')" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
