{{--
    Share button — glass icon button for the daily panels.

    Replaces the previous dead `<button>` that carried a share icon and no
    handler. Uses the Web Share sheet where the platform has one and falls back
    to the clipboard; it only reports success when a copy actually succeeded.

    Props:
        $url   — absolute URL to share
        $title — share sheet title
        $text  — optional share sheet body
--}}
@props([
    'url',
    'title',
    'text' => null,
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
                'bg-white/15 text-white ring-1 ring-inset ring-white/25 backdrop-blur-sm',
                'transition hover:bg-white/25 focus:outline-none focus-visible:ring-2',
                'focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-transparent',
            ]) }}>
        <i class="h-5 w-5" data-lucide="share-2" x-show="!copied"></i>
        <i class="h-5 w-5" data-lucide="check" x-show="copied" x-cloak></i>
    </button>

    {{-- Screen readers get the confirmation the icon swap conveys visually. --}}
    <span class="sr-only" aria-live="polite" x-text="copied ? @js(__('site.link_copied')) : ''"></span>
</div>
