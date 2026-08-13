@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $collectionTitle = $collection->title($locale);
    $bookTitle = $book->title_ar ?: $book->title_en;
@endphp

@section('title', $bookTitle.' — '.$collectionTitle)
@section('meta_description', $bookTitle.' — '.$collectionTitle)

@section('content')
<div class="min-h-dvh bg-gray-50 dark:bg-[#0b1120]">

    <section class="relative overflow-hidden bg-gradient-to-l from-[#0056b3] to-[#218838] text-white">
        <div class="absolute inset-0 opacity-[0.07] pointer-events-none"
             style="background-image:radial-gradient(circle at 1px 1px,#fff 1px,transparent 0);background-size:28px 28px"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-9 sm:py-12">
            <nav class="flex flex-wrap items-center gap-2 text-xs text-white/70 mb-4" aria-label="breadcrumb">
                <a href="{{ route('public.hadith') }}" class="hover:text-white transition-colors">{{ __('hadith.collections') }}</a>
                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-3.5 h-3.5"></i>
                <a href="{{ route('public.hadith.collection', $collection->slug) }}" class="hover:text-white transition-colors">{{ $collectionTitle }}</a>
                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-3.5 h-3.5"></i>
                <span class="text-white font-semibold">{{ __('hadith.book') }} {{ $book->book_number }}</span>
            </nav>

            <h1 class="text-2xl sm:text-3xl font-extrabold leading-tight">{{ $bookTitle }}</h1>

            <div class="mt-4 flex flex-wrap items-center gap-3 text-xs">
                <span class="px-3 py-1.5 rounded-lg bg-white/10 backdrop-blur">
                    <span class="font-extrabold tabular-nums">{{ number_format($hadiths->total()) }}</span>
                    <span class="ms-1 text-white/80">{{ __('hadith.hadiths') }}</span>
                </span>
                @if ($chapters->isNotEmpty())
                    <span class="px-3 py-1.5 rounded-lg bg-white/10 backdrop-blur">
                        <span class="font-extrabold tabular-nums">{{ number_format($chapters->count()) }}</span>
                        <span class="ms-1 text-white/80">{{ __('hadith.chapters') }}</span>
                    </span>
                @endif
                @if ($book->first_hadith_number && $book->last_hadith_number)
                    {{-- hadith_range is a placeholder string (':first'/':last'), not a
                         bare label — passing no params prints them literally. --}}
                    <span class="px-3 py-1.5 rounded-lg bg-white/10 backdrop-blur font-semibold">
                        {{ __('hadith.hadith_range', ['first' => $book->first_hadith_number, 'last' => $book->last_hadith_number]) }}
                    </span>
                @endif
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-12 grid lg:grid-cols-[280px_1fr] gap-8">

        {{-- Chapter index. Collapsed into a <details> below lg so it never
             pushes the hadiths off a phone screen. --}}
        @if ($chapters->isNotEmpty())
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <details class="lg:open group rounded-2xl border border-gray-100 dark:border-white/5 bg-white dark:bg-white/[.03] overflow-hidden" open>
                    <summary class="cursor-pointer lg:cursor-default list-none px-5 py-4 flex items-center justify-between gap-2">
                        <span class="font-extrabold text-sm text-gray-900 dark:text-white">{{ __('hadith.chapters') }}</span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 lg:hidden group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <nav class="px-2 pb-3 max-h-[60vh] overflow-y-auto custom-scrollbar">
                        @foreach ($chapters as $chapter)
                            <a href="#chapter-{{ $chapter->chapter_number }}"
                               class="block px-3 py-2 rounded-lg text-[13px] leading-snug text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#0056b3] dark:hover:text-[#64CCC5] transition-colors">
                                <span class="tabular-nums font-bold text-gray-400 dark:text-gray-600">{{ $chapter->chapter_number }}.</span>
                                {{ $chapter->title_ar ?: $chapter->title_en }}
                            </a>
                        @endforeach
                    </nav>
                </details>
            </aside>
        @else
            <div class="hidden lg:block"></div>
        @endif

        {{-- Hadiths --}}
        <main class="min-w-0">
            @if ($hadiths->isEmpty())
                <div class="rounded-2xl border border-dashed border-gray-300 dark:border-white/10 p-12 text-center">
                    <i data-lucide="scroll-text" class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600"></i>
                    <p class="text-gray-500 dark:text-gray-400">{{ __('hadith.no_hadiths') }}</p>
                </div>
            @else
                @php $lastChapter = null; @endphp
                <div class="space-y-4">
                    @foreach ($hadiths as $hadith)
                        @if ($hadith->chapter && $hadith->chapter->id !== $lastChapter)
                            @php $lastChapter = $hadith->chapter->id; @endphp
                            <h2 id="chapter-{{ $hadith->chapter->chapter_number }}"
                                class="scroll-mt-24 pt-4 first:pt-0 text-base sm:text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="w-1.5 h-6 rounded-full bg-gradient-to-b from-[#0056b3] to-[#218838]"></span>
                                {{ $hadith->chapter->title_ar ?: $hadith->chapter->title_en }}
                            </h2>
                        @endif

                        @include('public.hadith._hadith-card', ['hadith' => $hadith->setRelation('collection', $collection)])
                    @endforeach
                </div>

                @if ($hadiths->hasPages())
                    <div class="mt-8">{{ $hadiths->links() }}</div>
                @endif
            @endif
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>document.addEventListener('DOMContentLoaded', () => window.lucide?.createIcons());</script>
@endpush
