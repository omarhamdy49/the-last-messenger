@extends('layouts.public')

@php
    $label = $type->label();
    $routeName = match ($type) {
        \App\Domain\Quran\Enums\SectionType::JUZ => 'public.quran.juz',
        \App\Domain\Quran\Enums\SectionType::HIZB => 'public.quran.hizb',
        \App\Domain\Quran\Enums\SectionType::RUB_EL_HIZB => 'public.quran.rub',
        \App\Domain\Quran\Enums\SectionType::MANZIL => 'public.quran.manzil',
        \App\Domain\Quran\Enums\SectionType::RUKU => 'public.quran.ruku',
    };
    $navButton = "px-4 py-2 rounded-xl font-semibold shadow transition-colors";
    $navTheme = "\$store.theme.dark ? 'bg-gray-900 text-gray-200 hover:bg-gray-800' : 'bg-white text-gray-700 hover:bg-gray-100'";
@endphp

@section('title', $label.' '.$number.' — '.__('quran.public.page_title'))

@section('content')
<div :class="$store.theme.dark ? 'bg-gray-950' : 'bg-gray-50'" class="min-h-dvh font-['Cairo'] transition-colors duration-300">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <nav class="mb-6 text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'" aria-label="{{ __('quran.public.breadcrumb') }}">
            <a href="{{ route('public.quran') }}" class="hover:underline">{{ __('quran.public.page_title') }}</a>
            <span class="mx-2">/</span>
            <span>{{ $label }} {{ $number }}</span>
        </nav>

        <div class="rounded-3xl overflow-hidden mb-6 p-8 md:p-12 text-center text-white"
             :class="$store.theme.dark ? 'bg-gradient-to-r from-gray-900 to-gray-800' : 'bg-gradient-to-r from-blue-600 to-green-600'">
            <p class="text-sm opacity-80 mb-2">{{ $label }}</p>
            <h1 class="text-4xl md:text-5xl font-bold">{{ $label }} {{ $number }}</h1>
            <p class="mt-3 opacity-80">{{ $pagination['total'] }} {{ __('quran.public.verse_unit') }}</p>
        </div>

        {{-- Chapters in this section, with the verse range of each --}}
        @if ($section && $section->verse_mapping)
            <div class="mb-6 flex flex-wrap justify-center gap-2">
                @foreach ($section->verse_mapping as $chapter => $range)
                    <a href="{{ route('public.quran.surah', (int) $chapter) }}#v{{ (int) explode('-', $range)[0] }}"
                       class="rounded-full px-3 py-1 text-sm font-semibold border transition-colors"
                       :class="$store.theme.dark ? 'border-gray-700 text-gray-300 hover:border-[#64CCC5]' : 'border-gray-200 bg-white text-gray-700 hover:border-[#218838]'">
                        {{ $chapterNames[(int) $chapter] ?? $chapter }} <span class="opacity-70" dir="ltr">{{ $range }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="flex items-center justify-between gap-3 mb-6">
            @if ($number > 1)
                <a href="{{ route($routeName, $number - 1) }}" class="{{ $navButton }}" :class="{{ $navTheme }}">
                    {{ __('quran.public.previous') }} · {{ $label }} {{ $number - 1 }}
                </a>
            @else <span></span> @endif

            @if ($number < $type->max())
                <a href="{{ route($routeName, $number + 1) }}" class="{{ $navButton }}" :class="{{ $navTheme }}">
                    {{ $label }} {{ $number + 1 }} · {{ __('quran.public.next') }}
                </a>
            @endif
        </div>

        @if (count($verses))
            @include('public.quran._reader', ['verses' => $verses])

            @if ($pagination['total_pages'] > 1)
                <div class="flex items-center justify-center gap-3 mb-12">
                    @if ($pagination['has_prev'])
                        <a href="{{ route($routeName, [$number, 'page' => $currentPage - 1, 'per_page' => $perPage]) }}" class="{{ $navButton }}" :class="{{ $navTheme }}">{{ __('quran.public.previous') }}</a>
                    @endif
                    <span class="px-4 py-2 rounded-xl font-semibold" :class="$store.theme.dark ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-900'">
                        {{ $pagination['current_page'] }} / {{ $pagination['total_pages'] }}
                    </span>
                    @if ($pagination['has_next'])
                        <a href="{{ route($routeName, [$number, 'page' => $currentPage + 1, 'per_page' => $perPage]) }}" class="{{ $navButton }}" :class="{{ $navTheme }}">{{ __('quran.public.next') }}</a>
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
