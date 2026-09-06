{{--
    Related Articles Component

    Displays related article navigation:
    - Previous/Next article navigation
    - Related articles carousel

    @param array|null $prev Previous article data
    @param array|null $next Next article data
    @param array $related Related articles array
    @param bool $isRtl RTL direction flag
--}}

@props(['prev' => null, 'next' => null, 'related' => [], 'isRtl' => false])

{{-- ============================================================ --}}
{{-- PREVIOUS / NEXT ARTICLE --}}
{{-- ============================================================ --}}
@if (! empty($prev) || ! empty($next))
<nav class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-4 no-print" aria-label="{{ __('articles.public.related_articles') }}">
    @if (! empty($prev))
        <a href="{{ $prev['url'] }}" class="group card-premium p-5 flex items-center gap-4 hover-lift">
            <span class="w-11 h-11 rounded-full bg-gradient-to-l from-primary to-secondary text-white flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-5 h-5"></i>
            </span>
            <span class="min-w-0">
                <span class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('articles.public.previous_article') }}</span>
                <span class="block font-bold text-gray-900 dark:text-white line-clamp-1 group-hover:text-primary transition-colors">{{ $prev['title'] }}</span>
            </span>
        </a>
    @else
        <span class="hidden md:block"></span>
    @endif

    @if (! empty($next))
        <a href="{{ $next['url'] }}" class="group card-premium p-5 flex items-center gap-4 hover-lift text-end">
            <span class="flex-1 min-w-0">
                <span class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('articles.public.next_article') }}</span>
                <span class="block font-bold text-gray-900 dark:text-white line-clamp-1 group-hover:text-primary transition-colors">{{ $next['title'] }}</span>
            </span>
            <span class="w-11 h-11 rounded-full bg-gradient-to-l from-primary to-secondary text-white flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-5 h-5"></i>
            </span>
        </a>
    @endif
</nav>
@endif

{{-- ============================================================ --}}
{{-- RELATED ARTICLES — horizontal rail, per the design prototype. --}}
{{-- Uses the shared library card so related studies look like     --}}
{{-- studies everywhere else on the site.                          --}}
{{-- ============================================================ --}}
@if (! empty($related))
    <section class="mt-12 no-print" aria-labelledby="related-label"
             x-data="{
                 atStart: true,
                 atEnd: false,
                 update() {
                     const el = this.$refs.rail;
                     if (! el) return;
                     // scrollLeft runs negative in RTL, so compare on
                     // the absolute distance travelled.
                     const pos = Math.abs(el.scrollLeft);
                     const max = el.scrollWidth - el.clientWidth;
                     this.atStart = pos <= 4;
                     this.atEnd = pos >= max - 4;
                 },
                 page(dir) {
                     const sign = {{ $isRtl ? -1 : 1 }};
                     this.$refs.rail.scrollBy({ left: sign * dir * 340, behavior: 'smooth' });
                 },
             }"
             x-init="$nextTick(() => update())"
             @resize.window.debounce.150ms="update()">

        <h2 id="related-label" class="text-2xl font-bold mb-6 flex items-center gap-3 text-gray-900 dark:text-white">
            <i data-lucide="layers" class="w-6 h-6 text-accent" aria-hidden="true"></i>
            {{ __('articles.public.related_articles') }}
        </h2>

        <div class="relative">
            <div x-ref="rail" @scroll.debounce.60ms="update()"
                 class="flex gap-6 overflow-x-auto rail-scroll pb-4 scroll-smooth snap-x snap-mandatory">
                @foreach ($related as $item)
                    <x-site.article-card :article="$item"
                                         class="w-80 shrink-0 snap-start"
                                         aspect="aspect-[16/9]" />
                @endforeach
            </div>

            {{-- Hidden rather than disabled at the ends: an arrow that
                 cannot move is a control that does nothing. --}}
            <button type="button" x-show="!atStart" x-cloak @click="page(-1)"
                    aria-label="{{ __('articles.public.previous') }}"
                    class="hidden sm:flex absolute start-0 top-[38%] -translate-y-1/2 w-11 h-11 rounded-full bg-white dark:bg-[#16213e] border border-gray-200 dark:border-[#0f3460] shadow-xl items-center justify-center text-gray-700 dark:text-gray-200 hover:scale-110 transition-transform z-10">
                <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-5 h-5"></i>
            </button>
            <button type="button" x-show="!atEnd" x-cloak @click="page(1)"
                    aria-label="{{ __('articles.public.next') }}"
                    class="hidden sm:flex absolute end-0 top-[38%] -translate-y-1/2 w-11 h-11 rounded-full bg-white dark:bg-[#16213e] border border-gray-200 dark:border-[#0f3460] shadow-xl items-center justify-center text-gray-700 dark:text-gray-200 hover:scale-110 transition-transform z-10">
                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-5 h-5"></i>
            </button>
        </div>
    </section>
@endif
