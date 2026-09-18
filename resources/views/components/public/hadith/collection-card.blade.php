{{-- Hadith collection → editorial card. @props collection, eager --}}
@props(['collection', 'eager' => false])

@php
    $locale = app()->getLocale();
    // intro() already withholds the import's English-in-Arabic-column text.
    $intro = trim(strip_tags((string) $collection->intro($locale)));
@endphp

<x-public.editorial.card
    :url="route('public.hadith.collection', $collection->slug)"
    :title="$collection->title($locale)"
    :excerpt="$intro !== '' ? $intro : null"
    :image="$collection->coverUrl()"
    icon="book-open-text"
    :featured="(bool) $collection->is_featured"
    :meta="[
        ['icon' => 'scroll-text', 'text' => __('hadith.hadiths_count', ['count' => number_format((int) $collection->total_hadith)])],
    ]"
    :eager="$eager" {{ $attributes }} />
