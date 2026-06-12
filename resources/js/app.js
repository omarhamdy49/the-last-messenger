import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { createIcons, icons } from 'lucide';

/*
|--------------------------------------------------------------------------
| Public site bootstrap — Alpine + Lucide + shared stores & components
|--------------------------------------------------------------------------
| localStorage conventions (per spec §13.5):
|   darkMode, language, viewMode, fontSize, readingMode, perPage, myList
*/

window.Alpine = Alpine;
Alpine.plugin(collapse);

/* ---------------------------------------------------------------- icons --- */
const renderIcons = () => createIcons({ icons });
window.renderIcons = renderIcons;

/* ----------------------------------------------------------- dark mode --- */
Alpine.store('theme', {
    dark: localStorage.getItem('darkMode') !== 'false', // default: DARK (prototype: `!== 'false'`)
    toggle() {
        this.dark = !this.dark;
        localStorage.setItem('darkMode', this.dark);
        document.documentElement.classList.toggle('dark', this.dark);
        queueMicrotask(renderIcons);
    },
    init() {
        document.documentElement.classList.toggle('dark', this.dark);
    },
});

/* -------------------------------------------------------------- toasts --- */
Alpine.store('toast', {
    items: [],
    push(message, type = 'success') {
        const id = Date.now() + Math.round(performance.now());
        this.items.push({ id, message, type });
        setTimeout(() => this.dismiss(id), 3200);
    },
    dismiss(id) {
        this.items = this.items.filter((t) => t.id !== id);
    },
});
window.toast = (message, type) => Alpine.store('toast').push(message, type);

/* ------------------------------------------------ reusable components --- */

// Horizontal scroll row with RTL-aware arrow visibility.
Alpine.data('scrollRow', () => ({
    canStart: false,
    canEnd: true,
    rtl: document.documentElement.dir === 'rtl',
    init() {
        this.$refs.track.addEventListener('scroll', () => this.update(), { passive: true });
        this.$nextTick(() => this.update());
        window.addEventListener('resize', () => this.update());
    },
    update() {
        const el = this.$refs.track;
        const max = el.scrollWidth - el.clientWidth;
        const pos = Math.abs(el.scrollLeft);
        this.canStart = pos > 16;
        this.canEnd = pos < max - 16;
    },
    scrollStart() { this.by(-1); },
    scrollEnd() { this.by(1); },
    by(dir) {
        const el = this.$refs.track;
        const amount = el.clientWidth * 0.8 * (this.rtl ? -dir : dir);
        el.scrollBy({ left: amount, behavior: 'smooth' });
    },
}));

// Auto-rotating hero carousel with pause-on-hover.
Alpine.data('heroCarousel', (count = 1, interval = 5000) => ({
    active: 0,
    count,
    timer: null,
    init() {
        this.start();
    },
    start() {
        this.timer = setInterval(() => this.next(), interval);
    },
    pause() { clearInterval(this.timer); },
    resume() { this.start(); },
    next() { this.active = (this.active + 1) % this.count; },
    go(i) { this.active = i; },
}));

// Scroll-driven top reading-progress bar.
Alpine.data('readingProgress', () => ({
    progress: 0,
    init() {
        const onScroll = () => {
            const h = document.documentElement;
            const max = h.scrollHeight - h.clientHeight;
            this.progress = max > 0 ? Math.min(100, (h.scrollTop / max) * 100) : 0;
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    },
}));

// Persisted grid/list view toggle.
Alpine.data('viewToggle', (key = 'viewMode', initial = 'grid') => ({
    mode: localStorage.getItem(key) || initial,
    set(mode) {
        this.mode = mode;
        localStorage.setItem(key, mode);
    },
}));

/* ----------------------------------------------------------- lifecycle --- */
Alpine.store('theme').init();
Alpine.start();
document.addEventListener('alpine:initialized', renderIcons);
document.addEventListener('DOMContentLoaded', renderIcons);

/* Alpine x-for/x-if re-renders insert fresh <i data-lucide> nodes that the
   load-time createIcons pass never sees (e.g. clearing a list filter re-clones
   every card and silently drops ~all icons). Watch for them and re-render,
   coalesced to one pass per frame. createIcons swaps <i> for <svg>, which
   doesn't match the selector — no feedback loop. */
let iconRenderQueued = false;
const scheduleRenderIcons = () => {
    if (iconRenderQueued) return;
    iconRenderQueued = true;
    requestAnimationFrame(() => {
        iconRenderQueued = false;
        renderIcons();
    });
};
const containsUnrenderedIcon = (node) => node.nodeType === 1
    && (node.matches?.('i[data-lucide]') || node.querySelector?.('i[data-lucide]'));
new MutationObserver((mutations) => {
    for (const mutation of mutations) {
        for (const added of mutation.addedNodes) {
            if (containsUnrenderedIcon(added)) {
                scheduleRenderIcons();
                return;
            }
        }
    }
}).observe(document.documentElement, { childList: true, subtree: true });
