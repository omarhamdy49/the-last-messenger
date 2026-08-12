@extends('layouts.public')

@section('title', __('quran.nav.juz').' '.$juz.' — '.__('quran.public.page_title'))

@section('content')
<div :class="$store.theme.dark ? 'bg-gray-950' : 'bg-gray-50'" class="min-h-dvh font-['Cairo'] transition-colors duration-300">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <nav class="mb-6 text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">
            <a href="{{ route('public.quran') }}" class="hover:underline">{{ __('quran.public.page_title') }}</a>
            <span class="mx-2">/</span>
            <span>{{ __('quran.nav.juz') }} {{ $juz }}</span>
        </nav>

        {{-- Header --}}
        <div class="rounded-3xl overflow-hidden mb-8 p-8 md:p-12 text-center text-white"
             :class="$store.theme.dark ? 'bg-gradient-to-r from-gray-900 to-gray-800' : 'bg-gradient-to-r from-blue-600 to-green-600'">
            <p class="text-sm opacity-80 mb-2">{{ __('quran.public.juz_label') }}</p>
            <h1 class="text-4xl md:text-5xl font-bold">{{ __('quran.nav.juz') }} {{ $juz }}</h1>
            <p class="mt-3 opacity-80">{{ $pagination['total'] }} {{ __('quran.public.verse_unit') }}</p>
        </div>

        {{-- Prev / next juz --}}
        <div class="flex items-center justify-between gap-3 mb-6">
            @if($hasPrevJuz)
            <a href="{{ route('public.quran.juz', $juz - 1) }}"
               class="px-4 py-2 rounded-xl font-semibold shadow transition-colors"
               :class="$store.theme.dark ? 'bg-gray-900 text-gray-200 hover:bg-gray-800' : 'bg-white text-gray-700 hover:bg-gray-100'">
                {{ __('quran.public.previous') }} · {{ __('quran.nav.juz') }} {{ $juz - 1 }}
            </a>
            @else <span></span> @endif

            @if($hasNextJuz)
            <a href="{{ route('public.quran.juz', $juz + 1) }}"
               class="px-4 py-2 rounded-xl font-semibold shadow transition-colors"
               :class="$store.theme.dark ? 'bg-gray-900 text-gray-200 hover:bg-gray-800' : 'bg-white text-gray-700 hover:bg-gray-100'">
                {{ __('quran.nav.juz') }} {{ $juz + 1 }} · {{ __('quran.public.next') }}
            </a>
            @endif
        </div>

        @if(count($verses))
            @include('public.quran._reader', ['verses' => $verses])

            @if($pagination['total_pages'] > 1)
            <div class="flex items-center justify-center gap-3 mb-12">
                @if($pagination['has_prev'])
                <a href="{{ route('public.quran.juz', $juz) }}?page={{ $currentPage - 1 }}&per_page={{ $perPage }}"
                   class="px-4 py-2 rounded-xl font-semibold shadow transition-colors"
                   :class="$store.theme.dark ? 'bg-gray-900 text-gray-200 hover:bg-gray-800' : 'bg-white text-gray-700 hover:bg-gray-100'">
                    {{ __('quran.public.previous') }}
                </a>
                @endif
                <span class="px-4 py-2 rounded-xl font-semibold"
                      :class="$store.theme.dark ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-900'">
                    {{ $pagination['current_page'] }} / {{ $pagination['total_pages'] }}
                </span>
                @if($pagination['has_next'])
                <a href="{{ route('public.quran.juz', $juz) }}?page={{ $currentPage + 1 }}&per_page={{ $perPage }}"
                   class="px-4 py-2 rounded-xl font-semibold shadow transition-colors"
                   :class="$store.theme.dark ? 'bg-gray-900 text-gray-200 hover:bg-gray-800' : 'bg-white text-gray-700 hover:bg-gray-100'">
                    {{ __('quran.public.next') }}
                </a>
                @endif
            </div>
            @endif
        @else
            <div class="text-center py-16 text-lg" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">
                {{ __('quran.empty.no_verses') }}
            </div>
        @endif
    </div>
</div>
@endsection
