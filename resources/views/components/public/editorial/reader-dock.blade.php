{{--
    Reading progress bar + floating reader dock (optional navigator, optional layout switch,
    optional font size, save, share, top). Must render inside x-data="editorialReader(...)";
    the factory is pushed once below.

    @props navigator (bool, Seerah stations), switchUrl, switchLabel, switchIcon, fontScale (bool), saveLabel, unsaveLabel
--}}
@props(['navigator' => false, 'switchUrl' => null, 'switchLabel' => null, 'switchIcon' => 'file-text', 'fontScale' => false, 'saveLabel' => null, 'unsaveLabel' => null])

@php
    $saveLabel ??= __('seerah.public.save_event');
    $unsaveLabel ??= __('seerah.public.unsave_event');
@endphp

<div class="sx-progress" aria-hidden="true"><span :style="`transform: scaleX(${progress})`" style="transform: scaleX(0)"></span></div>

<div class="sx-dock" :class="{ 'is-hidden': dockHidden }" role="toolbar" aria-label="{{ __('seerah.public.reading_tools') }}">
    @if ($navigator)
        <button type="button" @click="$dispatch('seerah-navigator-open')" title="{{ __('seerah.public.navigator_open') }}">
            <i data-lucide="map" aria-hidden="true"></i><span class="sx-dock-label">{{ __('seerah.public.navigator_open') }}</span>
        </button>
    @endif
    @if ($switchUrl)
        <a href="{{ $switchUrl }}" title="{{ $switchLabel }}" aria-label="{{ $switchLabel }}">
            <i data-lucide="{{ $switchIcon }}" aria-hidden="true"></i>
        </a>
    @endif
    @if ($fontScale)
        <button type="button" @click="scaleFont(-10)" :disabled="fontSize <= 80" aria-label="A-" title="A-" class="disabled:opacity-40"><i data-lucide="a-arrow-down" aria-hidden="true"></i></button>
        <button type="button" @click="scaleFont(10)" :disabled="fontSize >= 150" aria-label="A+" title="A+" class="disabled:opacity-40"><i data-lucide="a-arrow-up" aria-hidden="true"></i></button>
    @endif
    @if ($navigator || $switchUrl || $fontScale)
        <span class="sx-dock-sep" aria-hidden="true"></span>
    @endif
    <button type="button" @click="toggleSave()" :class="{ 'is-on': saved }" :aria-pressed="saved.toString()" :disabled="savePending"
            :aria-label="saved ? @js($unsaveLabel) : @js($saveLabel)"
            :title="saved ? @js($unsaveLabel) : @js($saveLabel)">
        <span x-show="!saved"><i data-lucide="bookmark" aria-hidden="true"></i></span>
        <span x-show="saved" x-cloak><i data-lucide="bookmark-check" aria-hidden="true"></i></span>
    </button>
    <div class="relative" @click.outside="shareOpen = false" @keydown.escape.window="shareOpen = false">
        <button type="button" class="sx-dock-item" @click="share()" :aria-expanded="shareOpen.toString()" aria-label="{{ __('seerah.public.share') }}" title="{{ __('seerah.public.share') }}">
            <i data-lucide="share-2" aria-hidden="true"></i>
        </button>
        <div x-show="shareOpen" x-cloak x-transition.opacity class="sx-share-menu" role="menu">
            <button type="button" role="menuitem" @click="copyLink()"><i data-lucide="link" class="w-4 h-4" aria-hidden="true"></i>{{ __('seerah.public.copy_link') }}</button>
            <a role="menuitem" :href="shareUrl('whatsapp')" target="_blank" rel="noopener noreferrer"><i data-lucide="message-circle" class="w-4 h-4" aria-hidden="true"></i>{{ __('seerah.public.share_whatsapp') }}</a>
            <a role="menuitem" :href="shareUrl('x')" target="_blank" rel="noopener noreferrer"><i data-lucide="at-sign" class="w-4 h-4" aria-hidden="true"></i>{{ __('seerah.public.share_x') }}</a>
            <a role="menuitem" :href="shareUrl('facebook')" target="_blank" rel="noopener noreferrer"><i data-lucide="thumbs-up" class="w-4 h-4" aria-hidden="true"></i>{{ __('seerah.public.share_facebook') }}</a>
        </div>
    </div>
    <button type="button" @click="toTop()" aria-label="{{ __('seerah.public.scroll_to_top') }}" title="{{ __('seerah.public.scroll_to_top') }}">
        <i data-lucide="arrow-up" aria-hidden="true"></i>
    </button>
</div>

