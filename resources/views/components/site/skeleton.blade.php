@props(['type' => 'card', 'count' => 6])

@php
    $shimmer = 'relative overflow-hidden bg-gray-200 dark:bg-gray-700 rounded';
@endphp

@if ($type === 'card')
    <div {{ $attributes->merge(['class' => 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6']) }}>
        @for ($i = 0; $i < $count; $i++)
            <div class="card-premium p-5 animate-pulse">
                <div class="h-40 {{ $shimmer }} mb-4"></div>
                <div class="h-4 {{ $shimmer }} w-3/4 mb-2"></div>
                <div class="h-3 {{ $shimmer }} w-full mb-1.5"></div>
                <div class="h-3 {{ $shimmer }} w-2/3"></div>
            </div>
        @endfor
    </div>
@elseif ($type === 'list')
    <div {{ $attributes->merge(['class' => 'space-y-4']) }}>
        @for ($i = 0; $i < $count; $i++)
            <div class="card-premium p-4 flex gap-4 animate-pulse">
                <div class="w-24 h-24 {{ $shimmer }} shrink-0"></div>
                <div class="flex-1 space-y-2 py-1">
                    <div class="h-4 {{ $shimmer }} w-1/2"></div>
                    <div class="h-3 {{ $shimmer }} w-full"></div>
                    <div class="h-3 {{ $shimmer }} w-3/4"></div>
                </div>
            </div>
        @endfor
    </div>
@else
    <div {{ $attributes->merge(['class' => 'space-y-3 animate-pulse']) }}>
        @for ($i = 0; $i < $count; $i++)
            <div class="h-4 {{ $shimmer }} w-full"></div>
        @endfor
    </div>
@endif
