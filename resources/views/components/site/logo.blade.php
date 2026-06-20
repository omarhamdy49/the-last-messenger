{{--
    Brand logo — seal icon + site-name wordmark, linking home. Reusable lockup so the
    header (and anywhere else) never re-implements the mark + wordmark by hand.

    Props:
        $href     — link target (defaults to the home route)
        $showName — render the wordmark beside the icon (default true; it is sm:block)
        $iconSize — Tailwind sizing passed through to <x-site.brand-icon> (default w-12 h-12)
        $eager    — eager-load the icon (default true — the logo is above the fold)
--}}
@props([
    'href'     => null,
    'showName' => true,
    'iconSize' => 'w-12 h-12',
    'eager'    => true,
])
@php
    $href = $href ?? (Route::has('public.home') ? route('public.home') : url('/'));
    $name = __('site.site_name');
@endphp
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center space-s-3']) }}>
    <x-site.brand-icon :size="$iconSize" :alt="$name" :eager="$eager" />
    @if ($showName)
        <span class="text-2xl font-bold gradient-text hidden sm:block">{{ $name }}</span>
    @endif
</a>
