{{--
    Article Gallery Component

    Displays the article hero gallery with:
    - Multi-image slider with autoplay
    - Fullscreen lightbox
    - Thumbnail navigation
    - Category and tag overlays

    @param array $article Article data
    @param bool $isRtl RTL direction flag
--}}

@props(['article', 'isRtl' => false])

@php
    $heroImages = ! empty($article['gallery'])
        ? $article['gallery']
        : (! empty($article['image']) ? [['url' => $article['image'], 'caption' => null]] : []);
@endphp

@if (! empty($heroImages))
    <div x-data="gallerySlider(@js($heroImages), @js($article['title']), {{ $isRtl ? 'true' : 'false' }})"
         x-init="init()"
         @keydown.window="onKey($event)"
         @mouseenter="hover = true" @mouseleave="hover = false">

        {{-- Main stage --}}
        <div class="gallery-stage article-hero-media group relative h-64 sm:h-80 md:h-96 overflow-hidden bg-gray-100 dark:bg-[#0f3460] cursor-zoom-in"
             @click="openLightbox()">
            <img :src="current.url" :alt="current.caption || title"
                 onerror="this.style.display='none';this.parentElement.classList.add('img-gradient-fallback')"
                 onload="this.style.display='';this.parentElement.classList.remove('img-gradient-fallback')"
                 class="gallery-img w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/65 via-black/10 to-transparent pointer-events-none"></div>

            {{-- Per-image caption + article category/tag chips.
                 On phones the stage is short and the arrows sit at its
                 vertical centre — so the caption caps at one line, the
                 tag chips wait for sm:, and only the category chip
                 stays. Otherwise the overlays pile onto each other. --}}
            <div class="absolute bottom-4 start-4 end-4 sm:bottom-5 sm:start-5 sm:end-5 space-y-2 pointer-events-none z-10">
                <p x-show="current.caption" x-cloak x-text="current.caption"
                   class="text-white text-xs sm:text-sm md:text-base font-medium drop-shadow line-clamp-1 sm:line-clamp-2"></p>
                @if (! empty($article['category']) || ! empty($article['tags']))
                    <div class="flex flex-wrap gap-1.5 sm:gap-2">
                        {{-- Both sit on a photograph, so the surface has to be
                             darker than the text, not lighter. The light `glass`
                             utility put white type on a near-white pill. --}}
                        @if (! empty($article['category']))
                            <span class="px-3 py-1 text-xs sm:px-4 sm:py-1.5 sm:text-sm rounded-full font-semibold bg-gradient-to-l from-primary to-secondary text-white shadow-lg">{{ $article['category'] }}</span>
                        @endif
                        @foreach (array_slice($article['tags'], 0, 2) as $tag)
                            <span class="hidden sm:inline-flex px-4 py-1.5 rounded-full text-sm font-medium bg-black/45 backdrop-blur-sm ring-1 ring-white/25 text-white">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Multi-image controls --}}
            <template x-if="imgs.length > 1">
                <div>
                    {{-- Counter. `dir="ltr"` belongs on the numerals,
                         not on the positioned box: on the box it made
                         the element's own `start-4` resolve to the LEFT
                         edge in an RTL page, stacking the counter
                         underneath the autoplay button so only the "5"
                         of "1 / 5" was legible. --}}
                    <span class="absolute top-4 {{ $isRtl ? 'start-4' : 'end-4' }} px-3 py-1 rounded-full bg-black/50 backdrop-blur text-white text-xs font-medium tabular-nums z-10">
                        <span dir="ltr" class="inline-block"><span x-text="gi + 1"></span> / <span x-text="imgs.length"></span></span>
                    </span>
                    {{-- autoplay toggle --}}
                    <button type="button" @click.stop="togglePlay()" :aria-label="playing ? 'pause' : 'play'"
                            class="absolute top-4 {{ $isRtl ? 'end-4' : 'start-4' }} w-9 h-9 rounded-full backdrop-blur flex items-center justify-center text-white transition-all z-10"
                            :class="playing ? 'bg-gradient-to-l from-primary to-secondary shadow-lg' : 'bg-black/40 hover:bg-black/60'">
                        <i x-show="!playing" data-lucide="play" class="w-4 h-4"></i>
                        <i x-show="playing" x-cloak data-lucide="pause" class="w-4 h-4"></i>
                    </button>
                    {{-- prev / next. Always visible, as in the
                         design prototype: they were gated behind
                         group-hover, so on any touch device the
                         only way to reach slide 2 was the dot row
                         or a swipe nobody was told about. --}}
                    <button type="button" @click.stop="prev()" aria-label="{{ __('articles.public.previous') }}"
                            class="absolute start-2 sm:start-4 top-1/2 -translate-y-1/2 w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-white hover:bg-white/35 hover:scale-110 transition-all z-10">
                        <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                    </button>
                    <button type="button" @click.stop="next()" aria-label="{{ __('articles.public.next') }}"
                            class="absolute end-2 sm:end-4 top-1/2 -translate-y-1/2 w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-white hover:bg-white/35 hover:scale-110 transition-all z-10">
                        <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                    </button>
                    {{-- dot indicators (desktop) --}}
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 hidden sm:flex items-center gap-1.5 z-10">
                        <template x-for="(img, i) in imgs" :key="i">
                            <button type="button" @click.stop="select(i)" :aria-label="'image ' + (i + 1)"
                                    class="h-2 rounded-full transition-all duration-300"
                                    :class="gi === i ? 'w-6 bg-white' : 'w-2 bg-white/50 hover:bg-white/80'"></button>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Thumbnail strip under the main image --}}
        <div x-show="imgs.length > 1" class="flex gap-2 overflow-x-auto rail-scroll px-4 py-3 bg-gray-50 dark:bg-[#0f3460]/40 border-b border-gray-100 dark:border-[#0f3460]">
            <template x-for="(img, i) in imgs" :key="i">
                <button type="button" :data-thumb="i" @click="select(i)"
                        class="relative w-20 h-14 rounded-lg overflow-hidden shrink-0 bg-gray-100 dark:bg-[#0f3460] transition-all"
                        :class="gi === i ? 'ring-2 ring-primary' : 'opacity-60 hover:opacity-100'">
                    <img :src="img.url" :alt="img.caption || title"
                         onerror="this.style.display='none';this.parentElement.classList.add('img-gradient-fallback')"
                         class="w-full h-full object-cover">
                </button>
            </template>
        </div>

        {{-- Fullscreen lightbox — teleported to <body> so the article's
             transformed ancestor (animate-fade-in-up) can't clip the fixed overlay. --}}
        <template x-teleport="body">
        <div x-show="lightbox" x-cloak @click.self="closeLightbox()" @keydown.escape.window="closeLightbox()"
             class="fixed inset-0 z-[110] bg-black/95 backdrop-blur flex flex-col no-print"
             x-transition.opacity>
            <div class="flex items-center gap-3 p-4 text-white">
                <span dir="ltr" x-show="imgs.length > 1" class="px-3 py-1 rounded-full bg-white/10 text-sm font-medium tabular-nums">
                    <span x-text="gi + 1"></span> / <span x-text="imgs.length"></span>
                </span>
                <div class="flex items-center gap-2 ms-auto">
                    <button @click="closeLightbox()" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors" aria-label="{{ __('articles.public.close') }}">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            <div class="flex-1 relative flex items-center justify-center px-4 pb-2 min-h-0" @click.self="closeLightbox()">
                <img :src="current.url" :alt="current.caption || title"
                     class="max-w-full max-h-full object-contain rounded-lg shadow-2xl">
                <template x-if="imgs.length > 1">
                    <div>
                        <button @click="prev()" aria-label="{{ __('articles.public.previous') }}"
                                class="absolute start-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/15 hover:bg-white/30 backdrop-blur flex items-center justify-center text-white transition-all">
                            <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-6 h-6"></i>
                        </button>
                        <button @click="next()" aria-label="{{ __('articles.public.next') }}"
                                class="absolute end-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/15 hover:bg-white/30 backdrop-blur flex items-center justify-center text-white transition-all">
                            <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-6 h-6"></i>
                        </button>
                    </div>
                </template>
                <p x-show="current.caption" x-cloak x-text="current.caption"
                   class="absolute bottom-3 inset-x-0 mx-auto w-fit max-w-2xl text-center text-white text-sm bg-black/50 backdrop-blur rounded-full py-2 px-5"></p>
            </div>
            <div x-show="imgs.length > 1" class="flex gap-2 overflow-x-auto rail-scroll px-4 py-3 justify-start sm:justify-center">
                <template x-for="(img, i) in imgs" :key="i">
                    <button type="button" :data-thumb="i" @click="select(i)"
                            class="relative w-20 h-14 rounded-lg overflow-hidden shrink-0 transition-all"
                            :class="gi === i ? 'ring-2 ring-white' : 'opacity-50 hover:opacity-100'">
                        <img :src="img.url" :alt="img.caption || title"
                             onerror="this.style.display='none';this.parentElement.classList.add('img-gradient-fallback')"
                             class="w-full h-full object-cover">
                    </button>
                </template>
            </div>
        </div>
        </template>
    </div>
@endif
