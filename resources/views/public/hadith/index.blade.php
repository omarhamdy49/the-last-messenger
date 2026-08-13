@extends('layouts.public')

@section('title', __('hadith.library_title'))
@section('meta_description', __('hadith.library_tagline'))

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
@endphp

@section('content')
<div class="min-h-dvh bg-gray-50 dark:bg-[#0b1120]">

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-l from-[#0056b3] to-[#218838] text-white">
        <div class="absolute inset-0 opacity-[0.07] pointer-events-none"
             style="background-image:radial-gradient(circle at 1px 1px,#fff 1px,transparent 0);background-size:28px 28px"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-14 sm:py-20">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/15 backdrop-blur text-xs font-bold mb-5">
                <i data-lucide="book-open-text" class="w-4 h-4"></i>{{ __('hadith.full_library') }}
            </div>

            <h1 class="text-3xl sm:text-5xl font-extrabold leading-tight">{{ __('hadith.library_title') }}</h1>
            <p class="mt-3 max-w-2xl text-white/85 text-sm sm:text-base leading-relaxed">{{ __('hadith.library_tagline') }}</p>

            <div class="mt-8 flex flex-wrap items-center gap-3">
                <div class="px-4 py-2.5 rounded-xl bg-white/10 backdrop-blur">
                    <span class="text-2xl font-extrabold tabular-nums">{{ number_format($total) }}</span>
                    <span class="ms-1.5 text-xs text-white/80">{{ __('hadith.hadiths') }}</span>
                </div>
                <div class="px-4 py-2.5 rounded-xl bg-white/10 backdrop-blur">
                    <span class="text-2xl font-extrabold tabular-nums">{{ $collections->count() }}</span>
                    <span class="ms-1.5 text-xs text-white/80">{{ __('hadith.collections') }}</span>
                </div>
            </div>

            {{-- Search --}}
            <form action="{{ route('public.hadith.search') }}" method="GET" class="mt-8 max-w-2xl">
                <div class="relative">
                    <i data-lucide="search" class="absolute {{ $isRtl ? 'right-4' : 'left-4' }} top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    <input type="search" name="q" minlength="2" maxlength="120"
                           placeholder="{{ __('hadith.search_placeholder') }}"
                           class="w-full rounded-2xl border-0 bg-white dark:bg-[#111a2e] py-4 {{ $isRtl ? 'pr-12 pl-32' : 'pl-12 pr-32' }} text-gray-900 dark:text-white text-base shadow-xl focus:ring-2 focus:ring-[#64CCC5] outline-none">
                    <button type="submit"
                            class="absolute {{ $isRtl ? 'left-2' : 'right-2' }} top-1/2 -translate-y-1/2 px-5 py-2.5 rounded-xl bg-gradient-to-l from-[#0056b3] to-[#218838] text-white text-sm font-bold hover:scale-105 transition-transform">
                        {{ __('hadith.search_submit') }}
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{-- Collections --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white mb-2">{{ __('hadith.browse_collections') }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">{{ __('hadith.browse_intro') }}</p>

        @if ($collections->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-300 dark:border-white/10 p-12 text-center">
                <i data-lucide="library" class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600"></i>
                <p class="text-gray-500 dark:text-gray-400">{{ __('hadith.no_collections') }}</p>
            </div>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($collections as $collection)
                    <a href="{{ route('public.hadith.collection', $collection->slug) }}"
                       class="group relative rounded-2xl border border-gray-100 dark:border-white/5 bg-white dark:bg-white/[.03] p-6 hover:border-[#64CCC5]/50 hover:shadow-xl hover:-translate-y-1 transition-all">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0 shadow-md group-hover:scale-110 transition-transform">
                                <i data-lucide="book-marked" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-extrabold text-lg text-gray-900 dark:text-white leading-snug group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors">
                                    {{ $collection->title($locale) }}
                                </h3>
                                @if ($collection->is_featured)
                                    <span class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                        <i data-lucide="star" class="w-3 h-3"></i>{{ __('hadith.featured') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if ($collection->intro($locale))
                            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400 leading-relaxed line-clamp-3">
                                {{ \Illuminate\Support\Str::of(strip_tags($collection->intro($locale)))->limit(160) }}
                            </p>
                        @endif

                        <div class="mt-5 pt-4 border-t border-gray-100 dark:border-white/5 flex items-center gap-4 text-xs">
                            <span class="inline-flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                                <i data-lucide="book" class="w-3.5 h-3.5"></i>
                                <span class="font-bold tabular-nums text-gray-900 dark:text-white">{{ number_format($collection->books_count) }}</span>
                                {{ __('hadith.books') }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                                <i data-lucide="scroll-text" class="w-3.5 h-3.5"></i>
                                <span class="font-bold tabular-nums text-gray-900 dark:text-white">{{ number_format($collection->total_hadith) }}</span>
                                {{ __('hadith.hadiths') }}
                            </span>
                        </div>
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
