@extends('layouts.public')

@section('title', __('hadith.browse_collections').' — '.__('site.site_name'))
@section('meta_description', __('hadith.browse_intro'))

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <header class="mb-8">
            <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white">
                {{ __('hadith.browse_collections') }}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-300">{{ __('hadith.browse_intro') }}</p>
        </header>

        @forelse ($collections as $collection)
            @if ($loop->first)
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @endif

            <a href="{{ route('public.hadith.collection', $collection->code) }}"
               class="group flex flex-col rounded-2xl border border-gray-200 dark:border-gray-800
                      bg-white dark:bg-[#16213e] p-6 shadow-sm
                      transition hover:-translate-y-0.5 hover:shadow-lg motion-reduce:transform-none">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white line-clamp-2">
                        {{ $collection->getTranslation('name', app()->getLocale(), true) }}
                    </h2>
                    @if ($collection->featured)
                        <span class="shrink-0 px-2 py-0.5 rounded-full text-[11px] font-semibold
                                     bg-[#64CCC5]/20 text-[#0056b3] dark:text-[#64CCC5]">
                            {{ __('hadith.featured') }}
                        </span>
                    @endif
                </div>

                @if ($author = $collection->getTranslation('author', app()->getLocale(), true))
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300 line-clamp-1">{{ $author }}</p>
                @endif

                <p class="mt-4 text-sm font-semibold text-[#218838] dark:text-[#64CCC5]">
                    {{ __('hadith.hadiths_count', ['count' => number_format($collection->hadiths_count)]) }}
                </p>
            </a>

            @if ($loop->last)
                </div>
            @endif
        @empty
            <x-site.empty-state :title="__('hadith.no_collections')" />
        @endforelse
    </div>
@endsection
