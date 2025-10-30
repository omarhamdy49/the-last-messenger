export function smartCarousel() {
    return {
        itemCount: 0,
        visibleItems: 0,
        showNavigation: false,
        canScrollLeft: false,
        canScrollRight: false,

        init() {
            this.countItems();
            this.checkScroll();

            // Add resize listener with debounce
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    this.countItems();
                    this.checkScroll();
                }, 100);
            });

            // Add scroll listener
            this.$refs.scrollContainer?.addEventListener('scroll', () => {
                this.checkScroll();
            });
        },

        countItems() {
            const container = this.$refs.scrollContainer;
            if (!container) return;

            this.itemCount = container.children.length;

            // Calculate visible items based on container width
            const containerWidth = container.offsetWidth;
            const firstChild = container.children[0];
            if (!firstChild) return;

            const itemWidth = firstChild.offsetWidth;
            const gap = parseInt(getComputedStyle(container).gap) || 16;

            this.visibleItems = Math.floor(containerWidth / (itemWidth + gap));
            this.showNavigation = this.itemCount > this.visibleItems && this.itemCount > 1;
        },

        checkScroll() {
            const container = this.$refs.scrollContainer;
            if (!container) return;

            this.canScrollLeft = container.scrollLeft > 0;
            this.canScrollRight =
                container.scrollLeft < (container.scrollWidth - container.clientWidth - 10);
        },

        scrollLeft() {
            const container = this.$refs.scrollContainer;
            if (!container) return;

            const scrollAmount = container.offsetWidth * 0.8;
            container.scrollBy({
                left: -scrollAmount,
                behavior: 'smooth'
            });
        },

        scrollRight() {
            const container = this.$refs.scrollContainer;
            if (!container) return;

            const scrollAmount = container.offsetWidth * 0.8;
            container.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });
        }
    };
}
