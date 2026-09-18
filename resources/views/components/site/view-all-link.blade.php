{{--
    View-all action — the single treatment for every "see everything in this
    section" link, so the two on the home page stop being two unrelated designs.

    Two variants rather than one, deliberately: a section that ends with a
    centred call to action and a section header that carries a quiet link are
    different levels of emphasis, and flattening them would break the page's
    hierarchy. They share motion, focus ring, RTL arrow and count formatting.

    Props:
        $href     — target
        $label    — link text
        $count    — optional total, rendered as a chip (locale-formatted)
        $variant  — 'pill' (primary, centred under a section) | 'inline' (header link)
        $gradient — literal Tailwind gradient pair, pill variant only; null (the
                    default) paints the brand signature gradient from the tokens
        $tone     — literal Tailwind text-* class, inline variant only
--}}
@props([
    'href',
    'label' => null,
    'count' => null,
    'variant' => 'pill',
    'gradient' => null,
    'tone' => 'text-[color:var(--sx-accent)]',
])

@php
    $label = $label ?? __('site.view_all');
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $arrow = $isRtl ? 'arrow-left' : 'arrow-right';
    // Locale-aware digits and grouping — "7,796" vs "٧٬٧٩٦".
    $countLabel = $count !== null ? \Illuminate\Support\Number::format((int) $count, locale: app()->getLocale()) : null;
@endphp

@once
@push('head')
<style>
    /* ── View-all action ─────────────────────────────────────────────────────
       GPU-only motion, gated behind prefers-reduced-motion. The sheen is the
       only decoration and it exists to signal the primary action, not to fill
       space — the inline variant deliberately has none. */
    .view-all-pill,
    .view-all-arrow { transition: transform .45s cubic-bezier(.22, 1, .36, 1), box-shadow .45s ease; }

    .view-all-sheen {
        position: absolute;
        inset: 0;
        background: linear-gradient(115deg, transparent 35%, rgba(255, 255, 255, .28) 50%, transparent 65%);
        transform: translateX(-130%);
        pointer-events: none;
    }
    [dir="rtl"] .view-all-sheen {
        background: linear-gradient(245deg, transparent 35%, rgba(255, 255, 255, .28) 50%, transparent 65%);
        transform: translateX(130%);
    }

    @media (prefers-reduced-motion: no-preference) {
        .view-all-pill:hover { transform: translateY(-3px); }
        .group:hover .view-all-arrow { transform: translateX(4px); }
        [dir="rtl"] .group:hover .view-all-arrow { transform: translateX(-4px); }
        .group:hover .view-all-sheen {
            transition: transform 1s cubic-bezier(.22, 1, .36, 1);
            transform: translateX(130%);
        }
        [dir="rtl"] .group:hover .view-all-sheen { transform: translateX(-130%); }
    }
</style>
@endpush
@endonce

@if($variant === 'inline')
    <a href="{{ $href }}"
       {{ $attributes->class([
           'group inline-flex h-11 items-center gap-2 rounded-full px-3 text-sm font-bold',
           'transition hover:bg-black/5 focus:outline-none focus-visible:ring-2',
           'focus-visible:ring-current dark:hover:bg-white/10',
           $tone,
       ]) }}>
        <span>{{ $label }}</span>
        @if($countLabel)
            <span class="rounded-full bg-[color:var(--sx-surface-2)] px-2 py-0.5 text-xs font-bold tabular-nums">{{ $countLabel }}</span>
        @endif
        <i class="view-all-arrow h-4 w-4" data-lucide="{{ $arrow }}" aria-hidden="true"></i>
    </a>
@else
    <a href="{{ $href }}"
       @if (! $gradient) style="background: var(--sx-grad)" @endif
       {{ $attributes->class([
           'view-all-pill group relative isolate inline-flex h-12 items-center gap-3 overflow-hidden',
           'rounded-full px-7 text-base font-bold text-white',
           'bg-gradient-to-r '.$gradient => (bool) $gradient,
           'ring-1 ring-inset ring-white/25',
           'shadow-[0_12px_30px_-10px_rgba(0,0,0,.5)] hover:shadow-[0_20px_40px_-12px_rgba(0,0,0,.6)]',
           'focus:outline-none focus-visible:ring-2 focus-visible:ring-white',
           'focus-visible:ring-offset-2 focus-visible:ring-offset-transparent',
       ]) }}>
        <span class="view-all-sheen"></span>
        <span class="relative">{{ $label }}</span>
        @if($countLabel)
            <span class="relative rounded-full bg-white/20 px-2.5 py-1 text-xs font-bold tabular-nums
                         ring-1 ring-inset ring-white/25">{{ $countLabel }}</span>
        @endif
        <i class="view-all-arrow relative h-5 w-5" data-lucide="{{ $arrow }}" aria-hidden="true"></i>
    </a>
@endif
