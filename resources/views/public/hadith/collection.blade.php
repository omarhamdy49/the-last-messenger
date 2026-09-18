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
                {{-- A book index is a list to scan, not a gallery: compact rows,
                     three to a line. The tile card's figure slot also had nowhere
                     to put Muslim's "introduction", which is a book_number too. --}}
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($books as $book)
                        <a href="{{ route('public.hadith.book', [$collection->slug, $book->book_number]) }}"
                           class="sx-surface group relative flex items-start gap-3.5 p-4 overflow-hidden hover:border-[color:var(--sx-accent)] hover:-translate-y-0.5 transition-all">
                            <span aria-hidden="true" class="absolute inset-y-0 start-0 w-1 opacity-0 group-hover:opacity-100 transition-opacity" style="background:var(--sx-grad)"></span>
                            <span class="flex-none w-11 h-11 rounded-xl grid place-items-center text-sm font-extrabold tabular-nums text-[color:var(--sx-accent)]"
                                  style="background:var(--sx-grad-soft)">
                                @if ($book->hasFigureNumber())
                                    {{ $isRtl ? arabic_digits($book->book_number) : $book->book_number }}
                                @else
                                    <i data-lucide="book" class="w-5 h-5" aria-hidden="true"></i>
                                @endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-bold text-[15px] leading-snug text-[color:var(--sx-ink)] group-hover:text-[color:var(--sx-accent)] transition-colors sx-clamp-2" title="{{ $book->title_ar ?: $book->title_en }}">
                                    {{ $book->title_ar ?: $book->title_en }}
                                </span>
                                <span class="mt-1.5 flex items-center gap-1.5 text-[11px] text-[color:var(--sx-muted)]">
                                    <i data-lucide="scroll-text" class="w-3 h-3" aria-hidden="true"></i>
                                    <span class="tabular-nums font-semibold">{{ number_format((int) $book->hadiths_count) }}</span>
                                    {{ __('hadith.hadiths') }}
                                </span>
                            </span>
                            <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"
                               class="w-4 h-4 self-center flex-none text-[color:var(--sx-muted)] group-hover:text-[color:var(--sx-accent)] transition-colors"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-public.editorial.section>
    </div>
</div>
@endsection
