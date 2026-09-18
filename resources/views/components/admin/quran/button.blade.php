{{-- Button or link. variant: primary|secondary|danger. Renders <a> when href is given. --}}
@props(['variant' => 'primary', 'href' => null, 'icon' => null, 'type' => 'submit'])
@php
    $classes = match ($variant) {
        'danger' => 'border border-red-300 text-red-600 hover:bg-red-50',
        'secondary' => 'border border-gray-300 hover:border-accent',
        default => 'text-white bg-gradient-to-r from-[#0056b3] to-[#218838] shadow',
    };
    $base = 'inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition-colors '.$classes;
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base]) }}>@if ($icon)<i data-lucide="{{ $icon }}" class="w-4 h-4"></i>@endif{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $base]) }}>@if ($icon)<i data-lucide="{{ $icon }}" class="w-4 h-4"></i>@endif{{ $slot }}</button>
@endif