@once
@push('scripts')
<script>
    /**
     * Shared reader behaviour (Seerah events, articles): progress, dock visibility, scroll spy,
     * hero parallax, section reveal, figure count-up, favourites, sharing, and — when configured —
     * font scaling and async view counting. Degrades to the server-rendered page without JS.
     */
    function editorialReader(config) {
        return {
            fontSize: 100,
            views: config.views ?? null,
            progress: 0,
            dockHidden: config.hideDockOnHero ?? false,
            active: '',
            saved: !!config.saved,
            savePending: false,
            shareOpen: false,
            reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,

            init() {
                let ticking = false;
                const onScroll = () => {
                    if (ticking) return;
                    ticking = true;
                    requestAnimationFrame(() => { this.update(); ticking = false; });
                };
                window.addEventListener('scroll', onScroll, { passive: true });
                window.addEventListener('resize', onScroll, { passive: true });
                this.update();
                this.observeReveals();
                this.observeCounts();

                if (config.fontScale) {
                    try { this.fontSize = Math.min(150, Math.max(80, parseInt(localStorage.getItem('readerFontSize') || '100', 10) || 100)); } catch (e) {}
                }
                if (config.viewUrl) this.recordView();
            },

            scaleFont(step) {
                this.fontSize = Math.min(150, Math.max(80, this.fontSize + step));
                try { localStorage.setItem('readerFontSize', String(this.fontSize)); } catch (e) {}
            },

            // Counted asynchronously so it still works while the page itself is cached.
            recordView() {
                fetch(config.viewUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }).then((r) => (r.ok ? r.json() : null))
                  .then((data) => { if (data && typeof data.views === 'number') this.views = data.views; })
                  .catch(() => {});
            },

            update() {
                const max = Math.max(document.documentElement.scrollHeight - window.innerHeight, 1);
                this.progress = Math.min(window.scrollY / max, 1);

                if (config.hideDockOnHero) {
                    const hero = document.getElementById('event-hero');
                    this.dockHidden = !!hero && window.scrollY < hero.offsetHeight * 0.55;
                }

                const img = !this.reduced && document.querySelector('[data-sx-parallax]');
                if (img && window.scrollY < window.innerHeight * 1.2) {
                    img.style.transform = `translate3d(0, ${window.scrollY * 0.22}px, 0)`;
                }

                for (const id of (config.sections ?? [])) {
                    const el = document.getElementById(id);
                    if (!el) continue;
                    const rect = el.getBoundingClientRect();
                    if (rect.top <= 140 && rect.bottom > 140) { this.active = id; break; }
                }
            },

            observeReveals() {
                const items = [...document.querySelectorAll('.sx-reveal')];
                if (this.reduced || !('IntersectionObserver' in window)) return;
                const io = new IntersectionObserver((entries) => entries.forEach((entry) => {
                    if (entry.isIntersecting) { entry.target.dataset.shown = 'true'; io.unobserve(entry.target); }
                }), { rootMargin: '0px 0px -10% 0px' });
                // Only hide what is still below the fold, so nothing flashes out and back in.
                items.filter((el) => el.getBoundingClientRect().top > window.innerHeight)
                    .forEach((el) => { el.dataset.shown = 'false'; io.observe(el); });
            },

            observeCounts() {
                const items = [...document.querySelectorAll('[data-sx-count]')];
                if (this.reduced || !items.length || !('IntersectionObserver' in window)) return;
                const format = new Intl.NumberFormat(document.documentElement.lang || undefined);
                const io = new IntersectionObserver((entries) => entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    io.unobserve(entry.target);
                    const el = entry.target;
                    const target = parseFloat(el.dataset.sxCount);
                    const start = performance.now();
                    const step = (now) => {
                        const t = Math.min((now - start) / 1100, 1);
                        const eased = 1 - Math.pow(1 - t, 3);
                        el.textContent = format.format(Math.round(target * eased));
                        if (t < 1) requestAnimationFrame(step);
                    };
                    requestAnimationFrame(step);
                }), { threshold: 0.6 });
                items.forEach((el) => io.observe(el));
            },

            async toggleSave() {
                if (!config.isAuthenticated) { window.location.href = config.loginUrl; return; }
                if (this.savePending) return;
                const adding = !this.saved;
                this.saved = adding;
                this.savePending = true;
                try {
                    const response = await fetch(adding ? config.storeUrl : config.destroyUrl, {
                        method: adding ? 'POST' : 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ type: config.favoriteType ?? 'seerah', id: config.eventId }),
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok || payload.success === false) throw new Error('favorite_failed');
                    this.$store.toast?.push(payload.message || (adding ? config.messages.added : config.messages.removed), 'success');
                } catch (e) {
                    this.saved = !adding;
                    this.$store.toast?.push(config.messages.failed, 'error');
                } finally {
                    this.savePending = false;
                }
            },

            share() {
                if (navigator.share && window.matchMedia('(pointer: coarse)').matches) {
                    navigator.share({ title: config.title, url: window.location.href }).catch(() => {});
                    return;
                }
                this.shareOpen = !this.shareOpen;
                this.$nextTick(() => window.lucide?.createIcons());
            },

            shareUrl(network) {
                const url = encodeURIComponent(window.location.href);
                const text = encodeURIComponent(config.title);
                return {
                    whatsapp: `https://wa.me/?text=${text}%20${url}`,
                    x: `https://twitter.com/intent/tweet?text=${text}&url=${url}`,
                    facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
                }[network];
            },

            async copyLink() {
                try { await navigator.clipboard.writeText(window.location.href); } catch (e) {}
                this.$store.toast?.push(config.messages.copied, 'success');
                this.shareOpen = false;
            },

            toTop() {
                window.scrollTo({ top: 0, behavior: this.reduced ? 'auto' : 'smooth' });
            },
        };
    }
</script>
@endpush
@endonce
