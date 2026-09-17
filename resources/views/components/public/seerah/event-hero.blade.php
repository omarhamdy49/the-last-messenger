{{--
    Event hero. 'story' is the immersive full-bleed opener (default event view); 'reading' is a
    shorter banner for the article-style reader. Content sits on the start side over a scrim.

    @props event (detail payload), variant: story|reading, readingMinutes (int)
    @slots actions
--}}
@props(['event', 'variant' => 'story', 'readingMinutes' => null])

@php
    $excerpt = ! empty($event['description']) ? excerpt_sentence($event['description'], $variant === 'story' ? 220 : 180) : null;
@endphp

<section id="event-hero" @class(['sx-hero', 'is-story' => $variant === 'story', 'is-reading' => $variant === 'reading'])>
    <div class="sx-hero-media" aria-hidden="true">
        @if (! empty($event['coverImage']))
            <img src="{{ $event['coverImage'] }}" alt="" width="1920" height="1080" fetchpriority="high" decoding="async" data-sx-parallax>
        @else
            <div class="sx-hero-fallback"></div>
        @endif
    </div>

    <div class="sx-wrap sx-hero-inner">
        @include('public.seerah._event-navigator')

        <div class="grid gap-5 sx-rise">
            <div class="flex flex-wrap items-center gap-2">
                @if ($event['typeLabel'])
                    <span class="sx-glass-chip"><i data-lucide="{{ $event['icon'] }}" aria-hidden="true"></i>{{ $event['typeLabel'] }}</span>
                @endif
                @if (! empty($event['eraLabel']))
                    <span class="sx-glass-chip"><i data-lucide="hourglass" aria-hidden="true"></i>{{ $event['eraLabel'] }}</span>
                @endif
                @if (! empty($event['disputeNote']))
                    {{-- Anchor, not decoration: jumps to the note itself. --}}
                    <a href="#dispute" class="sx-glass-chip is-danger"><i data-lucide="scale" aria-hidden="true"></i>{{ __('seerah.public.dispute_badge') }}</a>
                @endif
            </div>

            <h1 class="sx-hero-title">{{ $event['title'] }}</h1>

            @if ($excerpt)
                <p class="sx-hero-lead">{{ $excerpt }}</p>
            @endif

            <div class="sx-hero-meta">
                @if ($event['hijriDate'])
                    <span class="sx-glass-chip"><i data-lucide="moon" aria-hidden="true"></i>{{ $event['hijriDate'] }}</span>
                @endif
                @if ($event['gregorianYear'])
                    <span class="sx-glass-chip"><i data-lucide="calendar" aria-hidden="true"></i>{{ $event['gregorianYear'] }} {{ __('seerah.public.gregorian_suffix') }}</span>
                @endif
                @if (! empty($event['place']['name']))
                    <span class="sx-glass-chip"><i data-lucide="map-pin" aria-hidden="true"></i>{{ $event['place']['name'] }}</span>
                @endif
                @if ($readingMinutes)
                    <span class="sx-glass-chip"><i data-lucide="clock" aria-hidden="true"></i>{{ __('seerah.public.reading_time', ['minutes' => $readingMinutes]) }}</span>
                @endif
            </div>

            @isset($actions)
                <div class="flex flex-wrap items-center gap-3 pt-1">{{ $actions }}</div>
            @endisset
        </div>
    </div>
</section>
