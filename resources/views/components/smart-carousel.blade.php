{{--
    Smart Carousel Component
    Handles 1, 2, or many items intelligently (no navigation for 1-2 items)

    Props:
    - $items (collection): Items to display
    - $component (string): Name of card component to use

    Usage:
    <x-smart-carousel :items="$programs" component="cards.media-card" />
--}}

@props([
    'items',
    'component' => 'cards.media-card'
])

<div class="relative" x-data="scrollableRow()">
    <div class="flex overflow-x-auto custom-scrollbar gap-4 pb-4" x-ref="scrollContainer">
        @foreach($items as $index => $item)
        <div class="flex-none opacity-0 animate-fade-in stagger-{{ ($index % 4) + 1 }}">
            <x-dynamic-component :component="$component" :item="$item" />
        </div>
        @endforeach
    </div>

    {{-- Smart Navigation Buttons - only show when needed --}}
    <button x-show="showNavigation && canScrollLeft"
            @click="scrollLeft"
            class="absolute start-0 top-1/3 -ms-6 w-14 h-14 glass rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-300 hover:scale-110 shadow-xl z-10">
        <i class="w-6 h-6" :data-lucide="isRTL ? 'chevron-right' : 'chevron-left'"></i>
    </button>

    <button x-show="showNavigation && canScrollRight"
            @click="scrollRight"
            class="absolute end-0 top-1/3 -me-6 w-14 h-14 glass rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-300 hover:scale-110 shadow-xl z-10">
        <i class="w-6 h-6" :data-lucide="isRTL ? 'chevron-left' : 'chevron-right'"></i>
    </button>
</div>

@push('scripts')
<script>
function scrollableRow() {
    return {
        canScrollLeft: false,
        canScrollRight: false,
        isRTL: document.dir === 'rtl',
        itemCount: 0,
        visibleItems: 0,
        showNavigation: false,

        init() {
            this.$nextTick(() => {
                this.countItems();
                this.checkScroll();
                this.$refs.scrollContainer?.addEventListener('scroll', () => this.checkScroll());

                window.addEventListener('resize', () => {
                    this.countItems();
                    this.checkScroll();
                });
            });
        },

        countItems() {
            const container = this.$refs.scrollContainer;
            if (!container) return;

            this.itemCount = container.children.length;
            const containerWidth = container.clientWidth;
            const itemWidth = container.children[0]?.clientWidth || 0;
            const gap = 16;

            if (itemWidth > 0) {
                this.visibleItems = Math.floor(containerWidth / (itemWidth + gap));
            }

            this.showNavigation = this.itemCount > this.visibleItems && this.itemCount > 1;
        },

        checkScroll() {
            const container = this.$refs.scrollContainer;
            if (!container || !this.showNavigation) {
                this.canScrollLeft = false;
                this.canScrollRight = false;
                return;
            }

            const scrollLeft = container.scrollLeft;
            const scrollWidth = container.scrollWidth;
            const clientWidth = container.clientWidth;
            const threshold = 10;

            this.canScrollLeft = scrollLeft > threshold;
            this.canScrollRight = scrollLeft < (scrollWidth - clientWidth - threshold);
        },

        scrollLeft() {
            const container = this.$refs.scrollContainer;
            if (!container) return;

            const itemWidth = container.children[0]?.clientWidth || 0;
            const gap = 16;
            const scrollAmount = (itemWidth + gap) * Math.max(1, this.visibleItems - 1);

            container.scrollBy({
                left: this.isRTL ? scrollAmount : -scrollAmount,
                behavior: 'smooth'
            });

            setTimeout(() => this.checkScroll(), 400);
        },

        scrollRight() {
            const container = this.$refs.scrollContainer;
            if (!container) return;

            const itemWidth = container.children[0]?.clientWidth || 0;
            const gap = 16;
            const scrollAmount = (itemWidth + gap) * Math.max(1, this.visibleItems - 1);

            container.scrollBy({
                left: this.isRTL ? -scrollAmount : scrollAmount,
                behavior: 'smooth'
            });

            setTimeout(() => this.checkScroll(), 400);
        }
    };
}
</script>
@endpush
