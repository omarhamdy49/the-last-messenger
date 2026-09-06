import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { createIcons } from 'lucide';
// Curated icon subset (only icons actually used on public pages) — importing the
// full `icons` map pulled in all ~1500 icons (~579KB). See resources/js/icons.js.
import { icons } from './icons';

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

/* --------------------------------------------------- genealogy canvas --- */
/*
 * The genealogy page's d3 tree, loaded on demand.
 *
 * This `import()` is written literally so Vite can see it: the module stays a
 * separate lazily-fetched chunk (d3 never rides along in app.js), but it is
 * part of the normal module graph, resolved by the bundler.
 *
 * It used to be a separate Vite *entry* that the page fetched by building the
 * URL by hand with `Vite::asset()`. That URL is the dev server's origin
 * (127.0.0.1:5173), which broke two ways: from a phone on the LAN, 127.0.0.1
 * is the phone, so the tree never loaded; and in the browser the cross-origin
 * module dragged in a second HMR client that put the page into a reload loop,
 * wiping any open branch or fullscreen canvas a second after you touched it.
 * Letting the bundler own the reference removes both, and the production
 * entry-export tree-shaking workaround with it.
 */
window.loadGenealogyTree = () =>
    import('./genealogy-tree.js').then((m) => m.mountGenealogyTree);

/* ----------------------------------------------------------- dark mode --- */
Alpine.store('theme', {
    dark: window.resolveDarkMode(),
    toggle() {
        this.dark = !this.dark;
        localStorage.setItem('darkMode', this.dark);
        document.documentElement.classList.toggle('dark', this.dark);
        queueMicrotask(renderIcons);
    },

    /** Forget the explicit choice and follow the system again. */
    useSystem() {
        this.dark = window.clearThemePreference('darkMode');
        queueMicrotask(renderIcons);
    },
    init() {
        document.documentElement.classList.toggle('dark', this.dark);

        // theme-preference.blade.php flips the class when the OS changes and no
        // explicit choice is stored; mirror it into reactive state so every
        // :class binding follows too.
        window.addEventListener('theme-changed', (event) => {
            this.dark = event.detail.dark;
            queueMicrotask(renderIcons);
        });
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

/* ----------------------------------------------------------------- geo --- */
// Coarse visitor location (country + city only), loaded once and cached for the
// session so every authenticated request can share it without re-fetching.
// Source is a third-party CMP endpoint that ad-blockers/DNS often block — this
// always fails open: a missing geo never blocks anything.
Alpine.store('geo', {
    data: null,
    loaded: false,
    async load() {
        if (this.loaded) return this.data;
        this.loaded = true;

        try {
            const cached = sessionStorage.getItem('tlm.geo');
            if (cached) {
                this.data = JSON.parse(cached);
                return this.data;
            }
        } catch (_) { /* sessionStorage unavailable — ignore */ }

        try {
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), 2500);
            const res = await fetch('https://cmp.inmobi.com/geoip', { signal: controller.signal });
            clearTimeout(timer);

            if (res.ok) {
                const raw = await res.json();
                const country = String(raw.countryCode || raw.country_code || raw.country || '')
                    .slice(0, 2).toUpperCase();
                const city = String(raw.city || raw.cityName || '').slice(0, 64);

                const data = {};
                if (/^[A-Z]{2}$/.test(country)) data.country = country;
                if (city) data.city = city;
                this.data = data;

                try { sessionStorage.setItem('tlm.geo', JSON.stringify(data)); } catch (_) { /* ignore */ }
            }
        } catch (_) { /* fail-open: geo is optional, never block the request */ }

        return this.data;
    },
    headers() {
        const h = {};
        if (this.data?.country) h['X-Geo-Country'] = this.data.country;
        if (this.data?.city) h['X-Geo-City'] = this.data.city;
        return h;
    },
});

/* ------------------------------------------------------- foldForSearch --- */
// JS mirror of App\Domain\Hadith\Services\ArabicTextNormalizer::normalize().
// Client-side filters compare a typed query against server-folded text
// (`hadith_texts.search_body`), so the two sides must fold identically —
// otherwise "الحياء" never matches the stored "الْحَيَاءُ".
// Keep in step with the PHP class if its folding table changes.
const ARABIC_FOLDING = {
    'آ': 'ا', 'أ': 'ا', 'إ': 'ا', 'ٱ': 'ا',
    'ؤ': 'و', 'ئ': 'ي', 'ى': 'ي', 'ی': 'ي', 'ے': 'ي',
    'ة': 'ه', 'ہ': 'ه', 'ۃ': 'ه',
    'ک': 'ك', 'ڪ': 'ك',
};

window.foldForSearch = (text) => (text || '')
    .replace(/[​-‏﻿؜]/g, '')          // zero-width + directional
    .replace(/[ً-ْٰۖ-ۭ]/g, '')   // tashkeel + Quranic marks
    .replace(/ـ/g, '')                                // tatweel
    .replace(/[آأإٱؤئىیےةہۃکڪ]/g, (c) => ARABIC_FOLDING[c])
    .replace(/[«»“”"'‘’،؛؟.,;:!?()[\]{}]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .toLowerCase();

/* ------------------------------------------------------------- apiFetch --- */
// One request wrapper: merges CSRF + JSON headers + the shared geo headers into
// every call, so feature code never re-implements header wiring (DRY).
window.apiFetch = (url, options = {}) => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    return fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
            ...(Alpine.store('geo')?.headers() || {}),
            ...(options.headers || {}),
        },
    });
};

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

