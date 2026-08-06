@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $name = $collection->getTranslation('name', $locale, true);
    $introduction = $collection->getTranslation('introduction', $locale, true);
@endphp

@section('title', $name.' — '.__('site.site_name'))
@section('meta_description', \Illuminate\Support\Str::limit(
    (string) $collection->getTranslation('short_intro', $locale, true) ?: $introduction ?: $name, 155))

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <x-site.breadcrumb :items="[
            ['label' => __('hadith.hadiths'), 'url' => route('public.hadith')],
            ['label' => $name],
        ]" />

        <header class="mt-6 mb-8 rounded-2xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-white px-5 sm:px-8 py-8">
            <h1 class="text-2xl sm:text-3xl font-black break-words">{{ $name }}</h1>

            @if ($author = $collection->getTranslation('author', $locale, true))
                <p class="mt-2 text-sm text-white/85">{{ __('hadith.author') }}: {{ $author }}</p>
            @endif

            <p class="mt-4 text-sm font-semibold text-white/90">
                {{ __('hadith.books_count', ['count' => number_format($books->count())]) }}
                <span class="mx-2 opacity-60">•</span>
                {{ __('hadith.hadiths_count', ['count' => number_format($collection->hadiths_count ?? 0)]) }}
            </p>
        </header>

        @if ($introduction)
            <details open class="mb-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-[#16213e]">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-bold text-gray-900 marker:content-none dark:text-white sm:px-6">
                    <span class="flex items-center gap-3">
                        <i data-lucide="scroll-text" class="h-5 w-5 text-[#218838]" aria-hidden="true"></i>
                        {{ __('hadith.authors_introduction') }}
                    </span>
                    <i data-lucide="chevron-down" class="h-5 w-5 shrink-0 text-gray-500" aria-hidden="true"></i>
                </summary>
                <div class="border-t border-gray-200 px-5 py-5 dark:border-gray-800 sm:px-6">
                    <p dir="{{ in_array($locale, ['ar', 'ur'], true) ? 'rtl' : 'ltr' }}"
                       lang="{{ $locale }}"
                       class="whitespace-pre-line text-start leading-9 text-gray-700 dark:text-gray-300">{{ $introduction }}</p>
                    @if ($collection->introduction_url)
                        <a href="{{ $collection->introduction_url }}" rel="noopener noreferrer" target="_blank"
                           class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-[#0056b3] hover:underline dark:text-[#64CCC5]">
                            {{ __('hadith.view_source') }}
                            <i data-lucide="external-link" class="h-4 w-4" aria-hidden="true"></i>
                        </a>
                    @endif
                </div>
            </details>
        @endif

        @if ($books->isNotEmpty())
            {{-- Books in the source's own order, never re-sorted by number: the
                 number is a string and `35b` must follow `35`. --}}
            <ul class="grid gap-3 sm:grid-cols-2">
                @foreach ($books as $book)
                    @php
                        $bookLabelKey = 'hadith.book_name_'.$book->book_number;
                        $bookNumberLabel = \Illuminate\Support\Facades\Lang::has($bookLabelKey)
                            ? __($bookLabelKey)
                            : $book->book_number;
                    @endphp
                    <li>
                        <a href="{{ route('public.hadith.book', [$collection->code, $book->book_number]) }}"
                           class="flex items-center gap-4 rounded-xl border border-gray-200 dark:border-gray-800
                                  bg-white dark:bg-[#16213e] px-4 py-3.5
                                  transition hover:border-[#218838] hover:shadow-sm">
                            <span class="shrink-0 min-w-11 min-h-11 px-2 rounded-lg bg-[#0056b3]/10 dark:bg-[#0f3460]
                                         flex items-center justify-center text-sm font-bold
                                         text-[#0056b3] dark:text-[#64CCC5]">
                                {{ $bookNumberLabel }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold text-gray-900 dark:text-white line-clamp-1">
                                    {{ $book->getTranslation('title', $locale, true) }}
                                </span>
                                <span class="block text-xs text-gray-600 dark:text-gray-300">
                                    {{ __('hadith.hadiths_count', ['count' => number_format($book->hadiths_count)]) }}
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @elseif ($booklessContents !== null && $booklessContents['paginator']->total() > 0)
            {{-- Forties and similar collections have no reader-visible books.
                 Their hadiths belong directly on this canonical collection URL. --}}
            <div x-data="{ textLanguage: @js($locale === 'en' && ($collection->english_texts_count ?? 0) > 0 ? 'en' : 'ar'), hit() { return true; } }">
                <div class="mb-6 flex justify-end">
                    <x-site.hadith-language-toggle :english-available="($collection->english_texts_count ?? 0) > 0" />
                </div>
                <div class="hadith-list">
                    @foreach ($booklessContents['groups'] as $group)
                        @include('public.hadith.partials.chapter', [
                            'chapter' => $group['chapter'],
                            'hadiths' => $group['hadiths'],
                            'collection' => $collection,
                        ])
                    @endforeach
                </div>
            </div>

            @if ($booklessContents['paginator']->hasPages())
                <div class="mt-10">
                    {{ $booklessContents['paginator']->withQueryString()->links() }}
                </div>
            @endif
        @else
            <x-site.empty-state icon="book" :title="__('hadith.no_books')" :message="__('hadith.browse_intro')" />
        @endif
    </div>
@endsection
