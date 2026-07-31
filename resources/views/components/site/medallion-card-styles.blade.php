{{--
    Shared "Refined Medallion" card styles.

    Lives on its own so the Blade <x-site.sahaba-card> and the Alpine-rendered
    genealogy person cards render the *same* card rather than two lookalikes
    drifting apart. `@once` is keyed to this file, so including it from several
    places still emits the stylesheet exactly once per request.

    All motion is GPU-only (transform/opacity) and gated behind
    prefers-reduced-motion, so the card degrades to a clean static composition.
--}}
@once
@push('head')
<style>
    .sahaba-medallion-card {
        transition: transform .45s cubic-bezier(.22, 1, .36, 1),
                    box-shadow .45s cubic-bezier(.22, 1, .36, 1),
                    border-color .45s ease;
        will-change: transform;
    }

    /* The portrait header clips the sheen sweep and the oversized medallion. */
    .sahaba-portrait { isolation: isolate; }

    /* Layered geometric medallion behind the monogram. */
    .sahaba-medallion { transform-box: fill-box; transform-origin: center; }
    .sahaba-medallion .ring-outer,
    .sahaba-medallion .ring-inner {
        transform-box: fill-box;
        transform-origin: center;
        transition: transform 1.2s cubic-bezier(.22, 1, .36, 1);
    }

    /* Diagonal light sheen — parked off the start edge, sweeps across on hover. */
    .sahaba-sheen {
        position: absolute;
        inset: 0;
        background: linear-gradient(115deg, transparent 32%, rgba(255, 255, 255, .35) 50%, transparent 68%);
        transform: translateX(-130%);
        pointer-events: none;
        z-index: 2;
    }
    [dir="rtl"] .sahaba-sheen {
        background: linear-gradient(245deg, transparent 32%, rgba(255, 255, 255, .35) 50%, transparent 68%);
        transform: translateX(130%);
    }

    /* Read-link arrow nudges toward the reading direction on hover. */
    .sahaba-card-arrow { transition: transform .45s cubic-bezier(.22, 1, .36, 1); }

    @media (prefers-reduced-motion: no-preference) {
        .sahaba-medallion-card:hover,
        .sahaba-medallion-card:focus-visible {
            transform: translateY(-8px);
        }
        .group:hover .sahaba-card-arrow { transform: translateX(4px); }
        [dir="rtl"] .group:hover .sahaba-card-arrow { transform: translateX(-4px); }
        .group:hover .sahaba-medallion .ring-outer { transform: rotate(45deg); }
        .group:hover .sahaba-medallion .ring-inner { transform: rotate(-30deg); }
        .group:hover .sahaba-sheen {
            transition: transform 1s cubic-bezier(.22, 1, .36, 1);
            transform: translateX(130%);
        }
        [dir="rtl"] .group:hover .sahaba-sheen { transform: translateX(-130%); }
    }
</style>
@endpush
@endonce
