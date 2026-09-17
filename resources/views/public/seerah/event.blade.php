@extends('layouts.public')

@section('title', ($event['seoTitle'] ?? null) ?: $event['title'] . ' — ' . __('seerah.public.page_title'))
@section('meta_description', ($event['seoDescription'] ?? null) ?: excerpt_sentence($event['description'] ?? $event['title'], 155))
@section('og_type', 'article')

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Article",
    "headline": @json($event['title']),
    "description": @json(excerpt_sentence($event['description'] ?? '', 200)),
    "url": "{{ $event['url'] }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@push('head')
@include('partials.module-embed-styles')
@include('partials.seerah-content-styles')
@endpush

@php
    /**
     * One template, two layouts:
     *  - story   (default): immersive full-bleed opener, single reading column, chapter numbers.
     *  - reading (?view=article): banner hero, article surface + sticky on-this-page aside.
     *
     * @var array $event detail payload (SeerahController::mapEventDetail)
     */
    $variant = ($variant ?? 'story') === 'reading' ? 'reading' : 'story';
    $isStory = $variant === 'story';

    $hasDescription = ! empty($event['description']);
    $hasContent = ! empty($event['content']);
    $details = collect($event['details'] ?? [])->filter(fn ($d) => ! empty($d['key']))->values();
    $sources = collect($event['sources'] ?? [])->filter(fn ($s) => ! empty($s['title']))->values();
    $statistics = collect($event['statistics'] ?? [])->filter(fn ($v) => is_scalar($v))->take(4);
    $disputeNote = $event['disputeNote'] ?? null;
    $gallery = $event['gallery'] ?? [];
    $media = $event['media'] ?? [];

    $words = count(preg_split('/\s+/u', trim(strip_tags(($event['description'] ?? '').' '.($event['content'] ?? ''))), -1, PREG_SPLIT_NO_EMPTY));
    $readingMinutes = $words > 60 ? max(1, (int) ceil($words / 200)) : null;

    // Every fact names what it is — no bare icons.
    $facts = collect([
        ['icon' => 'moon', 'label' => __('seerah.public.hijri_date_label'), 'value' => $event['hijriDate']],
        ['icon' => 'calendar-days', 'label' => __('seerah.public.gregorian_year_label'), 'value' => $event['gregorianYear'] ? $event['gregorianYear'].' '.__('seerah.public.gregorian_suffix') : null],
        ['icon' => $event['icon'], 'label' => __('seerah.public.type_label'), 'value' => $event['typeLabel']],
        ['icon' => 'map-pin', 'label' => __('seerah.public.location_label'), 'value' => $event['place']['name'] ?? null],
        ['icon' => 'landmark', 'label' => __('seerah.public.era_label'), 'value' => $event['eraLabel']],
        ['icon' => 'flag', 'label' => __('seerah.public.milestone_label'), 'value' => $event['milestone']['title'] ?? null],
    ])->filter(fn ($fact) => ! empty($fact['value']))->values();

    $toc = collect([
        ['id' => 'introduction', 'label' => __('seerah.public.section_intro'), 'show' => ! $isStory && $hasDescription],
        ['id' => 'dispute', 'label' => __('seerah.public.dispute_heading'), 'show' => filled($disputeNote)],
        ['id' => 'context', 'label' => __('seerah.public.section_context'), 'show' => $hasContent || ($isStory && $hasDescription)],
        ['id' => 'more-details', 'label' => __('seerah.public.section_more_details'), 'show' => $details->isNotEmpty()],
        ['id' => 'media', 'label' => __('seerah.public.section_media'), 'show' => $media !== []],
        ['id' => 'episodes', 'label' => __('seerah.public.section_episodes'), 'show' => ! empty($event['episodes'])],
        ['id' => 'linked', 'label' => __('seerah.public.section_linked'), 'show' => ! empty($event['linked'])],
        ['id' => 'gallery', 'label' => __('seerah.public.event_gallery'), 'show' => $gallery !== []],
        ['id' => 'sources', 'label' => __('seerah.public.section_sources'), 'show' => $sources->isNotEmpty()],
    ])->filter(fn ($t) => $t['show'])->values();

    $reader = [
        'eventId' => $event['id'] ?? null,
        'title' => $event['title'],
        'saved' => (bool) ($isSaved ?? false),
        'isAuthenticated' => auth()->check(),
        'loginUrl' => route('public.login'),
        'storeUrl' => route('public.favorites.store'),
        'destroyUrl' => route('public.favorites.destroy'),
        'sections' => $toc->pluck('id')->all(),
        'hideDockOnHero' => $isStory,
        'messages' => [
            'added' => __('seerah.public.bookmark_added'),
            'removed' => __('seerah.public.bookmark_removed'),
            'failed' => __('seerah.public.save_failed'),
            'copied' => __('seerah.public.link_copied'),
        ],
    ];

    $storyUrl = route('public.seerah.event', $event['slug']);
    $readingUrl = route('public.seerah.event', ['slug' => $event['slug'], 'view' => 'article']);
    $firstSection = $toc->first()['id'] ?? 'context';

    // Story chapters are numbered in reading order, only over sections that render.
    // (Computed up front: a `$n++` inside component props is evaluated more than once.)
    $chapters = $toc->pluck('id')
        ->intersect(['context', 'more-details', 'media', 'episodes', 'linked', 'gallery', 'sources'])
        ->values()
        ->flip()
        ->map(fn (int $position) => $position + 1)
        ->all();
    $chapterOf = fn (string $id): ?int => $isStory ? ($chapters[$id] ?? null) : null;
