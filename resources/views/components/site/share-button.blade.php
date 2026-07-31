{{--
    Share button — glass icon button for the daily panels.

    Replaces the previous dead `<button>` that carried a share icon and no
    handler. Uses the Web Share sheet where the platform has one and falls back
    to the clipboard; it only reports success when a copy actually succeeded.

    Props:
        $url     — absolute URL to share
        $title   — share sheet title
        $text    — optional share sheet body
        $variant — 'glass' (default) on coloured panels, 'surface' on white/dark
                   cards. A prop rather than a class override: both variants set
                   background and text colour, and utility order in the built
                   sheet — not source order — decides which wins.
--}}
@props([
    'url',
    'title',
    'text' => null,
    'variant' => 'glass',
])

<div x-data="{
        copied: false,
        async share() {
            const payload = { title: @js($title), text: @js($text ?? ''), url: @js($url) };

            if (navigator.share) {
                try { await navigator.share(payload); return; }
                catch (error) { if (error.name === 'AbortError') return; }
            }

            try {
                await navigator.clipboard.writeText(payload.url);
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2400);
            } catch (error) {
                // No share sheet and no clipboard access (insecure context or
                // denied permission) — stay silent rather than claim a copy.
                console.warn('Share unavailable:', error);
            }
        },
     }"
     class="contents">

    <button type="button"
            @click="share()"
            aria-label="{{ __('site.share') }}"
            {{ $attributes->class([
                'inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full',
                'ring-1 ring-inset transition focus:outline-none focus-visible:ring-2',
                'bg-white/15 text-white ring-white/25 backdrop-blur-sm hover:bg-white/25
                 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-transparent'
                    => $variant === 'glass',
                'bg-gray-100 text-gray-600 ring-gray-200 hover:bg-gray-200
                 dark:bg-[#0f3460] dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-[#0f3460]/70
                 focus-visible:ring-[#218838]'
                    => $variant === 'surface',
            ]) }}>
        <i class="h-5 w-5" data-lucide="share-2" x-show="!copied"></i>
        <i class="h-5 w-5" data-lucide="check" x-show="copied" x-cloak></i>
    </button>

    {{-- Screen readers get the confirmation the icon swap conveys visually. --}}
    <span class="sr-only" aria-live="polite" x-text="copied ? @js(__('site.link_copied')) : ''"></span>
</div>
