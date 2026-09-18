@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $title = $collection->title($locale);
    $intro = trim(strip_tags((string) $collection->intro($locale)));

    $stats = [];
    if ($collection->has_books) {
        $stats[] = ['icon' => 'book', 'label' => __('hadith.books'), 'value' => number_format($books->count())];
    }
    $stats[] = [
        'icon' => 'scroll-text',
        'label' => __('hadith.hadiths'),
        'value' => number_format($hadiths?->total() ?? $collection->availableHadithsCount()),
    ];
@endphp

@section('title', $title)
@section('meta_description', \Illuminate\Support\Str::of($intro)->limit(155) ?: __('hadith.library_tagline'))

@section('content')
<div class="sx min-h-[100dvh] pb-20">
    <div class="sx-wrap">

        <x-public.editorial.page-hero
            :eyebrow-url="route('public.hadith')" :eyebrow-label="__('hadith.collections')" eyebrow-icon="library-big"
            :title="$title" :lead="$intro !== '' ? \Illuminate\Support\Str::of($intro)->limit(320) : null"
            :stats="$stats">
            <a href="{{ route('public.hadith.search', ['collection' => $collection->slug]) }}" class="sx-btn is-primary justify-self-start">
                <i data-lucide="search" aria-hidden="true"></i>{{ __('hadith.search_hadiths') }}
            </a>
        </x-public.editorial.page-hero>

        <x-public.editorial.section
            :icon="$collection->has_books ? 'book-marked' : 'scroll-text'"
            :title="__($collection->has_books ? 'hadith.book_index' : 'hadith.hadiths')">

            @if ($hadiths !== null)
                @if ($hadiths->isEmpty())
                    <x-public.editorial.empty-state icon="scroll-text" :title="__('hadith.no_hadiths')" />
                @else
                    <div class="grid gap-4">
                        @foreach ($hadiths as $hadith)
                            <x-public.hadith.card :hadith="$hadith" :eager="$loop->first" />
                        @endforeach
                    </div>

                    <div class="mt-12">
                        <x-public.editorial.pagination :paginator="$hadiths" />
                    </div>
                @endif
            @elseif ($books->isEmpty())
                <x-public.editorial.empty-state icon="book" :title="__('hadith.no_books')" />
            @else
                <div class="sx-grid">
                    @foreach ($books as $book)
                        <x-public.editorial.card
                            :url="route('public.hadith.book', [$collection->slug, $book->book_number])"
                            :title="$book->title_ar ?: $book->title_en"
                            icon="book"
                            :big="$isRtl ? arabic_digits($book->book_number) : $book->book_number"
                            :meta="[
                                ['icon' => 'scroll-text', 'text' => __('hadith.hadiths_count', ['count' => number_format((int) $book->hadiths_count)])],
                            ]" />
                    @endforeach
                </div>
            @endif
        </x-public.editorial.section>
    </div>
</div>
@endsection
