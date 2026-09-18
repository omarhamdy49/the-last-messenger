@extends('layouts.public')

@section('title', ($query !== '' ? $query.' — ' : '').__('quran.public.search_title'))
@section('meta_description', __('quran.public.search_description'))

@section('content')
<div :class="$store.theme.dark ? 'bg-gray-950' : 'bg-gray-50'" class="min-h-dvh font-['Cairo'] transition-colors duration-300">
    <div class="container mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-8">

        <nav class="mb-6 text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'" aria-label="{{ __('quran.public.breadcrumb') }}">
            <a href="{{ route('public.quran') }}" class="hover:underline">{{ __('quran.public.page_title') }}</a>
            <span class="mx-2">/</span>
            <span>{{ __('quran.public.search_title') }}</span>
        </nav>

        <h1 class="mb-6 text-3xl font-bold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">{{ __('quran.public.search_title') }}</h1>

        <x-public.quran.command-bar :value="$query" class="mb-4" />

        <form method="GET" class="mb-8 flex flex-wrap items-center gap-4 text-sm" :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'">
            <input type="hidden" name="q" value="{{ $query }}">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="exact" value="1" @checked($exact) onchange="this.form.submit()" class="rounded text-[#218838]">
                {{ __('quran.public.search_exact') }}
            </label>
        </form>

        @if ($results === null)
            <p class="text-center py-12" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.public.search_hint') }}</p>
        @elseif ($results['verses'] === [])
            <p class="text-center py-12" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.public.search_no_results', ['query' => $query]) }}</p>
        @else
            <p class="mb-4 text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">
                {{ trans_choice('quran.public.search_count', $results['pagination']['total_records'], ['count' => $results['pagination']['total_records']]) }}
            </p>
            <ol class="space-y-4">
                @foreach ($results['verses'] as $hit)
                    <li class="rounded-2xl border p-5 shadow-sm" :class="$store.theme.dark ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-100'">
                        <a href="{{ $hit['url'] }}" class="mb-2 inline-flex items-center gap-2 text-sm font-semibold text-[#218838] hover:underline">
                            {{ $hit['chapter_name'] }} <span dir="ltr">{{ $hit['verse_key'] }}</span>
                        </a>
                        <p class="font-['Amiri'] text-2xl leading-loose" dir="rtl" lang="ar" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">{{ $hit['text'] }}</p>
                        @if ($hit['translation'])
                            {{-- Escaped server-side; only <em> highlight markup is added. --}}
                            <p class="mt-2 leading-relaxed [&_em]:not-italic [&_em]:bg-yellow-200/70 [&_em]:rounded [&_em]:px-0.5"
                               :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'" dir="auto">{!! $hit['translation'] !!}</p>
                        @endif
                    </li>
                @endforeach
            </ol>

            @php $pages = $results['pagination']; @endphp
            @if ($pages['total_pages'] > 1)
                <nav class="mt-8 flex items-center justify-center gap-3" aria-label="{{ __('quran.public.pagination') }}">
                    @if ($pages['current_page'] > 1)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $pages['current_page'] - 1]) }}" class="px-4 py-2 rounded-xl font-semibold shadow"
                           :class="$store.theme.dark ? 'bg-gray-900 text-gray-200' : 'bg-white text-gray-700'">{{ __('quran.public.previous') }}</a>
                    @endif
                    <span class="px-4 py-2 rounded-xl font-semibold" :class="$store.theme.dark ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-900'">{{ $pages['current_page'] }} / {{ $pages['total_pages'] }}</span>
                    @if ($pages['current_page'] < $pages['total_pages'])
                        <a href="{{ request()->fullUrlWithQuery(['page' => $pages['current_page'] + 1]) }}" class="px-4 py-2 rounded-xl font-semibold shadow"
                           :class="$store.theme.dark ? 'bg-gray-900 text-gray-200' : 'bg-white text-gray-700'">{{ __('quran.public.next') }}</a>
                    @endif
                </nav>
            @endif
        @endif
    </div>
</div>
@endsection
