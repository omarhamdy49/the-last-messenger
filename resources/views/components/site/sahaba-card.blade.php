{{--
    Sahaba card — "Refined Medallion" premium component.
    Used in the home carousel and the public Sahaba listing grid.

    Props:
        $sahabi      — Sahabi model instance with `translations` relation loaded
        $color       — Tailwind gradient pair for the portrait header e.g. 'from-[#6f42c1] to-[#5a32a3]'
        $badge       — short honorific text e.g. 'الخليفة الأول'
        $badgeIcon   — lucide icon name for the corner accent e.g. 'star'
        $badgeColor  — gradient for the corner badge circle e.g. 'from-yellow-400 to-yellow-600'
        $accentColor — Tailwind text colour for the "read biography" link e.g. 'text-[#6f42c1]'
        $width       — outer width utilities. Carousel uses the fixed default so its
                       scroll-snap paging keeps working; the listing grid passes 'w-full'.

    Alignment contract: the root keeps the `.sahaba-card` hook (the carousel JS measures it)
    and stretches to the tallest sibling; the anchor is a full-height flex column whose footer
    is pinned with `mt-auto`, so the metadata row lines up across every card regardless of bio
    length.
--}}
@props([
    'sahabi',
    'color'       => null,
    'badge'       => null,
    'badgeIcon'   => null,
    'badgeColor'  => null,
    'accentColor' => null,
    'width'       => 'w-[80vw] max-w-[18rem] sm:w-72',
])
@php
    $locale   = app()->getLocale();
    $isRtl    = in_array($locale, ['ar', 'ur'], true);
    $name     = $sahabi->getTranslation('name', $locale) ?: $sahabi->getTranslation('name', 'ar');
    $laqab    = $sahabi->getTranslation('laqab', $locale) ?: $sahabi->getTranslation('laqab', 'ar');
    $kunya    = $sahabi->getTranslation('kunya', $locale) ?: $sahabi->getTranslation('kunya', 'ar');
    $honor    = $badge ?? ($laqab ?: $kunya ?: '');

    // "Abu/Umm …" teknonym (kunya) shown under the name as a badge. Suppressed
    // when it would merely repeat the honorific chip (companions with no laqab),
    // in which case the card falls back to the radi-Allah-anhu line.
    $kunyaBadge = ($kunya && $kunya !== $honor) ? $kunya : null;

    $cardColor = $color ?? 'from-[#6f42c1] to-[#5a32a3]';
    $icon      = $badgeIcon ?? 'star';
    $ringColor = $badgeColor ?? 'from-yellow-400 to-yellow-600';
    $accent    = $accentColor ?? 'text-[#6f42c1]';

    // Calligraphic monogram — first grapheme of the display name. Falls back to a
    // neutral mark for empty names so the medallion never renders blank.
    // Skip leading punctuation before taking the initial: transliterations like
    // "'Umar" / "'Uthman" would otherwise render an apostrophe in the medallion.
    $initial = mb_substr(preg_replace('/^[^\p{L}\p{N}]+/u', '', trim($name)) ?: trim($name), 0, 1) ?: '۞';

    $href  = Route::has('public.sahaba.show') ? route('public.sahaba.show', $sahabi->slug) : '#';

    // death_date_hijri is stored as "13 AH" — take the year and pair it with the
    // localized era suffix so the chip reads "13 هـ" / "13 AH" / "13 H". Omitted
    // entirely when the column is empty rather than rendering a bare icon.
    $deathYear = preg_match('/\d+/', (string) $sahabi->death_date_hijri, $m) ? $m[0] : null;
@endphp

@once
@push('head')
<style>
    /* ── Refined Medallion card ──────────────────────────────────────────────
       Self-contained: the carousel keeps its `.sahaba-card` measurement hook,
       and all motion is GPU-only (transform/opacity) and gated behind
       prefers-reduced-motion so the card stays a clean static composition when
       motion is unwelcome. */
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

