{{-- Seerah event → editorial card. @props event (mapTimelineEvent payload), variant tile|row, eager --}}
@props(['event', 'variant' => 'tile', 'eager' => false])

@php
    $year = $event['hijriYear'];
@endphp

<x-public.editorial.card
    :url="$event['url']" :title="$event['title']" :excerpt="$event['description'] ?: null"
    :image="$event['image'] ?? null" :icon="$event['icon']" :badge="$event['typeLabel'] ?: null"
    :big="$year === null ? null : abs((int) $year)"
    :big-suffix="$year === null ? null : ((int) $year >= 0 ? __('seerah.public.hijri_suffix') : __('seerah.public.before_hijra_suffix'))"
    :meta="[
        ['icon' => 'moon', 'text' => $event['hijriDate'] ?? null],
        ['icon' => 'calendar', 'text' => ! empty($event['gregorianYear']) ? $event['gregorianYear'].' '.__('seerah.public.gregorian_suffix') : null],
        ['icon' => 'map-pin', 'text' => $event['location'] ?? null],
        ['icon' => 'scale', 'text' => ($event['hasDispute'] ?? false) ? __('seerah.public.dispute_badge') : null, 'danger' => true],
    ]"
    :variant="$variant" :eager="$eager" {{ $attributes }} />
