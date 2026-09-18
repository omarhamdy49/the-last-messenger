{{--
    Editorial card (events, articles, series). Long titles clamp to 2 lines; the full title is the tooltip.

    @props url, title, excerpt, image, icon (fallback glyph + badge icon), badge (label on the image),
           featured (bool), big / bigSuffix (large figure on the image, e.g. a Hijri year or a count),
           meta: list of {icon, text, danger?}, variant: tile|row, eager (bool)
--}}
@props(['url', 'title', 'excerpt' => null, 'image' => null, 'icon' => 'file-text', 'badge' => null, 'featured' => false,
        'big' => null, 'bigSuffix' => null, 'meta' => [], 'variant' => 'tile', 'eager' => false])

<a href="{{ $url }}" title="{{ $title }}" {{ $attributes->class(['sx-card group', 'is-row' => $variant === 'row']) }}>
    <div class="sx-card-media">
        @if ($image)
            <img src="{{ $image }}" alt="" width="640" height="400" loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async" onerror="this.remove()">
        @endif
        <span class="sx-card-glyph" aria-hidden="true"><i data-lucide="{{ $icon }}"></i></span>
        <span class="sx-card-type flex flex-wrap gap-1.5">
            @if ($featured)
                <span class="sx-glass-chip !bg-amber-500/85 !border-amber-200/40"><i data-lucide="star" aria-hidden="true"></i>{{ __('articles.public.featured') }}</span>
            @endif
            @if ($badge)
                <span class="sx-glass-chip sx-clamp-1 max-w-[12rem]"><i data-lucide="{{ $icon }}" class="inline align-[-2px]" aria-hidden="true"></i> {{ $badge }}</span>
            @endif
        </span>
        @if ($big !== null)
            <span class="sx-card-year" aria-hidden="true"><strong>{{ $big }}</strong>@if ($bigSuffix)<span>{{ $bigSuffix }}</span>@endif</span>
        @endif
    </div>

    <div class="sx-card-body">
        <h3 class="sx-card-title">{{ $title }}</h3>
        @if ($excerpt)
            <p class="sx-card-desc">{{ $excerpt }}</p>
        @endif
        @if ($meta !== [])
            <div class="sx-card-meta">
                @foreach ($meta as $item)
                    @continue(blank($item['text'] ?? null))
                    <span @class(['max-w-full', '!text-[color:var(--sx-danger)] font-bold' => $item['danger'] ?? false])>
                        <i data-lucide="{{ $item['icon'] }}" @class(['!text-[color:var(--sx-danger)]' => $item['danger'] ?? false]) aria-hidden="true"></i><span class="sx-clamp-1">{{ $item['text'] }}</span>
                    </span>
                @endforeach
            </div>
        @endif
        {{ $slot }}
    </div>
</a>
