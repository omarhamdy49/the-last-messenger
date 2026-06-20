{{--
    Brand icon — the Seal of the Prophet ﷺ (محمد رسول الله), optimized transparent WebP.
    Single source of truth for the mark; reuse everywhere instead of duplicating the <img>.

    Props:
        $size   — Tailwind sizing classes for the box (default w-12 h-12)
        $alt    — accessible alt text (defaults to the site name)
        $eager  — true above the fold (header) to load eagerly; otherwise lazy
--}}
@props([
    'size'  => 'w-12 h-12',
    'alt'   => null,
    'eager' => false,
])
@php $alt = $alt ?? __('site.site_name'); @endphp
<img src="{{ asset('images/site/rasool-allah-seal.webp') }}"
     alt="{{ $alt }}"
     width="48" height="48"
     loading="{{ $eager ? 'eager' : 'lazy' }}"
     decoding="async"
     draggable="false"
     {{ $attributes->merge(['class' => $size.' shrink-0 object-contain select-none']) }}>
