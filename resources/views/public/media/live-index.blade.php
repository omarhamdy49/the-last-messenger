@extends('layouts.public')

@section('title', __('media.public.live_channels').' | '.config('app.name'))
@section('meta_description', __('media.public.live_channels_description'))

@section('content')
@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<div class="min-h-screen font-['Cairo'] bg-gray-50 dark:bg-gray-950 transition-colors duration-300">

    {{-- ============================================================ --}}
    {{-- HERO — page banner (brand gradient + soft glows)             --}}
    {{-- ============================================================ --}}
    <section class="relative overflow-hidden bg-gray-950">
        <div class="absolute inset-0 bg-gradient-to-br from-[#0056b3] via-[#12335c] to-gray-950" aria-hidden="true"></div>
        {{-- Decorative glows --}}
        <div class="absolute -top-24 start-1/4 w-96 h-96 rounded-full bg-[#218838]/25 blur-3xl" aria-hidden="true"></div>
        <div class="absolute -bottom-32 end-0 w-[28rem] h-[28rem] rounded-full bg-[#64CCC5]/15 blur-3xl" aria-hidden="true"></div>
        <div class="absolute top-1/3 -start-20 w-72 h-72 rounded-full bg-red-600/10 blur-3xl" aria-hidden="true"></div>

        <div class="relative container mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-14 md:pt-10 md:pb-20">
            <nav aria-label="breadcrumb" class="mb-8">
                <a href="{{ route('public.media') }}"
                   class="inline-flex items-center gap-1.5 text-sm font-semibold text-white/70 hover:text-white transition-colors">
                    <i data-lucide="{{ $isRtl ? 'arrow-right' : 'arrow-left' }}" class="w-4 h-4" aria-hidden="true"></i>
                    {{ __('media.public.page_title') }}
                </a>
            </nav>

            <div class="max-w-2xl space-y-4">
                <span class="inline-flex items-center gap-2 px-3 py-1 bg-red-600 text-white text-xs font-bold rounded-full shadow-lg">
                    <span class="w-2 h-2 bg-white rounded-full animate-pulse motion-reduce:animate-none"></span>{{ __('media.public.live_now') }}
                </span>

                <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white leading-tight">
                    {{ __('media.public.live_channels') }}
                </h1>

                <p class="text-sm sm:text-base text-gray-300 leading-relaxed">
                    {{ __('media.public.live_channels_description') }}
                </p>
            </div>
        </div>

        {{-- Fade into the page background --}}
        <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-gray-50 to-transparent dark:from-gray-950 pointer-events-none" aria-hidden="true"></div>
    </section>

    {{-- ============================================================ --}}
    {{-- ALL CHANNELS — polished grid                                 --}}
    {{-- ============================================================ --}}
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex flex-wrap items-center gap-3 mb-8">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-red-600/15">
                <i data-lucide="radio" class="w-5 h-5 text-red-600" aria-hidden="true"></i>
            </span>
            <h2 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white">
                {{ __('media.public.live_channels') }}
            </h2>
            @if ($channels->total() > 0)
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-[#0056b3]/10 text-[#0056b3] dark:bg-[#64CCC5]/10 dark:text-[#64CCC5]">
                    {{ number_format($channels->total()) }}
                </span>
            @endif
        </div>

        @if ($channels->isEmpty())
            <x-site.empty-state icon="radio"
                                :title="__('media.public.live_channels')"
                                :message="__('media.public.no_live_channels')" />
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5">
                @foreach ($channels as $card)
                    @include('public.media._live-card', ['card' => $card, 'cardClass' => ''])
                @endforeach
            </div>

            <div class="mt-10">
                {{ $channels->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