@endphp

@section('content')
<div class="sx pb-28" x-data="seerahReader(@js($reader))">

    <x-public.seerah.reader-dock
        :switch-url="$isStory ? $readingUrl : $storyUrl"
        :switch-label="$isStory ? __('seerah.public.read_as_article') : __('seerah.public.read_as_timeline')"
        :switch-icon="$isStory ? 'file-text' : 'git-commit-vertical'" />

    <x-public.seerah.event-hero :event="$event" :variant="$variant" :reading-minutes="$readingMinutes">
        <x-slot:actions>
            @if ($isStory)
                <a href="#{{ $firstSection }}" class="sx-btn is-light"><i data-lucide="arrow-down" aria-hidden="true"></i>{{ __('seerah.public.explore_story') }}</a>
                <a href="{{ $readingUrl }}" class="sx-btn is-glass"><i data-lucide="file-text" aria-hidden="true"></i>{{ __('seerah.public.read_as_article') }}</a>
            @else
                <a href="#{{ $firstSection }}" class="sx-btn is-light"><i data-lucide="book-open" aria-hidden="true"></i>{{ __('seerah.public.start_reading') }}</a>
                <a href="{{ $storyUrl }}" class="sx-btn is-glass"><i data-lucide="git-commit-vertical" aria-hidden="true"></i>{{ __('seerah.public.read_as_timeline') }}</a>
            @endif
        </x-slot:actions>
    </x-public.seerah.event-hero>

    <x-public.seerah.fact-ribbon :facts="$facts" />

    <div class="sx-wrap mt-10 sm:mt-14 grid gap-10 sm:gap-14">
        <x-public.seerah.stats :statistics="$statistics" />

        @if ($isStory)
            {{-- ═══════ STORY ═══════ --}}
            <div class="mx-auto w-full max-w-4xl grid gap-12 sm:gap-16">
                <x-public.seerah.dispute :note="$disputeNote" />

                @if ($hasContent || $hasDescription)
                    <x-public.seerah.section id="context" icon="scroll-text" :title="__('seerah.public.section_context')" :index="$chapterOf('context')">
                        {{-- Purified admin HTML (SanitizesRichText). The hero already shows the
                             description excerpt, so the full description repeats only when there is
                             no rich content. --}}
                        @if ($hasContent)
                            <div class="sx-prose seerah-content">{!! $event['content'] !!}</div>
                        @else
                            <div class="sx-prose seerah-content">
                                {!! str_contains($event['description'], '<')
                                    ? app(\App\Domain\Shared\Services\RichTextSanitizer::class)->clean($event['description'])
                                    : nl2br(e($event['description'])) !!}
                            </div>
                        @endif
                    </x-public.seerah.section>
                @else
                    <x-public.seerah.empty-state icon="file-question" :title="__('seerah.public.no_content')" />
                @endif

                <x-public.seerah.details :details="$details" :index="$chapterOf('more-details')" />
                <x-public.seerah.media :media="$media" :index="$chapterOf('media')" />
                @include('public.seerah._linked-content', ['episodesIndex' => $chapterOf('episodes'), 'linkedIndex' => $chapterOf('linked')])
            </div>

            <x-public.seerah.gallery :images="$gallery" :title="$event['title']" :index="$chapterOf('gallery')" />

            <div class="mx-auto w-full max-w-4xl">
                <x-public.seerah.sources :sources="$sources" :index="$chapterOf('sources')" />
            </div>

            @if (! empty($event['mapUrl']) || ! empty($related))
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)] items-start">
                    <x-public.seerah.place-map :event="$event" />
                    <x-public.seerah.related :related="$related" />
                </div>
            @endif
        @else
            {{-- ═══════ READING ═══════ --}}
            <div class="sx-reading">
                <article class="sx-surface sx-article min-w-0 grid gap-10">
                    @if ($hasDescription)
                        <x-public.seerah.section id="introduction" icon="info" :title="__('seerah.public.section_intro')">
                            <div class="sx-prose seerah-content">
                                {!! str_contains($event['description'], '<')
                                    ? app(\App\Domain\Shared\Services\RichTextSanitizer::class)->clean($event['description'])
                                    : nl2br(e($event['description'])) !!}
                            </div>
                        </x-public.seerah.section>
                    @endif

                    <x-public.seerah.dispute :note="$disputeNote" />

                    @if ($hasContent)
                        <x-public.seerah.section id="context" icon="scroll-text" :title="__('seerah.public.section_context')">
                            <div class="sx-prose seerah-content">{!! $event['content'] !!}</div>
                        </x-public.seerah.section>
                    @endif

                    @if (! $hasDescription && ! $hasContent)
                        <x-public.seerah.empty-state icon="file-question" :title="__('seerah.public.no_content')" class="!shadow-none" />
                    @endif

                    <x-public.seerah.details :details="$details" />
                    <x-public.seerah.media :media="$media" />
                    @include('public.seerah._linked-content')
                    <x-public.seerah.gallery :images="$gallery" :title="$event['title']" />
                    <x-public.seerah.sources :sources="$sources" />
                </article>

                <aside class="sx-aside">
                    <x-public.seerah.toc :items="$toc" />
                    <x-public.seerah.place-map :event="$event" />
                    <x-public.seerah.related :related="$related" />
                </aside>
            </div>
        @endif

        <x-public.seerah.pager :prev="$prev" :next="$next" />
    </div>
</div>
@endsection
