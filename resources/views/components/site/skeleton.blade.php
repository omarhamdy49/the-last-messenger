@props(['type' => 'card', 'count' => 6])

{{-- Rows for the hadith hero's live search suggestions. Deliberately narrower
     than a real result: a placeholder that fills the full width implies the
     answer is already there. --}}
@if ($type === 'suggest')
    <div aria-hidden="true" {{ $attributes->merge(['class' => 'divide-y divide-[color:var(--sx-line)]']) }}>
        @for ($i = 0; $i < $count; $i++)
            <div class="flex items-start gap-3 px-4 py-3.5">
                <div class="sx-skeleton w-9 h-9 shrink-0"></div>
                <div class="flex-1 space-y-2 pt-0.5">
                    <div class="sx-skeleton h-3" style="width: {{ [92, 78, 85, 70, 88][$i % 5] }}%"></div>
                    <div class="sx-skeleton h-2.5 w-24"></div>
                </div>
            </div>
        @endfor
    </div>
@elseif ($type === 'card')
    <div aria-hidden="true" {{ $attributes->merge(['class' => 'sx-grid']) }}>
        @for ($i = 0; $i < $count; $i++)
            <div class="sx-surface p-5">
                <div class="sx-skeleton h-40 mb-4"></div>
                <div class="sx-skeleton h-4 w-3/4 mb-2"></div>
                <div class="sx-skeleton h-3 w-full mb-1.5"></div>
                <div class="sx-skeleton h-3 w-2/3"></div>
            </div>
        @endfor
    </div>
@elseif ($type === 'list')
    <div aria-hidden="true" {{ $attributes->merge(['class' => 'space-y-4']) }}>
        @for ($i = 0; $i < $count; $i++)
            <div class="sx-surface p-4 flex gap-4">
                <div class="sx-skeleton w-24 h-24 shrink-0"></div>
                <div class="flex-1 space-y-2 py-1">
                    <div class="sx-skeleton h-4 w-1/2"></div>
                    <div class="sx-skeleton h-3 w-full"></div>
                    <div class="sx-skeleton h-3 w-3/4"></div>
                </div>
            </div>
        @endfor
    </div>
@else
    <div aria-hidden="true" {{ $attributes->merge(['class' => 'space-y-3']) }}>
        @for ($i = 0; $i < $count; $i++)
            <div class="sx-skeleton h-4 w-full"></div>
        @endfor
    </div>
@endif
