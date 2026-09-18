{{--
    Horizontal rail: section head + a snapping scroller that bleeds to the page
    gutters, with arrows that only appear while the track overflows.
    The parent Alpine scope must provide scrollableRow() (see public/media/index).

    @props title, subtitle, icon, viewAllUrl (renders a link) or viewAllClick
           (Alpine expression, renders a button), label (section aria-label)
    @slot default — the cards
--}}
@props(['title', 'subtitle' => null, 'icon' => null, 'viewAllUrl' => null, 'viewAllClick' => null, 'label' => null])

@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<section {{ $attributes->class('sx-section') }} x-data="scrollableRow()" aria-label="{{ $label ?? $title }}">
    <header class="sx-section-head">
        @if ($icon)
            <span class="sx-section-icon"><i data-lucide="{{ $icon }}" aria-hidden="true"></i></span>
        @endif
        <div class="min-w-0 flex-1">
            <h2 class="sx-section-title sx-clamp-1" title="{{ $title }}">{{ $title }}</h2>
            @if ($subtitle)
                <p class="text-sm text-[color:var(--sx-muted)]">{{ $subtitle }}</p>
            @endif
        </div>

        @if ($viewAllUrl)
            <a href="{{ $viewAllUrl }}" class="sx-chip shrink-0">{{ __('media.public.view_all') }}<i data-lucide="arrow-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i></a>
        @elseif ($viewAllClick)
            <button type="button" @click="{{ $viewAllClick }}" class="sx-chip shrink-0">{{ __('media.public.view_all') }}<i data-lucide="arrow-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i></button>
        @endif

        <div class="hidden sm:flex items-center gap-2 shrink-0" x-show="canScrollBack || canScrollForward" x-cloak>
            <button type="button" @click="scrollRow(-1)" :disabled="!canScrollBack"
                    class="sx-btn !min-h-11 !w-11 !px-0 disabled:opacity-40"
                    aria-label="{{ __('media.public.scroll_back') }}" title="{{ __('media.public.scroll_back') }}">
                <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
            </button>
            <button type="button" @click="scrollRow(1)" :disabled="!canScrollForward"
                    class="sx-btn !min-h-11 !w-11 !px-0 disabled:opacity-40"
                    aria-label="{{ __('media.public.scroll_forward') }}" title="{{ __('media.public.scroll_forward') }}">
                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i>
            </button>
        </div>
    </header>

    {{-- The track bleeds into the .sx-wrap gutters so a row reads as continuing off-screen. --}}
    <div x-ref="track" @scroll.passive.debounce.150ms="checkScroll()"
         class="sx-scroller snap-x -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 !gap-4 pb-2">
        {{ $slot }}
    </div>
</section>
