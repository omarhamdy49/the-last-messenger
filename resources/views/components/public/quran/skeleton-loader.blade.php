{{--
    Skeleton Loader Component

    Displays a loading skeleton while verses are being loaded.
    Hidden once Alpine.js boots and real verses render.

    Props:
    - $count: number of skeleton cards to show (default: 3)
--}}

@props(['count' => 3])

<div x-show="false" class="space-y-6 mb-12" aria-hidden="true">
    @for($i = 0; $i < $count; $i++)
    <div class="sx-surface p-6">
        <div class="sx-skeleton h-4 w-24 ms-auto mb-6"></div>
        <div class="sx-skeleton h-6 mb-3"></div>
        <div class="sx-skeleton h-6 w-3/4 mx-auto mb-6"></div>
        <div class="sx-skeleton h-4 w-2/3 mx-auto"></div>
    </div>
    @endfor
</div>
