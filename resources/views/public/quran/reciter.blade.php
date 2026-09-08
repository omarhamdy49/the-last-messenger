@extends('layouts.public')

@section('title', $reciter['name'].' | '.__('quran.public.reciters'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) ($reciter['description'] ?? __('quran.public.reciters_page_description'))), 160))

@push('head')
<style>
    /* Equalizer bars for the currently-playing surah tile */
    .eq-bars { display: inline-flex; align-items: flex-end; gap: 2px; height: 14px; }
    .eq-bars span { width: 3px; border-radius: 2px; background: currentColor; animation: eqBounce 1s ease-in-out infinite; }
    .eq-bars span:nth-child(2) { animation-delay: .2s; }
    .eq-bars span:nth-child(3) { animation-delay: .4s; }
    @keyframes eqBounce { 0%, 100% { height: 4px; } 50% { height: 14px; } }
    @media (prefers-reduced-motion: reduce) { .eq-bars span { animation: none; height: 10px; } }
</style>
@endpush

@section('content')
@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<div class="min-h-dvh font-['Cairo'] bg-gray-50 dark:bg-gray-950 transition-colors duration-300"
     x-data
     :style="$store.audioPlayer.track ? 'padding-bottom: 7rem' : ''">

    {{-- ============================================================ --}}
    {{-- PROFILE HERO — brand gradient band, avatar, meta chips        --}}
    {{-- ============================================================ --}}
    <section class="relative overflow-hidden bg-gradient-to-l from-[#0056b3] via-[#116a53] to-[#218838]">
        <div class="absolute inset-0 opacity-[0.12]"
             style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;"></div>

        <div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-12 sm:pt-14 sm:pb-16">
            <a href="{{ route('public.quran.reciters') }}"
               class="inline-flex items-center gap-2 text-white/85 hover:text-white text-sm font-semibold mb-8 transition-colors">
                <i data-lucide="{{ $isRtl ? 'arrow-right' : 'arrow-left' }}" class="w-4 h-4"></i>
                <span>{{ __('quran.public.back_to_reciters') }}</span>
            </a>

            <div class="flex flex-col sm:flex-row items-center gap-6 sm:gap-10 text-center sm:text-start">
                {{-- Avatar --}}
                <div class="relative w-32 h-32 sm:w-40 sm:h-40 shrink-0 rounded-full p-1 bg-white/25 shadow-2xl">
                    @if ($reciter['image'])
                        <img src="{{ $reciter['image'] }}" alt="{{ $reciter['name'] }}"
                             class="w-full h-full rounded-full object-cover">
                    @else
                        <div class="w-full h-full rounded-full bg-white/15 backdrop-blur-sm flex items-center justify-center">
                            <span class="text-white text-5xl sm:text-6xl font-bold">{{ mb_substr($reciter['name'], 0, 1) }}</span>
                        </div>
                    @endif
                    @if ($reciter['is_featured'])
                        <span class="absolute bottom-1 end-1 w-9 h-9 rounded-full bg-white text-[#b8860b] flex items-center justify-center shadow-lg"
                              title="{{ __('quran.public.featured_reciters') }}">
                            <i data-lucide="star" class="w-5 h-5 fill-current"></i>
                        </span>
                    @endif
                </div>

                {{-- Name + chips --}}
                <div class="min-w-0">
                    <h1 class="text-3xl sm:text-5xl font-black text-white mb-4 leading-tight">{{ $reciter['name'] }}</h1>
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5">
                        @if ($reciter['country'])
                            <span class="inline-flex items-center gap-1.5 bg-white/15 backdrop-blur-sm text-white text-sm font-semibold px-4 py-1.5 rounded-full">
                                <span aria-hidden="true">{{ $reciter['country_flag'] }}</span>{{ $reciter['country_name'] }}
                            </span>
                        @endif
                        @if ($reciter['style'])
                            <span class="inline-flex items-center gap-1.5 bg-white/15 backdrop-blur-sm text-white text-sm font-semibold px-4 py-1.5 rounded-full">
                                <i data-lucide="mic" class="w-4 h-4"></i>{{ $reciter['style'] }}
                            </span>
                        @endif
                        @if ($reciter['riwayah'])
                            <span class="inline-flex items-center gap-1.5 bg-white/15 backdrop-blur-sm text-white text-sm font-semibold px-4 py-1.5 rounded-full">
                                <i data-lucide="book-open" class="w-4 h-4"></i>{{ $reciter['riwayah'] }}
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-1.5 bg-white/15 backdrop-blur-sm text-white text-sm font-semibold px-4 py-1.5 rounded-full">
                            <i data-lucide="audio-lines" class="w-4 h-4"></i>
                            {{ count($recitations) }} {{ __('quran.public.recording_unit') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- About --}}
        @if (filled($reciter['description']))
            <section class="mb-10">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                    <span class="w-1 h-7 rounded-full bg-gradient-to-b from-[#0056b3] to-[#218838]"></span>
                    {{ __('quran.public.reciter_about') }}
                </h2>
                <p class="text-gray-700 dark:text-gray-300 leading-relaxed max-w-3xl">{{ $reciter['description'] }}</p>
            </section>
        @endif

        {{-- Recitations --}}
        <section>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <span class="w-1 h-7 rounded-full bg-gradient-to-b from-[#0056b3] to-[#218838]"></span>
                {{ __('quran.public.reciter_recitations') }}
            </h2>

            @if (empty($recitations))
                <div class="rounded-3xl border-2 border-dashed border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900/40 py-16 text-center">
                    <i data-lucide="audio-lines" class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600"></i>
                    <p class="text-gray-500 dark:text-gray-400 font-semibold">{{ __('quran.public.reciter_no_recitations') }}</p>
                </div>
            @else
                {{-- Premium surah tiles — clicking routes through the shared
                     bottom-bar player ($store.audioPlayer), same as the Quran
                     reader. The active tile shows equalizer bars / pause. --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3"
                     x-data="{
                        isCurrent(url) { return $store.audioPlayer.track?.url === url },
                        isPlaying(url) { return this.isCurrent(url) && $store.audioPlayer.playing },
                        playOrToggle(url, title) {
                            this.isCurrent(url)
                                ? $store.audioPlayer.toggle()
                                : $store.audioPlayer.play({ url, title, subtitle: @js($reciter['name']) });
                        },
                     }">
                    @foreach ($recitations as $recitation)
                        @continue(blank($recitation['audio_url']))
                        <button type="button"
                                @click="playOrToggle(@js($recitation['audio_url']), @js($recitation['surah_name']))"
                                class="group flex items-center gap-3 rounded-2xl p-3.5 text-start border shadow-sm cursor-pointer transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 motion-reduce:transform-none bg-white dark:bg-gray-900 border-gray-100 dark:border-white/10"
                                :class="isCurrent(@js($recitation['audio_url'])) ? 'ring-2 ring-[#218838]/60 shadow-lg' : ''"
                                :aria-label="isPlaying(@js($recitation['audio_url'])) ? @js(__('quran.public.pause').' — '.$recitation['surah_name']) : @js(__('quran.public.listen_now').' — '.$recitation['surah_name'])">
                            <span class="w-9 h-9 shrink-0 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-white text-xs font-bold flex items-center justify-center tabular-nums">
                                {{ $recitation['surah_number'] }}
                            </span>
                            <span class="flex-1 min-w-0 font-bold text-sm text-gray-900 dark:text-white truncate">{{ $recitation['surah_name'] }}</span>

                            {{-- Play / pause / equalizer state --}}
                            <span class="w-9 h-9 shrink-0 rounded-full flex items-center justify-center transition-all duration-300 text-[#0056b3] dark:text-[#64CCC5] bg-[#0056b3]/10 dark:bg-white/10 group-hover:bg-gradient-to-r group-hover:from-[#0056b3] group-hover:to-[#218838] group-hover:text-white">
                                <template x-if="isPlaying(@js($recitation['audio_url']))">
                                    <span class="eq-bars" aria-hidden="true"><span></span><span></span><span></span></span>
                                </template>
                                <template x-if="isCurrent(@js($recitation['audio_url'])) && !$store.audioPlayer.playing">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6 4a1 1 0 011 1v10a1 1 0 11-2 0V5a1 1 0 011-1zm7 0a1 1 0 011 1v10a1 1 0 11-2 0V5a1 1 0 011-1z"/></svg>
                                </template>
                                <template x-if="!isCurrent(@js($recitation['audio_url']))">
                                    <svg class="w-4 h-4 {{ $isRtl ? '-scale-x-100' : '' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.84A1 1 0 004 3.7v12.6a1 1 0 001.5.86l11-6.3a1 1 0 000-1.72l-11-6.3z"/></svg>
                                </template>
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
