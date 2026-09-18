{{-- Compact related tiles. @props title, icon, items: list of {title, url, image?, icon?, sub?, current?} --}}
@props(['title', 'icon' => 'waypoints', 'items', 'numbered' => false])

@if (! empty($items))
    <div {{ $attributes->class('sx-surface p-5') }}>
        <h2 class="mb-4 flex items-center gap-2 font-extrabold text-[color:var(--sx-ink)]">
            <i data-lucide="{{ $icon }}" class="w-5 h-5 text-[color:var(--sx-accent)]" aria-hidden="true"></i>{{ $title }}
        </h2>
        <ol class="sx-tile-list">
            @foreach ($items as $item)
                <li>
                    <{{ ! empty($item['current']) ? 'span' : 'a' }} @if (empty($item['current'])) href="{{ $item['url'] }}" @else aria-current="page" @endif
                        @class(['sx-tile', '!border-[color:var(--sx-accent)] !bg-[color:var(--sx-accent-soft)]' => ! empty($item['current'])])>
                        <span class="sx-tile-icon">
                            @if ($numbered)
                                <span class="text-sm font-extrabold tabular-nums">{{ $loop->iteration }}</span>
                            @elseif (! empty($item['image']))
                                <img src="{{ $item['image'] }}" alt="" loading="lazy" onerror="this.remove()">
                            @else
                                <i data-lucide="{{ $item['icon'] ?? $icon }}" aria-hidden="true"></i>
                            @endif
                        </span>
                        <span class="min-w-0">
                            <span class="sx-tile-title" title="{{ $item['title'] }}">{{ $item['title'] }}</span>
                            @if (! empty($item['sub']))<span class="sx-tile-sub block" title="{{ $item['sub'] }}">{{ $item['sub'] }}</span>@endif
                        </span>
                        @if (empty($item['current']))<i data-lucide="chevron-left" class="w-4 h-4 ltr:rotate-180 text-[color:var(--sx-muted)]" aria-hidden="true"></i>@else<span></span>@endif
                    </{{ ! empty($item['current']) ? 'span' : 'a' }}>
                </li>
            @endforeach
        </ol>
    </div>
@endif
