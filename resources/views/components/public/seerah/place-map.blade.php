{{-- Place map card. @props event --}}
@props(['event'])

@if (! empty($event['mapUrl']))
    <div {{ $attributes->class('sx-surface sx-map') }}>
        <div class="p-5 flex items-center gap-3">
            <span class="sx-section-icon"><i data-lucide="map-pin" aria-hidden="true"></i></span>
            <div class="min-w-0">
                <h2 class="font-extrabold text-[color:var(--sx-ink)]">{{ __('seerah.public.event_location') }}</h2>
                @if (! empty($event['place']['name']))
                    <p class="text-sm text-[color:var(--sx-muted)] sx-clamp-1" title="{{ $event['place']['name'] }}">{{ $event['place']['name'] }}</p>
                @endif
            </div>
        </div>
        <iframe src="{{ $event['mapUrl'] }}" title="{{ __('seerah.public.event_location_map') }}" width="600" height="288" loading="lazy"></iframe>
    </div>
@endif
