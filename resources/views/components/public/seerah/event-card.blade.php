{{--
    Event card for the directory and related lists.

    @props event (mapTimelineEvent payload), variant: 'tile' (grid) | 'row' (timeline), eager (bool)
--}}
@props(['event', 'variant' => 'tile', 'eager' => false])

@php
    $year = $event['hijriYear'];
    $yearSuffix = $year === null ? null : ((int) $year >= 0 ? __('seerah.public.hijri_suffix') : __('seerah.public.before_hijra_suffix'));
@endphp

<a href="{{ $event['url'] }}" title="{{ $event['title'] }}" {{ $attributes->class(['sx-card group', 'is-row' => $variant === 'row']) }}>
    <div class="sx-card-media">
        @if (! empty($event['image']))
            <img src="{{ $event['image'] }}" alt="" width="640" height="400" loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async">
        @else
            <span class="sx-card-glyph" aria-hidden="true"><i data-lucide="{{ $event['icon'] }}"></i></span>
        @endif
        @if ($event['typeLabel'])
            <span class="sx-card-type sx-glass-chip"><i data-lucide="{{ $event['icon'] }}" aria-hidden="true"></i>{{ $event['typeLabel'] }}</span>
        @endif
        @if ($year !== null)
            <span class="sx-card-year" aria-hidden="true"><strong>{{ abs((int) $year) }}</strong><span>{{ $yearSuffix }}</span></span>
        @endif
    </div>

    <div class="sx-card-body">
        <h3 class="sx-card-title">{{ $event['title'] }}</h3>
        @if (! empty($event['description']))
            <p class="sx-card-desc">{{ $event['description'] }}</p>
        @endif
        <div class="sx-card-meta">
            @if (! empty($event['hijriDate']))
                <span><i data-lucide="moon" aria-hidden="true"></i>{{ $event['hijriDate'] }}</span>
            @endif
            @if (! empty($event['gregorianYear']))
                <span><i data-lucide="calendar" aria-hidden="true"></i>{{ $event['gregorianYear'] }} {{ __('seerah.public.gregorian_suffix') }}</span>
            @endif
            @if (! empty($event['location']))
                <span class="max-w-full"><i data-lucide="map-pin" aria-hidden="true"></i><span class="sx-clamp-1">{{ $event['location'] }}</span></span>
            @endif
            @if ($event['hasDispute'] ?? false)
                <span class="!text-[color:var(--sx-danger)] font-bold"><i data-lucide="scale" class="!text-[color:var(--sx-danger)]" aria-hidden="true"></i>{{ __('seerah.public.dispute_badge') }}</span>
            @endif
        </div>
    </div>
</a>
