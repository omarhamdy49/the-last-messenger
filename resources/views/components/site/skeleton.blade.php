@props(['type' => 'card', 'count' => 6])

@php
    $shimmer = 'relative overflow-hidden bg-gray-200 dark:bg-gray-700 rounded';
@endphp

{{-- Rows for the hadith hero's live search suggestions. Deliberately narrower
     than a real result: a placeholder that fills the full width implies the
     answer is already there. --}}
@if ($type === 'suggest')
    <div aria-hidden="true" {{ $attributes->merge(['class' => 'divide-y divide-gray-100 dark:divide-white/10']) }}>
        @for ($i = 0; $i < $count; $i++)
            <div class="flex items-start gap-3 px-4 py-3.5">
                <div class="skeleton-shimmer w-9 h-9 shrink-0 rounded-lg bg-gray-200 dark:bg-white/10"></div>
                <div class="flex-1 space-y-2 pt-0.5">
                    <div class="skeleton-shimmer h-3 rounded bg-gray-200 dark:bg-white/10"
                         style="width: {{ [92, 78, 85, 70, 88][$i % 5] }}%"></div>
                    <div class="skeleton-shimmer h-2.5 w-24 rounded bg-gray-200 dark:bg-white/10"></div>
                </div>
            </div>
        @endfor
    </div>
@elseif ($type === 'card')
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
