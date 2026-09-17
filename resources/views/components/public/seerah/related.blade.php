{{-- Related events as compact tiles. @props related --}}
@props(['related'])

@if (! empty($related))
    <div {{ $attributes->class('sx-surface p-5') }}>
        <h2 class="mb-4 flex items-center gap-2 font-extrabold text-[color:var(--sx-ink)]">
            <i data-lucide="waypoints" class="w-5 h-5 text-[color:var(--sx-accent)]" aria-hidden="true"></i>{{ __('seerah.public.related_events') }}
        </h2>
        <ul class="sx-tile-list">
            @foreach ($related as $rel)
                <li>
                    <a href="{{ $rel['url'] }}" class="sx-tile">
                        <span class="sx-tile-icon">
                            @if (! empty($rel['image']))<img src="{{ $rel['image'] }}" alt="" loading="lazy">@else<i data-lucide="{{ $rel['icon'] ?? 'circle' }}" aria-hidden="true"></i>@endif
                        </span>
                        <span class="min-w-0">
                            <span class="sx-tile-title" title="{{ $rel['title'] }}">{{ $rel['title'] }}</span>
                            @if (! empty($rel['hijriDate']))<span class="sx-tile-sub block">{{ $rel['hijriDate'] }}</span>@endif
                        </span>
                        <i data-lucide="chevron-left" class="w-4 h-4 ltr:rotate-180 text-[color:var(--sx-muted)]" aria-hidden="true"></i>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
