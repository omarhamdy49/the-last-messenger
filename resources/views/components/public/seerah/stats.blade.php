{{--
    Event figures. Numeric values count up once when scrolled into view (seerahReader);
    the server-rendered final value is what no-JS and reduced-motion visitors see.

    @props statistics: label => value
--}}
@props(['statistics'])

@if (collect($statistics)->isNotEmpty())
    <section id="event-stats" class="sx-section" aria-label="{{ __('seerah.public.event_stats_heading') }}">
        <div class="sx-stats">
            @foreach ($statistics as $label => $value)
                <div class="sx-surface sx-stat sx-reveal">
                    <p class="sx-stat-value" @if (is_numeric($value)) data-sx-count="{{ (float) $value }}" @endif>{{ $value }}</p>
                    <p class="sx-stat-label">{{ is_string($label) ? $label : '' }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endif
