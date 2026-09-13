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
    <div class="rounded-2xl p-6 bg-white dark:bg-gray-800 shadow-sm animate-pulse">
        <div class="h-4 w-24 rounded bg-gray-200 dark:bg-gray-700 ms-auto mb-6"></div>
        <div class="h-6 rounded bg-gray-200 dark:bg-gray-700 mb-3"></div>
        <div class="h-6 w-3/4 rounded bg-gray-200 dark:bg-gray-700 mx-auto mb-6"></div>
        <div class="h-4 w-2/3 rounded bg-gray-100 dark:bg-gray-700/60 mx-auto"></div>
    </div>
    @endfor
</div>
