@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $collectionTitle = $collection->title($locale);
    $bookTitle = $book->title_ar ?: $book->title_en;

    $stats = [
        ['icon' => 'scroll-text', 'label' => __('hadith.hadiths'), 'value' => number_format($hadiths->total())],
    ];
    if ($chapters->isNotEmpty()) {
        $stats[] = ['icon' => 'book-marked', 'label' => __('hadith.chapters'), 'value' => number_format($chapters->count())];
    }
    if ($book->first_hadith_number && $book->last_hadith_number) {
        // hadith_range is a placeholder string (':first'/':last'), not a
        // bare label — passing no params prints them literally.
        $stats[] = [
            'icon' => 'bookmark',
            'label' => __('hadith.reference'),
            'value' => __('hadith.hadith_range', ['first' => $book->first_hadith_number, 'last' => $book->last_hadith_number]),
            'small' => true,
        ];
    }
@endphp

@section('title', $bookTitle.' — '.$collectionTitle)
@section('meta_description', $bookTitle.' — '.$collectionTitle)

@section('content')
<div class="sx min-h-[100dvh] pb-20">
    <div class="sx-wrap">

        <x-public.editorial.page-hero
            :eyebrow-url="route('public.hadith.collection', $collection->slug)" :eyebrow-label="$collectionTitle" eyebrow-icon="library-big"
            :title="$bookTitle" :stats="$stats">
            <span class="sx-chip justify-self-start">
                <i data-lucide="book" aria-hidden="true"></i>{{ __('hadith.book') }}
                <span class="tabular-nums">{{ $isRtl ? arabic_digits($book->book_number) : $book->book_number }}</span>
            </span>
        </x-public.editorial.page-hero>

        {{-- Without a chapter index there is no aside, so the reading grid
             would leave an empty sticky column. --}}
        <div @class(['sx-reading' => $chapters->isNotEmpty()])>
            <main class="min-w-0">
                @if ($hadiths->isEmpty())
                    <x-public.editorial.empty-state icon="scroll-text" :title="__('hadith.no_hadiths')" />
                @else
                    @php $lastChapter = null; @endphp
                    <div class="grid gap-4">
                        @foreach ($hadiths as $hadith)
                            @if ($hadith->chapter && $hadith->chapter->id !== $lastChapter)
                                @php
                                    $lastChapter = $hadith->chapter->id;
                                    $chapterTitle = $hadith->chapter->title_ar ?: $hadith->chapter->title_en;
                                @endphp
                                <header id="chapter-{{ $hadith->chapter->chapter_number }}" class="sx-section sx-section-head !mb-0 mt-6 first:mt-0 scroll-mt-24">
                                    <span class="sx-section-icon"><i data-lucide="book-marked" aria-hidden="true"></i></span>
                                    <div class="min-w-0">
                                        <p class="sx-section-index">{{ $isRtl ? arabic_digits($hadith->chapter->chapter_number) : $hadith->chapter->chapter_number }}</p>
                                        <h2 class="sx-section-title sx-clamp-2" title="{{ $chapterTitle }}">{{ $chapterTitle }}</h2>
                                    </div>
                                </header>
                            @endif

                            <x-public.hadith.card :hadith="$hadith->setRelation('collection', $collection)" :eager="$loop->first" />
                        @endforeach
                    </div>

                    <div class="mt-12">
                        <x-public.editorial.pagination :paginator="$hadiths" />
                    </div>
                @endif
            </main>

            {{-- Chapter index: collapsible on phones so it never pushes the
                 hadiths off the screen, sticky beside them from 1100px up. --}}
            @if ($chapters->isNotEmpty())
                <aside class="sx-aside">
                    <details class="sx-surface sx-disclosure" open>
                        <summary>
                            <span class="inline-flex items-center gap-2"><i data-lucide="book-marked" aria-hidden="true"></i>{{ __('hadith.chapters') }}</span>
                            <i data-lucide="chevron-down" class="sx-disclosure-chevron" aria-hidden="true"></i>
                        </summary>
                        <nav class="sx-toc max-h-[60vh] overflow-y-auto overscroll-contain pb-2" aria-label="{{ __('hadith.chapters') }}">
                            @foreach ($chapters as $chapter)
                                @php
                                    $chapterTitle = $chapter->title_ar ?: $chapter->title_en;
                                    // The index lists every chapter of the book, but the
                                    // hadiths are paginated — so a `#chapter-N` anchor only
                                    // exists on the page its chapter starts on, and the rest
                                    // of these links scroll nowhere. Carry `?page=` when the
                                    // chapter is not on the page being read; keep a bare
                                    // fragment when it is, so that stays a scroll.
                                    $anchor = '#chapter-'.$chapter->chapter_number;
                                    $href = ($chapter->page === null || $chapter->page === $hadiths->currentPage())
                                        ? $anchor
                                        : $hadiths->url($chapter->page).$anchor;
                                @endphp
                                <a href="{{ $href }}" title="{{ $chapterTitle }}">
                                    <span class="tabular-nums font-bold">{{ $isRtl ? arabic_digits($chapter->chapter_number) : $chapter->chapter_number }}</span>
                                    <span class="sx-clamp-1">{{ $chapterTitle }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </details>
                </aside>
            @endif
        </div>
    </div>
</div>
@endsection
