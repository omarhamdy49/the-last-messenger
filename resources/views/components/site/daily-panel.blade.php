{{--
    Daily panel — the shell shared by the home page's Quran-of-the-day and
    Hadith-of-the-day cards. One raised surface from the public design system
    (`.sx-surface`), with the reading pane as a flat panel inside it.

    Props:
        $heading  — the panel's single heading. This used to be a small chip PLUS
                    an h3 beneath it saying nearly the same thing; the chip
                    absorbed the h3 instead of repeating it.
        $icon     — lucide icon name for the corner medallion (ignored when the
                    `glyph` slot is filled)
        $gradient — retained for callers that still pass it; the panel now takes
                    its colour from the design tokens, so it paints nothing.
                    ponytail: drop the prop once no caller passes it.
        $dotColor — same: retained, no longer painted.

    Slots:
        default   — panel body
        $glyph    — custom SVG for the medallion, for marks lucide has no icon for
        $actions  — footer action row, pinned to the bottom so both panels align

    Motion: the system's one card lift (transform only), disabled under
    prefers-reduced-motion by editorial.css.
--}}
@props([
    'heading',
    'icon' => null,
    'gradient' => null,
    'dotColor' => null,
])

<article {{ $attributes->class('sx-surface group flex h-full flex-col overflow-hidden') }}>
    <header class="flex items-center justify-between gap-4 p-5 pb-0 sm:p-7 sm:pb-0">
        <h3 class="sx-section-title sx-clamp-2 min-w-0" title="{{ $heading }}">{{ $heading }}</h3>

        <span class="sx-avatar is-square" aria-hidden="true">
            @isset($glyph)
                {{ $glyph }}
            @else
                <i data-lucide="{{ $icon }}"></i>
            @endisset
        </span>
    </header>

    <div class="flex flex-1 flex-col p-5 pt-5 sm:p-7 sm:pt-6">
        {{-- Centred so a short ayah doesn't leave a dead gap when the panel is
             stretched to match a long hadith beside it. --}}
        <div class="flex flex-1 flex-col justify-center">
            {{ $slot }}
        </div>

        @isset($actions)
            <div class="mt-auto flex flex-wrap items-center gap-3 pt-6">
                {{ $actions }}
            </div>
        @endisset
    </div>
</article>
