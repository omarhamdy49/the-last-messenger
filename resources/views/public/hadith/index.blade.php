@extends('layouts.public')

@section('title', __('hadith.library_title'))
@section('meta_description', __('hadith.library_tagline'))

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $featured = $collections->where('is_featured', true);
@endphp

@section('content')
<div class="min-h-dvh bg-gray-50 dark:bg-[#0b1120]">

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-l from-[#0056b3] to-[#218838] text-white">
        <div class="absolute inset-0 opacity-[0.07] pointer-events-none"
             style="background-image:radial-gradient(circle at 1px 1px,#fff 1px,transparent 0);background-size:28px 28px"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-14 sm:py-20 lg:py-0">
            <div class="grid lg:grid-cols-[1fr_auto] items-center gap-8 lg:gap-12">

                {{-- Copy + search. min-w-0: without it the featured strip's
                     intrinsic width (6 covers ≈ 636px) forces the grid track
                     wider than a phone viewport and clips the search button. --}}
                <div class="lg:py-24 min-w-0">
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

                {{-- Featured collections — coverflow slider (lg+): big glowing
                     center card, neighbours receding behind on both sides. --}}
                @if ($featured->isNotEmpty())
                    <div class="flex flex-col items-center gap-2 w-full max-w-[420px] mx-auto lg:mx-0 lg:w-[420px] xl:w-[500px] xl:max-w-none"
                         x-data="{
                             n: {{ $featured->count() }},
                             active: 0,
                             timer: null,
                             sx: 0,
                             dx: 0,
                             dragging: false,
                             swiped: false,
                             start() {
                                 if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                                 this.timer = setInterval(() => this.active = (this.active + 1) % this.n, 3500);
                             },
                             stop() { clearInterval(this.timer) },
                             down(e) { this.sx = e.clientX; this.dx = 0; this.swiped = false; this.dragging = true },
                             move(e) {
                                 if (!this.dragging) return;
                                 // capped drag distance → fractional card progress
                                 this.dx = Math.max(-140, Math.min(140, e.clientX - this.sx));
                             },
                             up(e) {
                                 const dx = e.clientX - this.sx;
                                 this.dragging = false;
                                 this.dx = 0;
                                 if (Math.abs(dx) > 40) {
                                     this.swiped = true;
                                     this.active = (this.active + (dx < 0 ? 1 : -1) + this.n) % this.n;
                                 }
                             },
                             cancel() { this.dragging = false; this.dx = 0 },
                             off(i) {
                                 let d = (i - this.active) % this.n;
                                 if (d > this.n / 2) d -= this.n;
                                 if (d < -this.n / 2) d += this.n;
                                 return d;
                             },
                             cardStyle(i) {
                                 // Each card animates ITSELF: while dragging, the pointer
                                 // offset becomes fractional progress added to the card's
                                 // slot, so every card morphs toward its next position
                                 // (position, scale, dimming, glow) continuously.
                                 const p = this.dragging ? this.dx / 180 : 0;
                                 const d = this.off(i) + p, a = Math.abs(d);
                                 // hidden cards park just beyond their OWN edge (not the
                                 // center) so entering/exiting cards slide in from the side
                                 if (a > 2.5) return `transform:translate(-50%,-50%) translateX(${Math.sign(d) * 3 * 72}%) scale(.55);opacity:0;pointer-events:none;z-index:0`;
                                 const t = Math.min(a, 1); // 0 at center → 1 one slot away
                                 return `transform:translate(-50%,-50%) translateX(${d * 72}%) scale(${1 - a * 0.17});`
                                     + `z-index:${Math.round(10 - a)};opacity:${1 - t * 0.45};`
                                     + `filter:brightness(${1 - t * 0.4});`
                                     + `box-shadow:0 0 55px rgba(100,204,197,${0.5 * (1 - t)}),0 0 20px rgba(100,204,197,${0.35 * (1 - t)}),0 16px 32px rgba(0,0,0,${0.35 * t})`;
                             }
                         }"
                         x-init="start()" @mouseenter="stop()" @mouseleave="start()"
                         aria-label="{{ __('hadith.featured_collections') }}">

                        <div class="relative w-full h-[180px] sm:h-[230px] lg:h-[300px] xl:h-[330px] select-none"
                             style="touch-action:pan-y"
                             @pointerdown="down($event)" @pointermove="move($event)"
                             @pointerup="up($event)" @pointercancel="cancel()" @pointerleave="cancel()">
                            @foreach ($featured->values() as $i => $collection)
                                <a href="{{ route('public.hadith.collection', $collection->slug) }}"
                                   @click="if (swiped || off({{ $i }}) !== 0) { $event.preventDefault(); if (!swiped) active = {{ $i }} }"
                                   :style="cardStyle({{ $i }})"
                                   :class="dragging ? '' : 'transition-all duration-500 [transition-timing-function:cubic-bezier(.34,1.4,.64,1)]'"
                                   :aria-hidden="off({{ $i }}) !== 0 ? 'true' : 'false'"
                                   draggable="false"
                                   class="absolute left-1/2 top-1/2 block w-24 sm:w-32 lg:w-40 xl:w-48 aspect-[3/4] rounded-xl lg:rounded-2xl overflow-hidden ring-1 ring-white/20 will-change-transform cursor-pointer">
                                    @include('public.hadith._collection-cover', ['collection' => $collection, 'locale' => $locale])
                                </a>
                            @endforeach
                        </div>

                        {{-- dots --}}
                        <div class="flex items-center gap-2" role="tablist">
                            @foreach ($featured->values() as $i => $collection)
                                <button type="button" @click="active = {{ $i }}"
                                        :class="active === {{ $i }} ? 'w-5 bg-[#64CCC5]' : 'w-1.5 bg-white/30 hover:bg-white/50'"
                                        class="h-1.5 rounded-full transition-all duration-300"
                                        aria-label="{{ $collection->title($locale) }}"></button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>


    {{-- Full library --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white mb-2 flex items-center gap-3">
            <span class="w-1.5 h-8 rounded-full bg-gradient-to-b from-[#0056b3] to-[#218838]"></span>
            {{ __('hadith.all_collections') }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-8 ms-[18px]">{{ __('hadith.library_tagline') }}</p>

        @if ($collections->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-300 dark:border-white/10 p-12 text-center">
                <i data-lucide="library" class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600"></i>
                <p class="text-gray-500 dark:text-gray-400">{{ __('hadith.no_collections') }}</p>
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4 sm:gap-5">
                @foreach ($collections as $collection)
                    <a href="{{ route('public.hadith.collection', $collection->slug) }}" class="group block">
                        <span class="relative block aspect-[3/4] rounded-xl overflow-hidden shadow-md shadow-black/10 group-hover:shadow-xl group-hover:-translate-y-1.5 transition-all duration-300">
                            @include('public.hadith._collection-cover', ['collection' => $collection, 'locale' => $locale])
                            @if ($collection->is_featured)
                                <span class="absolute top-2 end-2 w-6 h-6 rounded-full bg-black/25 backdrop-blur flex items-center justify-center">
                                    <i data-lucide="star" class="w-3 h-3 text-amber-300 fill-amber-300"></i>
                                </span>
                            @endif
                        </span>
                        <span class="block mt-3 px-0.5">
                            <span class="block font-bold text-sm text-gray-900 dark:text-white leading-snug truncate group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors">
                                {{ $collection->title($locale) }}
                            </span>
                            <span class="mt-0.5 block text-[11px] text-gray-400 dark:text-gray-500">
                                <span class="tabular-nums font-semibold">{{ number_format($collection->total_hadith) }}</span>
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
