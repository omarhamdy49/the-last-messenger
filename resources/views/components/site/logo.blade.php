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
        {{-- nowrap + truncate: the English wordmark is far longer than the Arabic
         one and wrapped to two lines at xl, which pushed the nav row out of
         alignment. Always visible — the mark alone reads as an icon, not a
         brand; phones get a smaller size and a truncation cap. --}}
        <span class="block truncate whitespace-nowrap text-base font-bold gradient-text max-w-[9.5rem] sm:max-w-none sm:text-xl 2xl:text-2xl">{{ $name }}</span>
    @endif
</a>
