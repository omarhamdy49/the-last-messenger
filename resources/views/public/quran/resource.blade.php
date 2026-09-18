@extends('layouts.public')

@php
    $isTafsir = $resource['kind'] === 'tafsir';
    $rtl = in_array($resource['language'], ['ar', 'ur', 'fa', 'he'], true);
    $endpoint = $isTafsir
        ? route('public.quran.tafsir.chapter', [$resource['id'], '__CHAPTER__'])
        : route('public.quran.surah.translation', ['__CHAPTER__', $resource['id']]);
@endphp

@section('title', $resource['name'].' — '.__('quran.public.page_title'))
@section('meta_description', \Illuminate\Support\Str::limit($resource['info'] ?: $resource['name'], 160))

@section('content')
<div :class="$store.theme.dark ? 'bg-gray-950' : 'bg-gray-50'" class="min-h-dvh font-['Cairo'] transition-colors duration-300">
    <div class="container mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-8"
         x-data="{
             open: {}, texts: {}, loading: {},
             async toggle(chapter) {
                 this.open[chapter] = !this.open[chapter];
                 if (!this.open[chapter] || this.texts[chapter] || this.loading[chapter]) { return; }
                 this.loading[chapter] = true;
                 try {
                     const response = await fetch(@js($endpoint).replace('__CHAPTER__', chapter), { headers: { Accept: 'application/json' } });
                     this.texts[chapter] = response.ok ? await response.json() : {};
                 } catch (e) { this.texts[chapter] = {}; }
                 this.loading[chapter] = false;
             },
         }">

        <nav class="mb-6 text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'" aria-label="{{ __('quran.public.breadcrumb') }}">
            <a href="{{ route('public.quran') }}" class="hover:underline">{{ __('quran.public.page_title') }}</a>
            <span class="mx-2">/</span>
            <span>{{ $resource['name'] }}</span>
        </nav>

        <header class="mb-8 rounded-3xl p-8 text-white" :class="$store.theme.dark ? 'bg-gradient-to-r from-gray-900 to-gray-800' : 'bg-gradient-to-r from-blue-600 to-green-600'">
            <p class="text-sm opacity-80">{{ $isTafsir ? __('quran.public.tafsir_label') : __('quran.public.translation_label') }} · <span class="uppercase">{{ $resource['language'] }}</span></p>
            <h1 class="mt-1 text-3xl md:text-4xl font-bold" dir="auto">{{ $resource['name'] }}</h1>
            @if ($resource['author'])
                <p class="mt-2 opacity-90" dir="auto">{{ $resource['author'] }}</p>
            @endif
        </header>

        @if ($resource['info'] !== '')
            <details class="mb-8 rounded-2xl border p-5" :class="$store.theme.dark ? 'bg-gray-900 border-gray-800 text-gray-300' : 'bg-white border-gray-100 text-gray-700'">
                <summary class="cursor-pointer font-semibold">{{ __('quran.public.about_resource') }}</summary>
                <p class="mt-3 whitespace-pre-line leading-relaxed" dir="auto">{{ $resource['info'] }}</p>
            </details>
        @endif

        <ol class="space-y-3">
            @foreach ($surahs as $surah)
                <li class="rounded-2xl border overflow-hidden" :class="$store.theme.dark ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-100'">
                    <button type="button" @click="toggle({{ $surah['number'] }})" :aria-expanded="!!open[{{ $surah['number'] }}]"
                            class="flex w-full items-center gap-3 p-4 text-start">
                        <span class="w-9 h-9 shrink-0 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-white text-sm font-bold flex items-center justify-center">{{ $surah['number'] }}</span>
                        <span class="flex-1 font-['Amiri'] text-xl font-bold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">{{ $surah['nameAr'] }}</span>
                        <span class="text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ $surah['verses'] }} {{ __('quran.public.verse_unit') }}</span>
                    </button>
                    <div x-show="open[{{ $surah['number'] }}]" x-cloak class="border-t px-4 pb-4" :class="$store.theme.dark ? 'border-gray-800' : 'border-gray-100'">
                        <p x-show="loading[{{ $surah['number'] }}]" class="py-4 text-sm opacity-70">{{ __('quran.public.loading') }}</p>
                        <template x-for="[verseId, text] in Object.entries(texts[{{ $surah['number'] }}] || {})" :key="verseId">
                            <p class="py-3 border-b last:border-0 leading-relaxed whitespace-pre-line" dir="{{ $rtl ? 'rtl' : 'auto' }}"
                               :class="$store.theme.dark ? 'border-gray-800 text-gray-300' : 'border-gray-100 text-gray-700'" x-text="text"></p>
                        </template>
                        <a href="{{ route('public.quran.surah', $surah['number']) }}" class="mt-3 inline-block text-sm font-semibold text-[#218838] hover:underline">{{ __('quran.public.open_in_reader') }}</a>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</div>
@endsection
