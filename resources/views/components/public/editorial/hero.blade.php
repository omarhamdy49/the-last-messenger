{{--
    Full-bleed hero. 'story' is the immersive opener; 'reading' is a shorter banner.
    Content sits on the start side over a scrim; the image drifts slightly on scroll (reader factory).

    @props image, variant: story|reading, title, lead
    @slots top (breadcrumb/navigator), chips, meta, actions
--}}
@props(['image' => null, 'variant' => 'reading', 'title', 'lead' => null])

<section id="event-hero" {{ $attributes->class(['sx-hero', 'is-story' => $variant === 'story', 'is-reading' => $variant !== 'story']) }}>
    <div class="sx-hero-media" aria-hidden="true" style="view-transition-name: sx-hero">
        <div class="sx-hero-fallback"></div>
        @if ($image)
            <img src="{{ $image }}" alt="" width="1920" height="1080" fetchpriority="high" decoding="async" data-sx-parallax class="relative" onerror="this.remove()">
        @endif
    </div>

    <div class="sx-wrap sx-hero-inner">
        {{ $top ?? '' }}

        <div class="grid gap-5 sx-rise">
            @isset($chips)<div class="flex flex-wrap items-center gap-2">{{ $chips }}</div>@endisset
            <h1 class="sx-hero-title">{{ $title }}</h1>
            @if ($lead)<p class="sx-hero-lead">{{ $lead }}</p>@endif
            @isset($meta)<div class="sx-hero-meta">{{ $meta }}</div>@endisset
            @isset($actions)<div class="flex flex-wrap items-center gap-3 pt-1">{{ $actions }}</div>@endisset
        </div>
    </div>
</section>
