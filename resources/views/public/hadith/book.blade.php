@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $collectionName = $collection->getTranslation('name', $locale, true);
    $bookTitle = $book->getTranslation('title', $locale, true);
@endphp

@section('title', $bookTitle.' — '.$collectionName)
@section('meta_description', \Illuminate\Support\Str::limit($bookTitle.' — '.$collectionName, 155))

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <x-site.breadcrumb :items="[
            ['label' => __('hadith.hadiths'), 'url' => route('public.hadith')],
            ['label' => $collectionName, 'url' => route('public.hadith.collection', $collection->code)],
            ['label' => $bookTitle],
        ]" />

        <header class="mt-6 mb-8">
            <p class="text-sm font-semibold text-[#218838] dark:text-[#64CCC5]">
                {{ __('hadith.book') }} {{ $book->book_number }}
            </p>
            <h1 class="mt-1 text-2xl sm:text-3xl font-black text-gray-900 dark:text-white break-words">
                {{ $bookTitle }}
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                {{ __('hadith.hadiths_count', ['count' => number_format($paginator->total())]) }}
            </p>

            @if ($intro = $book->getTranslation('intro', $locale, true))
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-300 leading-7 break-words">{{ $intro }}</p>
            @endif
        </header>

        @forelse ($groups as $group)
            @include('public.hadith.partials.chapter', [
                'chapter' => $group['chapter'],
                'hadiths' => $group['hadiths'],
                'collection' => $collection,
            ])
        @empty
            <x-site.empty-state icon="book-open" :title="__('hadith.no_hadiths')" :message="__('hadith.browse_intro')" />
        @endforelse

        @if ($paginator->hasPages())
            <div class="mt-8">
                {{ $paginator->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
