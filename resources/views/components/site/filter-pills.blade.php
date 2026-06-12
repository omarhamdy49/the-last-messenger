@props(['pills' => [], 'active' => 'all', 'param' => 'filter'])

{{-- pills: [['value' => 'all', 'label' => '...', 'count' => 12], ...] --}}
<div {{ $attributes->merge(['class' => 'flex items-center gap-2 overflow-x-auto scrollbar-hide pb-1']) }}>
    @foreach ($pills as $pill)
        @php $isActive = (string) $active === (string) $pill['value']; @endphp
        <a href="{{ $pill['url'] ?? '#' }}"
           class="whitespace-nowrap shrink-0 px-4 py-2 rounded-full text-sm font-semibold transition-all flex items-center gap-2
                  {{ $isActive
                        ? 'bg-gradient-to-l from-primary to-secondary text-white shadow-md'
                        : 'bg-white dark:bg-[#16213e] text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 hover:border-primary/50' }}">
            @if (! empty($pill['icon']))
                <i data-lucide="{{ $pill['icon'] }}" class="w-4 h-4"></i>
            @endif
            {{ $pill['label'] }}
            @if (isset($pill['count']))
                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $isActive ? 'bg-white/20' : 'bg-gray-100 dark:bg-gray-700' }}">{{ $pill['count'] }}</span>
            @endif
        </a>
    @endforeach
</div>
