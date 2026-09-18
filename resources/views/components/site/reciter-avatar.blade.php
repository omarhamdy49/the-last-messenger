{{--
    Reciter portrait with a name placeholder.

    No photo → the reciter's name, wrapped and clamped, on the brand gradient
    (never a lone initial). Below ~40px a wrapped name is unreadable, so
    `compact` shows the first word only; the full name stays in `title`.

    Props:
        $name     — display name (required)
        $image    — URL or null
        $size     — Tailwind size classes, e.g. 'w-12 h-12' (default 'w-10 h-10')
        $shape    — 'rounded-full' (default) | 'rounded-3xl' | …
        $text     — font-size class for the placeholder (default 'text-[10px]')
        $compact  — first word only (for tiny circles)
        $ring     — extra classes on the outer element (rings, shadows)
--}}
@props([
    'name',
    'image' => null,
    'size' => 'w-10 h-10',
    'shape' => 'rounded-full',
    'text' => 'text-[10px]',
    'compact' => false,
    'ring' => '',
])

@php
    $label = $compact ? (explode(' ', trim($name))[0] ?? $name) : $name;
@endphp

@if ($image)
    <img src="{{ $image }}" alt="{{ $name }}" loading="lazy"
         {{ $attributes->merge(['class' => "$size $shape object-cover bg-zinc-100 $ring"]) }}>
@else
    <span title="{{ $name }}" role="img" aria-label="{{ $name }}"
          {{ $attributes->merge(['class' => "$size $shape shrink-0 overflow-hidden [background:var(--sx-grad)] text-white font-bold flex items-center justify-center text-center px-1 $ring"]) }}>
        <span class="{{ $text }} leading-tight break-normal {{ $compact ? 'line-clamp-1' : 'line-clamp-3' }}">{{ $label }}</span>
    </span>
@endif
