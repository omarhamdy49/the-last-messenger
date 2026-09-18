@props(['pills' => [], 'active' => 'all', 'param' => 'filter'])

{{-- pills: [['value' => 'all', 'label' => '...', 'count' => 12], ...] --}}
<div {{ $attributes->merge(['class' => 'sx-scroller']) }}>
    @foreach ($pills as $pill)
        @php $isActive = (string) $active === (string) $pill['value']; @endphp
        <a href="{{ $pill['url'] ?? '#' }}" title="{{ $pill['label'] }}"
           @class(['sx-chip shrink-0', 'is-active' => $isActive])
           @if ($isActive) aria-current="page" @endif>
            @if (! empty($pill['icon']))
                <i data-lucide="{{ $pill['icon'] }}" aria-hidden="true"></i>
            @endif
            <span class="sx-clamp-1 max-w-[12rem]">{{ $pill['label'] }}</span>
            @if (isset($pill['count']))
                <span class="sx-count">{{ $pill['count'] }}</span>
            @endif
        </a>
    @endforeach
</div>
