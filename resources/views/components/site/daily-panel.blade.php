{{--
    Daily panel — "Illuminated Manuscript" premium shell shared by the home page's
    Quran-of-the-day and Hadith-of-the-day cards.

    Props:
        $heading  — the panel's single heading, rendered as a chip. This used to
                    be a small chip PLUS an h3 beneath it saying nearly the same
                    thing; the chip absorbed the h3 instead of repeating it.
        $icon     — lucide icon name for the corner medallion (ignored when the
                    `glyph` slot is filled)
        $gradient — literal Tailwind gradient pair for the surface
        $dotColor — literal Tailwind bg-* class for the heading dot

    Slots:
        default   — panel body
        $glyph    — custom SVG for the medallion, for marks lucide has no icon for
        $actions  — footer action row, pinned to the bottom so both panels align

    Colour contract: the gradients callers pass are deliberately DARKER than the
    matching module accents (#17a2b8 / #218838). At those lighter hues white body
    text lands near 3:1 and fails WCAG AA. Any new gradient must keep white text
    at 4.5:1 — verify before swapping a hue.

    Motion contract: transform/opacity only, and the hover lift is gated behind
    prefers-reduced-motion so the panel stays a still composition when motion is
    unwelcome.
--}}
@props([
    'heading',
    'icon' => null,
    'gradient',
    'dotColor' => 'bg-[#64CCC5]',
])

@php
    // Deterministic per-variant id so the two panels on a page never collide on
    // the SVG pattern reference (random would break the cached home markup).
    $patternId = 'daily-pattern-'.substr(md5($icon.$gradient), 0, 8);
@endphp

@once
@push('head')
<style>
    /* ── Daily panel ─────────────────────────────────────────────────────────
       Self-contained. All motion is GPU-only and opt-in via prefers-reduced-motion. */
    .daily-panel,
    .daily-panel__medallion {
        transition: transform .5s cubic-bezier(.22, 1, .36, 1),
                    box-shadow .5s cubic-bezier(.22, 1, .36, 1);
    }

    @media (prefers-reduced-motion: no-preference) {
        .daily-panel:hover { transform: translateY(-6px); }
        .daily-panel:hover .daily-panel__medallion { transform: rotate(-6deg) scale(1.06); }
    }
</style>
@endpush
@endonce

<article {{ $attributes->class([
    'daily-panel group relative isolate flex h-full flex-col overflow-hidden rounded-3xl',
    'bg-gradient-to-br '.$gradient,
    'ring-1 ring-inset ring-white/15',
    'shadow-[0_18px_50px_-18px_rgba(0,0,0,.55)] hover:shadow-[0_34px_70px_-20px_rgba(0,0,0,.65)]',
]) }}>

    {{-- Geometric khatim lattice — texture, not decoration for its own sake. --}}
    <svg class="pointer-events-none absolute inset-0 -z-10 h-full w-full opacity-[0.07]" aria-hidden="true">
        <defs>
            <pattern id="{{ $patternId }}" width="56" height="56" patternUnits="userSpaceOnUse">
                <rect x="14" y="14" width="28" height="28" fill="none" stroke="#fff" stroke-width="1"/>
                <rect x="14" y="14" width="28" height="28" fill="none" stroke="#fff" stroke-width="1"
                      transform="rotate(45 28 28)"/>
                <circle cx="28" cy="28" r="2.5" fill="#fff"/>
            </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#{{ $patternId }})"/>
    </svg>

    {{-- Top-start light source + far-corner bloom give the flat gradient depth. --}}
    <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true"
         style="background:radial-gradient(115% 80% at 12% 0%, rgba(255,255,255,.20) 0%, transparent 58%);"></div>
    <div class="pointer-events-none absolute -bottom-24 -end-20 -z-10 h-64 w-64 rounded-full bg-white/10 blur-3xl"
         aria-hidden="true"></div>

    <header class="flex items-center justify-between gap-4 px-6 pt-6 sm:px-8 sm:pt-8">
        <h3 class="inline-flex min-w-0 items-center gap-2.5 rounded-full bg-white/15 px-5 py-2.5
                   text-lg font-black text-white ring-1 ring-inset ring-white/25 backdrop-blur-sm sm:text-xl">
            <span class="h-2 w-2 shrink-0 rounded-full {{ $dotColor }}"></span>
            <span class="truncate">{{ $heading }}</span>
        </h3>

        <div class="daily-panel__medallion flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl
                    bg-white/15 ring-1 ring-inset ring-white/25 backdrop-blur-sm sm:h-16 sm:w-16">
            @isset($glyph)
                {{ $glyph }}
            @else
                <i class="h-7 w-7 text-white sm:h-8 sm:w-8" data-lucide="{{ $icon }}"></i>
            @endisset
        </div>
    </header>

    <div class="flex flex-1 flex-col px-6 pb-6 pt-5 sm:px-8 sm:pb-8">
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