<div class="flex-shrink-0 {{ $width }} h-full sahaba-card">
    <a href="{{ $href }}"
       class="sahaba-medallion-card group flex h-full flex-col rounded-2xl overflow-hidden
              bg-gradient-to-br from-white to-[#F8F9FA] dark:from-[#16213e] dark:to-[#1a1a2e]
              shadow-[0_10px_30px_-12px_rgba(0,0,0,0.25)] hover:shadow-[0_28px_50px_-12px_rgba(0,0,0,0.35)]
              border border-black/5 hover:border-black/10 dark:border-white/10 dark:hover:border-white/20
              focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
              focus-visible:ring-offset-transparent {{ str_replace('text-', 'focus-visible:ring-', $accent) }}">

        {{-- Portrait header --}}
        {{-- Taller than the original h-36/h-40: the name scrim covers the lower
             third, and at the old height it crowded the medallion. --}}
        <div class="sahaba-portrait relative h-44 sm:h-48 overflow-hidden bg-gradient-to-br {{ $cardColor }}
                    flex items-center justify-center">

            {{-- Soft radial vignette adds depth under the medallion. --}}
            <div class="absolute inset-0 opacity-90"
                 style="background:radial-gradient(120% 90% at 50% 18%, rgba(255,255,255,.18) 0%, transparent 55%);"></div>

            {{-- Honorific accent badge — top corner, clear of the monogram. --}}
            <div class="absolute top-2.5 end-2.5 z-[4] w-7 h-7 rounded-full flex items-center justify-center
                        bg-gradient-to-br {{ $ringColor }} shadow-md ring-2 ring-white/60 dark:ring-white/20">
                <i class="w-4 h-4 text-white" data-lucide="{{ $icon }}"></i>
            </div>

            {{-- Medallion + calligraphic monogram --}}
            <div class="relative z-[1] -mt-3 flex items-center justify-center
                        transition-transform duration-500 group-hover:scale-105">
                <svg class="sahaba-medallion absolute w-24 h-24 text-white/90" viewBox="0 0 100 100"
                     fill="none" aria-hidden="true">
                    {{-- Outer dotted orbit --}}
                    <circle cx="50" cy="50" r="47" stroke="currentColor" stroke-width="0.6"
                            stroke-dasharray="1 3" opacity="0.45"/>
                    {{-- Counter-rotating eight-point star (two squares) --}}
                    <g class="ring-outer" opacity="0.5">
                        <rect x="22" y="22" width="56" height="56" rx="4" stroke="currentColor" stroke-width="0.9"/>
                        <rect x="22" y="22" width="56" height="56" rx="4" stroke="currentColor" stroke-width="0.9"
                              transform="rotate(45 50 50)"/>
                    </g>
                    {{-- Inner medallion ring --}}
                    <g class="ring-inner" opacity="0.7">
                        <circle cx="50" cy="50" r="29" stroke="currentColor" stroke-width="0.8"/>
                        <circle cx="50" cy="50" r="24" stroke="currentColor" stroke-width="0.5" opacity="0.6"/>
                    </g>
                </svg>

                <div class="relative w-14 h-14 rounded-full flex items-center justify-center
                            bg-white/15 backdrop-blur-sm border border-white/30 shadow-inner">
                    <span class="text-white text-3xl leading-none select-none drop-shadow"
                          style="text-shadow:0 2px 8px rgba(0,0,0,.25);">{{ $initial }}</span>
                </div>
            </div>

            {{-- Light sheen sweep --}}
            <span class="sahaba-sheen"></span>

            {{-- Name overlay --}}
            <div class="absolute bottom-0 start-0 end-0 z-[3] px-3.5 pb-2.5 pt-7 text-white
                        bg-gradient-to-t from-black/75 via-black/30 to-transparent">
                @if($honor)
                    <span class="inline-flex max-w-full items-center mb-1 px-2 py-0.5 rounded-full
                                 text-[10px] font-semibold bg-white/20 backdrop-blur-sm truncate">{{ $honor }}</span>
                @endif
                <h3 class="text-lg sm:text-xl font-bold leading-tight truncate" title="{{ $name }}">{{ $name }}</h3>
                @if($kunyaBadge)
                    <span class="inline-flex max-w-full items-center mt-1 px-2 py-0.5 rounded-full text-[10px] font-semibold
                                 bg-black/25 ring-1 ring-white/25 text-white/90 backdrop-blur-sm truncate"
                          title="{{ $kunyaBadge }}">{{ $kunyaBadge }}</span>
                @else
                    <p class="text-[11px] text-white/80">{{ $sahabi->gender === 'female' ? __('site.radia_allah_anha') : __('site.radi_allah_anhu') }}</p>
                @endif
            </div>
        </div>

        {{-- Footer. The card used to end at the portrait: no metadata, and nothing
             saying it was clickable beyond a hover lift. `mt-auto` pins it so the
             read-link lines up across cards whatever the name length. --}}
        <div class="relative mt-auto px-4 pb-3.5 pt-3">
            {{-- Hairline in the card's accent, brightest at the centre. --}}
            <span class="absolute inset-x-4 top-0 h-px opacity-40 {{ $accent }}" aria-hidden="true"
                  style="background:linear-gradient(90deg,transparent,currentColor,transparent);"></span>

            {{-- One row, not two: the year is metadata and the read-link is the
                 affordance, so they read as a single footer line rather than a
                 stack that pushes the card taller. --}}
            <div class="flex items-center justify-between gap-3">
                @if($deathYear)
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold
                                 text-gray-500 dark:text-gray-400">
                        <i class="h-3.5 w-3.5 shrink-0" data-lucide="calendar" aria-hidden="true"></i>
                        <span class="sr-only">{{ __('sahaba.death_year') }}:</span>
                        <span class="tabular-nums">{{ $deathYear }} {{ __('sahaba.year_hijri') }}</span>
                    </span>
                @else
                    <span></span>
                @endif

                <span class="inline-flex items-center gap-1.5 text-xs font-bold {{ $accent }}">
                    <span>{{ __('site.read_biography') }}</span>
                    <i class="sahaba-card-arrow h-4 w-4 shrink-0"
                       data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}" aria-hidden="true"></i>
                </span>
            </div>
        </div>
    </a>
</div>
