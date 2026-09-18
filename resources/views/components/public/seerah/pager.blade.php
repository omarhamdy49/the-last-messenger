{{-- Previous / next event in the editorial chronology. @props prev, next --}}
@props(['prev' => null, 'next' => null])

@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

@if (! empty($prev) || ! empty($next))
    <nav class="sx-nav" aria-label="{{ __('seerah.public.event_navigation') }}">
        @if (! empty($prev))
            <a href="{{ $prev['url'] }}" rel="prev" class="sx-surface group">
                <span class="sx-nav-arrow"><i data-lucide="arrow-{{ $isRtl ? 'right' : 'left' }}" class="w-5 h-5" aria-hidden="true"></i></span>
                <span class="min-w-0"><small>{{ __('seerah.public.previous_event') }}</small><strong title="{{ $prev['title'] }}">{{ $prev['title'] }}</strong></span>
            </a>
        @else
            <span class="hidden md:block" aria-hidden="true"></span>
        @endif
        @if (! empty($next))
            <a href="{{ $next['url'] }}" rel="next" class="sx-surface group is-next">
                <span class="min-w-0"><small>{{ __('seerah.public.next_event') }}</small><strong title="{{ $next['title'] }}">{{ $next['title'] }}</strong></span>
                <span class="sx-nav-arrow"><i data-lucide="arrow-{{ $isRtl ? 'left' : 'right' }}" class="w-5 h-5" aria-hidden="true"></i></span>
            </a>
        @endif
    </nav>
@endif
