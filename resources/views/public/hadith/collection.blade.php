@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $title = $collection->title($locale);
@endphp

@section('title', $title)
@section('meta_description', \Illuminate\Support\Str::of(strip_tags((string) $collection->intro($locale)))->limit(155) ?: __('hadith.library_tagline'))

@section('content')
<div class="min-h-dvh bg-gray-50 dark:bg-[#0b1120]">

    <section class="relative overflow-hidden bg-gradient-to-l from-[#0056b3] to-[#218838] text-white">
        <div class="absolute inset-0 opacity-[0.07] pointer-events-none"
             style="background-image:radial-gradient(circle at 1px 1px,#fff 1px,transparent 0);background-size:28px 28px"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
            <nav class="flex items-center gap-2 text-xs text-white/70 mb-5" aria-label="breadcrumb">
                <a href="{{ route('public.hadith') }}" class="hover:text-white transition-colors">{{ __('hadith.collections') }}</a>
                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-3.5 h-3.5"></i>
                <span class="text-white font-semibold">{{ $title }}</span>
            </nav>

            <h1 class="text-2xl sm:text-4xl font-extrabold leading-tight">{{ $title }}</h1>

            @if ($collection->intro($locale))
                <p class="mt-3 max-w-3xl text-white/85 text-sm leading-relaxed">
                    {{ \Illuminate\Support\Str::of(strip_tags($collection->intro($locale)))->limit(320) }}
                </p>
            @endif

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <div class="px-4 py-2 rounded-xl bg-white/10 backdrop-blur text-sm">
                    <span class="font-extrabold tabular-nums">{{ number_format($books->count()) }}</span>
                    <span class="ms-1 text-white/80 text-xs">{{ __('hadith.books') }}</span>
                </div>
                <div class="px-4 py-2 rounded-xl bg-white/10 backdrop-blur text-sm">
                    <span class="font-extrabold tabular-nums">{{ number_format($collection->total_hadith) }}</span>
                    <span class="ms-1 text-white/80 text-xs">{{ __('hadith.hadiths') }}</span>
                </div>
                <a href="{{ route('public.hadith.search', ['collection' => $collection->slug]) }}"
                   class="px-4 py-2 rounded-xl bg-white text-[#0056b3] text-sm font-bold hover:scale-105 transition-transform inline-flex items-center gap-2">
                    <i data-lucide="search" class="w-4 h-4"></i>{{ __('hadith.search_hadiths') }}
                </a>
            </div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        <h2 class="text-xl sm:text-2xl font-extrabold text-gray-900 dark:text-white mb-6">{{ __('hadith.book_index') }}</h2>

        @if ($books->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-300 dark:border-white/10 p-12 text-center">
                <i data-lucide="book" class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600"></i>
                <p class="text-gray-500 dark:text-gray-400">{{ __('hadith.no_books') }}</p>
            </div>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($books as $book)
                    <a href="{{ route('public.hadith.book', [$collection->slug, $book->book_number]) }}"
                       class="group flex items-start gap-3 rounded-2xl border border-gray-100 dark:border-white/5 bg-white dark:bg-white/[.03] p-4 hover:border-[#64CCC5]/50 hover:shadow-md transition-all">
                        <span class="flex-shrink-0 w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 flex items-center justify-center text-sm font-extrabold tabular-nums text-[#0056b3] dark:text-[#64CCC5] group-hover:bg-gradient-to-br group-hover:from-[#0056b3] group-hover:to-[#218838] group-hover:text-white transition-all">
                            {{ $book->book_number }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-bold text-[15px] text-gray-900 dark:text-white leading-snug group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors">
                                {{ $book->title_ar ?: $book->title_en }}
                            </span>
                            <span class="mt-1 flex items-center gap-1.5 text-[11px] text-gray-400 dark:text-gray-500">
                                <i data-lucide="scroll-text" class="w-3 h-3"></i>
                                <span class="tabular-nums font-semibold">{{ number_format((int) $book->hadiths_count) }}</span>
                                {{ __('hadith.hadiths') }}
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>document.addEventListener('DOMContentLoaded', () => window.lucide?.createIcons());</script>
@endpush