/* Live hadith suggestions under the library hero's search field.
   The form itself still submits to the server-rendered search page, so this is
   purely an accelerator: a reader who recognises their hadith in the dropdown
   skips a page load, and everyone else presses Enter as before.

   State is explicit ('idle' | 'loading' | 'ready' | 'error') rather than
   inferred from `results.length`, because "no results yet" and "no results at
   all" need different UI and an empty array cannot tell them apart. */
Alpine.data('hadithSuggest', ({ endpoint, basePath, locale, limit = 5 }) => ({
    q: '',
    state: 'idle',
    results: [],
    total: 0,
    open: false,
    active: -1,
    timer: null,
    // Abort rather than ignore: a slow request for "ال" must not land after a
    // fast one for "الحياء" and overwrite the newer results.
    controller: null,

    get canQuery() { return this.q.trim().length >= 2; },
    get showPanel() { return this.open && this.canQuery; },

    onInput() {
        this.active = -1;
        this.open = true;
        clearTimeout(this.timer);

        if (!this.canQuery) {
            this.abort();
            this.state = 'idle';
            this.results = [];
            return;
        }

        // Typing is bursty; one request per pause, not per keystroke.
        this.timer = setTimeout(() => this.fetch(), 300);
    },

    async fetch() {
        this.abort();
        this.controller = new AbortController();
        this.state = 'loading';

        try {
            const url = `${endpoint}?q=${encodeURIComponent(this.q.trim())}&per_page=${limit}`;
            const res = await window.apiFetch(url, {
                signal: this.controller.signal,
                headers: { 'Accept-Language': locale },
            });

            if (!res.ok) throw new Error(res.status);

            const body = await res.json();
            this.results = (body.data || []).map((h) => this.toSuggestion(h));
            this.total = body.meta?.total ?? this.results.length;
            this.state = 'ready';
        } catch (e) {
            // An abort is this component cancelling itself, not a failure the
            // reader should see — leave the panel showing the older state.
            if (e.name === 'AbortError') return;
            this.state = 'error';
        }
    },

    /* The API returns every stored text; pick the reader's locale and fall back
       to Arabic, which every hadith has. */
    toSuggestion(h) {
        const texts = h.texts || [];
        const text = texts.find((t) => t.locale === locale)
            || texts.find((t) => t.locale === 'ar')
            || texts[0];

        return {
            key: `${h.collection}:${h.hadith_number}`,
            reference: h.reference?.canonical || `${h.collection} ${h.hadith_number}`,
            grade: h.display_grade || null,
            body: (text?.body || '').slice(0, 190),
            // Built from a base path rather than a placeholder-substituted named
            // route: `public.hadith.show` is `/hadith/{collection}:{number}`, and
            // a placeholder token wide enough to survive URL generation would
            // itself have to violate the route's own segment constraints.
            url: `${basePath}/${encodeURIComponent(h.collection)}:${encodeURIComponent(h.hadith_number)}`,
        };
    },

    abort() {
        this.controller?.abort();
        this.controller = null;
    },

    move(step) {
        if (!this.results.length) return;
        const n = this.results.length;
        this.active = (this.active + step + n) % n;
    },

    // Enter on a highlighted suggestion opens it; otherwise the form submits
    // and the reader lands on the full search page. Both are valid outcomes.
    choose(event) {
        const hit = this.results[this.active];
        if (!hit) return;
        event.preventDefault();
        window.location.href = hit.url;
    },

    close() {
        this.open = false;
        this.active = -1;
    },

    destroy() {
        clearTimeout(this.timer);
        this.abort();
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

/* --------------------------------------------------- broken-image fallback ---
   Any <img> that fails to load (missing/renamed media file, e.g. article or
   series thumbnails) swaps to the shared placeholder instead of showing a broken
   image. One capture-phase listener covers every image on the page (image errors
   don't bubble but do fire in capture at document level); the data flag makes it
   idempotent and stops a loop if the placeholder itself were ever missing. */
const swapToPlaceholder = (img) => {
    if (img.dataset.imgFallback) return;
    img.dataset.imgFallback = '1';
    img.src = '/images/placeholder.png';
    img.srcset = '';
};
document.addEventListener('error', (e) => {
    const t = e.target;
    if (t && t.tagName === 'IMG') swapToPlaceholder(t);
}, true);
// Catch images that already errored before this script ran (above-the-fold).
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('img').forEach((img) => {
        if (img.src && img.complete && img.naturalWidth === 0) swapToPlaceholder(img);
    });
});

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

// Debounce the observer callback to reduce processing frequency during rapid DOM updates
let iconObserverTimer = null;
let pendingMutations = [];

const processPendingIconMutations = () => {
    const mutations = pendingMutations;
    pendingMutations = [];

    for (const mutation of mutations) {
        for (const added of mutation.addedNodes) {
            if (containsUnrenderedIcon(added)) {
                scheduleRenderIcons();
                return;
            }
        }
    }
};

const debouncedIconCheck = (mutations) => {
    pendingMutations.push(...mutations);
    clearTimeout(iconObserverTimer);
    iconObserverTimer = setTimeout(processPendingIconMutations, 150);
};

/* Set up observers for icon containers. Supports scoped observation via
   data-icon-container attribute for better performance, with fallback to
   document.documentElement for backward compatibility. */
const iconObservers = [];

const cleanupIconObservers = () => {
    // Disconnect all active observers
    iconObservers.forEach(obs => obs.disconnect());
    iconObservers.length = 0;

    // Clear any pending timers and mutations
    clearTimeout(iconObserverTimer);
    iconObserverTimer = null;
    pendingMutations.length = 0;
};

const setupIconObservers = () => {
    // Clean up any existing observers first
    cleanupIconObservers();

    // Look for scoped containers with data-icon-container attribute
    const containers = document.querySelectorAll('[data-icon-container]');

    if (containers.length > 0) {
        // Observe each scoped container
        containers.forEach(container => {
            const observer = new MutationObserver(debouncedIconCheck);
            observer.observe(container, { childList: true, subtree: true });
            iconObservers.push(observer);
        });
    } else {
        // Fallback to document-wide observation (backward compatible)
        const observer = new MutationObserver(debouncedIconCheck);
        observer.observe(document.documentElement, { childList: true, subtree: true });
        iconObservers.push(observer);
    }
};

// Initialize observers after DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupIconObservers);
} else {
    setupIconObservers();
}

// Clean up observers when the page is being unloaded to prevent memory leaks
// during SPA-style navigation or tab closure
window.addEventListener('pagehide', cleanupIconObservers);

// Export for potential cleanup or re-initialization
window.iconObservers = iconObservers;
window.setupIconObservers = setupIconObservers;
window.cleanupIconObservers = cleanupIconObservers;
